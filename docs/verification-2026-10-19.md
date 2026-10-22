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
PHPCompatibility and runtime PHPStan pass. The final classified-apostrophe fast path passed the same reruns.

The 9-version / 6-catalog single-site and multisite compatibility sweep compares
570 rows per version/mode against patched release-specific core references and the
frozen October 17 outputs. The final-source sweep has **zero mismatches in all 10,260 rows**, also matching
all frozen October 17 outputs. The permanent integration suite now has 316
assertions including 282 literal corpus expectations, late lifecycle registration,
callback removal/order, locale restoration and generated shortcode preservation.
Core-fixed, unsupported/conflict and disabled-formatting guards run separately.
German/French subsites exercise actual blog/locale entry and restoration.

Keel shortcode cleanup was checked in 144 combinations alongside native, plugin
and patched engines; plugin and patched outputs agree. REST/feed/title/content/
excerpt probes retained stored content and returned 200 through the internal REST
dispatcher. These are API-rendering checks, not browser screenshots.

## Paired performance

Real WordPress/PHP 8.4, six real locales, 5 warm-up / 30 shuffled sample rounds,
3 formatting calls per sample. All short-source fixtures are repeated ten times
for ~36–40 KB inputs. No parallel tests run during the recorded benchmarks.
`previous_patch` is core PR #12249 at 1f07246ffe; `optimized_patch` is b290bb5f1b.
Native helpers come from each actual released version. No formatter batches add
queries, writes or attempted HTTP calls; allocated peaks match at the allocator's
coarse resolution. Bootstrap/locale loading and route latency are not measured.

WordPress 7.1.3 / en_US medians in milliseconds:

| ~40 KB fixture | Native | Previous patch | Optimized core | Feature plugin |
| --- | ---: | ---: | ---: | ---: |
| Plain prose | 0.097 | 0.097 | 0.098 | 0.099 |
| HTML | 2.708 | 4.310 | 3.252 | 3.254 |
| Quote-heavy HTML | 6.415 | 8.967 | 7.552 | 7.555 |

The added cost above native falls about 66% for HTML and 55% for quote-heavy
HTML. Absolute core costs still increase ~0.54 / 1.14 ms per call versus native.
WordPress 6.4 confirms the result: HTML 4.295 → 3.218 ms, quote-heavy 8.813 →
7.405 ms. The plugin measures essentially the same as optimized core. These are
function costs, not a route-budget pass or zero-cost claim. The namespace wrapper
uses fully qualified global calls to avoid losing PHP builtin fast paths.

[Machine-readable medians, p95, catalog mappings and hashes](verification-data-2026-10-19.json)
accompany this record. The lab harnesses, reference functions and core patch are
committed under [`tools/verification/`](../tools/verification/README.md); raw samples
are retained locally under `~/.codex/verification/typography-feature-2026-10-19/`.
The frozen corpus is committed in `tests/fixtures/inline-corpus.json` for CI.
No tagged release or production deployment was made.

This is not an entire-core-suite, browser/editor-typing, MySQL/Redis throughput,
linguistic or mixed-language inference certification. Core's direct function calls
remain outside the standalone plugin contract. Site switching requires explicit
WordPress locale entry/restoration; the engine refreshes automatically afterward.
