<?php
/**
 * Plugin Name: WP Texturize Inline Quote Fix — Legacy Loader
 * Plugin URI: https://github.com/dknauss/wp-texturize-inline-quote-fix
 * Description: Compatibility loader for the old faithful build. Uses the same engine as the main plugin; activate the main entry instead for new installations.
 * Version: 0.2.0-beta.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Dan Knauss
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/dknauss/wp-texturize-inline-quote-fix
 *
 * @package WP_Texturize_Inline_Quote_Fix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/wp-texturize-inline-quote-fix.php';
