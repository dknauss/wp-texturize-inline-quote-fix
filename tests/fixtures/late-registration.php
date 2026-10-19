<?php
/** Load as a lab/CI MU plugin to exercise registrations during both boot hooks. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return; }
add_action(
	'init',
	static function () {
		remove_filter( 'the_post_thumbnail_caption', 'wptexturize', 10 );
		add_filter( 'the_post_thumbnail_caption', 'wptexturize', 7 );
	},
	50
);
add_action(
	'wp_loaded',
	static function () {
		remove_filter( 'the_post_thumbnail_caption', 'wptexturize', 7 );
		add_filter( 'the_post_thumbnail_caption', 'wptexturize', 17 );
	},
	50
);
