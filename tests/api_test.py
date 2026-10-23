"""Run ONLY against a fresh, isolated local database. See README for usage."""
import os,json,urllib.request,urllib.error,http.cookiejar,datetime,secrets
base=os.environ.get('KR_TEST_URL','http://127.0.0.1:8097')
assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost'), 'Local test server required'
key=os.environ['KR_TEST_SETUP_KEY']
count=0
class Client:
 def __init__(self):
  self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.csrf=''
 def request(self,route,body=None,status=200,csrf=True,origin=None):
  global count
  headers={'Content-Type':'application/json'}
  if csrf: headers['X-CSRF-Token']=self.csrf
  if origin:headers['Origin']=origin
  req=urllib.request.Request(base+'/api.php?route='+route,data=json.dumps(body).encode() if body is not None else None,headers=headers)
  try:r=self.http.open(req)
  except urllib.error.HTTPError as e:r=e
  data=json.load(r)
  assert r.status==status,(r.status,status,data,body and body.get('action'))
  self.csrf=data.get('csrf',self.csrf);count+=1
  return data
a,b,guest=Client(),Client(),Client()
guest.request('auth');guest.request('data',status=401)
a.request('auth')
password=secrets.token_urlsafe(24)
email='admin-'+secrets.token_hex(4)+'@example.com'
a.request('auth',{'action':'register','email':email,'password':password,'token':'wrong'},403)
r=a.request('auth',{'action':'register','email':email,'password':password,'token':key});assert r['user']['isAdmin']
a.request('data',{'action':'company'},403,csrf=False)
a.request('data',{'action':'company'},403,origin='https://untrusted.example')
address={'name':'Testbetrieb','street':'Teststrasse','house':'1','zip':'8000','city':'Zürich','country':'CH','email':''}
profile={**address,'vat':True,'uid':'CHE-123.456.789','iban':'CH9300762011623852957','terms':30}
a.request('data',{'action':'company','version':0,'data':{**profile,'iban':'CH0000762011623852957'}},400)
a.request('data',{'action':'company','version':0,'data':profile})
a.request('data',{'action':'company','version':0,'data':profile},409)
customer=a.request('data',{'action':'customer','data':{**address,'name':'Testkunde'}})['id']
today=datetime.date.today();past=str(today-datetime.timedelta(days=60));due=str(today-datetime.timedelta(days=30))
draft={'kind':'invoice','documentTitle':'Schlussrechnung','customerId':customer,'title':'Testprojekt','project':'Testprojekt','date':past,'due':due,'serviceDate':past,'notes':'','lines':[{'description':'Fliesen','quantity':126,'unit':'m²','price':3500,'rate':8.1},{'description':'Arbeit','quantity':26,'unit':'Std.','price':6000,'rate':8.1},{'description':'Treppen','quantity':12,'unit':'Stk.','price':5000,'rate':8.1}]}
invoice=a.request('data',{'action':'create','data':draft})['id']
def getdoc(id):return next(d for d in a.request('data')['documents'] if d['id']==id)
d=getdoc(invoice);assert d['total']==710217
a.request('data',{'action':'pay','id':invoice,'version':0,'date':str(today),'amount':100},400)
a.request('data',{'action':'issue','id':invoice,'version':0})
a.request('data',{'action':'edit','id':invoice,'version':1,'data':draft},400)
a.request('data',{'action':'delete','id':invoice,'version':1},400)
a.request('data',{'action':'pay','id':invoice,'version':1,'date':str(today),'amount':710218},400)
a.request('data',{'action':'pay','id':invoice,'version':1,'date':str(today+datetime.timedelta(days=1)),'amount':100},400)
a.request('data',{'action':'pay','id':invoice,'version':1,'date':str(today),'amount':497260})
a.request('data',{'action':'pay','id':invoice,'version':1,'date':str(today),'amount':497260},409)
d=getdoc(invoice);assert d['total']-d['paid']==212957
reminder=a.request('data',{'action':'reminder','id':invoice,'version':2,'kind':'reminder','due':str(today+datetime.timedelta(days=10)),'notes':'Bitte bezahlen.'})['id']
assert getdoc(reminder)['data']['outstanding']==212957
a.request('data',{'action':'company','version':1,'data':{**profile,'name':'Geänderter Betrieb'}})
assert getdoc(invoice)['data']['company']['name']=='Testbetrieb'
a.request('data',{'action':'pay','id':invoice,'version':2,'date':str(today),'amount':212957})
assert getdoc(invoice)['paid']==710217
assert getdoc(reminder)['data']['outstanding']==212957
offer=a.request('data',{'action':'create','data':{**draft,'kind':'offer'}})['id']
a.request('data',{'action':'issue','id':offer,'version':0})
a.request('data',{'action':'accept','id':offer,'version':1})
converted=a.request('data',{'action':'convert','id':offer,'version':2})['id']
assert getdoc(converted)['data']['documentTitle']=='Rechnung'
a.request('data',{'action':'convert','id':offer,'version':2},409)
bemail='business-'+secrets.token_hex(4)+'@example.com'
invite=a.request('auth',{'action':'invite','email':bemail})['url'].split('invite=')[1]
b.request('auth');b.request('auth',{'action':'register','email':bemail,'password':password,'token':invite})
assert not b.request('auth')['user']['isAdmin']
assert b.request('data')['documents']==[]
b.request('data',{'action':'edit','id':invoice,'version':3,'data':draft},404)
b.request('data',{'action':'customer','customerId':customer,'data':address},404)
b.request('auth',{'action':'invite','email':'no@example.com'},403)
other=Client();other.request('auth');other.request('auth',{'action':'login','email':email,'password':password})
a.request('auth',{'action':'password','current':password,'password':secrets.token_urlsafe(24)})
other.request('data',status=401)
a.request('auth',{'action':'logout'});a.request('data',status=401)
print(f'{count} HTTP checks passed, including tenant isolation, CSRF, totals, immutable snapshots, partial payments, offer conversion and session revocation.')
