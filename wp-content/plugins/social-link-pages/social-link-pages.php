<?php
/*
Plugin Name:       Social Link Pages
Description:       Create a social profile landing page with all of your links at a single url for Instagram, Twitter, Facebook and more.
Tags:              social profile, landing page, Instagram bio, social links, one page, Linktree, linktr.ee, about.me, carrd
Version:           0.1.10
Release Date:      April 6, 2020
Requires at least: 5.0.0
Tested up to:      5.4
Requires PHP:      5.4.45
Author:            gelform
Stable tag:        trunk
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
*/

class Social_Link_Pages {

	private static $instance;

	public $plugin_dir_url;
	public $plugin_dir_path;
	public $plugin_basename;
	public $plugin_name_friendly;
	public $plugin_data;

	private function __construct() {
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	private function setup() {
		$this->plugin_dir_url       = plugin_dir_url( __FILE__ );
		$this->plugin_dir_path      = plugin_dir_path( __FILE__ );
		$this->plugin_basename      = plugin_basename( __FILE__ );
		$this->plugin_name_friendly = strtolower( __CLASS__ );

		require_once $this->plugin_dir_path . '/inc/class-db.php';
		require_once $this->plugin_dir_path . '/inc/class-admin.php';
		require_once $this->plugin_dir_path . '/inc/class-page.php';
	}

	public function get_asset_urls( $app, $type = 'css' ) {
		$dir = new DirectoryIterator( Social_Link_Pages()->get_asset_path( $app ) . $type );

		$scripts = array();
		foreach ( $dir as $file ) {
			if ( pathinfo( $file, PATHINFO_EXTENSION ) === $type ) {
				$fullName = basename( $file );
//				$name     = substr( basename( $fullName ), 0, strpos( basename( $fullName ), '.' ) );

				$scripts[] = array(
					'name'    => $fullName,
					'url'     => sprintf(
						'%s%s/%s',
						Social_Link_Pages()->get_asset_url( $app ),
						$type,
						$fullName
					),
					'version' => Social_Link_Pages()->plugin_data()['Version']
				);
			}
		}

		return $scripts;
	}

	public function get_asset_path( $app = 'admin' ) {
		return sprintf(
			'%s%s/build/static/',
			apply_filters(
				$this->plugin_name_friendly . '-plugin_dir_path',
				Social_Link_Pages()->plugin_dir_path
			),
			$app
		);
	}

	public function get_asset_url( $app = 'admin' ) {
		return sprintf(
			'%s%s/build/static/',
			apply_filters(
				$this->plugin_name_friendly . '-plugin_dir_url',
				Social_Link_Pages()->plugin_dir_url
			),
			$app
		);
	}

	public function plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			$this->plugin_data = get_plugin_data( __FILE__ );
		}

		return $this->plugin_data;
	}

	public function get_plugin_name_formal() {
		return apply_filters(
			Social_Link_Pages()->plugin_name_friendly . '_plugin_name_formal',
			ucwords( str_replace( '_', ' ', $this->plugin_name_friendly ) )
		);
	}

	public function get_asset_paths( $app, $type = 'css' ) {
		$dir = new DirectoryIterator( Social_Link_Pages()->get_asset_path( $app ) . $type );

		$scripts = array();
		foreach ( $dir as $file ) {
			if ( pathinfo( $file, PATHINFO_EXTENSION ) === $type ) {
				$fullName = basename( $file );
//				$name     = substr( basename( $fullName ), 0, strpos( basename( $fullName ), '.' ) );

				$scripts[] = array(
					'name'    => $fullName,
					'url'     => sprintf(
						'%s%s/%s',
						Social_Link_Pages()->get_asset_path( $app ),
						$type,
						$fullName
					),
					'version' => Social_Link_Pages()->plugin_data()['Version']
				);
			}
		}

		return $scripts;
	}
}

function Social_Link_Pages() {
	return Social_Link_Pages::instance();
}

Social_Link_Pages();