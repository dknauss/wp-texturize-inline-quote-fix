from pathlib import Path
import os,json,csv,statistics,collections,shutil
here=Path(__file__).parent
out=Path(os.environ.get('TYPOGRAPHY_OUT',here/'out'))
repo=Path(os.environ['KEEL_REPO'])
versions=['6.4','6.4.13','6.5.13','6.6.10','6.7.10','6.8.11','6.9.10','7.0.7','7.1.3']
expected=json.loads((here/'sources.json').read_text());hashes={r['version']:r['formatting_sha256']for r in expected}
files=[out/(m+'-'+v+'.json')for m in ['single','multisite']for v in versions]
sets=[json.loads(p.read_text())for p in files]
base=sets[-1]['rows'];get=lambda rows:{(r['locale'],r['surface'],r['name']):(r['input'],r['native'],r['bridge'],r['reference'])for r in rows}
base_map=get(base)
for j in sets:
 assert len(j['rows'])==570
 assert all(all(v for v in x['contracts'].values())for x in j['summary'].values())
 assert get(j['rows'])==base_map,(j['mode'],j['version'],'output differs')
 if 'native_sha256'in j:assert j['native_sha256']==hashes[j['version']]
 for loc,mp in j['maps'].items():
  if loc!='en_US':assert mp['_catalog_sha256']
for v in ['6.4','7.1.3']:
 extra=json.loads((out/('multisite-'+v+'-keel.json')).read_text());assert get(extra['rows'])==base_map
for p in out.glob('surfaces-*.json'):
 rows=json.loads(p.read_text());assert all(r['rest_status']==200 for r in rows)
 assert len(set(r['raw']for r in rows))==1
networks=[json.loads((out/('network-'+v+'.json')).read_text())for v in versions]
assert all(len(j['records'])==3 and j['restored']['blog'] and j['restored']['locale']=='en_US' for j in networks)
raw_dir=repo/'docs/review/data';raw_dir.mkdir(exist_ok=True)
fieldnames=['locale','surface','name','input','native','bridge','reference']
mis=[r for r in base if r['bridge']!=r['reference']]
with (raw_dir/'typography-mismatches-2026-10-17.csv').open('w',newline='')as f:
 w=csv.DictWriter(f,fieldnames=fieldnames,extrasaction='ignore');w.writeheader();w.writerows(mis)
summary={'versions':versions,'locales':list(sets[-1]['maps']),'core_patch':'1f07246ffe33a9d288c17070c856fd56989cbb62','bridge':'1c8af5d524c406afc33c936b05c029ea35ce8c53','environments':len(sets)*6,'rows':sum(len(x['rows'])for x in sets),'identical_across_versions_and_modes':True,'distinct_mismatch_rows':len(mis),'matrix_mismatch_rows':len(mis)*len(sets),'keel_additional_rows':1140,'sources':expected,'per_locale':sets[-1]['summary'],'effective_quote_mappings':sets[-1]['maps'],'benchmarks':[]}
for p in sorted(out.glob('bench-*.json')):
 j=json.loads(p.read_text());rows=[]
 assert j['http']==0 and j['writes']==0
 for r in j['results']:
  vals={a:{'median_ms':statistics.median(v),'p95_ms':sorted(v)[28],'peak_allocated_bytes':max(r['peaks'][a]),'max_queries':max(r['queries'][a])}for a,v in r['samples'].items()}
  assert all(v['max_queries']==0 for v in vals.values())
  rows.append({k:r[k]for k in ['version','mode','locale','name','bytes']}|{'arms':vals})
 summary['benchmarks'].append({'file':p.name,'rows':rows,'http':j['http'],'writes':j['writes']})
(raw_dir/'typography-validation-2026-10-17.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({k:summary[k]for k in ['environments','rows','distinct_mismatch_rows','matrix_mismatch_rows','keel_additional_rows']},indent=2))
for b in summary['benchmarks']:
 print(b['file'])
 for r in b['rows']:
  if r['locale']=='en_US':print(r['name'],r['bytes'],{a:round(v['median_ms'],4)for a,v in r['arms'].items()})
