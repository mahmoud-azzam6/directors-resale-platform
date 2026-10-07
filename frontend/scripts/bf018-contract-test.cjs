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
const { PublishedCatalogPage, PublishedDetailPage, MyInterestRequestsPage, InterestResult, RequestConfirmation, requestError } = require('../features/requests/published-listing-pages.tsx');
const { publishedListingApi } = require('../lib/api/published-listing.ts');
const { ApiClientError } = require('../lib/api/client.ts');
const cache = () => new QueryClient({ defaultOptions: { queries: { retry: false, retryOnMount: false, gcTime: Infinity } } });
const render = (component, client) => renderToStaticMarkup(React.createElement(QueryClientProvider, { client }, component));
const listing = { id: 7, organization_id: 2, organization_property_id: 8, status: 'published', revision: 2, organization: { id: 2, name: 'مؤسسة' }, property: { id: 8, property_code: 'PROP-FIXTURE', property_label: 'شقة النيل', address_text: 'شارع النيل', location: { name_ar: 'القاهرة' }, initial_asking_price: '250', currency_code: 'EGP', primary_image_url: '/listings/7/primary-image/content' } };
const receipt = { id: 1, reference: 'REQUEST-REFERENCE', listing_id: 7, organization_property_id: 8, status: 'submitted', created_at: '2026-10-07', property: listing.property };
async function main() {
  const client = cache();
  client.setQueryData(['auth-context'], { permissions: ['published_listings.view','requests.create','requests.view'] });
  client.setQueryData(['published-listings'], [listing]);
  client.setQueryData(['published-listing','7'], listing);
  client.setQueryData(['own-listing-requests'], []);
  for (const element of [React.createElement(PublishedCatalogPage),React.createElement(PublishedDetailPage,{id:'7'})]) {
    const html = render(element,client);
    for (const value of ['شقة النيل','PROP-FIXTURE','القاهرة','250','EGP','منشور','dir="rtl"','/api/published-listings/7/primary-image/content']) assert.ok(html.includes(value),value);
    assert.equal(html.includes('type="number"'),false);
    assert.equal(html.includes('organization_property_id'),false);
  }
  assert.ok(render(React.createElement(PublishedDetailPage,{id:'7'}),client).includes('إرسال طلب اهتمام'));
  client.setQueryData(['own-listing-requests'],[receipt]);
  let html=render(React.createElement(PublishedDetailPage,{id:'7'}),client);
  assert.ok(html.includes('سبق إرسال طلب اهتمام'));assert.equal(html.includes('>إرسال طلب اهتمام<'),false);
  html=render(React.createElement(MyInterestRequestsPage),client);assert.ok(html.includes('REQUEST-REFERENCE'));assert.ok(html.includes('تم الإرسال'));
  client.setQueryData(['own-listing-requests'],[]);
  for(const status of ['archived','draft']) {
    client.setQueryData(['published-listing','7'],{...listing,status});
    html=render(React.createElement(PublishedDetailPage,{id:'7'}),client);
    assert.ok(html.includes('الإعلان لم يعد متاحًا'));assert.equal(html.includes('>إرسال طلب اهتمام<'),false);
  }
  client.setQueryData(['published-listing','7'],listing);
  client.setQueryData(['auth-context'],{permissions:['published_listings.view']});
  assert.ok(render(React.createElement(PublishedDetailPage,{id:'7'}),client).includes('لا تملك صلاحية إرسال'));
  client.setQueryData(['published-listings'],[]);assert.ok(render(React.createElement(PublishedCatalogPage),client).includes('لا توجد إعلانات متاحة'));
  assert.ok(render(React.createElement(PublishedCatalogPage),cache()).includes('جارٍ تحميل'));
  assert.ok(render(React.createElement(InterestResult,{receipt}),client).includes('تم إرسال طلب الاهتمام بنجاح'));
  const confirmation={onConfirm:()=>{},onCancel:()=>{},saving:false,unavailable:false};
  assert.ok(render(React.createElement(RequestConfirmation,confirmation),client).includes('تأكيد الإرسال'));
  html=render(React.createElement(RequestConfirmation,{...confirmation,saving:true}),client);assert.ok(html.includes('جارٍ إرسال الطلب'));assert.ok(html.includes('disabled=""'));
  for(const [status,code,expected] of [[401,'unauthenticated','انتهت الجلسة'],[403,'forbidden','لا تملك صلاحية'],[404,'listing_unavailable','لم يعد متاحًا'],[500,'server_error','تعذر إكمال الطلب']]) {
    const failed=cache();await failed.fetchQuery({queryKey:['published-listing','7'],queryFn:()=>Promise.reject(new ApiClientError(status,{success:false,error:{code,message:'PRIVATE EXCEPTION DETAILS'}}))}).catch(()=>{});
    html=render(React.createElement(PublishedDetailPage,{id:'7'}),failed);assert.ok(html.includes(expected));assert.equal(html.includes('PRIVATE EXCEPTION'),false);
  }
  assert.ok(requestError(new ApiClientError(409,{success:false,error:{code:'already_requested',message:'Duplicate'}})).includes('سبق إرسال'));
  const calls=[];global.fetch=async(url,options)=>{calls.push({url,options});return {ok:true,status:201,json:async()=>({success:true,data:receipt})};};
  assert.deepEqual(await publishedListingApi.submit('7'),receipt);assert.equal(calls[0].url,'/api/published-listings/7/requests');assert.deepEqual(JSON.parse(calls[0].options.body),{});assert.equal(calls[0].options.method,'POST');
  global.fetch=async()=>({ok:false,status:409,json:async()=>({success:false,error:{code:'already_requested',message:'Already submitted'}})});
  await assert.rejects(publishedListingApi.submit('7'),error=>error.code==='already_requested');
  console.log('BF018 published catalog/details/receipt/confirmation/Arabic/error/payload contracts: PASS');
}
main().catch(error=>{console.error(error);process.exitCode=1;});
