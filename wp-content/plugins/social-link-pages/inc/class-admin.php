<?php


class Social_Link_Pages_Admin {
	private static $instance;

	private function __construct() {
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->setup();
		}

		return self::$instance;
	}

	public function setup() {
		add_action( 'admin_head', array( $this, 'custom_favicon' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'plugin_action_links_' . Social_Link_Pages()->plugin_basename, array( $this, 'add_action_links' ) );

		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_update_page', array(
			$this,
			'ajax_update_page'
		) );
		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_slug_exists', array(
			$this,
			'ajax_slug_exists'
		) );
		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_create_page', array(
			$this,
			'ajax_create_page'
		) );
		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_delete_page', array(
			$this,
			'ajax_delete_page'
		) );
		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_get_pages', array(
			$this,
			'ajax_get_all_pages'
		) );
		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_MailChimp_get_lists', array(
			$this,
			'ajax_MailChimp_get_lists'
		) );

	}

	public function ajax_create_page() {
		global $wpdb;

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Social_Link_Pages()->plugin_name_friendly ) ) {
			wp_send_json_error();
		}

		if ( empty( $_POST['page'] ) || empty ( $_POST['page']['slug'] ) ) {
			wp_send_json_error();
		}

		$post_id = Social_Link_Pages_Db()->create_page( $_POST['page'] );

		if ( false === $post_id ) {
			wp_send_json_error( 'Page could not be created.' );
		}

		$page_data = Social_Link_Pages_Db()->page_data_from_post( $post_id );

		wp_send_json_success( $page_data );
	}

	public function ajax_slug_exists() {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Social_Link_Pages()->plugin_name_friendly ) ) {
			wp_send_json_error();
		}

		if ( empty ( $_POST['slug'] ) ) {
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'is_unique' => Social_Link_Pages_Db()->is_slug_unique( sanitize_title( $_POST['slug'] ) )
			)
		);
	}

	public function ajax_get_all_pages() {
		$args = apply_filters(
			Social_Link_Pages()->plugin_name_friendly . '_get_all_pages',
			array(
				'numberposts' => - 1,
				'post_type'   => Social_Link_Pages()->plugin_name_friendly,
				'post_status' => 'publish'
			) );

		$records = get_posts( $args );

		$pages = array();
		foreach ( $records as $record ) {
			$pages[] = Social_Link_Pages_Db()->page_data_from_post( $record );
		}

		wp_send_json_success( $pages );
	}

	public function ajax_update_page() {

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Social_Link_Pages()->plugin_name_friendly ) ) {
			wp_send_json_error();
		}

		if ( empty( $_POST['page'] ) || empty ( $_POST['page']['id'] ) ) {
			wp_send_json_error();
		}

		$success = Social_Link_Pages_Db()->update_page_data(
			sanitize_title( $_POST['page']['id'] ),
			$_POST['page']
		);

		$success === false ? wp_send_json_error() : wp_send_json_success();
	}

	public function ajax_delete_page() {
		if ( empty ( $_POST['page_id'] ) ) {
			wp_send_json_error();
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Social_Link_Pages()->plugin_name_friendly ) ) {
			wp_send_json_error();
		}

		$post_id = sanitize_title( $_POST['page_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			wp_send_json_error();
		}

		$permission_check = apply_filters(
			Social_Link_Pages()->plugin_name_friendly . '_delete_page_permission_check',
			true,
			$post
		);

		if ( ! $permission_check ) {
			wp_send_json_error();
		}

		wp_trash_post( $post_id );

		wp_send_json_success();
	}

	public function ajax_MailChimp_get_lists() {

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], Social_Link_Pages()->plugin_name_friendly ) ) {
			wp_send_json_error();
		}

		if ( empty( $_POST['api'] ) ) {
			wp_send_json_error();
		}

		// get domain from api key
		$APIKey    = sanitize_title( $_POST['api'] );
		$APIKeyArr = explode( '-', $APIKey );
		$domain    = end( $APIKeyArr );

		if ( empty( $domain ) ) {
			wp_send_json_error();
		}

		$response = wp_remote_get(
			sprintf( 'https://%s.api.mailchimp.com/3.0/lists', $domain ),
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'a:' . $APIKey )
				),
			)
		);

		wp_send_json_success( json_decode( $response['body'] )->lists );
	}

	public function admin_enqueue_scripts( $hook ) {
		// Load only on ?page=mypluginname
		if ( $hook != 'toplevel_page_' . Social_Link_Pages()->plugin_name_friendly ) {
			return;
		}

		$this->enqueue_scripts();
		$this->localize_footer_vars( 'jquery' );
	}

	public function enqueue_scripts() {

		wp_enqueue_script( 'tinymce',
			Social_Link_Pages()->plugin_dir_url . 'client/tinymce/tinymce.min.js',
			array(),
			'5.1.1',
			true
		);
		wp_enqueue_media();

		$enqueued = array(
			'style'  => array(),
			'script' => array()
		);

		$styles = Social_Link_Pages()->get_asset_urls( 'admin', 'css' );

		foreach ( $styles as $style ) {
			$enqueued['style'][] = $style['name'];
			wp_enqueue_style(
				$style['name'],
				$style['url'],
				array(),
				$style['version']
			);
		}

		$scripts = Social_Link_Pages()->get_asset_urls( 'admin', 'js' );
		foreach ( $scripts as $script ) {
			$enqueued['script'][] = $script['name'];
			wp_enqueue_script(
				$script['name'],
				$script['url'],
				array(),
				$script['version'],
				true
			);
		}

		return $enqueued;
	}

	public function localize_footer_vars( $handle ) {
		$app_vars = apply_filters(
			Social_Link_Pages()->plugin_name_friendly . '_admin_footer_vars',
			array(
				'admin_ajax' => admin_url( 'admin-ajax.php' ),
				'site_url'   => trailingslashit( site_url() ),
				'branding'   => Social_Link_Pages()->get_plugin_name_formal()
			)
		);

		wp_localize_script(
			$handle,
			Social_Link_Pages()->plugin_name_friendly,
			$app_vars
		);
	}

	public function add_menu_page() {
		add_menu_page(
			__( 'Link Pages', 'textdomain' ),
			'Link Pages',
			'manage_options',
			Social_Link_Pages()->plugin_name_friendly,
			array( $this, 'render_menu_page' ),
			'dashicons-' . Social_Link_Pages()->plugin_name_friendly
		);

		add_submenu_page(
			Social_Link_Pages()->plugin_name_friendly,
			__( 'Link Pages', 'textdomain' ),
			'Link Pages',
			'manage_options',
			Social_Link_Pages()->plugin_name_friendly,
			array( $this, 'render_menu_page' )
		);
	}

	public function render_menu_page() {
		?>
        <div class="wrap">
            <div id="<?php echo Social_Link_Pages()->plugin_name_friendly ?>-root"></div>
			<?php wp_nonce_field( Social_Link_Pages()->plugin_name_friendly, Social_Link_Pages()->plugin_name_friendly . '_wpnonce' ) ?>
        </div>
		<?php
	}

	public function custom_favicon() {
		?>
        <style>
            .dashicons-<?php echo Social_Link_Pages()->plugin_name_friendly ?> {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='19' height='19' viewBox='0 0 48.15 34.8'%3E%3Cpath style='fill:%239ea3a8;' d='M11.5,17.4,0,34.8H20.2L30.3,17.4,20.2,0H0L11.5,17.4M29.1,0H24.65l10.1,17.4L24.65,34.8H29.1L39.2,17.4,29.1,0m8.95,0H33.6L43.7,17.4,33.6,34.8h4.45l10.1-17.4Z'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: center;
            }
        </style>
		<?php
	}

	public function add_action_links( $links ) {
		$link = array(
			sprintf( '<a href="%s">Settings</a>', admin_url( 'admin.php?page=' . Social_Link_Pages()->plugin_name_friendly ) ),
		);

		return array_merge( $links, $link );
	}
}

function Social_Link_Pages_Admin() {
	return Social_Link_Pages_Admin::instance();
}

add_action( 'plugins_loaded', 'Social_Link_Pages_Admin', 10 );
