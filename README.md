# WP Texturize Inline Quote Fix

An opt-in **feature plugin** for the core quote-context work in
[Trac #18549](https://core.trac.wordpress.org/ticket/18549),
[#43810](https://core.trac.wordpress.org/ticket/43810) and
[core PR #12249](https://github.com/WordPress/wordpress-develop/pull/12249).
Prerelease candidate: **0.2.0-beta.1**. For testing; not a stable release.

## What changes

Contractions such as `<strong>He</strong>'s` and `I<strong>'ve</strong>` retain
word context across inline markup. Quotes around inline elements close using the
active WordPress locale's glyphs. Unicode letters and combining marks participate
in word context. Locale entry and restoration refresh one bounded set of tables,
without requiring explicit resets or rerunning the request-level opt-out filter.

The previous English-only post-processor is removed. It could alter authored
entities and code examples and did not match the core patch. The old faithful
filename remains a labelled compatibility loader and loads the main plugin once;
it no longer defines a competing engine.

## Install and scope

Install the **whole directory** in `wp-content/plugins/` and activate the main
plugin. Network activation is supported. To use it as an MU plugin, keep the
whole directory under `mu-plugins/` and add a top-level loader requiring its main
file. Copying or symlinking just the main file is no longer sufficient.

Activation is the opt-in. No settings, database storage, cron jobs, frontend
assets or outbound calls are added. Keel is independent and is not modified.
Deactivate all active entries (including the legacy loader, if enabled) to restore
native callback behavior on subsequent requests.

The plugin substitutes native `wptexturize` callbacks on the documented core
hooks at the end of `init` and `wp_loaded`. It preserves their keys, priorities,
argument counts and order. Removing a native callback still removes it; replacing
one with another formatter is respected. Registrations made after `wp_loaded`
are outside this automatic integration; callers may invoke
`DK\Texturize\bootstrap()` after their registrations. It does not add a global
`all` hook or format content a second time after shortcodes.

Covered hooks: `term_name`, `link_name`, `link_description`, `link_notes`,
`bloginfo`, `wp_title`, `document_title`, `widget_title`, `single_post_title`,
`single_cat_title`, `single_tag_title`, `single_month_title`, `nav_menu_attr_title`,
`nav_menu_description`, `get_the_post_type_description`, `the_post_thumbnail_caption`,
`list_cats`, `comment_author`, `the_title`, `the_content`, `the_excerpt`,
`the_excerpt_embed`, `comment_text`, `term_description`, `widget_text`,
`widget_text_content`. Feeds and REST inherit corrections through these paths.

**Direct calls to core `wptexturize()` stay native.** Only the upstream core patch
can fix every direct caller. Stored content and editor typing are unchanged.

## Compatibility, languages and retirement

PHP **7.4+**; released WordPress **6.4–7.1**. Other versions and prereleases fail
unchanged. The plugin also stands down when core defines both inline-context
helpers from the upstream correction, or a known older quote engine is present.
`DK\Texturize\status()` reports `active`, `unsupported`, `core-fixed` or `conflict`.
These gates are deliberately conservative; revalidate when core changes.

Glyphs come from WordPress's active locale, not a language detector. Mixed-language
spans and `lang` attributes do not switch conventions automatically. A blog switch
alone does not change the loaded locale: multilingual renderers must explicitly
enter/restore their locale using WordPress APIs. The formatter then refreshes on
its next call. Same-locale translation filters changed mid-request still need an
explicit reset, just as custom pattern changes do.

English, German, French, Russian, Arabic and Japanese catalogs are exercised.
That is compatibility evidence, not native-speaker review or complete linguistic
support. Arabic/Japanese catalogs retain core curly-quote mappings. Nested-quote
grammar, CJK punctuation conversion, bidirectional layout, feet/inches, numeric
grouping, MathML/parser changes and disabling all punctuation are out of scope.
Existing code/pre/kbd and shortcode exclusions remain in force.

## Source and verification

`includes/formatter.php` is generated from the reviewed core patch and reuses
native tokenizer/prime helpers. Its header records source revision and SHA-256.
Regenerate with `python3 tools/sync-core.py /path/to/formatting.php --revision HASH`.
There are no production dependencies. Qualifying global calls preserves PHP's
builtin fast paths in the plugin namespace.

Run `composer install`, `composer lint`, and `composer lint:compat`. On a disposable
WordPress with the plugin active and the six catalogs installed, run the real
WordPress integration script and each guard in a fresh WP-CLI request. CI covers
single-site/multisite at the 7.4 floor and PHP 8.4. See the verification record for
full corpus, locale switching and paired performance measurements.

GPL-2.0-or-later. WordPress-derived formatter copyright WordPress contributors;
opening-tag reconciliation credits irozum's core PR #12645. See [LICENSE](LICENSE).

## Prerelease packaging

Run `python3 tests/package-contracts.py` (or `python3 tools/build-zip.py`) to create
`dist/wp-texturize-inline-quote-fix-0.2.0-beta.1.zip` and its SHA-256 sidecar.
The deterministic package includes nine runtime/documentation/license files and
Composer metadata, with no installed dependencies, tests, CI or development tools.
See [release notes](CHANGELOG.md) and the prerelease verification record in `docs/`.
The legacy loader retains its own plugin header to preserve old active paths;
both entries share one engine. New installations should activate the main entry.

GitHub's Update URI intentionally prevents unrelated WordPress.org updates.
Plugin Check's WordPress.org-only Update URI/name/slug findings are retained in the
verification record; this is not a WordPress.org submission package.
