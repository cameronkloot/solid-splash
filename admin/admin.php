<?php

if ( !class_exists( 'Solid_Admin' ) ):

class Solid_Admin {

	function __construct() {
		
		if ( $this->is_solid_admin() ):
		
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_admin_enqueue_scripts' ) );

		endif;
	}

	function wp_admin_enqueue_scripts() {

		//: Admin Style://
		wp_enqueue_style( 'solid-splash', esc_url( SOLID_URL . 'assets/css/admin.css' ) );

		//: Dequeue other versions ://
		wp_dequeue_style( 'select2' );
        wp_deregister_style( 'select2' );
        wp_enqueue_style( 'select2', esc_url( SOLID_URL . 'assets/inc/select2/dist/css/select2.min.css' ) );

        //: Enqueue Select2 ://
        wp_dequeue_script( 'select2' );
        wp_deregister_script( 'select2' );
		wp_enqueue_script( 'select2', esc_url( SOLID_URL . 'assets/inc/select2/dist/js/select2.min.js' ), array( 'jquery' ), false, true );
	}

	private function is_solid_admin() {
		return (bool) ( isset( $_GET['post'] ) && get_post_type( sanitize_key( $_GET['post'] ) ) == self::$solid_post_type )
		|| ( isset( $_GET['post_type'] ) && $_GET['post_type'] == self::$solid_post_type );
	}



} //: END Solid_Admin class ://

new Solid_Admin();

endif;
