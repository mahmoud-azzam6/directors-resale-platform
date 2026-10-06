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
const listing = { id: 7, organization_id: 3, organization_property_id: 8, status: 'draft', revision: 2, organization: { id: 3, name: 'Child agency', organization_type: 'partner_agency' }, property: { id: 8, property_code: 'PROP-CODE', property_label: 'Saved unit', address_text: 'Saved address', location: { id: 4, name_ar: 'Saved location' }, initial_asking_price: '250.0000', currency_code: 'EGP', primary_image_url: '/listings/7/primary-image/content' } };
const cache = () => new QueryClient({ defaultOptions: { queries: { retry: false, retryOnMount: false, gcTime: Infinity } } });
const render = (component, client) => renderToStaticMarkup(React.createElement(QueryClientProvider, { client }, component));
async function main() {
  const client = cache(); client.setQueryData(['auth-context'], { permissions: ['listings.view', 'listings.manage'] }); client.setQueryData(['listings'], [listing]); client.setQueryData(['listing', '7'], listing);
  for (const component of [React.createElement(ListingListPage), React.createElement(ListingDetailPage, { id: '7' })]) {
    const html = render(component, client);
    for (const value of ['PROP-CODE', 'Saved unit', 'Saved address', 'Saved location', '250.0000', 'EGP', 'Child agency', 'draft', '/api/listings/7/primary-image/content', 'dir="rtl"']) assert.ok(html.includes(value), value);
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
    assert.ok(html.includes(status === 401 ? 'انتهت الجلسة' : status === 403 ? 'لا تملك صلاحية' : 'Required price'));
  }
  const calls = [];
  global.fetch = async (url, options) => { calls.push({ url, options }); return { ok: true, status: 200, json: async () => ({ success: true, data: listing }) }; };
  assert.deepEqual(await listingApi.create(8), listing); assert.equal(calls[0].url, '/api/listings'); assert.deepEqual(JSON.parse(calls[0].options.body), { organization_property_id: 8 });
  await listingApi.transition('7', 'publish', 2); assert.equal(calls[1].url, '/api/listings/7/publish'); assert.deepEqual(JSON.parse(calls[1].options.body), { revision: 2 });
  let requests = 0;
  global.fetch = async () => { requests++; return { ok: false, status: 409, json: async () => ({ success: false, error: { code: 'revision_conflict', message: 'Reload explicitly' } }) }; };
  await assert.rejects(listingApi.transition('7', 'archive', 1), error => error.status === 409 && error.code === 'revision_conflict'); assert.equal(requests, 1, 'Conflict must not auto-retry.');
  console.log('BF017 Listing frontend hydration/capability/error/API contracts: PASS');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
