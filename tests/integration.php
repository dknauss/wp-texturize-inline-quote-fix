<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit; }
/** Real-WordPress regressions. Run via WP-CLI after activating the feature plugin. */
$assertions = 0;
$check      = static function ( $expected, $actual, $label ) use ( &$assertions ) {
	++$assertions;
	if ( $expected !== $actual ) {
		throw new RuntimeException( $label . ': expected ' . var_export( $expected, true ) . '; got ' . var_export( $actual, true ) );
	}
};
$check( 'active', DK\Texturize\status(), 'Supported request' );
if ( file_exists( WPMU_PLUGIN_DIR . '/late-registration.php' ) ) {
	$check( 'I<strong>&#8217;ve</strong>', apply_filters( 'the_post_thumbnail_caption', "I<strong>'ve</strong>" ), 'Late lifecycle registration' );
}
require_once WP_PLUGIN_DIR . '/wp-texturize-inline-quote-fix/wp-texturize-inline-quote-fix-faithful.php';
$check( 'I<strong>&#8217;ve</strong>', apply_filters( 'the_title', "I<strong>'ve</strong>" ), 'Opening apostrophe' );
$check( '<code><em>x</em>&#8216;s</code>', apply_filters( 'the_title', '<code><em>x</em>&#8216;s</code>' ), 'Opaque entity' );
$check( '<em>x</em>&#8216;Hello&#8217;', apply_filters( 'the_title', '<em>x</em>&#8216;Hello&#8217;' ), 'Authored entity' );
$locale_cases = array(
	'en_US' => array( '&#8216;', '&#8217;' ),
	'de_DE' => array( '&#8218;', '&#8216;' ),
	'fr_FR' => array( '&lsquo;', '&rsquo;' ),
	'ru_RU' => array( '&#8216;', '&#8217;' ),
	'ar'    => array( '&#8216;', '&#8217;' ),
	'ja'    => array( '&#8216;', '&#8217;' ),
);
foreach ( $locale_cases as $locale => $quotes ) {
	$switched = switch_to_locale( $locale );
	$check( $locale, get_locale(), 'Loaded catalog' );
	$check( $quotes[0] . '<em>Hello</em>' . $quotes[1], apply_filters( 'the_title', "'<em>Hello</em>'" ), 'Locale without reset' );
	if ( $switched ) {
		restore_previous_locale();
	}
	$check( '&#8216;<em>Hello</em>&#8217;', apply_filters( 'the_title', "'<em>Hello</em>'" ), 'Locale restored without reset' );
}
// Native callback keys remain removable after substitution; other callbacks stay ordered.
$saved = clone $GLOBALS['wp_filter']['the_title'];
remove_all_filters( 'the_title' );
$before = static function ( $value ) {
	return 'BEFORE ' . $value;
};
$after  = static function ( $value ) {
	return $value . ' AFTER';
};
add_filter( 'the_title', $before, 10 );
add_filter( 'the_title', 'wptexturize', 10, 2 );
add_filter( 'the_title', $after, 10 );
DK\Texturize\bootstrap();
$check( 'BEFORE I<strong>&#8217;ve</strong> AFTER', apply_filters( 'the_title', "I<strong>'ve</strong>" ), 'Priority and order' );
$check( 2, $GLOBALS['wp_filter']['the_title']->callbacks[10]['wptexturize']['accepted_args'], 'Accepted arguments' );
remove_filter( 'the_title', 'wptexturize', 10 );
$check( "BEFORE I<strong>'ve</strong> AFTER", apply_filters( 'the_title', "I<strong>'ve</strong>" ), 'Native removal still works' );
$foreign = static function ( $value ) {
	return 'FOREIGN ' . $value;
};
add_filter( 'the_title', $foreign );
DK\Texturize\bootstrap();
$check( "FOREIGN BEFORE I<strong>'ve</strong> AFTER", apply_filters( 'the_title', "I<strong>'ve</strong>" ), 'Other formatter unchanged' );
$GLOBALS['wp_filter']['the_title'] = $saved;
// Future/older core versions fail unchanged, even if the request was bootstrapped earlier.
$version = $GLOBALS['wp_version'];
foreach ( array( '6.3.13', '7.2-alpha', '8.0' ) as $unsupported ) {
	$GLOBALS['wp_version'] = $unsupported;
	$check( 'unsupported', DK\Texturize\status(), 'Unsupported version' );
	$input = "I<strong>'ve</strong>";
	$check( wptexturize( $input ), DK\Texturize\render( $input ), 'Unsupported passthrough' );
}
$GLOBALS['wp_version'] = $version;
// do_shortcode runs after the formatter; generated entities must not be reprocessed.
add_shortcode(
	'labquote',
	static function () {
		return '<em>x</em>&#8216;s';
	}
);
$check( '<em>x</em>&#8216;s', trim( apply_filters( 'the_content', '[labquote]' ) ), 'Generated shortcode output' );
remove_shortcode( 'labquote' );
echo "Feature integration: {$assertions} assertions passed\n";
