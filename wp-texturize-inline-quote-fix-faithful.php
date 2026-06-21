<?php
/**
 * Plugin Name:       WP Texturize Inline Quote Fix — Faithful (forked wptexturize)
 * Plugin URI:        https://github.com/dknauss/wp-texturize-inline-quote-fix
 * Description:       Exact Trac #18549 fix. Replaces core wptexturize() with a forked copy carrying the comprehensive #18549 patch, so output matches WordPress/wordpress-develop PR #12249 byte-for-byte — including the edge cases the post-process plugin approximates. Demonstration / dogfood build, NOT the lightweight stopgap.
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      7.2
 * Author:            Dan Knauss
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Forked from WordPress 7.0 `wp-includes/formatting.php` `wptexturize()`, with the
 * comprehensive Trac #18549 patch applied. Because it forks the whole function it
 * tracks a specific core version — re-sync if core changes `wptexturize()`. This is
 * a temporary fix to remove once #18549 lands.
 *   - Trac:  https://core.trac.wordpress.org/ticket/18549
 *   - PR:    https://github.com/WordPress/wordpress-develop/pull/12249
 *   - Issue: see docs/repro in the dirtbag theme for the patch + parity tests.
 *
 * NOTE: use EITHER this faithful build OR the post-process plugin
 * (wp-texturize-inline-quote-fix.php) — not both. This one replaces texturize
 * wholesale; the other rides on top of core's output. Drop one file into
 * wp-content/mu-plugins/ (or activate it as a regular plugin).
 *
 * @package WP_Texturize_Inline_Quote_Fix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Re-point every filter that core registered `wptexturize` on to `dk_wptexturize`.
 *
 * Mirrors core's own registrations exactly (same tags, same priorities) instead of
 * guessing the list, so the forked, patched texturizer runs everywhere core's did.
 * A few direct `wptexturize()` calls in core (not via filters) are unaffected and
 * still use the unpatched original; those are rare and internal.
 */
if ( ! function_exists( 'dk_wptexturize_faithful_swap' ) ) {
	function dk_wptexturize_faithful_swap() {
		global $wp_filter;
		foreach ( $wp_filter as $tag => $hook ) {
			if ( ! is_object( $hook ) || empty( $hook->callbacks ) ) {
				continue;
			}
			foreach ( array_keys( $hook->callbacks ) as $priority ) {
				if ( isset( $hook->callbacks[ $priority ]['wptexturize'] ) ) {
					remove_filter( $tag, 'wptexturize', $priority );
					add_filter( $tag, 'dk_wptexturize', $priority );
				}
			}
		}
	}
}
add_action( 'init', 'dk_wptexturize_faithful_swap', 11 );

