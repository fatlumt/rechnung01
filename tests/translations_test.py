"""Translation security/integration checks. Fresh isolated localhost database only."""
import os,json,secrets,urllib.request,urllib.error,urllib.parse,http.cookiejar,pathlib,re
base=os.environ['KR_TEST_URL'];assert urllib.parse.urlparse(base).hostname in ('localhost','127.0.0.1')
catalog=json.loads((pathlib.Path(__file__).resolve().parents[1]/'backend/translation-catalog.json').read_text(encoding='utf-8'))
plain=next(key for key in catalog['messages'] if not re.search(r'\{[a-zA-Z_][a-zA-Z0-9_]*\}',key))
parameter=next(key for key in catalog['messages'] if re.search(r'\{[a-zA-Z_][a-zA-Z0-9_]*\}',key))
tokens=re.findall(r'\{[a-zA-Z_][a-zA-Z0-9_]*\}',parameter)
checks=0
class Client:
 def __init__(self):
  self.http=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));self.csrf='';self.req('auth')
 def req(self,route,body=None,status=200,csrf=True):
  global checks
  request=urllib.request.Request(base+'/api.php?route='+route,data=None if body is None else json.dumps(body).encode(),headers={'Content-Type':'application/json','X-CSRF-Token':self.csrf if csrf else ''})
  try:r=self.http.open(request)
  except urllib.error.HTTPError as e:r=e
  data=json.load(r);assert r.status==status,(route,r.status,status,data)
  self.csrf=data.get('csrf',self.csrf);checks+=1;return data
guest=Client();initial=guest.req('translations');assert initial['version']==0 and initial['overrides']=={'de':{},'en':{},'sq':{}}
payload=dict(action='save',locale='en',source=plain,text='Edited application label',version=0)
guest.req('translations',payload,401)
password=secrets.token_urlsafe(24);admin=Client();admin.req('auth',dict(action='register',email='translator-admin@example.com',password=password,token=os.environ['KR_TEST_SETUP_KEY']))
admin.req('translations',payload,403,csrf=False)
invite=admin.req('admin',dict(action='invite',email='translator-user@example.com'))
user=Client();user.req('auth',dict(action='register',email='translator-user@example.com',password=password,token=invite['url'].split('invite=')[1]))
user.req('translations',payload,403)
saved=admin.req('translations',payload);assert saved['version']==1 and saved['overrides']['en'][plain]==payload['text']
assert guest.req('translations')==saved and user.req('translations')==saved
admin.req('translations',dict(payload,text='Must not overwrite',version=0),409)
assert guest.req('translations')==saved
for invalid in [dict(locale='fr'),dict(source='not-in-shipped-catalog'),dict(action='arbitrary'),dict(text=''),dict(text='x'*8001),dict(text='<script>alert(1)</script>'),dict(text='x\x00y')]:
 admin.req('translations',dict(payload,version=1,**invalid),400)
admin.req('translations',dict(payload,version=1,source=parameter,text='Missing parameters'),400)
admin.req('translations',dict(payload,version=1,source=parameter,text=' '.join(tokens+['{unexpected}'])),400)
admin.req('translations',dict(payload,version=1,source=parameter,text=' '.join(tokens+tokens)),400)
assert guest.req('translations')==saved
parameters=admin.req('translations',dict(payload,version=1,source=parameter,text='Translated: '+' '.join(tokens)));assert parameters['version']==2
german=admin.req('translations',dict(payload,locale='de',text='Angepasster App-Text',version=2));assert german['version']==3 and german['overrides']['en'][plain]==payload['text']
reset=admin.req('translations',dict(action='reset',locale='en',source=plain,version=3));assert reset['version']==4 and plain not in reset['overrides']['en'] and reset['overrides']['de'][plain]=='Angepasster App-Text'
assert set(reset)=={'version','overrides'} and set(reset['overrides'])=={'de','en','sq'}
dump=json.dumps(guest.req('translations'));assert all(private not in dump for private in ('translator-admin@example.com','translator-user@example.com','password_hash','csrf'))
admin.req('translations',dict(action='reset',locale='en',source=parameter,version=4))
admin.req('translations',dict(action='reset',locale='de',source=plain,version=5))
assert guest.req('translations')['overrides']=={'de':{},'en':{},'sq':{}}
albanian=admin.req('translations',dict(payload,locale='sq',text='Tekst i përshtatur',version=6));assert albanian['overrides']['sq'][plain]=='Tekst i përshtatur' and albanian['overrides']['en']=={}
admin.req('translations',dict(action='reset',locale='sq',source=plain,version=7));assert guest.req('translations')['overrides']=={'de':{},'en':{},'sq':{}}
print(f'{checks} translation HTTP checks passed: public catalogue, superadmin-only edits, CSRF, optimistic conflicts, whitelisted keys/languages, placeholders, text-only bounds, per-language overrides and reset.')
