<?php

//add admin page
add_action( 'admin_menu', 'wishlist_admin_menu' );

function wishlist_admin_menu() {
	add_theme_page( 
        __( 'About Wishlist', 'wishlist' ),
        __( 'About Wishlist', 'wishlist' ),
        'edit_theme_options',
        'about-wishlist',
        'wishlist_theme_info_page'   
    );

}

function wishlist_theme_info_page(){

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wishlist' ) );
	}

	$theme_info = wp_get_theme(); ?>

	<div class="wrap about-wrap theme-info-wrap">
		<h1>
			<?php 
			/* translators: 1: Theme Name 2: Version of the theme */
			echo sprintf( esc_html__( 'Welcome to %1$s - %2$s', 'wishlist' ), esc_html( $theme_info->get( 'Name' ) ), esc_html( $theme_info->get( 'Version' ) ) ); 
			?>
		</h1>

		<div class="about-text">
			<?php echo esc_html( $theme_info->get( 'Description' ) ); ?>
		</div>

		<p>
			<a href="http://demo.ithemer.com/wishlist/" class="button" target="_blank"><?php echo esc_html__( ' View Demo', 'wishlist' ); ?></a>
			<a href="https://ithemer.com/themes/wishlist-pro/" class="button button-primary" target="_blank"><?php echo esc_html__( 'Buy Pro', 'wishlist' ); ?></a>

		</p>

		<div class="feature-section has-2-columns alignleft">
			<div class="card">
				<h3><?php echo esc_html__( 'Customize Everything', 'wishlist' ); ?></h3>
				<p><?php echo esc_html__( 'Start customizing every aspect of the website with customizer.', 'wishlist' ); ?></p>
				<p><a target="_self" href="<?php echo esc_url( wp_customize_url() ); ?>" class="button button-primary"><?php echo esc_html__( 'Go to Customizer', 'wishlist' ); ?></a></p>
			</div>

			<div class="card">
				<h3><?php echo esc_html__( 'Get Support', 'wishlist' ); ?></h3>
				<p><?php echo esc_html__( 'Have any queries, feedback or suggestions?', 'wishlist' ); ?></p>
			</div>

		</div>

	</div>
	<?php
}