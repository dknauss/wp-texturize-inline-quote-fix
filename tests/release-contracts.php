<?php
/** Real ZIP installation/upgrade assertions; invoke in a disposable WP-CLI lab. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$root = 'wp-texturize-inline-quote-fix/';
foreach ( array( 'wp-texturize-inline-quote-fix.php', 'wp-texturize-inline-quote-fix-faithful.php' ) as $file ) {
    $result = validate_plugin( $root . $file );
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
    $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $root . $file, false, false );
    if ( '0.2.0-beta.1' !== $data['Version'] ) { throw new RuntimeException( 'Wrong installed version' ); }
}
$input = "I<strong>'ve</strong>";
$expected = 'I<strong>&#8217;ve</strong>';
if ( 'active' !== DK\Texturize\status() || $expected !== apply_filters( 'the_title', $input ) ) {
    throw new RuntimeException( 'Installed ZIP not rendering' );
}
$count = 0;
foreach ( $GLOBALS['wp_filter']['the_title']->callbacks as $callbacks ) {
    foreach ( $callbacks as $callback ) {
        if ( 'DK\\Texturize\\render' === $callback['function'] ) { ++$count; }
    }
}
if ( 1 !== $count ) { throw new RuntimeException( 'Expected exactly one formatter callback' ); }
echo "ZIP metadata, both valid entry paths, output and single callback: pass\n";
