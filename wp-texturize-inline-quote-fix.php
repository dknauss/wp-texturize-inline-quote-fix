<?php
/**
 * Plugin Name:       WP Texturize Inline Quote Fix
 * Plugin URI:        https://github.com/dknauss/wp-texturize-inline-quote-fix
 * Description:       Stopgap for WordPress core Trac #18549 — corrects quotes and apostrophes that wptexturize() curls the wrong way immediately after a closing inline HTML tag. Remove once the core fix lands.
 * Version:           0.1.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Dan Knauss
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This is a temporary, rendered-content workaround — NOT the preferred fix. The
 * real fix belongs in WordPress core's wptexturize(). Track and remove when it lands:
 *   - Trac: https://core.trac.wordpress.org/ticket/18549
 *   - PR:   https://github.com/WordPress/wordpress-develop/pull/12249
 *
 * Drop this file into wp-content/mu-plugins/ (or activate it as a regular plugin).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fix #18549 rendered output after wptexturize() has run.
 *
 * Mirrors the bounded core patch (not the old 4.7-era replacement engine): it
 * flips an opening quote that sits immediately after a closing inline tag to a
 * closing quote/apostrophe when it is followed by punctuation (incl. Unicode
 * punctuation), whitespace, an entity such as an ellipsis, a closing HTML tag,
 * an alphanumeric continuation, or the end of the string.
 *
 * The trailing-context class intentionally excludes `]` to preserve WordPress
 * core's existing "crazy" shortcode texturize fixture.
 *
 * @param string $text Rendered text.
 * @return string Adjusted rendered text.
 */
function dk_texturize_inline_quote_fix( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}

	$inline_tags = 'a|abbr|b|bdi|bdo|cite|data|del|dfn|em|i|ins|label|mark|q|s|samp|small|span|strong|sub|sup|time|u|var';
	$after_quote = '(?=(?:<\/[a-z][a-z0-9]*\s*>|&#8230;|[\p{L}\p{N}\p{Po}\p{Pf}\s.,;:!?\)\}\-&]|$))';

	$text = preg_replace(
		'/<\/(?:' . $inline_tags . ')>\K&#8220;' . $after_quote . '/iu',
		'&#8221;',
		$text
	);

	return preg_replace(
		'/<\/(?:' . $inline_tags . ')>\K&#8216;' . $after_quote . '/iu',
		'&#8217;',
		$text
	);
}

$dk_texturize_inline_quote_filters = array(
	'comment_text',
	'term_description',
	'the_content',
	'the_excerpt',
	'the_excerpt_embed',
	'the_title',
	'widget_text',
);

foreach ( $dk_texturize_inline_quote_filters as $dk_texturize_inline_quote_filter ) {
	add_filter( $dk_texturize_inline_quote_filter, 'dk_texturize_inline_quote_fix', 12 );
}
unset( $dk_texturize_inline_quote_filter, $dk_texturize_inline_quote_filters );
