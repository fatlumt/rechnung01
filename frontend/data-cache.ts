// Replace only server-confirmed data; never guess payment balances locally.
export function applyDocumentPatch(current:any,result:any){
 const doc=result.documentPatch;if(!current||!doc||!Array.isArray(result.documentPayments)||!current.documents.some((d:any)=>d.id===doc.id))return null;
 const updateIds=(ids:string[]=[],enabled:boolean)=>enabled?Array.from(new Set([...ids,doc.id])):ids.filter(id=>id!==doc.id);
 return {...current,documents:current.documents.map((d:any)=>d.id===doc.id?doc:d),
  archivedDocumentIds:updateIds(current.archivedDocumentIds,!!doc.archived),trashedDocumentIds:updateIds(current.trashedDocumentIds,!!doc.trashed),
  payments:[...current.payments.filter((p:any)=>p.document_id!==doc.id),...result.documentPayments].sort((a:any,b:any)=>b.date.localeCompare(a.date)||a.id.localeCompare(b.id))};
}
