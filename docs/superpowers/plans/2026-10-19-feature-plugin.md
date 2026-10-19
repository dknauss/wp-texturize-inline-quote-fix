# Locale-aware inline quote feature plugin implementation plan

Goal: provide an opt-in standalone compatibility plugin, optimize the upstream core patch and close its locale-cache gap without changing Keel typography.

Architecture: maintain one generated, namespaced formatter derived from the reviewed core patch; preserve native tokenizer/prime helpers. Replace only native callbacks, in place. Support WordPress 6.4 through tested 7.1 releases and PHP 7.4; fail unchanged outside that range, with known competing engines or when core supplies the fix. Activation opts in; no settings, persistent state, assets, network traffic or source rewriting.

Spec: Keel docs/review/typography-compatibility-2026-10-17.md and maintainer authorization of 2026-10-19.

1. Add core regressions for cache refresh on locale entry/restoration without explicit reset; retain once-per-request run_wptexturize behavior. Run red, implement bounded last-locale invalidation and run green.
2. Profile the previous formatter against native released core. Defer trailing-text classification until a quote actually crosses an inline boundary; retain single-quote state and all established outputs. Run full formatting suites and compare the frozen corpus, then commit core changes.
3. Replace the unsafe regex bridge and old faithful demonstration with one version-gated feature plugin. Generate a namespaced engine reproducibly; preserve callback priorities/order/argument counts and plugin choice to remove or replace native callbacks. Provide a legacy filename loader only, retirement detection, conflict checks and standalone documentation.
4. Add real WordPress behavioral tests for locale switching, exclusions, filter ordering, later registrations, disabled core texturization, unsupported versions and retirement. Run these against the old bridge first where applicable.
5. Rerun nine core versions, six real locale catalogs, single-site/multisite; exercise blog/locale restores, REST/feed/shortcode behavior, and paired 5-warmup/30-sample benchmarks. Update verification and Keel planning docs only; publish reviewable PRs without tagging or deploying.

Review focus: quote-free HTML must avoid context overhead; generated shortcode output must not be texturized a second time; disabled formatting must remain disabled; locale refresh must not grow a per-locale cache; callback swaps must preserve third-party ordering and removals. Direct core calls are outside the plugin contract and inside the upstream patch contract.
