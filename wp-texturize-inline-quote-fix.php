<?php
/**
 * Plugin Name: WP Texturize Inline Quote Fix
 * Plugin URI: https://github.com/dknauss/wp-texturize-inline-quote-fix
 * Description: Feature plugin for locale-aware quotes and apostrophes across inline formatting. Tracks core Trac #18549 and #43810.
 * Version: 0.2.0-dev
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Dan Knauss
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/dknauss/wp-texturize-inline-quote-fix
 *
 * @package WP_Texturize_Inline_Quote_Fix
 */

namespace DK\Texturize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/bootstrap.php';
add_action( 'init', __NAMESPACE__ . '\\bootstrap', PHP_INT_MAX );
add_action( 'wp_loaded', __NAMESPACE__ . '\\bootstrap', PHP_INT_MAX );
