"""Local-only fresh database tests for country profiles and design snapshots."""
import runpy,copy
from pathlib import Path
s=runpy.run_path(str(Path(__file__).with_name('api_test.py')));b=s['b']
profile={**s['profile'],'country':'DE','uid':'DE123456789','iban':'DE89370400440532013000','templateId':'modern','legalDetails':'Testregister'}
def company(p,status=200):return b.request('data',{'action':'company','version':b.request('data')['companyVersion'],'data':p},status)
company({**profile,'uid':'','taxNumber':''},400);company(profile)
customer=b.request('data',{'action':'customer','data':{**s['address'],'country':'DE'}})['id']
draft={**s['draft'],'customerId':customer,'lines':[{'description':'Arbeit','quantity':1,'price':10000,'rate':19}]}
def get(id):return next(d for d in b.request('data')['documents'] if d['id']==id)
def create(v,status=200):return b.request('data',{'action':'create','data':v},status)
def act(id,action,**extra):return b.request('data',{'action':action,'id':id,'version':get(id)['version'],**extra})
id=create(draft)['id'];assert get(id)['total']==11900 and get(id)['data']['currency']=='EUR' and get(id)['data']['templateId']=='modern'
act(id,'issue');old=copy.deepcopy(get(id));company({**profile,'templateId':'compact'});assert get(id)==old
offer=create({**draft,'kind':'offer','templateId':'compact'})['id'];converted=act(offer,'convert')['id'];assert get(converted)['data']['templateId']=='compact'
rem=act(id,'quickReminder',kind='dunning')['id'];assert get(id)['fee']==0 and get(rem)['data']['amountDue']==11900 and get(rem)['data']['sourceInvoice']['data']['templateId']=='modern'
company({**s['profile'],'templateId':'classic'},400)
create({**draft,'templateId':'unknown'},400)
create({**draft,'lines':[{'description':'Wrong rate','quantity':1,'price':100,'rate':8.1}]},400)
foreign=b.request('data',{'action':'customer','data':s['address']})['id'];create({**draft,'customerId':foreign},400)
company({**profile,'vat':False,'uid':'','taxNumber':'12/345/67890'});create(draft,400)
small=create({**draft,'lines':[{'description':'Arbeit','quantity':1,'price':10000,'rate':0}]})['id'];assert get(small)['total']==10000 and not get(small)['data']['company']['vat']
assert get(id)['data']['company']['vat'] and get(id)['data']['company']['uid']=='DE123456789'
print('Country/template tests passed: EUR/VAT, ID/IBAN, domestic scope, no CHF fee in DE, design inheritance, immutable snapshots, Kleinunternehmer and country locking.')
