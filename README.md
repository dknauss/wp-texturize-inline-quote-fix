# WP Texturize Inline Quote Fix

A small stopgap plugin that corrects quotes and apostrophes that WordPress's
`wptexturize()` curls the **wrong way** when they sit immediately after a closing
inline HTML tag.

> **This is a workaround, not the fix.** The real fix belongs in WordPress core and
> is proposed upstream. Remove this plugin once that lands.
>
> - Trac: <https://core.trac.wordpress.org/ticket/18549>
> - PR: <https://github.com/WordPress/wordpress-develop/pull/12249>

## Two builds — pick one

This repo ships two plugins that fix the same bug differently. **Use one, not both.**

- **`wp-texturize-inline-quote-fix.php`** (default, lightweight) — *post-processes*
  already-texturized output, flipping the mis-curled quote after an inline tag. Tiny,
  drift-free, and rides on top of whatever core does. A couple of edge cases differ
  slightly from the core patch.
- **`wp-texturize-inline-quote-fix-faithful.php`** (demonstration / dogfood) —
  *replaces* `wptexturize()` with a forked copy carrying the comprehensive #18549
  patch, so output matches PR #12249 **byte-for-byte**, including those edge cases.
  It forks the whole function, so it tracks a specific WordPress version (currently
  **7.0**) — re-sync if core changes `wptexturize()`.

Most sites want the lightweight post-process build. The faithful build is for
dogfooding the exact patch on a live site, or demonstrating it for the ticket.

## What it fixes

`wptexturize()` loses word context across an inline tag boundary, so a straight
quote right after a closing inline tag is curled as an *opening* quote:

| Input | Without this plugin | With this plugin |
| --- | --- | --- |
| `<strong>He</strong>'s here.` | `…</strong>&#8216;s` (opening — wrong) | `…</strong>&#8217;s` (apostrophe) |
| `The word is '<a>quoted</a>'` | trailing `&#8216;` (opening — wrong) | trailing `&#8217;` (closing) |

A **space** before the quote keeps normal opening-quote behaviour
(`<strong>He</strong> 'go'` stays `&#8216;go&#8217;`), so quotations are untouched.

## Install

It is designed as a **must-use plugin**. Symlink or copy the file into
`wp-content/mu-plugins/`:

```bash
ln -s /path/to/wp-texturize-inline-quote-fix/wp-texturize-inline-quote-fix.php \
      wp-content/mu-plugins/wp-texturize-inline-quote-fix.php
```

It also works as a regular plugin — drop the folder in `wp-content/plugins/` and
activate it.

## How it works

It post-processes already-texturized output (`the_content`, `the_title`,
`the_excerpt`, `comment_text`, `term_description`, `widget_text`) at priority 12,
flipping an opening quote that immediately follows a closing **inline** tag to a
closing quote/apostrophe. It mirrors the bounded core patch rather than the old
4.7-era replacement engine.

## Scope and limits

- Only the inline elements `a, abbr, b, bdi, bdo, cite, data, del, dfn, em, i, ins,
  label, mark, q, s, samp, small, span, strong, sub, sup, time, u, var`.
- Excludes `kbd` (a core no-texturize tag) and deprecated `acronym`.
- Excludes `]` from the trailing-context match to preserve core's "crazy" shortcode
  texturize fixture.
- Out of scope: quote-inside-quote behaviour (Trac #29882).

Because it works on rendered output, it cannot fix content that never passes through
these filters. The core patch (above) is the complete, in-engine fix.

## The faithful build

`wp-texturize-inline-quote-fix-faithful.php` re-points every filter that core
registered `wptexturize` on to a forked `dk_wptexturize()` — WordPress 7.0's
`wptexturize()` with the comprehensive #18549 patch applied. It reuses core's
`_wptexturize_pushpop_element()` and `wptexturize_primes()`; only the function body
and the two new context helpers are forked.

Parity is verified against core on the same install: the #18549 fixtures produce the
corrected output, and a corpus of ordinary text (dashes, primes, quotes, shortcodes,
no-texturize tags) is **byte-identical** to core's `wptexturize()` — the fork changes
nothing but the inline-tag quote decision.

Because it forks the whole function it must track core: re-sync the body if core
changes `wptexturize()`, and remove the plugin once #18549 lands. A few direct
`wptexturize()` calls in core (not via filters) are unaffected.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
