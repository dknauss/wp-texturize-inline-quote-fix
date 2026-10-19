# Feature-plugin verification — 2026-10-19

Core source: `b290bb5f1bd8dc29607571e48d7565cca68e76ed`, PR #12249.
Feature build: 0.2.0-dev; activation opts in. Keel runtime is unchanged.

## Regressions and implementation

The new core locale regression failed against the prior head with English quote
marks after switching to German. The old standalone plugin failed the opening-tag
apostrophe regression. The fixes preserve the request-level run_wptexturize gate,
refresh one bounded set of tables on locale entry/restoration, and classify inline
context lazily using token indices rather than a growing delimiter buffer.
Already-classified apostrophes skip unnecessary apostrophe/prime processing.

The generated plugin formatter uses the same algorithm, qualifying global calls
to preserve PHP builtin fast paths. Integration substitutes only native callbacks,
in place, preserving ordering, priorities, accepted arguments and native removal.
Guards cover unsupported/prerelease core, native fix detection, known conflicting
engines, disabled formatting and legacy-loader idempotency. No runtime dependencies,
options, persistent caches, assets or network requests are introduced.

## Checks

Core focused suite: 394 tests / 513 assertions on PHP 7.4.33 and 8.4.26.
Full formatting groups, single-site and multisite: 2,212 tests / 1,118,560 assertions
each; five pre-existing PHPUnit deprecation warnings, no failures/errors. WPCS,
PHPCompatibility and runtime PHPStan pass. Final fast-path rerun is recorded with
the completed evidence below.

The 9-version / 6-catalog single-site and multisite compatibility sweep compares
570 rows per version/mode against patched release-specific core references and the
frozen October 17 outputs. Preliminary sweeps have zero differences. Final-source
sweep and paired benchmark evidence are being completed before review readiness.

This is not an entire-core-suite, browser/editor-typing, MySQL/Redis throughput,
linguistic or mixed-language inference certification. Core's direct function calls
remain outside the standalone plugin contract. Site switching requires explicit
WordPress locale entry/restoration; the engine refreshes automatically afterward.
