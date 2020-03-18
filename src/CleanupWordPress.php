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
		public $redirects = array();

		/**
		 * Cleanup constructor.
		 *
		 * @param array $args Array of args. Keys include: widgets, dashboard, menu, admin_bar, links
		 *                    post_formats.
		 */
		protected function __construct( $args ) {

			// Ensure we have the proper setup.
			$args = wp_parse_args( $args, $this->get_defaults() );

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

			// Dequeue jQuery Migrate.
			add_filter( 'wp_default_scripts', array( $this, 'dequeue_jquery_migrate' ) );

			// Singular body class.
			add_filter( 'body_class', array( $this, 'singular_body_class' ) );

			// Comment form, button class.
			add_filter( 'comment_form_defaults', array( $this, 'comment_form_button_class' ) );

			// Remove avatars from comment list.
			add_filter( 'get_avatar', array( $this, 'remove_avatars_from_comments' ) );

			// Clean Post Classes.
			add_filter( 'post_class', array( $this, 'clean_post_classes' ), 5 );

			// Archive Title, remove prefix.
			add_filter( 'get_the_archive_title', array( $this, 'archive_title_remove_prefix' ) );

			// Clean Nav Menu Classes.
			add_filter( 'nav_menu_css_class', array( $this, 'clean_nav_menu_classes' ), 5, 3 );

			// Clean body classes.
			add_filter( 'body_class', array( $this, 'clean_body_classes' ), 20 );
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
		 * Dequeue jQuery Migrate
		 *
		 */
		public function dequeue_jquery_migrate( &$scripts ) {
			if ( ! is_admin() ) {
				$scripts->remove( 'jquery' );
				$scripts->add( 'jquery', false, array( 'jquery-core' ), '1.10.2' );
			}
		}

		/**
		 * Singular body class
		 *
		 */
		public function singular_body_class( $classes ) {
			if ( is_singular() ) {
				$classes[] = 'singular';
			}

			return $classes;
		}

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

		/**
		 * Clean body classes
		 *
		 * @param array $classes Array of body classes.
		 *
		 * @return array
		 */
		public function clean_body_classes( $classes ) {

			$allowed_classes = [
				'singular',
				'single',
				'page',
				'archive',
				'admin-bar',
				'full-width-content',
				'content-sidebar',
				'content',
			];

			return array_intersect( $classes, $allowed_classes );

		}

		/**
		 * Clean Nav Menu Classes
		 *
		 * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
		 * @param \WP_Post $item The current menu item.
		 * @param \stdClass $args An object of wp_nav_menu() arguments.
		 *
		 * @return array
		 */
		public function clean_nav_menu_classes( $classes, $item, $args ) {
			if ( ! is_array( $classes ) ) {
				return $classes;
			}

			foreach ( $classes as $i => $class ) {

				// Remove class with menu item id
				$id = strtok( $class, 'menu-item-' );
				if ( 0 < intval( $id ) ) {
					unset( $classes[ $i ] );
				}

				// Remove menu-item-type-*
				if ( false !== strpos( $class, 'menu-item-type-' ) ) {
					unset( $classes[ $i ] );
				}

				// Remove menu-item-object-*
				if ( false !== strpos( $class, 'menu-item-object-' ) ) {
					unset( $classes[ $i ] );
				}

				// Change page ancestor to menu ancestor
				if ( 'current-page-ancestor' == $class ) {
					$classes[] = 'current-menu-ancestor';
					unset( $classes[ $i ] );
				}
			}

			// Remove submenu class if depth is limited
			if ( isset( $args->depth ) && 1 === $args->depth ) {
				$classes = array_diff( $classes, array( 'menu-item-has-children' ) );
			}

			return $classes;
		}

		/**
		 * Clean Post Classes
		 *
		 * @param array $classes Array of post classes.
		 *
		 * @return array
		 */
		public function clean_post_classes( $classes ) {

			if ( ! is_array( $classes ) ) {
				return $classes;
			}

			$allowed_classes = array(
				'hentry',
				'type-' . get_post_type(),
			);

			return array_intersect( $classes, $allowed_classes );
		}

		/**
		 * Archive Title, remove prefix.
		 *
		 * @param string $title Archive Title.
		 *
		 * @return string
		 */
		public function archive_title_remove_prefix( $title ) {
			$title_pieces = explode( ': ', $title );
			if ( count( $title_pieces ) > 1 ) {
				unset( $title_pieces[0] );
				$title = join( ': ', $title_pieces );
			}

			return $title;
		}

		/**
		 * Remove avatars from comment list.
		 *
		 * @param string $avatar &lt;img&gt; tag for the user's avatar.
		 *
		 * @return string
		 */
		public function remove_avatars_from_comments( $avatar ) {
			global $in_comment_loop;

			return $in_comment_loop ? '' : $avatar;
		}

		/**
		 * Comment form, button class
		 *
		 * @param array $args The default comment form arguments.
		 *
		 * @return array
		 */
		public function comment_form_button_class( $args ) {
			$args['class_submit'] = 'submit wp-block-button__link';

			return $args;
		}

	}
}
