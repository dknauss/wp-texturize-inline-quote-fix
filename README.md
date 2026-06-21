# WP Texturize Inline Quote Fix

A small stopgap plugin that corrects quotes and apostrophes that WordPress's
`wptexturize()` curls the **wrong way** when they sit immediately after a closing
inline HTML tag.

> **This is a workaround, not the fix.** The real fix belongs in WordPress core and
> is proposed upstream. Remove this plugin once that lands.
>
> - Trac: <https://core.trac.wordpress.org/ticket/18549>
> - PR: <https://github.com/WordPress/wordpress-develop/pull/12249>

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

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
