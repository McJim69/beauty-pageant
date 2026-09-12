import { execSync } from 'child_process';
import fs from 'fs';

console.log('1. Build JS ...');
execSync('bunx rollup -c --bundleConfigAsCjs', { stdio: 'inherit' });
console.log('OK!\n');

console.log('2. Build CSS ...');
execSync('bunx sass src/sweetalert2.scss dist/sweetalert2.css --no-source-map', { stdio: 'inherit' });
execSync('bunx sass src/sweetalert2.scss dist/sweetalert2.min.css --no-source-map --style=compressed', { stdio: 'inherit' });
console.log('OK!\n');

console.log('3. Build JS+CSS ...');
const css = fs.readFileSync('dist/sweetalert2.min.css', 'utf8');
const cssInJs = `"undefined"!=typeof document&&function(e,t){var n=e.createElement("style");if(e.getElementsByTagName("head")[0].appendChild(n),n.styleSheet)n.styleSheet.disabled||(n.styleSheet.cssText=t);else try{n.innerHTML=t}catch(e){n.innerText=t}}(document,"${css
  .trim()
  .replace(/"/g, '\\"')}");`;

fs.writeFileSync('dist/sweetalert2.all.js', `${fs.readFileSync('dist/sweetalert2.js', 'utf-8')}${cssInJs}`);
fs.writeFileSync('dist/sweetalert2.all.min.js', `${fs.readFileSync('dist/sweetalert2.min.js', 'utf-8')}${cssInJs}`);
fs.writeFileSync('dist/sweetalert2.esm.all.js', `${fs.readFileSync('dist/sweetalert2.esm.js', 'utf-8')}${cssInJs}`);
fs.writeFileSync('dist/sweetalert2.esm.all.min.js', `${fs.readFileSync('dist/sweetalert2.esm.min.js', 'utf-8')}${cssInJs}`);

console.log('OK!\nAll compilation steps completed successfully!');
