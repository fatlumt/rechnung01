export function attachedInvoice(doc:any,documents:any[]=[]){
 if(!['reminder','dunning'].includes(doc.kind))return null;
 if(doc.data.sourceInvoice)return doc.data.sourceInvoice;
 const source=documents.find(d=>d.id===doc.data.sourceId&&d.kind==='invoice');
 return source?{...source,fee:0,total:source.invoiceTotal??source.total,paid:Math.min(doc.data.paidAtCreation||0,source.invoiceTotal??source.total)}:null;
}
export const dueAmount=(doc:any)=>['reminder','dunning'].includes(doc.kind)?doc.data.amountDue??doc.data.outstanding:Math.max(0,doc.total-doc.paid);
