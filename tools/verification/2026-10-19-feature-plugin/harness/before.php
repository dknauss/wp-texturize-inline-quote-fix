<?php
function before_wptexturize( $text, $reset = false ) {
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
		$closing_quotes                  = null,
		$open_q_flag                     = '<!--oq-->',
		$open_sq_flag                    = '<!--osq-->',
		$apos_flag                       = '<!--apos-->';

	// If there's nothing to do, just stop.
	if ( empty( $text ) || false === $run_texturize ) {
		return $text;
	}

	// Set up static variables. Run once only.
	if ( $reset || ! isset( $static_characters ) ) {
		/**
		 * Filters whether to skip running before_wptexturize().
		 *
		 * Returning false from the filter will effectively short-circuit before_wptexturize()
		 * and return the original text passed to the function instead.
		 *
		 * The filter runs only once, the first time before_wptexturize() is called.
		 *
		 * @since 4.0.0
		 *
		 * @see before_wptexturize()
		 *
		 * @param bool $run_texturize Whether to short-circuit before_wptexturize().
		 */
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

		// Recognize configured closing glyphs in their emitted and literal forms.
		$closing_quotes = array( $apos, $closing_single_quote, $closing_quote );
		foreach ( $closing_quotes as $quote ) {
			$decoded          = html_entity_decode( $quote, ENT_QUOTES, 'UTF-8' );
			$closing_quotes[] = $decoded;
			$closing_quotes[] = preg_replace( '/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $decoded );
		}
		$closing_quotes = array_unique( array_filter( $closing_quotes ) );

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
	/**
	 * Filters the list of HTML elements not to texturize.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $default_no_texturize_tags An array of HTML element names.
	 */
	$no_texturize_tags = apply_filters( 'no_texturize_tags', $default_no_texturize_tags );
	/**
	 * Filters the list of shortcodes not to texturize.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $default_no_texturize_shortcodes An array of shortcode names.
	 */
	$no_texturize_shortcodes = apply_filters( 'no_texturize_shortcodes', $default_no_texturize_shortcodes );

	$no_texturize_tags_stack       = array();
	$no_texturize_shortcodes_stack = array();
	$last_text_context             = 0;
	$inline_quote_context          = 0;
	$single_quote_open             = false;

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
				$inline_quote_context = 0;
				$last_text_context    = 0;
				$single_quote_open    = false;
				continue;
			} else {
				// This is an HTML element delimiter.
				$inline_tag = ( $last_text_context || $single_quote_open ) ? before__wptexturize_get_inline_tag_type( $curl ) : 0;
				// Opening tags continue words; closing tags can also end quotations.
				$inline_quote_context = 2 === $inline_tag ? $last_text_context : ( 1 === $inline_tag && 1 === $last_text_context ? 1 : 0 );
				if ( 0 === $inline_tag ) {
					$last_text_context = 0;
					$single_quote_open = false;
				}

				// Replace each & with &#038; unless it already looks like an entity.
				$curl = preg_replace( '/&(?!#(?:\d+|x[a-f0-9]+);|[a-z1-4]{1,8};)/i', '&#038;', $curl );

				_wptexturize_pushpop_element( $curl, $no_texturize_tags_stack, $no_texturize_tags );
				if ( ! empty( $no_texturize_tags_stack ) ) {
					$inline_quote_context = 0;
					$last_text_context    = 0;
					$single_quote_open    = false;
				}
			}
		} elseif ( '' === trim( $curl ) ) {
			// This is a newline between delimiters. Performance improves when we check this.
			$inline_quote_context = 0;
			$last_text_context    = 0;
			continue;

		} elseif ( '[' === $first && $found_shortcodes && 1 === preg_match( '/^' . $shortcode_regex . '$/', $curl ) ) {
			$inline_quote_context = 0;
			$last_text_context    = 0;
			$single_quote_open    = false;
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

			$inline_apostrophe = false;
			if ( $inline_quote_context && ( "'" === $curl[0] || '"' === $curl[0] ) ) {
				if ( preg_match( "/^'[\p{L}\p{N}\p{Po}\p{Pf}\s.,;:!?\)\}\-&]|^'$/u", $curl ) ) {
					// A following letter/number continues a word even inside a quotation.
					if ( $single_quote_open && ! preg_match( "/^'[\p{L}\p{N}]/u", $curl ) ) {
						$curl              = $closing_single_quote . substr( $curl, 1 );
						$single_quote_open = false;
					} else {
						$curl              = $apos_flag . substr( $curl, 1 );
						$inline_apostrophe = true;
					}
				} elseif ( preg_match( '/^"[\p{L}\p{N}\p{Po}\p{Pf}\s.,;:!?\)\}\-&]|^"$/u', $curl ) ) {
					$curl = $closing_quote . substr( $curl, 1 );
				}
			}
			$inline_quote_context = 0;

			if ( $inline_apostrophe || str_contains( $curl, "'" ) ) {
				$curl = preg_replace( $dynamic_characters['apos'], $dynamic_replacements['apos'], $curl );
				$curl = wptexturize_primes( $curl, "'", $prime, $open_sq_flag, $closing_single_quote );
				// Inspect core's existing markers before restoring apostrophes, which
				// can share a glyph with closing quotes in the current locale.
				$last_open  = strrpos( $curl, $open_sq_flag );
				$last_close = strrpos( $curl, $closing_single_quote );
				if ( false !== $last_open && ( false === $last_close || $last_open > $last_close ) ) {
					$single_quote_open = true;
				} elseif ( false !== $last_close ) {
					$single_quote_open = false;
				}
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

			$last_text_context = before__wptexturize_get_text_context( $curl, $closing_quotes );
		} else {
			$inline_quote_context = 0;
			$last_text_context    = 0;
			$single_quote_open    = false;
		}
	}

	return implode( '', $textarr );
}


