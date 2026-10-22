# Not in the original 2026-10-19 run directory: this is the 2026-10-17 prepare.py
# pointed at this harness's core-inline.patch. Applied to stock WordPress sources it
# reproduces the committed harness/reference-*.php files byte-for-byte.
from pathlib import Path
import os,urllib.request,zipfile,io,subprocess,hashlib,json
here=Path(__file__).parent
out=Path(os.environ.get('TYPOGRAPHY_OUT',here/'out'))
lab=Path(os.environ.get('TYPOGRAPHY_LAB',here/'harness'))
versions=['6.4','6.4.13','6.5.13','6.6.10','6.7.10','6.8.11','6.9.10','7.0.7','7.1.3']
manifest=[]
for v in versions:
 raw=urllib.request.urlopen('https://wordpress.org/wordpress-'+v+'.zip').read()
 source=zipfile.ZipFile(io.BytesIO(raw)).read('wordpress/wp-includes/formatting.php')
 stage=out/'references'/v/'src/wp-includes';stage.mkdir(parents=True,exist_ok=True)
 (stage/'formatting.php').write_bytes(source)
 root=stage.parent.parent
 r=subprocess.run(['git','apply','--unsafe-paths',str(here/'core-inline.patch')],cwd=root,capture_output=True,text=True)
 if r.returncode: raise RuntimeError(v+': '+r.stderr)
 patched=(stage/'formatting.php').read_text();start=patched.index('function wptexturize(');end=patched.index('function wptexturize_primes(');end=patched.rfind('/**',start,end)
 code=patched[start:end]
 for name in ['_wptexturize_get_text_context','_wptexturize_get_inline_tag_type','wptexturize']:
  import re
  code=re.sub(r'\b'+name+r'\b','lab_'+name,code)
 (lab/('reference-'+v+'.php')).write_text('<?php\n'+code)
 manifest.append({'version':v,'archive_sha256':hashlib.sha256(raw).hexdigest(),'formatting_sha256':hashlib.sha256(source).hexdigest(),'patch_applied':True})
 print(v,'patch applied',flush=True)
(out/'sources.json').write_text(json.dumps(manifest,indent=2))
