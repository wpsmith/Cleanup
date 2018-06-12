<?php
/**
 * Cleanup_Admin Abstract Class
 *
 * Cleans up some of the output from WordPress to obscure the CMS.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\Core
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Core\Cleanup_Admin' ) ) {
	/**
	 * Cleanup_Admin Abstract Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\Core
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class Cleanup_Admin extends Cleanup {

		/**
		 * Supported WP Widget classes that can be removed.
		 *
		 * @var array
		 */
		protected $_wp_widgets = array(
			// WordPress.
			'WP_Widget_Pages',
			'WP_Widget_Calendar',
			'WP_Widget_Archives',
			'WP_Widget_Links',
			'WP_Widget_Meta',
			'WP_Widget_Search',
			'WP_Widget_Text',
			'WP_Widget_Categories',
			'WP_Widget_Recent_Posts',
			'WP_Widget_Recent_Comments',
			'WP_Widget_RSS',
			'WP_Widget_Tag_Cloud',
			'WP_Nav_Menu_Widget',

			// Genesis.
			'Genesis_Featured_Page',
			'Genesis_Featured_Post',
			'Genesis_User_Profile_Widget',
			'Genesis_eNews_Updates',
			'Genesis_Menu_Pages_Widget',
			'Genesis_Widget_Menu_Categories',
			'Genesis_Latest_Tweets_Widget',

			// Plugins.
			'Akismet_Widget',

			// WooCommerce
			'WC_Widget_Products',
			'WC_Widget_Recent_Products',
			'WC_Widget_Featured_Products',
			'WC_Widget_Product_Categories',
			'WC_Widget_Product_Tag_Cloud',
			'WC_Widget_Cart',
			'WC_Widget_Layered_Nav',
			'WC_Widget_Layered_Nav_Filters',
			'WC_Widget_Price_Filter',
			'WC_Widget_Rating_Filter',
			'WC_Widget_Product_Search',
			'WC_Widget_Top_Rated_Products',
			'WC_Widget_Recent_Reviews',
			'WC_Widget_Recently_Viewed',
			'WC_Widget_Best_Sellers',
			'WC_Widget_Onsale',
			'WC_Widget_Random_Products',
		);

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
		 * Array of widgets to remove.
		 *
		 * @var array
		 */
		public $widgets;

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
		 * Whether to remove Post Formats UI.
		 *
		 * @var bool
		 */
		public $remove_post_formats = false;

		/**
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		public function get_defaults() {
			return array(
				'widgets'             => array(),
				'dashboard'           => array(),
				'menu'                => array(),
				'remove_post_formats' => false,
			);
		}

		/**
		 * Initializer.
		 *
		 * Runs immediately on instantiation.
		 */
		public function setup( $args ) {

			if ( ! is_admin() ) {
				return;
			}

			// Setup.
			$this->widgets   = 'all' === $args['widgets'] ? $this->_wp_widgets : $args['widgets'];
			$this->dashboard = 'all' === $args['dashboard'] ? $this->_dashboard_widgets : $args['dashboard'];
			$this->menu      = 'all' === $args['menu'] ? $this->_menu : $args['menu'];

			$this->remove_post_formats = $args['remove_post_formats'];
		}

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {
			// Post Formats
			if ( $this->remove_post_formats ) {
				// Disable Post Formats UI.
				add_filter( 'enable_post_format_ui', '__return_false' );
			}

			// Widgets.
			if ( ! empty( $this->widgets ) ) {
				add_action( 'widgets_init', array( $this, 'remove_default_wp_widgets' ), 15 );
			}

			// Dashboard.
			if ( ! empty( $this->dashboard ) ) {
				add_action( 'admin_menu', array( $this, 'remove_dashboard_widgets' ), 11 );
			}

			// Admin Menu Items.
			if ( ! empty( $this->menu ) ) {
				add_action( 'admin_menu', array( $this, 'remove_admin_menus' ), 11 );
			}

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 99 );

		}

		/**
		 * Remove default WordPress widgets.
		 *
		 * @since 1.0
		 */
		public function remove_default_wp_widgets() {

			foreach ( $this->widgets as $widget ) {
				if ( in_array( $widget, $this->_wp_widgets, true ) ) {
					unregister_widget( $widget );
				}
			}

		}

		/**
		 * Widgets to be removed.
		 *
		 * @param array $widgets Array of strings.
		 */
		public static function remove_widgets( $widgets ) {
			foreach ( $widgets as $widget ) {
				unregister_widget( $widget );
			}
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

		/**
		 * Remove admin bar items.
		 *
		 * @global \WP_Admin_Bar $wp_admin_bar
		 */
		public function remove_admin_bar_items() {
			global $wp_admin_bar;

			foreach ( $this->admin_bar as $ab_item ) {
				$wp_admin_bar->remove_node( $ab_item );
			}

		}

	}
}
