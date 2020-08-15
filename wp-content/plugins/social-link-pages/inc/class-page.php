<?php


class Social_Link_Pages_Page {
	private static $instance;

	private $page_data = array();

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
		add_action( 'init', array( $this, 'catch_slug' ), 0 );

		add_action( 'wp_ajax_' . Social_Link_Pages()->plugin_name_friendly . '_send_email', array(
			$this,
			'ajax_send_email'
		) );
		add_action( 'wp_ajax_nopriv_' . Social_Link_Pages()->plugin_name_friendly . '_send_email', array(
			$this,
			'ajax_send_email'
		) );
		add_action( 'wp_ajax_nopriv_' . Social_Link_Pages()->plugin_name_friendly . '_button_click', array(
			$this,
			'ajax_button_click'
		) );
		add_action( 'wp_ajax_nopriv_' . Social_Link_Pages()->plugin_name_friendly . '_MailChimp_subscribe', array(
			$this,
			'ajax_MailChimp_subscribe'
		) );
	}

	public function catch_slug() {
		if ( is_admin() ) {
			return;
		}

		$request_uri = basename( strtok( $_SERVER["REQUEST_URI"], '?' ) );

		$post = get_page_by_path( $request_uri, OBJECT, Social_Link_Pages()->plugin_name_friendly );

		if ( ! $post ) {
			return;
		}

		$this->render( $post );
		exit;
	}

	public function render( $post ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post, OBJECT );
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$page_data = Social_Link_Pages_Db()->page_data_from_post( $post );

		if ( ! $page_data ) {
			throw new Exception( 'Page data not found.' );
		}

		$this->page_data = $page_data;

		http_response_code( 200 );

		if ( ! is_user_logged_in() ) {
			// Update count.
			$page_data->pageLoads = empty( $page_data->pageLoads ) ? 1 : $page_data->pageLoads + 1;
			Social_Link_Pages_Db()->update_page_data( $page_data->id, $page_data );
		}

		try {
			unset( $page_data->email );

			if ( ! empty( $page_data->buttons ) ) {
				foreach ( $page_data->buttons as $index => $button ) {
					if ( 'MailChimp' === $button->type ) {
						if ( ! empty( $page_data->buttons[ $index ]->APIKey ) && ! empty( $page_data->buttons[ $index ]->listId ) ) {
							$page_data->buttons[ $index ]->isSet = 'true';
						}
						unset( $page_data->buttons[ $index ]->APIKey );
						unset( $page_data->buttons[ $index ]->listId );
					}

					if ( 'email' === $button->type ) {
						if ( ! empty( $button->value ) ) {
							$page_data->buttons[ $index ]->isSet = 'true';
						}
						unset( $button->value );

					}
				}
			}
		} catch ( Exception $e ) {
		}

		add_action( Social_Link_Pages()->plugin_name_friendly . '_wp_head', array( $this, 'enqueue_style' ), 10 );
		add_action( Social_Link_Pages()->plugin_name_friendly . '_wp_footer', array( $this, 'enqueue_script' ), 10 );

		include Social_Link_Pages()->plugin_dir_path . '/link-page/link-page-template.php';
		exit;

	}

	public function ajax_button_click() {
		if ( is_user_logged_in() ) {
			wp_send_json_error();
		}

		if ( empty ( $_POST['page_id'] ) || empty ( $_POST['button_id'] ) ) {
			wp_send_json_error();
		}

		$page_id = sanitize_title( $_POST['page_id'] );

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $page_id ) ) {
			wp_send_json_error();
		}

		// Get page data
		$page_data = Social_Link_Pages_Db()->page_data_from_post( $page_id );

		if ( ! $page_data ) {
			wp_send_json_error();
		}

		// find button
		$button = null;
		foreach ( $page_data->buttons as &$button ) {
			if ( $_POST['button_id'] === $button->id ) {
				$button->buttonClicks = empty( $button->buttonClicks ) ? 1 : $button->buttonClicks + 1;
				Social_Link_Pages_Db()->update_page_data( $page_id, $page_data );
				wp_send_json_success();
				break;
			}
		}

		wp_send_json_error();
	}

	public function ajax_send_email() {
		if ( empty( $_POST['email'] ) || empty ( $_POST['page_id'] ) || empty ( $_POST['message'] ) ) {
			wp_send_json_error();
		}

		$page_id = sanitize_title( $_POST['page_id'] );

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $page_id ) ) {
			wp_send_json_error();
		}

		// Get page data
		$page_data = Social_Link_Pages_Db()->page_data_from_post( $page_id );

		if ( ! $page_data ) {
			wp_send_json_error();
		}

		$button = null;
		foreach ( $page_data->buttons as $b ) {
			if ( $_POST['button_id'] === $b->id ) {
				$button = $b;
				break;
			}
		}

		if ( ! $button ) {
			wp_send_json_error();
		}

		wp_mail(
			$button->value,
			sprintf( 'Email from %s', site_url( $page_data->slug ) ),
			sanitize_textarea_field( $_POST['message'] ),
			array(
				sprintf( 'From: <%s>', sanitize_email( $_POST['email'] ) )
			)
		);

		wp_send_json_success();
	}

	public function ajax_MailChimp_subscribe() {

		if ( empty( $_POST['email'] ) || empty ( $_POST['page_id'] ) || empty ( $_POST['button_id'] ) ) {
			wp_send_json_error();
		}

		$page_id = sanitize_title( $_POST['page_id'] );

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $page_id ) ) {
			wp_send_json_error();
		}

		// Get page data
		$page_data = Social_Link_Pages_Db()->page_data_from_post( $page_id );

		if ( ! $page_data ) {
			wp_send_json_error();
		}

		// find button
		$button = null;
		foreach ( $page_data->buttons as $b ) {
			if ( $_POST['button_id'] === $b->id ) {
				$button = $b;
				break;
			}
		}

		// check api key
		if ( ! $button || empty( $button->APIKey ) ) {
			wp_send_json_error();
		}

		// get domain from api key
		$APIKeyArr = explode( '-', $button->APIKey );
		$domain    = end( $APIKeyArr );

		if ( empty( $domain ) ) {
			wp_send_json_error();
		}

		$body = array(
			'email_address' => sanitize_email( $_POST['email'] ),
			'status'        => 'subscribed'
		);

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'a:' . $button->APIKey )

			),
			'body'    => json_encode( $body ),
		);

		$response = wp_remote_post(
			sprintf(
				'https://%s.api.mailchimp.com/3.0/lists/%s/members',
				$domain,
				$button->listId
			),
			$args
		);

		wp_send_json_success();
	}

	public function enqueue_script() {
		$scripts = Social_Link_Pages()->get_asset_urls( 'link-page', 'js' );

		$this->page_data->appData = (object) array(
			'admin_ajax' => admin_url( 'admin-ajax.php' )
		);

		$app_vars = apply_filters(
			Social_Link_Pages()->plugin_name_friendly . '_page_footer_vars',
			$this->page_data
		);

		?>
        <script>
            var <?php echo Social_Link_Pages()->plugin_name_friendly ?> = <?php echo json_encode( $app_vars ) ?>;
        </script>
		<?php

		foreach ( $scripts as $script ) {
			echo $this->create_script_tag( $script );
		}
	}

	public function create_script_tag( $script ) {
		if ( empty( $script['url'] ) ) {
			return false;
		}

		if ( empty( $script['version'] ) ) {
			$script['version'] = Social_Link_Pages()->plugin_data()['Version'];
		}

		return sprintf(
			'<script type="text/javascript" src="%s?ver=%s"></script>',
			$script['url'],
			$script['version']
		);
	}

	public function enqueue_style() {

		echo $this->create_style_tag( array(
			'url' => sprintf( '%s/client/link-page/link-page.css', Social_Link_Pages()->plugin_dir_url )
		) );

		$scripts = Social_Link_Pages()->get_asset_urls( 'link-page', 'css' );

		foreach ( $scripts as $script ) {
			echo $this->create_style_tag( $script );
		}
	}

	public function create_style_tag( $script ) {
		if ( empty( $script['url'] ) ) {
			return false;
		}

		if ( empty( $script['version'] ) ) {
			$script['version'] = Social_Link_Pages()->plugin_data()['Version'];
		}

		return sprintf(
			'<link rel="stylesheet" href="%s?ver=%s" type="text/css" media="all" />',
			$script['url'],
			$script['version']
		);
	}

	public function wp_head() {
		do_action( Social_Link_Pages()->plugin_name_friendly . '_wp_head' );
	}

	public function wp_footer() {
		do_action( Social_Link_Pages()->plugin_name_friendly . '_wp_footer' );
	}
}

function Social_Link_Pages_Page() {
	return Social_Link_Pages_Page::instance();
}

Social_Link_Pages_Page();