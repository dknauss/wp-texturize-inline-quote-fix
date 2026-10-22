# Verification harnesses

Lab harnesses behind the typography compatibility and feature-plugin verification
records. They are kept here so the results can be re-run; they are not part of the
plugin. `tools/**` is excluded by `.pressshipignore`, and `tools/build-zip.py`
packages an explicit allowlist that does not include this directory.

**Disposable lab sites only.** The harnesses bootstrap a real WordPress install,
rewrite filter callbacks in memory, and several of them write: `surfaces*.php` and
`cleanup.php` insert and then delete a post; `network.php` creates `/german/` and
`/french/` subsites and sets their `WPLANG`. Never point them at a real site.

## `2026-10-17-keel-compatibility/`

Compares three engines on every input: native `wptexturize()`, native plus the
post-process bridge (`dk_texturize_inline_quote_fix()`, this repo at
`bridge-head.txt`, copied as `harness/bridge.php`), and `lab_wptexturize()`, the
core #18549 patch (PR #12249 at `1f07246ffe`, `core-inline.patch`) applied to each
release's own `formatting.php`.

| Script | What it does | Output |
| --- | --- | --- |
| `prepare.py` | Downloads each release ZIP from wordpress.org, applies `core-inline.patch`, extracts `wptexturize()` renamed to `lab_*`, writes `harness/reference-<version>.php` and refreshes `sources.json` (archive and source hashes) | `out/sources/` |
| `harness/run.php` | 47 direct inputs and 12 filter pipelines × 4 inputs, per locale, plus the stock locale-cache probe; counts HTTP attempts and DB writes | `<mode>-<version>.json` |
| `harness/run-keel.php`, `harness/surfaces-keel.php` | Same as `run.php` / `surfaces.php`, rerun with Keel active | `*-keel.json` |
| `harness/surfaces.php` | Renders a temporary post through title, content, excerpt, feed and the internal REST dispatcher for each engine | `surfaces-<mode>-<version>.json` |
| `harness/network.php` | Multisite only: blog and locale entry/restoration across subsites | `network-<version>.json` |
| `harness/cleanup.php` | Keel's `keel_defaults_hide_shortcode_residue` on/off against each engine (requires Keel) | `cleanup-<version>.json` |
| `harness/bench.php`, `harness/bench-large.php` | Paired, shuffled benchmark: 5 warm-up + 30 sample rounds, 10 (or 3, ~40 KB inputs) calls per sample | `bench-*.json` |
| `summarize.py` | Asserts 570 rows per version/mode, identical output across versions and modes, source hashes, REST 200 and unchanged stored content; writes Keel's condensed evidence | `$KEEL_REPO/docs/review/data/` |

Results: Keel's `docs/review/typography-compatibility-2026-10-17.md` (with
`docs/review/data/typography-validation-2026-10-17.json` and
`typography-mismatches-2026-10-17.csv`).

## `2026-10-19-feature-plugin/`

Same corpus and matrix, with the feature plugin's formatter
(`DK\Texturize\render()`, which the plugin must load) compared to the optimized
core patch (PR #12249 at `b290bb5f1b`, `core-inline.patch`). `harness/before.php`
and `harness/after.php` are the previous and optimized patched functions
renamed `before_wptexturize()` / `after_wptexturize()` for the four-arm benchmark
(native, previous patch, optimized patch, feature plugin).

| Script | What it does |
| --- | --- |
| `prepare.py` | As above, with this directory's patch (see the note in the file) |
| `harness/run.php` | 570 rows per version/mode: native vs plugin vs reference |
| `harness/surfaces.php`, `harness/cleanup.php` | Rendering-surface and Keel cleanup probes |
| `harness/bench.php` | Four-arm paired benchmark on ~40 KB inputs |
| `summarize.py` | Asserts zero plugin/reference mismatches and that reference output equals the frozen 2026-10-17 outputs (`$KEEL_OUT`); writes `docs/verification-data-2026-10-19.json` in this repo |

Results: [`docs/verification-2026-10-19.md`](../../docs/verification-2026-10-19.md)
and [`docs/verification-data-2026-10-19.json`](../../docs/verification-data-2026-10-19.json).
The core PHPUnit, PHPStan and PHP 7.4 runs recorded there were made in a
`wordpress-develop` checkout, not by these scripts.

## Requirements

- A disposable local site driven by WP-CLI (the recorded runs used WordPress
  Studio: PHP 8.4.26, SQLite, Twenty Twenty-Four, which still boots 6.4). PHP 8.2+
  is needed for `memory_reset_peak_usage()`.
- WordPress 6.4, 6.4.13, 6.5.13, 6.6.10, 6.7.10, 6.8.11, 6.9.10, 7.0.7 and 7.1.3,
  switched in turn on the same site (`wp core update --version=<v> --force`).
  Each harness loads `reference-<installed version>.php`.
- Core language packs for `de_DE fr_FR ru_RU ar ja`, reinstalled for each version
  (`wp language core install …`). `run.php` fails if a locale does not load.
- Single-site pass first, then conversion to a three-site subdirectory network
  (`network.php` creates the subsites).
- Keel active for `cleanup.php` and the `*-keel.php` reruns. The feature harness
  needs this plugin active (`DK\Texturize\render`); the Keel harness loads its own
  bridge copy.
- Python 3.9+ and `git` for `prepare.py` / `summarize.py`.

## Running

```bash
cd tools/verification/2026-10-17-keel-compatibility
export TYPOGRAPHY_OUT="$PWD/out"; mkdir -p "$TYPOGRAPHY_OUT"
python3 prepare.py                      # optional: references are committed
wp eval-file harness/run.php            # repeat per version, single then multisite
wp eval-file harness/surfaces.php
wp eval-file harness/bench.php
KEEL_REPO=/path/to/keel python3 summarize.py
```

Every harness writes to `$TYPOGRAPHY_OUT`, defaulting to `out/` beside its
`harness/` directory (ignored by git). The feature `summarize.py` reads the frozen
2026-10-17 outputs from `$KEEL_OUT` (default: the sibling harness's `out/`) and
writes to `$PLUGIN_REPO` (default: this repo).

## Provenance

Copied from the original local run directories:

- `~/.codex/verification/keel-typography-compatibility-2026-10-17/` — evidence
  archive `typography-evidence.zip`, SHA-256
  `b85504eeda0f5c70ed3db2d9093735a88b590b6d1cde087f1a4d69202350c476`.
- `~/.codex/verification/typography-feature-2026-10-19/` — evidence archive
  `verification-evidence.zip`, SHA-256
  `6a2523763f23b84e9c1b0e3e60149461844ed12f39826b1b0d8b86e0520548d6`.

Changes from the originals: hard-coded absolute output, lab and repo paths were
replaced with the environment variables above, and the feature directory's
`prepare.py` was reconstructed (the original run kept no copy). The committed
reference files are unchanged and are byte-identical across versions within each
harness, because `wptexturize()` itself did not change across these releases.

Not committed: raw JSON outputs and benchmark samples (condensed in the docs
above), the downloaded and patched core `formatting.php` copies (regenerated by
`prepare.py` and pinned by `sources.json`), the evidence ZIPs (over 1 MB each),
CI status snapshots, the WordPress.org offers snapshot, PHPUnit/PHPStan logs and a
copy of this plugin's README.