if ( ! function_exists( 'dk_wptexturize' ) ) {
	/**
	 * Forked WordPress 7.0 wptexturize() with the comprehensive Trac #18549 patch.
	 *
	 * Identical to core except that a straight quote immediately following a closing
	 * inline HTML tag, when the visible text before the tag ended with quote context,
	 * is texturized as a closing quote/apostrophe rather than an opening quote.
	 *
	 * @param string $text  The text to be formatted.
	 * @param bool   $reset Set to true to reset the static cache (testing).
	 * @return string The formatted text.
	 */
	function dk_wptexturize( $text, $reset = false ) {
		global $wp_cockneyreplace, $shortcode_tags;
		static $static_characters            = null,
			$static_replacements             = null,
			$dynamic_characters              = null,
			$dynamic_replacements            = null,
			$default_no_texturize_tags       = null,
			$default_no_texturize_shortcodes = null,
			$run_texturize                   = true,
			$apos                            = null,
			$prime                           = null,
			$double_prime                    = null,
			$opening_quote                   = null,
			$closing_quote                   = null,
			$opening_single_quote            = null,
			$closing_single_quote            = null,
			$open_q_flag                     = '<!--oq-->',
			$open_sq_flag                    = '<!--osq-->',
			$apos_flag                       = '<!--apos-->';

		// If there's nothing to do, just stop.
		if ( empty( $text ) || false === $run_texturize ) {
			return $text;
		}

		// Set up static variables. Run once only.
		if ( $reset || ! isset( $static_characters ) ) {
			/** This filter is documented in wp-includes/formatting.php */
			$run_texturize = apply_filters( 'run_wptexturize', $run_texturize );
			if ( false === $run_texturize ) {
				return $text;
			}

			/* translators: Opening curly double quote. */
			$opening_quote = _x( '&#8220;', 'opening curly double quote' );
			/* translators: Closing curly double quote. */
			$closing_quote = _x( '&#8221;', 'closing curly double quote' );

			/* translators: Apostrophe, for example in 'cause or can't. */
			$apos = _x( '&#8217;', 'apostrophe' );

			/* translators: Prime, for example in 9' (nine feet). */
			$prime = _x( '&#8242;', 'prime' );
			/* translators: Double prime, for example in 9" (nine inches). */
			$double_prime = _x( '&#8243;', 'double prime' );

			/* translators: Opening curly single quote. */
			$opening_single_quote = _x( '&#8216;', 'opening curly single quote' );
			/* translators: Closing curly single quote. */
			$closing_single_quote = _x( '&#8217;', 'closing curly single quote' );

			/* translators: En dash. */
			$en_dash = _x( '&#8211;', 'en dash' );
			/* translators: Em dash. */
			$em_dash = _x( '&#8212;', 'em dash' );

			$default_no_texturize_tags       = array( 'pre', 'code', 'kbd', 'style', 'script', 'tt' );
			$default_no_texturize_shortcodes = array( 'code' );

			// If a plugin has provided an autocorrect array, use it.
			if ( isset( $wp_cockneyreplace ) ) {
				$cockney        = array_keys( $wp_cockneyreplace );
				$cockneyreplace = array_values( $wp_cockneyreplace );
			} else {
				/*
				 * translators: This is a comma-separated list of words that defy the syntax of quotations in normal use,
				 * for example... 'We do not have enough words yet'... is a typical quoted phrase. But when we write
				 * lines of code 'til we have enough of 'em, then we need to insert apostrophes instead of quotes.
				 */
				$cockney = explode(
					',',
					_x(
						"'tain't,'twere,'twas,'tis,'twill,'til,'bout,'nuff,'round,'cause,'em",
						'Comma-separated list of words to texturize in your language'
					)
				);

				$cockneyreplace = explode(
					',',
					_x(
						'&#8217;tain&#8217;t,&#8217;twere,&#8217;twas,&#8217;tis,&#8217;twill,&#8217;til,&#8217;bout,&#8217;nuff,&#8217;round,&#8217;cause,&#8217;em',
						'Comma-separated list of replacement words in your language'
					)
				);
			}

			$static_characters   = array_merge( array( '...', '``', '\'\'', ' (tm)' ), $cockney );
			$static_replacements = array_merge( array( '&#8230;', $opening_quote, $closing_quote, ' &#8482;' ), $cockneyreplace );

			/*
			 * Pattern-based replacements of characters.
			 * Sort the remaining patterns into several arrays for performance tuning.
			 */
			$dynamic_characters   = array(
				'apos'  => array(),
				'quote' => array(),
				'dash'  => array(),
			);
			$dynamic_replacements = array(
				'apos'  => array(),
				'quote' => array(),
				'dash'  => array(),
			);
			$dynamic              = array();
			$spaces               = wp_spaces_regexp();

			// '99' and '99" are ambiguous among other patterns; assume it's an abbreviated year at the end of a quotation.
			if ( "'" !== $apos || "'" !== $closing_single_quote ) {
				$dynamic[ '/\'(\d\d)\'(?=\Z|[.,:;!?)}\-\]]|&gt;|' . $spaces . ')/' ] = $apos_flag . '$1' . $closing_single_quote;
			}
			if ( "'" !== $apos || '"' !== $closing_quote ) {
				$dynamic[ '/\'(\d\d)"(?=\Z|[.,:;!?)}\-\]]|&gt;|' . $spaces . ')/' ] = $apos_flag . '$1' . $closing_quote;
			}

			// '99 '99s '99's (apostrophe)  But never '9 or '99% or '999 or '99.0.
			if ( "'" !== $apos ) {
				$dynamic['/\'(?=\d\d(?:\Z|(?![%\d]|[.,]\d)))/'] = $apos_flag;
			}

			// Quoted numbers like '0.42'.
			if ( "'" !== $opening_single_quote && "'" !== $closing_single_quote ) {
				$dynamic[ '/(?<=\A|' . $spaces . ')\'(\d[.,\d]*)\'/' ] = $open_sq_flag . '$1' . $closing_single_quote;
			}

			// Single quote at start, or preceded by (, {, <, [, ", -, or spaces.
			if ( "'" !== $opening_single_quote ) {
				$dynamic[ '/(?<=\A|[([{"\-]|&lt;|' . $spaces . ')\'/' ] = $open_sq_flag;
			}

			// Apostrophe in a word. No spaces, double apostrophes, or other punctuation.
			if ( "'" !== $apos ) {
				$dynamic[ '/(?<!' . $spaces . ')\'(?!\Z|[.,:;!?"\'(){}[\]\-]|&[lg]t;|' . $spaces . ')/' ] = $apos_flag;
			}

			$dynamic_characters['apos']   = array_keys( $dynamic );
			$dynamic_replacements['apos'] = array_values( $dynamic );
			$dynamic                      = array();

			// Quoted numbers like "42".
			if ( '"' !== $opening_quote && '"' !== $closing_quote ) {
				$dynamic[ '/(?<=\A|' . $spaces . ')"(\d[.,\d]*)"/' ] = $open_q_flag . '$1' . $closing_quote;
			}

			// Double quote at start, or preceded by (, {, <, [, -, or spaces, and not followed by spaces.
			if ( '"' !== $opening_quote ) {
				$dynamic[ '/(?<=\A|[([{\-]|&lt;|' . $spaces . ')"(?!' . $spaces . ')/' ] = $open_q_flag;
			}

			$dynamic_characters['quote']   = array_keys( $dynamic );
			$dynamic_replacements['quote'] = array_values( $dynamic );
			$dynamic                       = array();

			// Dashes and spaces.
			$dynamic['/---/'] = $em_dash;
			$dynamic[ '/(?<=^|' . $spaces . ')--(?=$|' . $spaces . ')/' ] = $em_dash;
			$dynamic['/(?<!xn)--/']                                       = $en_dash;
			$dynamic[ '/(?<=^|' . $spaces . ')-(?=$|' . $spaces . ')/' ]  = $en_dash;

			$dynamic_characters['dash']   = array_keys( $dynamic );
			$dynamic_replacements['dash'] = array_values( $dynamic );
		}

		// Must do this every time in case plugins use these filters in a context sensitive manner.
		/** This filter is documented in wp-includes/formatting.php */
		$no_texturize_tags = apply_filters( 'no_texturize_tags', $default_no_texturize_tags );
		/** This filter is documented in wp-includes/formatting.php */
		$no_texturize_shortcodes = apply_filters( 'no_texturize_shortcodes', $default_no_texturize_shortcodes );

		$no_texturize_tags_stack           = array();
		$no_texturize_shortcodes_stack     = array();
		$last_text_ends_with_quote_context = false;
		$quote_after_inline_tag            = false;

		// Look for shortcodes and HTML elements.

		preg_match_all( '@\[/?([^<>&/\[\]\x00-\x20=]++)@', $text, $matches );
		$tagnames         = array_intersect( array_keys( $shortcode_tags ), $matches[1] );
		$found_shortcodes = ! empty( $tagnames );
		$shortcode_regex  = $found_shortcodes ? _get_wptexturize_shortcode_regex( $tagnames ) : '';
		$regex            = _get_wptexturize_split_regex( $shortcode_regex );

		$textarr = preg_split( $regex, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		foreach ( $textarr as &$curl ) {
			// Only call _wptexturize_pushpop_element if $curl is a delimiter.
			$first = $curl[0];
			if ( '<' === $first ) {
				if ( str_starts_with( $curl, '<!--' ) ) {
					// This is an HTML comment delimiter.
					$quote_after_inline_tag = false;
					continue;
				} else {
					// This is an HTML element delimiter.
					$quote_after_inline_tag = $last_text_ends_with_quote_context && dk_wptexturize_is_inline_closing_tag( $curl );

					// Replace each & with &#038; unless it already looks like an entity.
					$curl = preg_replace( '/&(?!#(?:\d+|x[a-f0-9]+);|[a-z1-4]{1,8};)/i', '&#038;', $curl );

					_wptexturize_pushpop_element( $curl, $no_texturize_tags_stack, $no_texturize_tags );
				}
			} elseif ( '' === trim( $curl ) ) {
				// This is a newline between delimiters. Performance improves when we check this.
				$quote_after_inline_tag = false;
				continue;

			} elseif ( '[' === $first && $found_shortcodes && 1 === preg_match( '/^' . $shortcode_regex . '$/', $curl ) ) {
				$quote_after_inline_tag = false;
				// This is a shortcode delimiter.

				if ( ! str_starts_with( $curl, '[[' ) && ! str_ends_with( $curl, ']]' ) ) {
					// Looks like a normal shortcode.
					_wptexturize_pushpop_element( $curl, $no_texturize_shortcodes_stack, $no_texturize_shortcodes );
				} else {
					// Looks like an escaped shortcode.
					continue;
				}
			} elseif ( empty( $no_texturize_shortcodes_stack ) && empty( $no_texturize_tags_stack ) ) {
				// This is neither a delimiter, nor is this content inside of no_texturize pairs. Do texturize.

				$curl = str_replace( $static_characters, $static_replacements, $curl );

				if ( $quote_after_inline_tag ) {
					if ( preg_match( "/^'[\p{L}\p{N}\p{Po}\p{Pf}\s.,;:!?\)\}\-&]|^'$/u", $curl ) ) {
						$curl = $apos . substr( $curl, 1 );
					} elseif ( preg_match( '/^"[\p{L}\p{N}\p{Po}\p{Pf}\s.,;:!?\)\}\-&]|^"$/u', $curl ) ) {
						$curl = $closing_quote . substr( $curl, 1 );
					}
				}
				$quote_after_inline_tag = false;

				if ( str_contains( $curl, "'" ) ) {
					$curl = preg_replace( $dynamic_characters['apos'], $dynamic_replacements['apos'], $curl );
					$curl = wptexturize_primes( $curl, "'", $prime, $open_sq_flag, $closing_single_quote );
					$curl = str_replace( $apos_flag, $apos, $curl );
					$curl = str_replace( $open_sq_flag, $opening_single_quote, $curl );
				}
				if ( str_contains( $curl, '"' ) ) {
					$curl = preg_replace( $dynamic_characters['quote'], $dynamic_replacements['quote'], $curl );
					$curl = wptexturize_primes( $curl, '"', $double_prime, $open_q_flag, $closing_quote );
					$curl = str_replace( $open_q_flag, $opening_quote, $curl );
				}
				if ( str_contains( $curl, '-' ) ) {
					$curl = preg_replace( $dynamic_characters['dash'], $dynamic_replacements['dash'], $curl );
				}

				// 9x9 (times), but never 0x9999.
				if ( 1 === preg_match( '/(?<=\d)x\d/', $curl ) ) {
					// Searching for a digit is 10 times more expensive than for the x, so we avoid doing this one!
					$curl = preg_replace( '/\b(\d(?(?<=0)[\d\.,]+|[\d\.,]*))x(\d[\d\.,]*)\b/', '$1&#215;$2', $curl );
				}

				// Replace each & with &#038; unless it already looks like an entity.
				$curl = preg_replace( '/&(?!#(?:\d+|x[a-f0-9]+);|[a-z1-4]{1,8};)/i', '&#038;', $curl );

				$last_text_ends_with_quote_context = dk_wptexturize_text_ends_with_quote_context( $curl );
			} else {
				$quote_after_inline_tag = false;
			}
		}

		return implode( '', $textarr );
	}
}

if ( ! function_exists( 'dk_wptexturize_text_ends_with_quote_context' ) ) {
	/**
	 * Determines whether text ends with a character that can provide quote context.
	 *
	 * Avoids running a Unicode regular expression for every text token. ASCII
	 * letters/numbers and `. ! ? )` use a direct check; closing quote entities use a
	 * narrow regex; only multibyte endings fall back to the Unicode regex.
	 *
	 * @param string $text Text token from dk_wptexturize().
	 * @return bool Whether the text ends with quote context.
	 */
	function dk_wptexturize_text_ends_with_quote_context( $text ) {
		if ( '' === $text ) {
			return false;
		}

		$last_character = substr( $text, -1 );

		if ( ctype_alnum( $last_character ) || in_array( $last_character, array( '.', '!', '?', ')' ), true ) ) {
			return true;
		}

		if ( ';' === $last_character ) {
			return (bool) preg_match( '/&#(?:8217|8221);$/', $text );
		}

		if ( ord( $last_character ) >= 0x80 ) {
			return (bool) preg_match( '/[\p{L}\p{N}]$/u', $text );
		}

		return false;
	}
}

if ( ! function_exists( 'dk_wptexturize_is_inline_closing_tag' ) ) {
	/**
	 * Determines whether a token is a closing tag for a common inline HTML element.
	 *
	 * @param string $text A token from dk_wptexturize()'s split input.
	 * @return bool Whether the token is a closing inline HTML element.
	 */
	function dk_wptexturize_is_inline_closing_tag( $text ) {
		if ( ! preg_match( '/^<\/([a-z][a-z0-9]*)\s*>$/i', $text, $matches ) ) {
			return false;
		}

		$inline_tags = array(
			'a',
			'abbr',
			'b',
			'bdi',
			'bdo',
			'cite',
			'data',
			'del',
			'dfn',
			'em',
			'i',
			'ins',
			'label',
			'mark',
			'q',
			's',
			'samp',
			'small',
			'span',
			'strong',
			'sub',
			'sup',
			'time',
			'u',
			'var',
		);

		return in_array( strtolower( $matches[1] ), $inline_tags, true );
	}
}
