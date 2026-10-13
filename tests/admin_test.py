"""Local-only integration tests; fresh isolated database and KR_TEST_SETUP_KEY required."""
import os, json, urllib.request, urllib.error, urllib.parse, http.cookiejar, secrets
base=os.environ['KR_TEST_URL']
assert urllib.parse.urlparse(base).hostname in ('127.0.0.1','localhost')
checks=0
class Client:
 def __init__(self):
  self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar())); self.csrf=''; self.req('auth')
 def req(self,route,body=None,status=200,csrf=True):
  global checks
  headers={'Content-Type':'application/json','X-CSRF-Token':self.csrf if csrf else ''}
  req=urllib.request.Request(base+'/api.php?route='+route, data=None if body is None else json.dumps(body).encode(),headers=headers)
  try:r=self.http.open(req)
  except urllib.error.HTTPError as e:r=e
  d=json.load(r);assert r.status==status,(route,body and body.get('action'),r.status,status,d)
  self.csrf=d.get('csrf',self.csrf);checks+=1;return d
pw=secrets.token_urlsafe(24)
a=Client(); owner=a.req('auth',dict(action='register',email='owner@example.com',password=pw,token=os.environ['KR_TEST_SETUP_KEY']))['user'];assert owner['isSuperadmin']
g=Client();g.req('admin',status=401)
a.req('admin',dict(action='create',email='csrf@example.com'),403,csrf=False)
def manage(action,**kw):return a.req('admin',dict(action=action,**kw))
def token(r):return r['url'].split('invite=')[1]
def user(email):return next(u for u in a.req('admin')['users'] if u['email']==email)
def register(c,email,t,status=200):return c.req('auth',dict(action='register',email=email,password=pw,token=t,is_admin=1,isSuperadmin=True),status)
first=token(manage('create',email='new@example.com'));u=user('new@example.com');assert u['state']=='pending'
a.req('admin',dict(action='create',email='new@example.com'),409)
second=token(manage('resend',id=u['id']))
b=Client();register(b,'new@example.com',first,403);register(b,'wrong@example.com',second,403)
assert not register(b,'new@example.com',second)['user']['isSuperadmin']
register(g,'new@example.com',second,403)
b.req('admin',status=403)
for action in ['create','invite','deactivate','reactivate','resend','revoke']:
 b.req('admin',dict(action=action,email='hack@example.com',id=owner['id']),403)
address=dict(name='Private business',street='Test',house='1',zip='8000',city='Zürich',country='CH',email='')
b.req('data',dict(action='company',data=dict(**address,vat=False,terms=30),version=0))
customer=b.req('data',dict(action='customer',data=address))['id']
assert a.req('data')['company'] is None
a.req('data',dict(action='customer',customerId=customer,data=address),404)
listing=a.req('admin');assert 'password_hash' not in json.dumps(listing) and 'token_hash' not in json.dumps(listing) and 'Private business' not in json.dumps(listing)
for action in ['deactivate','reactivate','resend']:
 a.req('admin',dict(action=action,id=owner['id']),403)
manage('deactivate',id=u['id']);b.req('data',status=401)
g.req('auth',dict(action='login',email='new@example.com',password=pw),403)
manage('reactivate',id=u['id']);b.req('data',status=401)
b.req('auth',dict(action='login',email='new@example.com',password=pw));assert b.req('data')['company']['name']=='Private business'
old=token(manage('create',email='pending@example.com'));p=user('pending@example.com')
manage('deactivate',id=p['id']);register(g,'pending@example.com',old,403)
manage('reactivate',id=p['id']);assert user('pending@example.com')['state']=='pending'
register(g,'pending@example.com',old,403)
register(g,'pending@example.com',token(manage('resend',id=p['id'])))
invite=token(manage('invite',email='invited@example.com'))
assert any(i['email']=='invited@example.com' for i in a.req('admin')['invitations'])
manage('revoke',email='invited@example.com');c=Client();register(c,'invited@example.com',invite,403)
register(c,'invited@example.com',token(manage('invite',email='invited@example.com')))
assert c.req('data')['customers']==[]
print(f'{checks} admin HTTP checks passed: roles, CSRF, setup links, isolation, protected owner, deactivation, session revocation and reactivation.')
