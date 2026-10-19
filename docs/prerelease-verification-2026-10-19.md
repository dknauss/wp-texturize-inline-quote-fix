# 0.2.0-beta.1 candidate verification — 2026-10-19

## Artifact and scope

Candidate: `dist/wp-texturize-inline-quote-fix-0.2.0-beta.1.zip`, 21,833 bytes.
SHA-256: `0bd3acebb6a7083d0daeb31e0dac28c45098a21ab7d11c7be069e7872c374b68`.
Build: `python3 tests/package-contracts.py`; the sidecar contains the same hash.
Nine files: main entry, legacy loader, two includes, LICENSE, README.md,
CHANGELOG.md, readme.txt and composer.json. No installed dependencies, tests,
tools, CI, verification data or repository metadata ship. Two independent builds
are byte-identical; archive bytes match the current source files.

No tag, GitHub release, WordPress.org submission/SVN operation or production
site write was performed. No publication dry run was needed or run. Keel was
left unchanged. Historical 10,260-row validation is in the earlier record; this
pass is candidate installation/upgrade/rendering verification, not a repeated
nine-version/performance matrix. GitHub CI has not run for this local branch.

## Findings fixed

- Reproduced `validate_plugin()` returning `no_plugin_header` for the legacy
  faithful path. Restored a labelled compatibility header, pointing to the same
  engine. Both entry paths remain valid; both active still produce one callback.
- Added ABSPATH guards in bootstrap, generated formatter and legacy loader;
  direct browser requests return empty bodies without loading WordPress.
- Generated core translation calls explicitly use the `default` domain. Core
  hooks retain native names with scoped PHPCS explanations. These are core-owned
  contracts: changing them to a plugin domain/prefix would break compatibility.
- Renamed release notes to the accepted CHANGELOG.md root filename.
- Integration corpus loads from the source test directory so it can test an
  installed ZIP that deliberately excludes development fixtures.

## Local checks

WPCS, PHPCompatibility (7.4 floor), PHP syntax, generated-source reproduction,
package metadata/allowlist/source parity and deterministic build pass.
Pressship 0.2.2 local readme validation reports no findings. Its archive helper
produces identical filenames and uncompressed bytes to the deterministic ZIP.
Pressship's remote validator returned no findings but also no explicit validation
result text; do not treat that as a confirmed remote validator pass. Plugin Check
ran directly through Studio WP-CLI against the installed package, not a symlink.
No `--no-verify`, excluded checks or ignored findings were used to claim a pass.
The Pressship CLI `verify` command was not used; its readme/archive library checks
and the separately invoked real-site Plugin Check are recorded independently.

## Plugin Check 2.1.0

Full remaining output: [Plugin Check findings](plugin-check-0.2.0-beta.1.txt).
One error: `plugin_updater_detected`, because WordPress.org forbids the GitHub
Update URI. Three warnings: the existing WP name/slug in main metadata/readme.
These are retained, explicit WordPress.org-distribution limitations, not a clean
Plugin Check result. The GitHub Update URI protects against unrelated directory
updates; no updater code or outbound request was added. Renaming the installed
slug would undermine migration. A future WordPress.org submission needs separate
metadata/slug review and an appropriate Update URI change. All initially reported
direct-access, missing-domain, core-hook-prefix and markdown-file findings clear.

## Disposable WordPress lab

WordPress 7.1.3, Studio PHP 8.4, SQLite; real installed ZIP, not source symlink.
Fresh ZIP installation/activation passes. Recreated 0.1.0 packages from pinned
repository revision `1c8af5d524c406afc33c936b05c029ea35ce8c53`; upgraded each active
main/faithful entry by replacing the full directory with the candidate. Both
preserve a valid active path and corrected rendering. Enabling both entries
retains exactly one callback. These checks do not imply automatic migration of
an old standalone PHP file outside the plugin directory; notes explain that case.

Single-site integration: 315 assertions (without the late MU fixture). Final
multisite integration: 316 with that fixture. Real network activation, German/
French subsites and locale entry/restoration pass. Separate retired-core,
conflicting-engine and request-disabled guards pass. Release contracts validate
both installed headers, versions, output and one callback.

An initial network fixture failed because the lab remained German while the test
expects an English starting locale. `WPLANG` must be cleared to restore default
English; setting `en_US` was rejected/unchanged by this site's option validation.
After clearing the option, both integration and site/locale restoration pass;
no formatter change was made for this setup failure.

## Browser observations

Real frontend DOM assertions pass for opening-tag `I’ve`, closing-tag `He’s`,
Unicode `café’s`, and unchanged literal code/entity examples. German site locale
renders `‚Hello‘`; restored English renders `‘Hello’`. Deactivating both entries
restores native `I‘ve`/`He‘s` output. Network activation renders corrected output.
Stored post-content SHA-256 stays unchanged across rendering and activation.

Initial query-ID navigation hit a stale canonical redirect from an earlier lab
using this local port; the actual new permalink plus a fresh query reached the
correct fixture. No formatter defect was inferred from those initial 404s.
Browser checks used public pages, not editor typing or an admin upload UI.

## Remaining limits

This local candidate pass uses PHP 8.4/SQLite; earlier CI covered PHP 7.4/8.4,
MariaDB and nine core versions. The updated ZIP-based CI must run before tagging.
This is not native-speaker, multilingual-plugin, production-cache/throughput or
editor-keystroke certification. No new performance claim beyond the recorded
paired benchmarks is made. The disposable lab is removed after verification.
