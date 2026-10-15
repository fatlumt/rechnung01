"""Runs the existing invoice suite plus document-option checks on a fresh local DB."""
import runpy
from pathlib import Path
s=runpy.run_path(str(Path(__file__).with_name('api_test.py')))
b=s['b'];profile=s['profile'];address=s['address']
b.request('data',{'action':'company','data':profile,'version':0})
customer=b.request('data',{'action':'customer','data':address})['id']
draft={**s['draft'],'customerId':customer,'pricingMode':'total','totalPrice':100000,'totalRate':8.1,'visibility':{'number':False,'reference':False},'lines':[{'type':'section','description':'Zimmer 1'},*s['draft']['lines'],{'type':'section','description':'Badezimmer'},{'description':'Abdichten','quantity':1,'price':999999,'rate':2.6}]}
def create(d):return b.request('data',{'action':'create','data':d})['id']
def doc(id):return next(d for d in b.request('data')['documents'] if d['id']==id)
id=create(draft);d=doc(id);assert d['total']==108100 and not d['data']['visibility']['number'] and d['data']['visibility']['due']
assert d['data']['lines'][0]=={'type':'section','description':'Zimmer 1'}
b.request('data',{'action':'edit','id':id,'version':0,'data':{**draft,'totalPrice':200000}});assert doc(id)['total']==216200
b.request('data',{'action':'issue','id':id,'version':1})
b.request('data',{'action':'pay','id':id,'version':2,'date':str(s['today']),'amount':100000})
rem=b.request('data',{'action':'reminder','id':id,'version':3,'kind':'reminder','due':str(s['today']+s['datetime'].timedelta(days=10)),'notes':'Bitte bezahlen.'})['id']
assert doc(rem)['data']['outstanding']==116200
assert doc(rem)['data']['pricingMode']=='total' and not doc(rem)['data']['visibility']['number']
offer=create({**draft,'kind':'offer'});b.request('data',{'action':'issue','id':offer,'version':0});b.request('data',{'action':'accept','id':offer,'version':1})
converted=b.request('data',{'action':'convert','id':offer,'version':2})['id'];assert doc(converted)['total']==108100 and doc(converted)['data']['lines'][0]['type']=='section'
item=create({**draft,'pricingMode':'items','lines':[{'type':'section','description':'Zimmer 1'},*s['draft']['lines']]});assert doc(item)['total']==710217
for patch in [{'totalPrice':0},{'totalRate':19},{'visibility':{'number':'false'}},{'lines':[{'type':'section','description':'Only heading'}]},{'lines':[{'type':'section','description':''},*s['draft']['lines']]}]:
 b.request('data',{'action':'create','data':{**draft,**patch}},400)
b.request('data',{'action':'company','data':{**profile,'vat':False},'version':1})
b.request('data',{'action':'create','data':draft},400)
nonvat=create({**draft,'totalRate':0});assert doc(nonvat)['total']==100000
print('Document options passed: persisted headings/visibility, total-only pricing, VAT, edits, payments, reminders, offer conversion, and invalid-input rejection.')
