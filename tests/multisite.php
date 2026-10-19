<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit; }
/** Real network/site/locale restoration regression. Disposable sites only. */
if ( ! is_multisite() ) {
	throw new RuntimeException( 'Expected a network' ); }
$root = get_current_blog_id();
foreach ( array(
	'german' => array( 'de_DE', '&#8218;', '&#8216;' ),
	'french' => array( 'fr_FR', '&lsquo;', '&rsquo;' ),
) as $slug => $case ) {
	$existing = get_sites(
		array(
			'path'   => '/' . $slug . '/',
			'number' => 1,
		)
	);
	$id       = $existing ? (int) $existing[0]->blog_id : wpmu_create_blog( get_network()->domain, '/' . $slug . '/', $slug, 1, array( 'WPLANG' => $case[0] ), get_current_network_id() );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() ); }
	update_blog_option( $id, 'WPLANG', $case[0] );
	switch_to_blog( $id );
	$switched = switch_to_locale( get_option( 'WPLANG' ) );
	$expected = $case[1] . '<em>Hello</em>' . $case[2];
	if ( $expected !== apply_filters( 'the_title', "'<em>Hello</em>'" ) ) {
		throw new RuntimeException( 'Wrong site locale: ' . $case[0] ); }
	if ( $switched ) {
		restore_previous_locale(); }
	restore_current_blog();
	if ( $root !== get_current_blog_id() || 'en_US' !== get_locale() || '&#8216;<em>Hello</em>&#8217;' !== apply_filters( 'the_title', "'<em>Hello</em>'" ) ) {
		throw new RuntimeException( 'Site/locale restoration failed' ); }
}
echo "Multisite locale entry/restoration: pass\n";
