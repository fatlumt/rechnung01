"""Local-only regression for one-time fees, snapshots, and reusable QR settings."""
import runpy,copy,base64
from pathlib import Path
s=runpy.run_path(str(Path(__file__).with_name('row_actions_test.py')))
b=s['b'];get=s['get'];action=s['action'];create=s['create'];today=s['today']
def action(id,command,status=200,**kw):return b.request('data',{'action':command,'id':id,'version':get(id)['version'],**kw},status)
inv=create(s['draft']);action(inv,'issue');base=get(inv)['total']
r=action(inv,'quickReminder',kind='reminder')['id']
assert get(inv)['fee']==0 and get(r)['data']['amountDue']==base
m=action(inv,'quickReminder',kind='dunning')['id'];snapshot=copy.deepcopy(get(m))
assert get(inv)['fee']==5000 and get(inv)['total']==base+5000
assert snapshot['data']['feeOutstanding']==5000 and snapshot['data']['amountDue']==base+5000
assert snapshot['data']['sourceInvoice']['total']==base and snapshot['data']['sourceInvoice']['fee']==0
again=action(inv,'quickReminder',kind='dunning')['id']
assert get(inv)['fee']==5000 and get(again)['data']['amountDue']==base+5000
action(inv,'pay',amount=base+2000,date=str(today))
r2=action(inv,'quickReminder',kind='reminder')['id']
assert get(r2)['data']['outstanding']==0 and get(r2)['data']['feeOutstanding']==3000 and get(r2)['data']['amountDue']==3000
b.request('data',{'action':'status','id':inv,'version':get(inv)['version'],'status':'paid'})
assert get(inv)['paid']==base+5000 and get(m)==snapshot
action(inv,'quickReminder',status=400,kind='dunning')
b.request('data',{'action':'status','id':inv,'version':get(inv)['version'],'status':'open','confirmReversal':True})
action(inv,'quickReminder',kind='dunning');assert get(inv)['total']==base+5000
profile=s['profile'];parts=['SPC','0200','1',profile['iban'],'S','Testbetrieb','Teststrasse','1','8000','Zürich','CH',*(['']*7),'','CHF',*(['']*7),'NON','','','EPD']
assert len(parts)==31
# Valid image fixture; image decoding itself is tested separately in the browser.
pixel='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aY9sAAAAASUVORK5CYII='
def company(payload,status=200):
 data=b.request('data');return b.request('data',{'action':'company','version':data['companyVersion'],'data':{**profile,'qrUrl':pixel,'qrPayload':payload}},status)
for index,value in [(18,'100.00'),(20,'S'),(27,'QRR'),(28,'123'),(3,'CH0000762011623852957')]:
 bad=parts.copy();bad[index]=value;company('\n'.join(bad),400)
company('\n'.join(parts))
new=create(s['draft']);assert get(new)['data']['company']['qrPayload']=='\n'.join(parts)
assert not get(inv)['data']['company'].get('qrUrl') and get(m)==snapshot
print('Payment bundle tests passed: fee once, partial settlement, corrections, immutable attachments, QR constraints and snapshots.')

