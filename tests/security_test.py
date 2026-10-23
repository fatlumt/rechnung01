"""Only run against a fresh isolated localhost DB and dedicated session directory."""
import os,json,secrets,urllib.request,urllib.error,urllib.parse,http.cookiejar,pathlib,re,time
base=os.environ['KR_TEST_URL'];assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1')
sessions=pathlib.Path(os.environ['KR_TEST_SESSION_DIR']).resolve()
assert sessions.name=='security-sessions'
checks=0
class Client:
 def __init__(self):
  self.jar=http.cookiejar.CookieJar();self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar));self.csrf=''
 def req(self,route='auth',body=None,status=200,headers=None,method=None):
  global checks
  h={'Content-Type':'application/json','X-CSRF-Token':self.csrf};h.update(headers or {})
  q=urllib.request.Request(base+'/api.php?route='+route,data=json.dumps(body).encode() if body is not None else None,headers=h,method=method)
  try:r=self.http.open(q)
  except urllib.error.HTTPError as e:r=e
  data=json.load(r);assert r.status==status,(r.status,status,data)
  assert r.headers['X-Frame-Options']=='DENY'
  assert 'camera=()' in r.headers['Permissions-Policy']
  assert 'no-store' in r.headers['Cache-Control']
  assert not r.headers.get('X-Powered-By')
  self.csrf=data.get('csrf',self.csrf);checks+=1;return data
 def sid(self):return next(c.value for c in self.jar if c.name=='klarrechnung_session')
 def age(self,key,seconds):
  p=sessions/('sess_'+self.sid());assert p.parent==sessions and p.is_file()
  raw=p.read_text();raw,n=re.subn(re.escape(key)+r'\|i:\d+;',key+'|i:'+str(int(time.time())-seconds)+';',raw);assert n==1;p.write_text(raw)
a,b=Client(),Client();a.req();before=a.sid();pw=secrets.token_urlsafe(24)
a.req(body={'action':'register','email':'security@example.com','password':pw,'token':os.environ['KR_TEST_SETUP_KEY']});assert a.sid()!=before
cookie=next(iter(a.jar));assert cookie.has_nonstandard_attr('HttpOnly') and cookie.get_nonstandard_attr('SameSite')=='Lax'
b.req();b.req(body={'action':'login','email':'security@example.com','password':pw});stale=b.sid()
a.req(body={'action':'revokeSessions','current':'wrong-password'},status=403)
assert b.req()['user']
old_csrf=a.csrf;old_sid=a.sid();a.req(body={'action':'revokeSessions','current':pw});assert a.sid()!=old_sid and a.csrf!=old_csrf
b.req('data',status=401);assert a.req()['user']
a.req(body={'action':'logout'},headers={'X-CSRF-Token':old_csrf},status=403)
a.req(body={'action':'logout'},headers={'Origin':'https://evil.example'},status=403)
a.req(headers={'Sec-Fetch-Site':'cross-site'},status=403)
a.req(body={'action':'logout'},headers={'Content-Type':'application/json-evil'},status=415)
a.req(method='PUT',status=405)
a.req(body={'action':'unknown'},headers={'Content-Type':'application/json; charset=utf-8'},status=400)
newpw=secrets.token_urlsafe(24);b.req();b.req(body={'action':'login','email':'security@example.com','password':pw})
a.req(body={'action':'password','current':pw,'password':newpw});assert a.req()['user'];b.req('data',status=401)
a.age('last',1801);assert a.req()['user'] is None
a.req(body={'action':'login','email':'security@example.com','password':newpw});a.age('authenticated_at',28801);assert a.req()['user'] is None
a.req(body={'action':'login','email':'security@example.com','password':newpw});a.req(body={'action':'logout'});assert a.req()['user'] is None
fake=Client();fake.req(headers={'Cookie':'klarrechnung_session=attackerchosen1234567890123456'});assert fake.sid()!='attackerchosen1234567890123456'
for i in range(13):fake.req(body={'action':'login','email':'missing@example.com','password':pw},status=401 if i<12 else 429)
print(f'{checks} security HTTP checks passed: CSRF, session fixation, expiry, rotation, password changes, other-device revocation, rate limits, headers and request validation.')
