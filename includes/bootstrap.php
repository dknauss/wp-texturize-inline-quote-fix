<?php
/**
 * Version gates and ordered callback integration; no persistent state.
 *
 * @package WP_Texturize_Inline_Quote_Fix
 */

namespace DK\Texturize;

/**
 * Report whether this request can use the validated formatter.
 *
 * @return string active, core-fixed, unsupported or conflict.
 */
function status() {
	global $wp_version;
	if ( function_exists( '_wptexturize_get_text_context' ) && function_exists( '_wptexturize_get_inline_tag_type' ) ) {
		return 'core-fixed';
	}
	if ( ! preg_match( '/^(?:6\.[4-9]|7\.[01])(?:\.\d+)?$/D', $wp_version ) ) {
		return 'unsupported';
	}
	if ( function_exists( 'dk_wptexturize' ) || function_exists( 'dk_texturize_inline_quote_fix' ) || function_exists( 'dk_wptexturize_18549_mark_inline_apostrophes' ) ) {
		return 'conflict';
	}
	return 'active';
}

/**
 * Render once through the isolated engine. Direct core calls stay native.
 *
 * @param string $text Text from a native formatting callback.
 * @param bool   $reset Whether to explicitly reset replacement tables.
 * @return string Formatted text.
 */
function render( $text, $reset = false ) {
	if ( 'active' !== status() ) {
		return \wptexturize( $text, $reset );
	}
	require_once __DIR__ . '/formatter.php';
	return format( $text, $reset );
}

/**
 * Substitute native callbacks without changing keys, priorities or ordering.
 *
 * Keeping the native key also preserves remove_filter( $hook, 'wptexturize' ).
 * Call at the end of init and wp_loaded to include late plugin registrations.
 *
 * @return void
 */
function bootstrap() {
	global $wp_filter;
	if ( 'active' !== status() ) {
		return;
	}
	$hooks = array(
		'term_name',
		'link_name',
		'link_description',
		'link_notes',
		'bloginfo',
		'wp_title',
		'document_title',
		'widget_title',
		'single_post_title',
		'single_cat_title',
		'single_tag_title',
		'single_month_title',
		'nav_menu_attr_title',
		'nav_menu_description',
		'get_the_post_type_description',
		'the_post_thumbnail_caption',
		'list_cats',
		'comment_author',
		'the_title',
		'the_content',
		'the_excerpt',
		'the_excerpt_embed',
		'comment_text',
		'term_description',
		'widget_text',
		'widget_text_content',
	);
	foreach ( $hooks as $hook ) {
		if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof \WP_Hook ) {
			continue;
		}
		foreach ( $wp_filter[ $hook ]->callbacks as &$callbacks ) {
			foreach ( $callbacks as &$callback ) {
				if ( 'wptexturize' === $callback['function'] ) {
					$callback['function'] = __NAMESPACE__ . '\\render';
				}
			}
			unset( $callback );
		}
		unset( $callbacks );
	}
}
