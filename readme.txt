=== WP Texturize Inline Quote Fix ===
Contributors: dknauss
Tags: typography, formatting, quotes, apostrophes
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0-beta.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Feature plugin for locale-aware quotes and apostrophes across inline formatting. Experimental GitHub prerelease.

== Description ==

Preserves word and quote context across inline HTML on existing WordPress formatting filters. For example, I<strong>'ve</strong> receives an apostrophe instead of an opening quote.

Activation opts in. Stored content and editor typing are unchanged. Direct calls to core wptexturize() remain native. No settings, persistent storage, assets, or outbound requests are added.

Supports released WordPress 6.4 through 7.1 and PHP 7.4+. Other core versions and prereleases stand down. Also stands down when the upstream fix is present or a known older quote engine is loaded.

Uses the active WordPress locale. Mixed-language spans are not detected; blog switching alone does not switch locales. This is not a complete typography or language grammar engine. See README.md for exact hooks, language limits, performance evidence and retirement behavior.

== Installation ==

1. Upload the complete plugin ZIP in Plugins > Add New > Upload Plugin.
2. Activate WP Texturize Inline Quote Fix. Network activation is supported.
3. Deactivate to restore native rendering on subsequent requests.

Upgrade the whole directory, not an individual PHP file. Back up custom edits before replacing an older demonstration build. See CHANGELOG.md for migration details.

== Frequently Asked Questions ==

= Is this a stable release? =

No. This GitHub prerelease is for compatibility testing before core adoption.

= Does this change Keel? =

No. It is independent of Keel and does not change Keel's typography.

= Does it change stored content? =

No. It changes selected rendering callbacks. Direct core function calls and editor keystrokes are outside its scope.

== Changelog ==

= 0.2.0-beta.1 =
* Replace the English-only post-processor with one locale-aware formatter derived from core PR #12249.
* Reconcile opening and closing inline-tag context, Unicode word endings and HTML tag casing/whitespace.
* Refresh punctuation tables on locale entry and restoration.
* Preserve callback order, priority, arguments and native removal.
* Add conservative version, conflict and core-fix retirement gates.
* Reduce context-processing overhead compared with the earlier patch; native core remains faster on HTML-heavy inputs.

== Upgrade Notice ==

= 0.2.0-beta.1 =
Experimental replacement for the 0.1.0 demonstration builds. Install the whole directory and review migration notes before activation.
