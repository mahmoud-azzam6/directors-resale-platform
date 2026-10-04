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
  const originalFetch = global.fetch;
  global.fetch = async () => ({ ok: true, status: 200, json: async () => ({ success: true, data: { profile: null } }) });
  assert.equal(await networkApi.basicProfile('2'), null, 'Empty envelope must unwrap to null safely.');
  global.fetch = async () => ({ ok: true, status: 200, json: async () => ({ success: true, data: { profile: { geographic_location_id: 5, address_text: 'Saved street' } } }) });
  const saved = await networkApi.basicProfile('2');
  assert.equal(saved.address_text, 'Saved street');
  assert.equal(saved.geographic_location_id, 5);
  global.fetch = originalFetch;
  console.log('BF016 frontend geography hydration, empty company envelope and error-state contracts: PASS');
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
