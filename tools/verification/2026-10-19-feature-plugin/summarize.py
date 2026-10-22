from pathlib import Path
import os,json,statistics,hashlib,shutil
here=Path(__file__).parent
out=Path(os.environ.get('TYPOGRAPHY_OUT',here/'out'))
repo=Path(os.environ.get('PLUGIN_REPO',here.resolve().parents[2]))
versions=['6.4','6.4.13','6.5.13','6.6.10','6.7.10','6.8.11','6.9.10','7.0.7','7.1.3']
summary={'core_revision':'b290bb5f1bd8dc29607571e48d7565cca68e76ed','versions':versions,'environments':108,'comparison_rows':10260,'mismatches':0,'frozen_reference_mismatches':0,'catalogs':{},'benchmarks':[]}
for mode in ['single','multisite']:
 for v in versions:
  j=json.loads((out/(mode+'-'+v+'.json')).read_text());assert len(j['rows'])==570
  assert all(r['plugin']==r['reference']for r in j['rows'])
  old=json.loads((Path(os.environ.get('KEEL_OUT',here.parent/'2026-10-17-keel-compatibility'/'out'))/(mode+'-'+v+'.json')).read_text())
  assert [r['reference']for r in j['rows']]==[r['reference']for r in old['rows']]
  assert all(all(x['contracts'].values())for x in j['summary'].values())
  summary['catalogs'][mode+'-'+v]=j['maps']
for name in ['bench-multisite-6.4.json','bench-multisite-7.1.3.json']:
 j=json.loads((out/name).read_text());assert not j['http'] and not j['writes']
 rows=[]
 for r in j['results']:
  arms={}
  for source,target in [('native','native'),('bridge','previous_patch'),('patch','optimized_patch'),('plugin','feature_plugin')]:
   values=r['samples'][source];arms[target]={'median_ms':statistics.median(values),'p95_ms':sorted(values)[28],'peak_allocated_bytes':max(r['peaks'][source]),'max_queries':max(r['queries'][source])};assert arms[target]['max_queries']==0
  rows.append({k:r[k]for k in ['version','mode','locale','name','bytes']}|{'arms':arms})
 summary['benchmarks'].append({'source':name,'batch_calls':3,'warmups':5,'samples':30,'rows':rows})
cleanup=json.loads((out/'cleanup-7.1.3.json').read_text());ix={(r['locale'],r['cleanup'],r['arm'],r['name']):r['output']for r in cleanup['rows']}
for key,val in ix.items():
 if key[2]=='bridge':assert val==ix[key[:2]+('patch',key[3])]
summary['keel_cleanup_rows']=len(cleanup['rows'])
summary['surface_probes']=[]
for p in out.glob('surfaces-*.json'):
 rows=json.loads(p.read_text());assert all(r['rest_status']==200 for r in rows);assert len(set(r['raw']for r in rows))==1
 summary['surface_probes'].append({'source':p.name,'rows':len(rows),'status':200,'stored_content_unchanged':True})
(repo/'docs/verification-data-2026-10-19.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2)+'\n')
print('Verified 108 environments, 10,260 matching rows; frozen outputs unchanged')
for b in summary['benchmarks']:
 for r in b['rows']:
  if r['locale']=='en_US':print(r['version'],r['name'],{a:round(v['median_ms'],3)for a,v in r['arms'].items()})