/**
 * Classifies the trailing text context used at inline HTML boundaries.
 *
 * @since 7.2.0
 * @access private
 *
 * @param string   $text           Formatted text token.
 * @param string[] $closing_quotes Localized closing quote/apostrophe representations.
 * @return int 1 for a word ending, 2 for closing punctuation/quotes, 0 otherwise.
 */
function before__wptexturize_get_text_context( $text, $closing_quotes ) {
	if ( '' === $text ) {
		return 0;
	}
	$last = substr( $text, -1 );
	if ( str_contains( " \t\n\r\f\v", $last ) ) {
		return 0;
	}
	if ( ord( $last ) < 0x80 && ctype_alnum( $last ) ) {
		return 1;
	}
	if ( in_array( $last, array( '.', '!', '?', ')' ), true ) ) {
		return 2;
	}
	if ( ord( $last ) >= 0x80 && preg_match( '/[\p{L}\p{N}]\p{M}*$/u', $text ) ) {
		return 1;
	}
	foreach ( $closing_quotes as $quote ) {
		if ( str_ends_with( $text, $quote ) ) {
			return 2;
		}
	}
	// Authored entities may use another spelling of the configured closing glyph.
	if ( ';' === $last && preg_match( '/&(?:\#(?:x[0-9a-f]+|[0-9]+)|[a-z][a-z0-9]+);$/i', $text, $entity )
		&& in_array( html_entity_decode( $entity[0], ENT_QUOTES, 'UTF-8' ), $closing_quotes, true ) ) {
		return 2;
	}
	return 0;
}

/**
 * Classifies an inline HTML delimiter without parsing its attribute values.
 *
 * @since 7.2.0
 * @access private
 *
 * @param string $text HTML delimiter token.
 * @return int 1 for an opening inline tag, 2 for a closing inline tag, 0 otherwise.
 */
function before__wptexturize_get_inline_tag_type( $text ) {
	if ( '<' !== substr( $text, 0, 1 ) || '>' !== substr( $text, -1 ) || str_ends_with( $text, '/>' ) ) {
		return 0;
	}
	$closing = isset( $text[1] ) && '/' === $text[1];
	$start   = $closing ? 2 : 1;
	$length  = strcspn( $text, " \t\n\r\f/>", $start );
	$name    = strtolower( substr( $text, $start, $length ) );
	if ( $closing && '' !== trim( substr( $text, $start + $length, -1 ) ) ) {
		return 0;
	}
	static $inline_tags = array(
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
	return in_array( $name, $inline_tags, true ) ? ( $closing ? 2 : 1 ) : 0;
}

