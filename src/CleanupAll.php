<?php
/**
 * CleanupAll Class
 *
 * Cleans up all of the stupported output from WordPress to obscure the CMS.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\WP
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2020 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/Cleanup
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\CleanupAll' ) ) {
	/**
	 * CleanupAll Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class CleanupAll extends WordPressPluginsCleanup {

		/**
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		public function get_defaults() {

			return wp_parse_args( array(
				'rest'             => array(),
				'frontend_widgets' => array(),
			), parent::get_defaults() );

		}

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			parent::__construct( $args );
			CleanupAdmin::get_instance( $args );
			CleanupPublic::get_instance( $args );

		}

	}
}
