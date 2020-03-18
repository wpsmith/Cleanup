<?php
/**
 * WordPress Cleanup Class
 *
 * Cleans up WordPress header output, metabox priorities, etc.
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

use WPS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\WordPressCleanup' ) ) {
	/**
	 * WordPress Cleanup Class
	 *
	 * Cleans up various WordPress Plugins metaboxes.
	 *
	 * @package WPS\WP
	 * @author Travis Smith <t@wpsmith.net>
	 */
	class WordPressCleanup extends Cleanup {

		/**
		 * Array of redirects.
		 *
		 * @var array
		 */
		public $_redirects = array(
			'attachment',
			'author',
			'date',
		);

		/**
		 * Array of redirects.
		 *
		 * @var array
		 */
		public $redirects;
//		public $redirect_attachments;
//		public $redirect_author;
//		public $redirect_date;

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			parent::__construct( $args );

			if ( 'all' === $args ) {

				$this->remove_all();

			} else {

				$this->redirects = 'all' === $args['redirects'] || true === $args['redirects'] ? $this->_redirects : $args['redirects'];

			}

		}

		/**
		 * Returns an array of defaults.
		 *
		 * @return array
		 */
		public function get_defaults() {

			return wp_parse_args( array(
				'redirects' => array(),
			), parent::get_defaults() );

		}

		/**
		 * Implements plugins_loaded abstract method.
		 *
		 * @return mixed|void
		 */
		public function plugins_loaded() {
			static $hooked = false;

			if ( $hooked ) {
				return;
			}

			$hooked = true;

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			$this->remove_allowed_tags();

			// Reset excerpt priority.
			add_action( 'add_meta_boxes', array( $this, 'reset_excert_metabox' ), 10 );

			// Remove version numbering.
			add_filter( 'script_loader_src', array( $this, 'remove_wp_version_strings' ) );
			add_filter( 'style_loader_src', array( $this, 'remove_wp_version_strings' ) );

			// Redirect attachment pages to parent pages.
			if ( ! empty( $this->redirects ) ) {
				add_filter( 'template_redirect', array( $this, 'template_redirect' ) );
			}

		}

		/** PUBLIC API */

		/**
		 * Determines whether the current page is the plugin page.
		 *
		 * @return bool
		 */
		public static function is_plugin_page() {
			global $plugin_page;

			return (
				// DOING_AJAX = not on plugin page.
				( isset( $plugin_page ) && defined( 'DOING_AJAX' ) && ! DOING_AJAX ) ||
				( isset( $plugin_page ) && ! defined( 'DOING_AJAX' ) ) ||

				// Whether Document URI || PHP_SELF is the plugins.php
				( isset( $_SERVER['DOCUMENT_URI'] ) && strpos( $_SERVER['DOCUMENT_URI'], '/wp-admin/plugins.php' ) > - 1 ) ||
				( isset( $_SERVER['PHP_SELF'] ) && strpos( $_SERVER['PHP_SELF'], '/wp-admin/plugins.php' ) > - 1 )
			);
		}

		/**
		 * Removes all the items.
		 */
		public function remove_all() {

			parent::remove_all();
			$this->redirects = $this->_redirects;

		}

		/** PRIVATE API */

		/**
		 * Hide WP version strings from scripts and styles
		 *
		 * @return string $src
		 */
		public function remove_wp_version_strings( $src ) {
			global $wp_version;
			parse_str( parse_url( $src, PHP_URL_QUERY ), $query );
			if ( ! empty( $query['ver'] ) && $query['ver'] === $wp_version ) {
				$src = remove_query_arg( 'ver', $src );
			}

			return $src;
		}

		/**
		 * Remove cite, q, del, abbr, acronym from allowed tags.
		 */
		public function remove_allowed_tags() {
			global $allowedtags;

			unset( $allowedtags['cite'] );
			unset( $allowedtags['q'] );
			unset( $allowedtags['del'] );
			unset( $allowedtags['abbr'] );
			unset( $allowedtags['acronym'] );
		}

		/**
		 * Resets excerpt metabox to high priority.
		 *
		 * @param string $post_type Post Type.
		 */
		public function reset_excert_metabox( $post_type ) {

			if ( ! post_type_supports( $post_type, 'excerpt' ) ) {
				return;
			}
			add_meta_box( 'postexcerpt', __( 'Excerpt' ), 'post_excerpt_meta_box', null, 'normal', 'high' );

		}

		/**
		 * Redirect attachment pages to parent pages and set to 404, author/date pages to 404.
		 */
		public function template_redirect() {
			global $wp_query, $post;

			if ( is_attachment() && in_array( 'attachment', $this->redirects ) ) {
				$post_parent = $post->post_parent;

				if ( $post_parent ) {
					wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
					exit;
				}

				$wp_query->set_404();

				return;
			}

			if (
				( is_author() && in_array( 'author', $this->redirects ) ) ||
				( is_date() && in_array( 'date', $this->redirects ) )
			) {
				$wp_query->set_404();
			}
		}

	}
}
