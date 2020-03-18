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
 * @package    WPS\WP
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2020 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/Cleanup
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Cleanup' ) ) {
	/**
	 * Cleanup Abstract Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	abstract class Cleanup extends Singleton {

		/**
		 * Default WP Widget classes that can be removed.
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
		 * Default scripts that can be removed.
		 *
		 * Values should be a key => array( hook, callback, priority )
		 * or script handle name.
		 *
		 * @var array
		 */
		protected $_scripts = array();

		/**
		 * Whether the actions have been hooked.
		 *
		 * @var bool
		 */
		protected $_hooked = false;

		/**
		 * Default header links that can be removed.
		 *
		 * All callbacks are performed in the wp_head hook.
		 * Values should be callback => priority.
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

			// Remove the link to the Windows Live Writer manifest file.
			'wlwmanifest_link'                => 10,

			// remove really simple discovery link.
			// Remove if not using a blog client found.
			// https://codex.wordpress.org/Weblog_Client.
			'rsd_link'                        => 10,

			// Removes WP 4.2 emoji styles and JS. Nasty stuff.
			'print_emoji_detection_script'    => 7,

			// Display the XHTML generator that is generated on the wp_head hook, WP version
			'wp_generator'                    => 10,
			'rel_canonical'                   => 10,

			// Remove start post link.
			'start_post_rel_link'             => 10,

			// Remove parent post link, prev link.
			'parent_post_rel_link'            => 10,

			// Remove relational links for the posts adjacent to the current post.
			// Remove the next and previous post links.
			'adjacent_posts_rel_link_wp_head' => 10,

			// REST
			'rest_output_link_wp_head'        => 10,
		);

		/**
		 * Array of widgets to remove.
		 *
		 * @var array
		 */
		public $widgets;

		/**
		 * Scripts to remove.
		 *
		 * @var array Array of scripts.
		 */
		public $scripts;

		/**
		 * Array of header links to remove.
		 *
		 * @var array
		 */
		public $links;

		/**
		 * Array of admin bar items to remove.
		 *
		 * @var array
		 */
		public $admin_bar;

		/**
		 * Whether to remove Post Formats UI.
		 *
		 * @var bool
		 */
		public $remove_post_formats = false;

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			// Ensure we have the proper setup.
			$args = wp_parse_args( $args, $this->get_defaults() );

			if ( 'all' === $args ) {

				$this->remove_all();

			} else {

				$this->links     = 'all' === $args['links'] ? $this->_links : $args['links'];
				$this->widgets   = 'all' === $args['widgets'] ? $this->_wp_widgets : $args['widgets'];
				$this->scripts   = 'all' === $args['scripts'] || true === $args['scripts'] ? $this->_scripts : $args['scripts'];
				$this->admin_bar = $args['admin_bar'];

				if ( isset( $args['post_formats'] ) && $args['post_formats'] ) {
					// Disable Post Formats UI.
					$this->remove_post_formats = true;
					add_filter( 'enable_post_format_ui', '__return_false' );
				}

			}

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

		}

		/** PUBLIC API */

		/**
		 * Removes links.
		 *
		 * @param array $items
		 */
		public function remove_links( $items ) {

			$this->links = $items;

		}

		/**
		 * Removes scripts items.
		 *
		 * @param array $items
		 */
		public function remove_scripts( $items ) {

			$this->scripts = $items;

		}

		/**
		 * Removes widgets.
		 *
		 * @param array $items
		 */
		public function remove_widgets( $items ) {

			$this->widgets = $items;

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
		 * Remove admin bar items.
		 *
		 * @global \WP_Admin_Bar $wp_admin_bar
		 */
		public function remove_admin_bar_items( $items ) {
			/** @var $wp_admin_bar \WP_Admin_Bar */
			global $wp_admin_bar;

			foreach ( $items as $ab_item ) {
				$wp_admin_bar->remove_node( $ab_item );
			}

		}

		/**
		 * Removes all the items.
		 */
		public function remove_all() {

			$this->links               = $this->_links;
			$this->widgets             = $this->_wp_widgets;
			$this->scripts             = $this->_scripts;
			$this->remove_post_formats = true;
			add_filter( 'enable_post_format_ui', '__return_false' );

			if ( did_action( 'plugins_loaded' ) && !$this->_hooked ) {
				$this->plugins_loaded();
			}

		}

		/**
		 * Widgets to be unregistered.
		 *
		 * @param array $widgets Array of strings.
		 */
		public static function unregister_widgets( $widgets ) {

			foreach ( $widgets as $widget ) {
				unregister_widget( $widget );
			}

		}

		/** PRIVATE API */

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {
			static $hooked = false;

			if ( $hooked ) {
				return;
			}

			$hooked = true;

			// Widgets.
			if ( ! empty( $this->widgets ) ) {
				add_action( 'widgets_init', array( $this, 'remove_default_wp_widgets' ), PHP_INT_MAX ); // 15
			}

			// Scripts
			if ( ! empty( $this->scripts ) ) {
				add_action( 'init', array( $this, '_remove_scripts' ), PHP_INT_MAX );
			}

			// Admin Bar.
			if ( ! empty( $this->admin_bar ) ) {
				add_action( 'wp_before_admin_bar_render', array( $this, '_remove_admin_bar_items' ), PHP_INT_MAX );
			}

			// Links.
			if ( ! empty( $this->links ) ) {
				$this->_remove_links();
			}

		}

		/**
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		protected function get_defaults() {
			return array(
				'widgets'      => array(),
				'admin_bar'    => array(),
				'links'        => array(),
				'scripts'      => array(),
				'post_formats' => false,
			);
		}

		/**
		 * Remove links from header.
		 *
		 * @access private
		 */
		public function _remove_links() {

			foreach ( $this->links as $link => $priority ) {
				if ( in_array( $link, $this->_links, true ) ) {
					remove_action( 'wp_head', $link, $priority );
				}

				switch ( $link ) {
					case 'wp_generator':
						add_filter( 'the_generator', '__return_empty_string' );
						break;
					case 'feed_links':
						remove_theme_support( 'automatic-feed-links' );

						// Just in case someone re-adds these later
						add_filter( 'feed_links_show_posts_feed', '__return_false' );
						add_filter( 'feed_links_show_comments_feed', '__return_false' );
						break;
					case 'print_emoji_detection_script':
						remove_action( 'wp_print_styles', 'print_emoji_styles' );
						break;
				}
			}

		}

		/**
		 * Remove admin bar items.
		 *
		 * @global \WP_Admin_Bar $wp_admin_bar
		 */
		public function _remove_admin_bar_items() {

			$this->remove_admin_bar_items( $this->admin_bar );

		}

		/**
		 * Removes WP native scripts.
		 *
		 * @access private
		 */
		public function _remove_scripts() {

			foreach ( $this->scripts as $key => $data ) {
				if ( is_array( $data ) ) {
					foreach ( $data as $d ) {
						if ( is_array( $d ) && count( $d ) > 2 ) {
							remove_action( $d[0], $d[1], $d[2] );
						}
					}
				} elseif ( is_string( $data ) ) {
					wp_deregister_script( $data );
				}
			}

		}

	}
}
