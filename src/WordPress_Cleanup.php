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
 * @package    WPS\Core
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Core;

use WPS;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WordPress_Cleanup' ) ) {
	/**
	 * WordPress Cleanup Class
	 *
	 * Cleans up various WordPress Plugins metaboxes.
	 *
	 * @package WPS\Core
	 * @author Travis Smith <t@wpsmith.net>
	 */
	class WordPress_Cleanup extends Cleanup {

		/**
		 * Implements plugins_loaded abstract method.
		 *
		 * @return mixed|void
		 */
		public function plugins_loaded() {

			if ( WPS\is_doing_ajax() ) {
				return;
			}

			$this->remove_allowed_tags();

			add_action( 'add_meta_boxes', array( $this, 'reset_excert_metabox' ), 10 );

			// Yoast WP SEO.
			add_filter( 'wpseo_metabox_prio', array( $this, 'wpseo_metabox_priority' ) );
			if ( ! WPS\is_plugin_active( 'remove-yoast-seo-comments/remove-yoast-seo-comments.php' ) && ! is_admin() ) {
				add_action( 'wp_head', array( $this, 'remove_yoast_comments' ), ~PHP_INT_MAX );
			}

			// RCP.
			add_action( 'rcp_metabox_priority', array( $this, 'rcp_metabox_priority' ) );

			// Envira Gallery.
			add_action( 'envira_gallery_loaded', array( $this, 'envira_gallery_loaded' ), 9999 );

			// Soliloquy.
			add_action( 'soliloquy_init', array( $this, 'soliloquy_init' ), 9999 );
		}

		/**
		 * Set RCP Metabox Priority to low.
		 */
		public function rcp_metabox_priority() {
			return 'low';
		}

		/**
		 * Set WP SEO Metabox Priority to default.
		 *
		 * @return string
		 */
		public function wpseo_metabox_priority() {
			return 'default';
		}

		/**
		 * Remove all Yoast SEO HTML Comments.
		 *
		 * @link https://gist.github.com/paulcollett/4c81c4f6eb85334ba076
		 */
		public function remove_yoast_comments() {
			ob_start( function ( $o ) {
				return preg_replace( '/^\n?<!--.*?[Y]oast.*?-->\n?$/mi', '', $o );
			} );
		}

		/**
		 * Redirect attachment pages to parent pages and set to 404, author/date pages to 404.
		 */
		public function template_redirect() {
			global $wp_query, $post;

			if ( is_attachment() ) {
				$post_parent = $post->post_parent;

				if ( $post_parent ) {
					wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
					exit;
				}

				$wp_query->set_404();

				return;
			}

			if ( is_author() || is_date() ) {
				$wp_query->set_404();
			}
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
		public static function reset_excert_metabox( $post_type ) {

			if ( ! post_type_supports( $post_type, 'excerpt' ) ) {
				return;
			}
			add_meta_box( 'postexcerpt', __( 'Excerpt' ), 'post_excerpt_meta_box', null, 'normal', 'high' );

		}

		/**
		 * Determines whether the current page is the plugin page.
		 *
		 * @return bool
		 */
		protected static function is_plugin_page() {
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
		 * On pages other than the plugin page, removes envira gallery updater.
		 */
		public function envira_gallery_loaded() {
			if ( ! self::is_plugin_page() ) {
				return;
			}
			self::remove_envira_updater();
		}

		/**
		 * On pages other than the plugin page, removes soliloquy updater.
		 */
		public function soliloquy_init() {
			if ( ! self::is_plugin_page() ) {
				return;
			}

			self::remove_soliloquy_updater();
		}

		/**
		 * Removes the Soliloquy Updater hooks.
		 */
		public static function remove_soliloquy_updater() {
			remove_action( 'soliloquy_updater', 'soliloquy_custom_css_updater' );
			remove_action( 'soliloquy_updater', 'soliloquy_themes_updater' );

			if ( class_exists( 'Soliloquy_Defaults' ) ) {
				remove_action( 'soliloquy_updater', array( \Soliloquy_Defaults::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Soliloquy_Dynamic' ) ) {
				remove_action( 'soliloquy_updater', array( \Soliloquy_Dynamic::get_instance(), 'updater' ) );
			}
		}

		/**
		 * Removes the Envira Gallery Updater hooks.
		 */
		public static function remove_envira_updater() {
			if ( class_exists( 'Envira_Albums' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Albums::get_instance(), 'updater' ) );
			}

			remove_action( 'envira_gallery_updater', 'envira_custom_css_updater' );
			add_action( 'envira_gallery_updater', 'envira_gallery_themes_updater' );

			if ( class_exists( 'Envira_Defaults' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Defaults::get_instance(), 'updater' ) );
			}

			if ( class_exists( 'Envira_Dynamic' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Dynamic::get_instance(), 'updater' ) );
			}

			if ( class_exists( 'Envira_Featured_Content' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Featured_Content::get_instance(), 'updater' ) );
			}

			if ( class_exists( 'Envira_Fullscreen' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Fullscreen::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Lightroom' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Lightroom::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Proofing' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Proofing::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Slideshow' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Slideshow::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Social' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Social::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Tags' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Tags::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Videos' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Videos::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Watermarking' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Watermarking::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_ZIP_Importer' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_ZIP_Importer::get_instance(), 'updater' ) );
			}
			if ( class_exists( 'Envira_Zoom' ) ) {
				remove_action( 'envira_gallery_updater', array( \Envira_Zoom::get_instance(), 'updater' ) );
			}
		}
	}
}
