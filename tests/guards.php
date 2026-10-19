<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit; }
/** Run each mode in a fresh WP-CLI request: guards.php retired|conflict|disabled. */
$mode  = $args[0] ?? '';
$input = "I<strong>'ve</strong>";
if ( 'retired' === $mode ) {
	function _wptexturize_get_text_context() {}
	function _wptexturize_get_inline_tag_type() {}
	$expected_status = 'core-fixed';
} elseif ( 'conflict' === $mode ) {
	function dk_texturize_inline_quote_fix( $text ) {
		return $text; }
	$expected_status = 'conflict';
} elseif ( 'disabled' === $mode ) {
	add_filter( 'run_wptexturize', '__return_false' );
	if ( $input !== DK\Texturize\render( $input, true ) ) {
		throw new RuntimeException( 'Disabled formatter changed the input' );
	}
	remove_filter( 'run_wptexturize', '__return_false' );
	switch_to_locale( 'de_DE' );
	if ( $input !== DK\Texturize\render( $input ) ) {
		throw new RuntimeException( 'Locale change bypassed the request gate' );
	}
	restore_previous_locale();
	echo "Disabled request gate: pass\n";
	return;
} else {
	throw new RuntimeException( 'Unknown guard fixture' );
}
if ( $expected_status !== DK\Texturize\status() || wptexturize( $input ) !== DK\Texturize\render( $input ) ) {
	throw new RuntimeException( 'Guard did not stand down: ' . $mode );
}
remove_all_filters( 'the_title' );
add_filter( 'the_title', 'wptexturize' );
DK\Texturize\bootstrap();
if ( 'wptexturize' !== $GLOBALS['wp_filter']['the_title']->callbacks[10]['wptexturize']['function'] ) {
	throw new RuntimeException( 'Guard replaced core callbacks' );
}
echo "Guard {$mode}: pass\n";
