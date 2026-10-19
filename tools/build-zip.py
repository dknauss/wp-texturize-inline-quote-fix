#!/usr/bin/env python3
"""Build a deterministic, dependency-free GitHub release ZIP."""
from pathlib import Path
import hashlib, re, zipfile
root = Path(__file__).resolve().parents[1]
header = (root / 'wp-texturize-inline-quote-fix.php').read_text()
version = re.search(r'^ \* Version: ([0-9A-Za-z.-]+)$', header, re.M).group(1)
files = ['LICENSE', 'README.md', 'CHANGELOG.md', 'readme.txt', 'composer.json',
         'wp-texturize-inline-quote-fix.php', 'wp-texturize-inline-quote-fix-faithful.php',
         'includes/bootstrap.php', 'includes/formatter.php']
out = root / 'dist'; out.mkdir(exist_ok=True)
archive = out / ('wp-texturize-inline-quote-fix-' + version + '.zip')
with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as z:
    for name in sorted(files):
        info = zipfile.ZipInfo('wp-texturize-inline-quote-fix/' + name, (2026, 1, 1, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        z.writestr(info, (root / name).read_bytes())
digest = hashlib.sha256(archive.read_bytes()).hexdigest()
archive.with_suffix('.zip.sha256').write_text(digest + '  ' + archive.name + '\n')
print(str(archive)); print(str(archive.stat().st_size) + ' bytes; SHA-256 ' + digest)
