"""Run against a fresh isolated local database, as documented in README."""
import runpy,copy
from pathlib import Path
s=runpy.run_path(str(Path(__file__).with_name('payment_bundle_test.py')))
b=s['b'];get=s['get'];create=s['create'];action=s['action']
draft={**s['s']['draft'],'lines':[{'description':'A','quantity':1,'price':10000,'rate':8.1},{'type':'section','description':'Zimmer 2'},{'description':'B','quantity':1,'price':10000,'rate':2.6}]}
for kind,value,total in [('percent',1000,18963),('fixed',2000,18963),('none',0,21070)]:
 id=create({**draft,'discountType':kind,'discountValue':value});assert get(id)['total']==total
 action(id,'issue');r=action(id,'quickReminder',kind='reminder')['id'];assert get(r)['data']['amountDue']==total
 assert get(r)['data']['sourceInvoice']['data']['discountType']==kind
for patch in [{'discountType':'percent','discountValue':10001},{'discountType':'fixed','discountValue':20001},{'discountType':'fixed','discountValue':-1},{'discountType':'percent','discountValue':10000},{'discountType':'unknown'}]:
 b.request('data',{'action':'create','data':{**draft,**patch}},400)
id=create({**draft,'pricingMode':'total','totalPrice':10000,'totalRate':8.1,'discountType':'fixed','discountValue':1000});assert get(id)['total']==9729
offer=create({**draft,'kind':'offer','discountType':'percent','discountValue':1250});converted=action(offer,'convert')['id'];assert get(converted)['total']==18437 and get(converted)['data']['discountValue']==1250
original=copy.deepcopy(get(converted));action(converted,'issue');b.request('data',{'action':'edit','id':converted,'version':get(converted)['version'],'data':{**draft,'discountType':'fixed','discountValue':1}},400)
print('Discount API checks passed: mixed VAT, percent/CHF, limits, total-only, conversion, notices and immutable issued documents.')

