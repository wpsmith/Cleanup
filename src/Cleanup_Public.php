<?php
/**
 * Cleanup_Public Abstract Class
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
use function WPS\printr;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS\Core\Cleanup_Public' ) ) {
	/**
	 * Cleanup_Public Abstract Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\Core
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class Cleanup_Public extends Cleanup {

		/**
		 * Widgets filters to filter.
		 *
		 * Values should be filter => bool.
		 *
		 * @var array
		 */
		protected $_widgets = array(
			'show_recent_comments_widget_style' => false,
		);

		/**
		 * Supported header scripts that can be removed.
		 *
		 * Values should be a key => array( hook, callback, priority )
		 * or script handle name.
		 *
		 * @var array
		 */
		protected $_scripts = array(
			// Removes WP 4.2 emoji styles and JS. Nasty stuff.
			// print_emoji_detection_script
			'print_emoji_detection_script' => array(
				array( 'wp_head', 'print_emoji_detection_script', 7 ),
				array( 'admin_print_scripts', 'print_emoji_detection_script', 10 ),
				array( 'embed_head', 'print_emoji_detection_script', 10 ),
			),
			'wp-embed',
		);

		/**
		 * Supported header links that can be removed.
		 *
		 * All callbacks are performed in the wp_head hook.
		 * Values should be callback => priority.
		 *
		 * @var array
		 */
		protected $_links = array(
			// remove rss feed links (make sure you add them in yourself if youre using feedblitz or an rss service).
			// Remove the links to the general feeds: Post and Comment Feed
			'feed_links'                      => 2,
			// Remove the links to the extra feeds such as category feeds
			'feed_links_extra'                => 3,

			// remove link to index page.
			'index_rel_link'                  => 10,
			'wp_shortlink_wp_head'            => 10,

			// Remove the link to the Windows Live Writer manifest file.
			'wlwmanifest_link'                => 10,

			// Remove really simple discovery link, EditURI link.
			// Remove if not using a blog client found.
			// https://codex.wordpress.org/Weblog_Client.
			'rsd_link'                        => 10,

			// Display the XHTML generator that is generated on the wp_head hook, WP version
			'wp_generator'                    => 10,

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

		protected $_rest = array(
			array( 'xmlrpc_rsd_apis', 'rest_output_rsd' ),
			array( 'template_redirect', 'rest_output_link_header', 11, 0 ),
			array( 'auth_cookie_malformed', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_expired', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_bad_username', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_bad_hash', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_valid', 'rest_cookie_collect_status' ),
			array( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 ),
		);

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
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		public function get_defaults() {
			return array(
				'admin_bar' => array(),
				'links'     => array(),
				'widgets'   => array(),
			);
		}

		/**
		 * Initializer.
		 *
		 * Runs immediately on instantiation.
		 */
		public function setup( $args ) {

			if ( is_admin() ) {
				return;
			}

			// Setup.
			$this->admin_bar = $args['admin_bar'];
			$this->links     = 'all' === $args['links'] || true === $args['links'] ? $this->_links : $args['links'];
			$this->scripts   = 'all' === $args['scripts'] || true === $args['scripts'] ? $this->_scripts : $args['scripts'];
			$this->widgets   = 'all' === $args['widgets'] || true === $args['widgets'] ? $this->_widgets : $args['widgets'];

		}

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {

			// Admin Bar.
			if ( ! empty( $this->admin_bar ) ) {
				add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_items' ) );
			}

			// Scripts
			if ( ! empty( $this->scripts ) ) {
				add_action( 'init', array( $this, 'remove_scripts' ), ~PHP_INT_MAX );
			}

			// Links.
			if ( ! empty( $this->links ) ) {
				add_action( 'init', array( $this, 'remove_links' ), ~PHP_INT_MAX );
			}

			// Widgets.
			if ( ! empty( $this->widgets ) ) {
				add_action( 'init', array( $this, 'remove_frontend_widgets' ), ~PHP_INT_MAX );
			}

		}

		/**
		 * Removes the admin bar.
		 */
		public function remove_admin_bar() {

			show_admin_bar( false );
			add_filter( 'show_admin_bar', '__return_false' );

		}

		/**
		 * Remove links from header.
		 */
		public function remove_links() {

			foreach ( $this->links as $link => $priority ) {
				if ( array_key_exists( $link, $this->_links ) ) {
					remove_action( 'wp_head', $link, $priority );
				}

				if ( 'wp_generator' === $link ) {
					add_filter( 'the_generator', '__return_false' );
				} elseif ( 'feed_links' === $link ) {
					remove_theme_support( 'automatic-feed-links' );

					// Just in case someone re-adds these later
					add_filter( 'feed_links_show_posts_feed', '__return_false' );
					add_filter( 'feed_links_show_comments_feed', '__return_false' );
				}

			}

		}

		/**
		 * Removes front-end widget stuffs.
		 */
		public function remove_frontend_widgets() {
			foreach ( $this->widgets as $filter => $value ) {
				if ( false === $value ) {
					add_filter( $filter, '__return_false' );
				} else {
					add_filter( $filter, '__return_true' );
				}
			}
		}

		/**
		 * Removes WP native scripts.
		 */
		public function remove_scripts() {

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
