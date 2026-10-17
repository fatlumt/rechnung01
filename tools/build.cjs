const path=require('node:path');
const fs=require('node:fs');
const esbuild=require('esbuild');
const root=path.resolve(__dirname,'..');
esbuild.buildSync({absWorkingDir:root,entryPoints:['frontend/main.tsx'],bundle:true,minify:true,jsx:'automatic',outfile:'assets/app.js',define:{'process.env.NODE_ENV':'"production"'},target:['es2020'],nodePaths:(process.env.NODE_PATH||'').split(path.delimiter).filter(Boolean),legalComments:'eof'});
fs.copyFileSync(path.join(root,'public/favicon.svg'),path.join(root,'favicon.svg'));
console.log('Built assets/app.js and assets/app.css');
require('./copy-pdf-reader.cjs');
