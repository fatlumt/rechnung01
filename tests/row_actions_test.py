"""Local-only fresh database test: base invoice suite plus row actions."""
import runpy,copy
from pathlib import Path
s=runpy.run_path(str(Path(__file__).with_name('api_test.py')))
b=s['b'];address=s['address'];profile=s['profile'];today=s['today']
b.request('data',{'action':'company','data':profile,'version':0})
customer=b.request('data',{'action':'customer','data':address})['id']
draft={**s['draft'],'customerId':customer,'due':str(today+s['datetime'].timedelta(days=30))}
def get(id):return next(d for d in b.request('data')['documents'] if d['id']==id)
def create(data):return b.request('data',{'action':'create','data':data})['id']
def action(id,kind,status=200,**kw):return b.request('data',{'action':kind,'id':id,'version':get(id)['version'],**kw},status)
inv=create(draft)
# Use direct calls for status, whose field name is also used by request().
b.request('data',{'action':'status','id':inv,'version':0,'status':'paid'},400)
b.request('data',{'action':'quickReminder','id':inv,'version':0,'kind':'reminder'},400)
b.request('data',{'action':'status','id':inv,'version':0,'status':'open'})
action(inv,'pay',amount=100000,date=str(today))
r=b.request('data',{'action':'quickReminder','id':inv,'version':2,'kind':'reminder'})['id']
snapshot=copy.deepcopy(get(r))
assert snapshot['data']['outstanding']==610217 and snapshot['data']['due']==str(today+s['datetime'].timedelta(days=10))
b.request('data',{'action':'quickReminder','id':inv,'version':2,'kind':'reminder'},409)
b.request('data',{'action':'status','id':inv,'version':3,'status':'paid'})
assert get(inv)['paid']==710217
payments=b.request('data')['payments'];assert sum(p['amount'] for p in payments if p['document_id']==inv)==710217
b.request('data',{'action':'status','id':inv,'version':3,'status':'paid'},409)
b.request('data',{'action':'quickReminder','id':inv,'version':4,'kind':'dunning'},400)
b.request('data',{'action':'status','id':inv,'version':4,'status':'open'},409)
b.request('data',{'action':'status','id':inv,'version':4,'status':'open','confirmReversal':True})
assert get(inv)['paid']==0
payments=[p for p in b.request('data')['payments'] if p['document_id']==inv]
assert len(payments)==3 and sum(p['amount'] for p in payments)==0 and any(p['amount']==-710217 for p in payments)
assert get(r)==snapshot
m=b.request('data',{'action':'quickReminder','id':inv,'version':5,'kind':'dunning'})['id'];assert get(m)['data']['outstanding']==710217
b.request('data',{'action':'status','id':s['invoice'],'version':3,'status':'open','confirmReversal':True},404)
b.request('data',{'action':'convert','id':s['offer'],'version':2},404)
for state in ['draft','issued','accepted']:
 offer=create({**draft,'kind':'offer','pricingMode':'total','totalPrice':100000,'totalRate':8.1,'visibility':{'number':False},'lines':[{'type':'section','description':'Zimmer 1'},*draft['lines']]})
 if state!='draft':action(offer,'issue')
 if state=='accepted':action(offer,'accept')
 original=copy.deepcopy(get(offer));edited={**original['data'],'kind':'invoice','documentTitle':'Rechnung','title':'Geändert','totalPrice':200000}
 result=b.request('data',{'action':'convert','id':offer,'version':original['version'],'data':edited})['id']
 converted=get(result);assert converted['status']=='draft' and converted['total']==216200 and converted['data']['title']=='Geändert'
 assert converted['source_offer']==offer and converted['data']['lines'][0]['description']=='Zimmer 1' and not converted['data']['visibility']['number']
 assert get(offer)==original
 b.request('data',{'action':'convert','id':offer,'version':original['version'],'data':edited},409)
 b.request('data',{'action':'status','id':offer,'version':original['version'],'status':'paid'},400)
print('Row action tests passed: status accounting, correction journal, stale requests, reminders before due date, tenant isolation, and editable conversion from all offer states.')
