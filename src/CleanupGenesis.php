<?php
/**
 * CleanupAdmin Abstract Class
 *
 * Cleans up some of the output from WordPress to obscure the CMS.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\Core
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

if ( ! class_exists( __NAMESPACE__ . '\CleanupAdmin' ) ) {
	/**
	 * CleanupAdmin Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class CleanupAdmin extends Cleanup {

		/**
		 * Supported menu files that can be removed.
		 *
		 * @var array
		 */
		protected $_menu = array(
			'edit.php', // Posts.
			'upload.php', // Media.
			'edit-comments.php', // Comments.
			'edit.php?post_type=page', // Pages.
			'plugins.php', // Plugins.
			'themes.php', // Appearance.
			'users.php', // Users.
			'tools.php', // Tools.
			'options-general.php', // Settings.
			'admin.php?page=genesis', // Genesis
		);

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			if ( ! is_admin() ) {
				return;
			}

			// Ensure we have the proper setup.
			$args = wp_parse_args( $args, $this->get_defaults() );

			parent::__construct( $args );

			if ( 'all' === $args ) {

				$this->remove_all();

			} else {

				$this->menu      = 'all' === $args['menu'] ? $this->_menu : $args['menu'];
				$this->dashboard = 'all' === $args['dashboard'] ? $this->_dashboard_widgets : $args['dashboard'];

			}

		}

		/** PRIVATE API */

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {

			parent::plugins_loaded();

			// Remove Edit link
			add_filter( 'genesis_edit_post_link', '__return_false' );

			// Remove Genesis Favicon (use site icon instead)
			remove_action( 'wp_head', 'genesis_load_favicon' );

			// Remove Header Description
			remove_action( 'genesis_site_description', 'genesis_seo_site_description' );

			// Remove Genesis Templates
			add_filter( 'theme_page_templates', 'ea_remove_genesis_templates' );

		}

		/**
		 * Remove Genesis Templates
		 *
		 */
		function ea_remove_genesis_templates( $page_templates ) {
			unset( $page_templates['page_archive.php'] );
			unset( $page_templates['page_blog.php'] );

			return $page_templates;
		}

	}
}
