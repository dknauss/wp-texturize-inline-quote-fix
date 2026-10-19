# 0.2.0-beta.1 — prerelease candidate

This experimental feature plugin carries the inline quote-context fixes from core
PR #12249, including opening-tag reconciliation credited to Igor Rozum's #12645.
It is intended for compatibility feedback before upstream adoption.

## Changes

- Preserve word/quote context across inline formatting, including Unicode endings,
  uppercase tags and tab/newline attributes.
- Use locale-derived quote/apostrophe values; refresh tables on locale entry and
  restoration while preserving the request-level formatting opt-out.
- Replace native callbacks in place, keeping order, priority, argument counts and
  native callback removal.
- Replace the English-only post-processor and old faithful engine with one engine.
- Stand down on unsupported/prerelease core, known old engines and core fixes.

## Installation and migration

Install the attached ZIP as a complete directory. It contains all runtime files;
Composer and Node dependencies are not required. Replace the complete old plugin
directory, preserving its `wp-texturize-inline-quote-fix` name. Single-file MU
installations must migrate to a complete subdirectory plus a top-level loader.
Do not leave an old copied engine active alongside this package. A standalone
PHP file installed directly in `plugins/` cannot be replaced by this directory
ZIP: deactivate that old entry before installing this package.

The legacy faithful entry remains listed as a compatibility loader so an active
old installation is not invalidated by WordPress. It uses the same engine as the
main entry; enabling both does not run formatting twice. Prefer the main entry
for new installations.

Deactivate both entries if both are active to restore native rendering on the next request. Stored post source is
unchanged, so no content rollback or database migration is needed.

## Scope and costs

Released WordPress 6.4–7.1, PHP 7.4+. Only documented native filter callbacks are
replaced; direct `wptexturize()` calls and editor typing remain native. No automatic
per-span language detection or complete linguistic correctness is promised.

Six real locale catalogs, nine WordPress versions and single-site/multisite were
validated. The earlier 10,260-row comparison matches the patched references and
frozen corpus. See the verification records for boundaries and excluded scenarios.

On the recorded ~40 KB WordPress 7.1.3 fixtures, the plugin adds about 0.55 ms for
HTML and 1.14 ms for quote-heavy HTML over native core. This reduces the earlier
patch's added cost about 66% / 55%; it is not faster than native core. Bootstrap,
page latency and production throughput are not measured by that microbenchmark.

Keel remains independent and unchanged. This candidate has not been tagged,
published or deployed to production.
