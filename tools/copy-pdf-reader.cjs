const fs=require('node:fs'),path=require('node:path');
const root=path.resolve(__dirname,'..'),dest=path.join(root,'assets/pdf-reader');fs.mkdirSync(dest,{recursive:true});
for(const file of ['pdf.min.mjs','pdf.worker.min.mjs'])fs.copyFileSync(path.join(root,'node_modules/pdfjs-dist/legacy/build',file),path.join(dest,file));
fs.copyFileSync(path.join(root,'node_modules/pdfjs-dist/LICENSE'),path.join(dest,'LICENSE.txt'));
