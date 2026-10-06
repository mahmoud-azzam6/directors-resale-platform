const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const Module = require('node:module');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');
const { QueryClient, QueryClientProvider } = require('@tanstack/react-query');
const root = path.resolve(__dirname, '..');
const resolve = Module._resolveFilename;
Module._resolveFilename = function (name, ...args) { return resolve.call(this, name.startsWith('@/') ? path.join(root, name.slice(2)) : name, ...args); };
for (const extension of ['.ts', '.tsx']) Module._extensions[extension] = function (module, filename) {
  module._compile(ts.transpileModule(fs.readFileSync(filename, 'utf8'), { compilerOptions: { module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX, target: ts.ScriptTarget.ES2020, esModuleInterop: true } }).outputText, filename);
};
const { ListingListPage, ListingDetailPage } = require('../features/listings/listing-pages.tsx');
const { listingApi } = require('../lib/api/listing.ts');
const { ApiClientError } = require('../lib/api/client.ts');
const { PropertySelector, loadPropertyChoices, availableChoices, createSelectedDraft } = require('../features/listings/property-selector.tsx');
const { arabicError } = require('../lib/ui/arabic-errors.ts');
const listing = { id: 7, organization_id: 3, organization_property_id: 8, status: 'draft', revision: 2, organization: { id: 3, name: 'Child agency', organization_type: 'partner_agency' }, property: { id: 8, property_code: 'PROP-CODE', property_label: 'Saved unit', address_text: 'Saved address', location: { id: 4, name_ar: 'Saved location' }, initial_asking_price: '250.0000', currency_code: 'EGP', primary_image_url: '/listings/7/primary-image/content' } };
const cache = () => new QueryClient({ defaultOptions: { queries: { retry: false, retryOnMount: false, gcTime: Infinity } } });
const render = (component, client) => renderToStaticMarkup(React.createElement(QueryClientProvider, { client }, component));
async function main() {
  const client = cache(); client.setQueryData(['auth-context'], { permissions: ['listings.view', 'listings.manage'] }); client.setQueryData(['listings'], [listing]); client.setQueryData(['listing', '7'], listing);
  for (const component of [React.createElement(ListingListPage), React.createElement(ListingDetailPage, { id: '7' })]) {
    const html = render(component, client);
    for (const value of ['PROP-CODE', 'Saved unit', 'Saved address', 'Saved location', '250.0000', 'EGP', 'Child agency', 'مسودة', '/api/listings/7/primary-image/content', 'dir="rtl"']) assert.ok(html.includes(value), value);
  }
  assert.ok(render(React.createElement(ListingListPage), client).includes('إنشاء مسودة'));
  client.setQueryData(['auth-context'], { permissions: ['properties.view', 'properties.manage'] });
  assert.equal(render(React.createElement(ListingListPage), client).includes('إنشاء مسودة'), false);
  assert.equal(render(React.createElement(ListingDetailPage, { id: '7' }), client).includes('تنفيذ الإجراء'), false);
  client.setQueryData(['listings'], []); assert.ok(render(React.createElement(ListingListPage), client).includes('لا توجد إعلانات'));
  assert.ok(render(React.createElement(ListingListPage), cache()).includes('جارٍ تحميل'));
  for (const status of [401, 403, 422]) {
    const failed = cache();
    await failed.fetchQuery({ queryKey: ['listing', '7'], queryFn: () => Promise.reject(new ApiClientError(status, { success: false, error: { code: 'denied', message: 'Rejected', fields: status === 422 ? { price: 'Required price' } : undefined } })) }).catch(() => {});
    const html = render(React.createElement(ListingDetailPage, { id: '7' }), failed);
    assert.ok(html.includes(status === 401 ? 'انتهت الجلسة' : status === 403 ? 'لا تملك صلاحية' : 'السعر الموجب مطلوب'));
    assert.equal(html.includes('Required price'), false);
  }
  const calls = [];
  global.fetch = async (url, options) => { calls.push({ url, options }); return { ok: true, status: 200, json: async () => ({ success: true, data: listing }) }; };
  assert.deepEqual(await listingApi.create(8), listing); assert.equal(calls[0].url, '/api/listings'); assert.deepEqual(JSON.parse(calls[0].options.body), { organization_property_id: 8 });
  await listingApi.transition('7', 'publish', 2); assert.equal(calls[1].url, '/api/listings/7/publish'); assert.deepEqual(JSON.parse(calls[1].options.body), { revision: 2 });
  let requests = 0;
  global.fetch = async () => { requests++; return { ok: false, status: 409, json: async () => ({ success: false, error: { code: 'revision_conflict', message: 'Reload explicitly' } }) }; };
  await assert.rejects(listingApi.transition('7', 'archive', 1), error => error.status === 409 && error.code === 'revision_conflict'); assert.equal(requests, 1, 'Conflict must not auto-retry.');
  // All selector reads and writes are mocked: no real database or login is used.
  const property = (id, status = 'active', code = `PROP-${String(id).padStart(26, '0')}`) => ({ id, organization_id: 3, property_label: 'شقة مكررة الاسم', property_code: code, status });
  const rows = [property(8), property(9), property(10, 'archived'), property(11), property(12, 'active', ''), property(13, 'active', 'INVALID')];
  const reads = [];
  global.fetch = async (url, options) => {
    reads.push({ url, options });
    if (url === '/api/properties') return { ok: true, status: 200, json: async () => ({ success: true, data: rows }) };
    if (url.startsWith('/api/properties/11/')) return { ok: false, status: 403, json: async () => ({ success: false, error: { code: 'forbidden', message: 'Private organization' } }) };
    const id = Number(url.split('/')[3]);
    const data = url.endsWith('/primary-image') ? { primary_image: id === 8 ? { reference: 'fixture' } : null } : { property: property(id), profile: { address_text: 'شارع النيل', initial_asking_price: '250.0000', currency_code: 'EGP' }, geography: { location: { name_ar: 'القاهرة' } }, measurements: [], attributes: [] };
    return { ok: true, status: 200, json: async () => ({ success: true, data }) };
  };
  const loaded = await loadPropertyChoices();
  assert.deepEqual(loaded.choices.map(choice => choice.property.id), [8, 9]);
  assert.equal(loaded.unavailable, 1);
  assert.equal(reads.some(call => /properties\/(10|12|13)\//.test(call.url)), false);
  assert.deepEqual(availableChoices(loaded.choices, [], property(9).property_code).map(choice => choice.property.id), [9]);
  assert.equal(availableChoices(loaded.choices, [], 'النيل').length, 2);
  assert.equal(availableChoices(loaded.choices, [], 'شقة').length, 2, 'Duplicate labels remain allowed.');
  assert.deepEqual(availableChoices(loaded.choices, [listing], '').map(choice => choice.property.id), [9]);
  const selectorCache = cache(); selectorCache.setQueryData(['listing-property-choices'], loaded);
  const selected = render(React.createElement(PropertySelector, { selectedId: 8, onSelect: () => {}, listings: [], canRead: true }), selectorCache);
  for (const value of [property(8).property_code, 'شقة مكررة الاسم', 'شارع النيل', 'القاهرة', '250.0000', 'EGP', '/api/properties/8/primary-image/content', 'type="radio"', 'type="search"', 'checked=""']) assert.ok(selected.includes(value), value);
  assert.equal(selected.includes('type="number"'), false);
  assert.equal(selected.includes(property(11).property_code), false);
  assert.equal(render(React.createElement(PropertySelector, { selectedId: null, onSelect: () => {}, listings: [], canRead: false }), selectorCache).includes('type="radio"'), false);
  const emptyChoices = cache(); emptyChoices.setQueryData(['listing-property-choices'], { choices: [], unavailable: 0 });
  assert.ok(render(React.createElement(PropertySelector, { selectedId: null, onSelect: () => {}, listings: [], canRead: true }), emptyChoices).includes('لا توجد وحدات متاحة يمكن إنشاء إعلان لها.'));
  const writes = [];
  global.fetch = async (url, options) => { writes.push({ url, options }); return { ok: true, status: 200, json: async () => ({ success: true, data: listing }) }; };
  await createSelectedDraft(8, loaded.choices, []);
  assert.deepEqual(JSON.parse(writes[0].options.body), { organization_property_id: 8 });
  for (const id of [null, 11, 10, 999]) await assert.rejects(createSelectedDraft(id, loaded.choices, []));
  await assert.rejects(createSelectedDraft(8, loaded.choices, [listing]));
  assert.equal(writes.length, 1, 'Unavailable selections never submit.');
  const errors = arabicError(new ApiClientError(422, { success: false, error: { code: 'validation_error', message: 'English backend message', fields: { property_code: 'Missing', address: 'Missing', price: 'Missing', currency: 'Missing', image: 'Missing', ownership: 'Missing' } } }));
  for (const word of ['كود الوحدة', 'العنوان التفصيلي', 'السعر الموجب', 'العملة', 'صورة أساسية', 'الملكية الحالية']) assert.ok(errors.includes(word));
  assert.equal(errors.includes('English'), false);
  // Audit fixed visible copy; persisted names, codes, enum values and image formats remain technical values.
  const uiFiles = ['features/listings/listing-pages.tsx', 'features/listings/property-selector.tsx', 'features/properties/property-list-page.tsx', 'features/properties/add-property-page.tsx', 'features/properties/property-data-page.tsx', 'features/properties/property-review-page.tsx', 'features/properties/ownership-step-page.tsx', 'features/properties/property-primary-image.tsx', 'components/layout/sidebar.tsx', 'components/layout/header.tsx', 'components/layout/admin-shell.tsx'];
  for (const file of uiFiles) {
    const source = ts.createSourceFile(file, fs.readFileSync(path.join(root, file), 'utf8'), ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
    const visit = node => {
      if (ts.isJsxText(node) || (ts.isJsxAttribute(node) && /^(title|description|placeholder|label|aria-label|alt)$/.test(node.name.text) && node.initializer && ts.isStringLiteral(node.initializer))) {
        const text = (ts.isJsxText(node) ? node.text : node.initializer.text).replace(/JPEG|PNG|WebP|DIRECTORS|Directors/g, '');
        assert.equal(/[A-Za-z]{2,}/.test(text), false, `Untranslated UI copy in ${file}: ${text.trim()}`);
      }
      ts.forEachChild(node, visit);
    };
    visit(source);
  }
  console.log('BF017 Listing frontend hydration/capability/error/API contracts: PASS');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
