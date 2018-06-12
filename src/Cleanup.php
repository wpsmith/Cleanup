<?php
/**
 * Cleanup Abstract Class
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

if ( ! class_exists( 'WPS\Core\Cleanup' ) ) {
	/**
	 * Cleanup Abstract Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\Core
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	abstract class Cleanup extends Singleton {

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
		 * Supported header links that can be removed.
		 *
		 * @var array
		 */
		protected $_links = array(
			// remove rss feed links (make sure you add them in yourself if youre using feedblitz or an rss service).
			'feed_links'                      => 2,
			// removes all extra rss feed links.
			'feed_links_extra'                => 3,

			// remove link to index page.
			'index_rel_link'                  => 10,
			'wp_shortlink_wp_head'            => 10,
			'wlwmanifest_link'                => 10,

			// remove really simple discovery link.
			// Remove if not using a blog client found.
			// https://codex.wordpress.org/Weblog_Client.
			'rsd_link'                        => 10,

			// Removes WP 4.2 emoji styles and JS. Nasty stuff.
			'print_emoji_detection_script'    => 7,
			'wp_generator'                    => 10,
			'rel_canonical'                   => 10,

			// random post link.
			'start_post_rel_link'             => 10,

			// parent post link.
			'parent_post_rel_link'            => 10,

			// remove the next and previous post links.
			'adjacent_posts_rel_link_wp_head' => 10,
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
		 * Array of admin bar items to remove.
		 *
		 * @var array
		 */
		public $admin_bar;

		/**
		 * Array of header links to remove.
		 *
		 * @var array
		 */
		public $links;

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			// Ensure we have the proper setup.
			$defaults = array(
				'widgets'      => array(),
				'dashboard'    => array(),
				'menu'         => array(),
				'admin_bar'    => array(),
				'links'        => array(),
				'post_formats' => false,
			);
			$args     = wp_parse_args( $args, $defaults );

			// Setup.
			$this->widgets   = 'all' === $args['widgets'] ? $this->_wp_widgets : $args['widgets'];
			$this->dashboard = 'all' === $args['dashboard'] ? $this->_dashboard_widgets : $args['dashboard'];
			$this->menu      = 'all' === $args['menu'] ? $this->_menu : $args['menu'];
			$this->admin_bar = $args['admin_bar'];
			$this->links     = 'all' === $args['links'] ? $this->_links : $args['links'];

			if ( isset( $args['post_formats'] ) && $args['post_formats'] ) {
				// Disable Post Formats UI.
				add_filter( 'enable_post_format_ui', '__return_false' );
			}

			// Initiate Cleansing.
			$this->init();

		}

		/**
		 * Abstracted function to be implemented.
		 *
		 * @return mixed
		 */
		abstract public function plugins_loaded();

		/**
		 * Initializer.
		 *
		 * Runs immediately on instantiation.
		 */
		public function init() {

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

			// Admin Bar.
			if ( ! empty( $this->admin_bar ) ) {
				add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_items' ) );
			}

			// Links.
			if ( ! empty( $this->links ) ) {
				add_action( 'plugins_loaded', array( $this, 'remove_links' ) );
			}

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 99 );

		}

		/**
		 * Removes the admin bar.
		 */
		public function remove_admin_bar() {

			show_admin_bar( false );
			add_filter( 'show_admin_bar', '__return_false' );

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
		 * Remove links from header.
		 */
		public function remove_links() {

			foreach ( $this->links as $link => $priority ) {
				if ( in_array( $link, $this->_links, true ) ) {
					remove_action( 'wp_head', $link, $priority );
				}

				if ( 'wp_generator' === $link ) {
					add_filter( 'the_generator', '__return_false' );
				} elseif ( 'print_emoji_detection_script' === $link ) {
					remove_action( 'wp_print_styles', 'print_emoji_styles' );
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
