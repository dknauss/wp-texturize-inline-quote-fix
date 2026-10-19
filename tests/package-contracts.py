#!/usr/bin/env python3
"""Assert release metadata, the package allowlist, reproducibility and source parity."""
from pathlib import Path
import hashlib, re, subprocess, zipfile
root = Path(__file__).resolve().parents[1]
version = re.search(r' \* Version: (.+)', (root / 'wp-texturize-inline-quote-fix.php').read_text()).group(1)
assert 'Stable tag: ' + version in (root / 'readme.txt').read_text()
assert 'Version: ' + version in (root / 'wp-texturize-inline-quote-fix-faithful.php').read_text()
archive = root / 'dist' / ('wp-texturize-inline-quote-fix-' + version + '.zip')
subprocess.run(['python3', str(root / 'tools/build-zip.py')], check=True, capture_output=True)
first = archive.read_bytes()
subprocess.run(['python3', str(root / 'tools/build-zip.py')], check=True, capture_output=True)
assert first == archive.read_bytes(), 'Non-reproducible ZIP'
with zipfile.ZipFile(archive) as z:
    expected = {'LICENSE', 'README.md', 'CHANGELOG.md', 'readme.txt', 'composer.json',
                'wp-texturize-inline-quote-fix.php', 'wp-texturize-inline-quote-fix-faithful.php',
                'includes/bootstrap.php', 'includes/formatter.php'}
    prefix = 'wp-texturize-inline-quote-fix/'
    assert set(z.namelist()) == {prefix + name for name in expected}, 'Wrong package contents'
    for name in expected:
        assert z.read(prefix + name) == (root / name).read_bytes(), 'Stale package file: ' + name
print('Package metadata, nine-file allowlist, source parity and reproducibility: pass')
