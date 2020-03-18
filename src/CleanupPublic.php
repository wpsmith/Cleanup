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

if ( ! class_exists( __NAMESPACE__ . '\CleanupPublic' ) ) {
	/**
	 * CleanupPublic Class
	 *
	 * Assists in cleaning up some widgets, dashboard,
	 * menu items, admin bar, post formats, and frontend HTML header tags.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class CleanupPublic extends Cleanup {

		/**
		 * Widgets filters to filter.
		 *
		 * Values should be filter => bool.
		 *
		 * @var array
		 */
		protected $_frontend_widgets = array(
			'show_recent_comments_widget_style' => false,
		);

		/**
		 * Default rest methods that can be removed.
		 *
		 * @var array
		 */
		protected $_rest = array(
			array( 'xmlrpc_rsd_apis', 'rest_output_rsd' ),
			array( 'template_redirect', 'rest_output_link_header', 11 ),
			array( 'auth_cookie_malformed', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_expired', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_bad_username', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_bad_hash', 'rest_cookie_collect_status' ),
			array( 'auth_cookie_valid', 'rest_cookie_collect_status' ),
			array( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 ),
		);

		/**
		 * Default header scripts that can be removed.
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
		 * Array of rest methods to remove.
		 *
		 * @var array
		 */
		public $rest;

		/**
		 * Widgets filters to filter.
		 *
		 * @var array|mixed
		 */
		public $frontend_widgets;

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

			if ( is_admin() ) {
				return;
			}

			// Ensure we have the proper setup.
			$args = wp_parse_args( $args, $this->get_defaults() );

			parent::__construct( $args );

			if ( 'all' === $args ) {

				$this->remove_all();

			} else {

				$this->rest             = 'all' === $args['rest'] || true === $args['rest'] ? $this->_rest : $args['rest'];
				$this->frontend_widgets = 'all' === $args['frontend_widgets'] || true === $args['frontend_widgets'] ? $this->_frontend_widgets : $args['frontend_widgets'];

			}

		}

		/**
		 * Add the hooks.
		 */
		public function plugins_loaded() {

			parent::plugins_loaded();

			// Widgets.
			if ( ! empty( $this->frontend_widgets ) ) {
				add_action( 'init', array( $this, 'remove_frontend_widgets' ), PHP_INT_MAX );
			}

			// REST.
			if ( ! empty( $this->rest ) ) {
				add_action( 'init', array( $this, 'remove_rest' ), PHP_INT_MAX );
			}

		}

		/**
		 * Removes front-end widget stuffs.
		 */
		public function remove_frontend_widgets() {
			foreach ( $this->frontend_widgets as $filter => $value ) {
				if ( false === $value ) {
					add_filter( $filter, '__return_false' );
				} else {
					add_filter( $filter, '__return_true' );
				}
			}
		}


		/**
		 * Removes front-end widget stuffs.
		 */
		public function remove_rest() {

			foreach ( $this->rest as $action ) {
				$priority = isset( $action[2] ) ? $action[2] : 10;
				remove_action( $action[0], $action[1], $priority );
			}

		}

		/**
		 * Removes all the items.
		 */
		public function remove_all() {

			parent::remove_all();
			$this->rest             = $this->_rest;
			$this->frontend_widgets = $this->_frontend_widgets;

		}


	}
}
