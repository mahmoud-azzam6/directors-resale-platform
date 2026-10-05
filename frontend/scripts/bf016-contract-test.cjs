// Exercise the real components and API unwrapping with isolated React Query caches.
// Uses the project's installed TypeScript/React and Node assertions; no new test framework.
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
Module._resolveFilename = function (name, ...args) {
  return resolve.call(this, name.startsWith('@/') ? path.join(root, name.slice(2)) : name, ...args);
};
for (const extension of ['.ts', '.tsx']) {
  Module._extensions[extension] = function (module, filename) {
    module._compile(ts.transpileModule(fs.readFileSync(filename, 'utf8'), {
      compilerOptions: { module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX, target: ts.ScriptTarget.ES2020, esModuleInterop: true },
    }).outputText, filename);
  };
}
const { canonicalPath, activeChildren } = require('../features/network/geography-contract.ts');
const { GeographySelector } = require('../features/network/geography-selector.tsx');
const { OrganizationProfilePage } = require('../features/network/organization-profile-page.tsx');
const { PropertyPrimaryImage } = require('../features/properties/property-primary-image.tsx');
const { networkApi } = require('../lib/api/network.ts');
const { ApiClientError } = require('../lib/api/client.ts');
const row = (id, type, parent) => ({ id, location_type: type, parent_id: parent, status: 'active', code: type, name_ar: type, name_en: type });
const locations = [row(1, 'COUNTRY', null), row(2, 'GOVERNORATE', 1), row(3, 'CITY', 2), row(4, 'AREA', 3), row(5, 'DISTRICT', 4)];
const ancestry = { stop_reason: 'root', locations: [...locations].reverse() };
const client = () => new QueryClient({ defaultOptions: { queries: { retry: false, retryOnMount: false, gcTime: Infinity } } });
function render(component, cache) {
  return renderToStaticMarkup(React.createElement(QueryClientProvider, { client: cache }, component));
}
function geographicCache(savedAncestry = ancestry) {
  const cache = client();
  cache.setQueryData(['onboarding-geography-ancestry', savedAncestry.locations[0].id], savedAncestry);
  cache.setQueryData(['onboarding-geography', 'roots'], [locations[0]]);
  locations.forEach((item, index) => cache.setQueryData(['onboarding-geography', item.id], locations[index + 1] ? [locations[index + 1]] : []));
  return cache;
}
async function main() {
  assert.deepEqual(canonicalPath(ancestry).map((item) => item.id), [1, 2, 3, 4, 5]);
  const skipped = { stop_reason: 'root', locations: [row(9, 'DISTRICT', 1), locations[0]] };
  assert.deepEqual(canonicalPath(skipped).map((item) => item.id), [1, 9]);
  for (const invalid of [
    { ...ancestry, stop_reason: 'cycle' },
    { ...ancestry, locations: [row(2, 'CITY', null)] },
    { ...ancestry, locations: [{ ...locations[4], status: 'inactive' }, ...ancestry.locations.slice(1)] },
    { stop_reason: 'root', locations: [row(9, 'GOVERNORATE', 3), locations[2], locations[1], locations[0]] },
    { stop_reason: 'root', locations: [row(9, 'DISTRICT', 999), locations[0]] },
  ]) assert.deepEqual(canonicalPath(invalid), []);
  assert.deepEqual(activeChildren([row(9, 'DISTRICT', 1), row(10, 'CITY', 999), row(11, 'COUNTRY', 1)], locations[0]).map((item) => item.id), [9]);

  let emitted = false;
  const html = render(React.createElement(GeographySelector, { canRead: true, savedLocationId: 5, onChange: () => { emitted = true; } }), geographicCache());
  for (const label of ['الدولة', 'المحافظة', 'المدينة', 'المنطقة', 'الحي']) assert.ok(html.includes(label));
  for (const location of locations) assert.match(html, new RegExp(`value="${location.id}" selected`));
  assert.equal(emitted, false, 'Hydration must not emit an edit or overwrite local state.');
  assert.ok(render(React.createElement(GeographySelector, { canRead: false, onChange: () => {} }), client()).includes('الموقع المحفوظ لن يتغير'));
  assert.ok(render(React.createElement(GeographySelector, { canRead: true, savedLocationId: 5, onChange: () => {} }), client()).includes('جارٍ تحميل الموقع المحفوظ'));
  for (const status of [401, 403, 500]) {
    const cache = client();
    await cache.fetchQuery({ queryKey: ['onboarding-geography-ancestry', 5], queryFn: () => Promise.reject(new ApiClientError(status, { success: false, error: { code: 'error', message: 'Rejected' } })) }).catch(() => {});
    const result = render(React.createElement(GeographySelector, { canRead: true, savedLocationId: 5, onChange: () => {} }), cache);
    assert.ok(result.includes(status === 401 ? 'انتهت الجلسة' : status === 403 ? 'ليس لديك صلاحية' : 'إعادة المحاولة'));
    if (status !== 500) assert.equal(result.includes('إعادة المحاولة'), false);
  }
  const cache = client();
  cache.setQueryData(['auth-context'], { organization: { id: 2, name: 'Saved company name', organization_type: 'franchise' }, permissions: ['organizations.view', 'organizations.update'], user: {} });
  cache.setQueryData(['organization-basic-profile', '2'], null);
  const empty = render(React.createElement(OrganizationProfilePage), cache);
  assert.ok(empty.includes('لا يوجد ملف أساسي محفوظ'));
  assert.ok(empty.includes('value="Saved company name"'));
  assert.ok(empty.includes('dir="rtl"'));
  const imageCache = () => {
    const c = client();
    c.setQueryData(['auth-context'], { permissions: ['properties.view', 'properties.manage'], user: {} });
    return c;
  };
  const noImage = imageCache();
  noImage.setQueryData(['property-primary-image', '7'], { primary_image: null });
  const emptyImage = render(React.createElement(PropertyPrimaryImage, { id: '7' }), noImage);
  assert.ok(emptyImage.includes('لا توجد صورة أساسية محفوظة'));
  assert.ok(emptyImage.includes('type="file"'));
  assert.equal(emptyImage.includes('multiple='), false);
  const savedImage = imageCache();
  savedImage.setQueryData(['property-primary-image', '7'], { primary_image: { reference: 'a'.repeat(32), mime_type: 'image/webp', width: 600, height: 800, byte_size: 123 } });
  const imageHtml = render(React.createElement(PropertyPrimaryImage, { id: '7' }), savedImage);
  assert.ok(imageHtml.includes('/api/properties/7/primary-image/content?v='));
  assert.ok(imageHtml.includes('استبدال الصورة الأساسية'));
  assert.ok(imageHtml.includes('إزالة الصورة الأساسية'));
  assert.equal(render(React.createElement(PropertyPrimaryImage, { id: '7', readOnly: true }), savedImage).includes('type="file"'), false);
  assert.ok(render(React.createElement(PropertyPrimaryImage, { id: '7' }), imageCache()).includes('جارٍ تحميل الصورة'));
  for (const status of [401, 403, 500]) {
    const c = imageCache();
    await c.fetchQuery({ queryKey: ['property-primary-image', '7'], queryFn: () => Promise.reject(new ApiClientError(status, { success: false, error: { code: 'error', message: 'Rejected' } })) }).catch(() => {});
    const html = render(React.createElement(PropertyPrimaryImage, { id: '7' }), c);
    assert.ok(html.includes(status === 401 ? 'انتهت الجلسة' : status === 403 ? 'ليس لديك صلاحية' : 'إعادة تحميل الصورة'));
    if (status !== 500) assert.ok(html.includes('disabled=""'));
  }
  const originalFetch = global.fetch;
  global.fetch = async () => ({ ok: true, status: 200, json: async () => ({ success: true, data: { profile: null } }) });
  assert.equal(await networkApi.basicProfile('2'), null, 'Empty envelope must unwrap to null safely.');
  global.fetch = async () => ({ ok: true, status: 200, json: async () => ({ success: true, data: { profile: { geographic_location_id: 5, address_text: 'Saved street' } } }) });
  const saved = await networkApi.basicProfile('2');
  assert.equal(saved.address_text, 'Saved street');
  assert.equal(saved.geographic_location_id, 5);
  global.fetch = originalFetch;
  // Test real multipart/binary proxy behavior without exposing a real auth token or DB.
  let proxyToken = 'isolated-test-token';
  const load = Module._load;
  Module._load = function (name, ...args) {
    if (name === 'next/headers') return { cookies: () => ({ get: () => proxyToken ? { value: proxyToken } : undefined }) };
    return load.call(this, name, ...args);
  };
  const { primaryImageProxy } = require('../lib/api/primary-image-proxy.ts');
  Module._load = load;
  try {
    let calls = 0;
    global.fetch = async (_url, options) => {
      calls += 1;
      assert.equal(options.headers.Authorization, 'Bearer isolated-test-token');
      assert.equal(options.headers['Content-Type'], undefined, 'Fetch must generate the multipart boundary.');
      assert.ok(options.body instanceof FormData);
      assert.equal(options.body.get('image').name, 'image.png');
      return Response.json({ success: true, data: { primary_image: { reference: 'b'.repeat(32) } } });
    };
    const data = new FormData(); data.append('image', new File(['fixture'], 'image.png', { type: 'image/png' }));
    assert.equal((await primaryImageProxy(new Request('http://localhost/api', { method: 'POST', body: data }), '7')).status, 200);
    assert.equal(calls, 1);
    const invalid = new FormData(); invalid.append('reference', '../../outside');
    assert.equal((await primaryImageProxy(new Request('http://localhost/api', { method: 'POST', body: invalid }), '7')).status, 422);
    assert.equal(calls, 1);
    proxyToken = null;
    assert.equal((await primaryImageProxy(new Request('http://localhost/api'), '7')).status, 401);
    assert.equal(calls, 1);
    proxyToken = 'isolated-test-token';
    global.fetch = async () => new Response('binary-fixture', { headers: { 'Content-Type': 'image/webp' } });
    const content = await primaryImageProxy(new Request('http://localhost/api'), '7', true);
    assert.equal(content.headers.get('content-type'), 'image/webp');
    assert.equal(content.headers.get('cache-control'), 'private, no-store');
    assert.equal(await content.text(), 'binary-fixture');
    global.fetch = async () => Response.json({ success: false, error: { code: 'unauthenticated', message: 'Expired' } }, { status: 401 });
    const expired = await primaryImageProxy(new Request('http://localhost/api'), '7');
    assert.equal(expired.status, 401);
    assert.ok(expired.headers.get('set-cookie').includes('directors_admin_token='));
    global.fetch = async () => { throw new Error('Disconnected'); };
    assert.equal((await primaryImageProxy(new Request('http://localhost/api'), '7')).status, 503);
  } finally { global.fetch = originalFetch; }
  console.log('BF016 frontend geography/company and primary-image hydration, authorization, empty/error states: PASS');
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
