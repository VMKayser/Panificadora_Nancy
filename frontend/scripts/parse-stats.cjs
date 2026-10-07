const fs = require('fs');
const path = require('path');
const file = path.resolve(__dirname, '..', 'dist', 'bundle-stats.json');
if (!fs.existsSync(file)) {
  console.error('file not found:', file);
  process.exit(1);
}
const s = JSON.parse(fs.readFileSync(file, 'utf8'));
const parts = s.nodeParts || {};
const metas = s.nodeMetas || {};
const arr = [];
for (const uid in parts) {
  const p = parts[uid];
  const metaUid = p.metaUid;
  const meta = metas[metaUid] || {};
  const id = meta.id || metaUid;
  arr.push({ uid, rendered: p.renderedLength || 0, gzip: p.gzipLength || 0, brotli: p.brotliLength || 0, id });
}
arr.sort((a,b) => b.rendered - a.rendered);
const top = arr.slice(0, 20);
const human = (n) => {
  if (n > 1e6) return (n/1e6).toFixed(2) + ' MB';
  if (n > 1e3) return (n/1e3).toFixed(2) + ' kB';
  return n + ' B';
};
console.log('Top contributors (by rendered bytes):');
top.forEach((x,i) => {
  console.log(`${i+1}. ${human(x.rendered).padStart(9)} -> ${x.id}`);
});

// Also aggregate by package folder (node_modules or src)
const agg = {};
for (const it of arr) {
  const id = it.id;
  const key = id.includes('/node_modules/') ? id.split('/node_modules/')[1].split('/')[0] : (id.startsWith('src/') ? 'src' : id);
  agg[key] = (agg[key] || 0) + it.rendered;
}
const aggArr = Object.entries(agg).map(([k,v]) => ({k,v})).sort((a,b)=>b.v-a.v).slice(0,10);
console.log('\nTop aggregated packages/folders:');
aggArr.forEach((a,i)=>console.log(`${i+1}. ${(a.v/1024).toFixed(2)} kB -> ${a.k}`));
