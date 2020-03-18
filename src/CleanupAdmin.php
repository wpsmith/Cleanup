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
		 * Supported dashboard widgets that can be removed.
		 *
		 * @var array
		 */
		protected $_dashboard_widgets = array(
			'dashboard_activity', // Activity.
			'dashboard_right_now', // Right Now.
			'dashboard_recent_comments', // Recent Comments.
			'dashboard_incoming_links', // Incoming Links.
			'dashboard_plugins', // Plugins.
			'dashboard_quick_press', // Quick Press.
			'dashboard_recent_drafts', // Recent Drafts.
			'dashboard_primary', // WordPress Blog.
			'dashboard_secondary', // Other WordPress News.

			'rg_forms_dashboard', // Gravity Forms.
		);

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
		);

		/**
		 * Array of dashboard widgets to remove.
		 *
		 * @var array
		 */
		public $dashboard;

		/**
		 * Array of admin menu items to remove.
		 *
		 * @var array
		 */
		public $menu;

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

		/**
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		protected function get_defaults() {
			return wp_parse_args( array(
				'dashboard' => array(),
				'menu'      => array(),
			), parent::get_defaults() );
		}

		/** PUBLIC API */

		/**
		 * Removes dashbaord widgets.
		 *
		 * @param array $items
		 */
		public function remove_dashboard( $items ) {

			$this->dashboard = $items;

		}

		/**
		 * Removes menu items.
		 *
		 * @param array $items
		 */
		public function remove_menu( $items ) {

			$this->menu = $items;

		}

		/**
		 * Removes all the items.
		 */
		public function remove_all() {

			parent::remove_all();
			$this->menu      = $this->_menu;
			$this->dashboard = $this->_dashboard_widgets;

		}

		/**
		 * Remove extra dashboard widgets
		 */
		public function remove_dashboard_widgets() {

			foreach ( $this->dashboard as $widget ) {
				if ( in_array( $widget, $this->_dashboard_widgets, true ) ) {
					remove_meta_box( $widget, 'dashboard', 'core' );
				}
			}

		}

		/**
		 * Remove admin menu items
		 */
		public function remove_admin_menus() {

			foreach ( $this->menu as $menu ) {
				if ( in_array( $menu, $this->_menu, true ) ) {
					remove_menu_page( $menu );
				}
			}

		}

		/** PRIVATE API */

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {

			parent::plugins_loaded();

			// Dashboard.
			if ( ! empty( $this->dashboard ) ) {
				add_action( 'admin_menu', array( $this, 'remove_dashboard_widgets' ), PHP_INT_MAX ); // 11
			}

			// Admin Menu Items.
			if ( ! empty( $this->menu ) ) {
				add_action( 'admin_menu', array( $this, 'remove_admin_menus' ), PHP_INT_MAX ); // 11
			}

		}

	}
}
