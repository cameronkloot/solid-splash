<?php

if ( !class_exists( 'Solid_Options' ) ):

class Solid_Options {
	
	private static $solid_post_type 	= 'solid_page';

	private static $solid_nonce_name 	= 'solid_redirect_nonce';
    private static $solid_nonce_action 	= 'solid-save-page-meta';

    private static $solid_options_meta_key = 'solid_options';

	function __construct() {
		
		//: Register Post Type ://
		add_action( 'init', array( $this, 'wp_init_register_post_type' ) );

	}

	function wp_init_register_post_type() {

		$labels = array(
			'name'                => esc_html__( 'Solid Splash', 'solid-splash' ),
			'singular_name'       => esc_html__( 'Solid Splash', 'solid-splash' ),
			'add_new'             => esc_html__( 'Add Page', 'solid-splash', 'solid-splash' ),
			'add_new_item'        => esc_html__( 'Add New Splash Page', 'solid-splash' ),
			'edit_item'           => esc_html__( 'Edit Splash Page', 'solid-splash' ),
			'new_item'            => esc_html__( 'New Splash Page', 'solid-splash' ),
			'view_item'           => esc_html__( 'View Splash Page', 'solid-splash' ),
			'search_items'        => esc_html__( 'Search splash pages', 'solid-splash' ),
			'not_found'           => esc_html__( 'No splash pages found', 'solid-splash' ),
			'not_found_in_trash'  => esc_html__( 'No splash pages found in Trash', 'solid-splash' ),
			'menu_name'           => esc_html__( 'Solid Splash', 'solid-splash' ),
			'all_items'           => esc_html__( 'Splash Pages', 'solid-splash' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'public'              => false,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 999,
			// 'menu_icon'           => $this->solid_splash_icon_url(),
			'capability_type'     => 'post',
			'register_meta_box_cb' 	=> array( $this, 'solid_page_metabox_callback' ),
			'supports'            	=> array( 'title' )
		);

		register_post_type( self::$solid_post_type, $args );
		
	}

	function solid_page_metabox_callback() {
		add_meta_box( 'solid_page_options', esc_html__( 'Options', 'solid-splash' ), array( $this, 'solid_page_metabox_output' ), self::$solid_post_type, 'normal', 'core' );
	}

	function solid_page_metabox_output( $post ) {
		wp_nonce_field( self::$solid_nonce_action, self::$solid_nonce_name );

		$post_meta = get_post_meta( $post->ID, self::$solid_options_meta_key );

		$page_id = isset( $post_meta['page_id'] ) ? $post_meta['page_id'] : '';


		?>
		<p><label for="solid-page-id"><strong>Page<span style="color:red">*</span></strong></label></p>
		<p>
		<select name="<?php echo esc_attr( self::$solid_options_meta_key ) ?>[page_id]" id="solid-page-id" class="solid-select" required="false">
			<?php if ( $page_id === '' ): ?>
				<option value="" selected="selected">Search Pages...</option>
				<option value="123">123</option>
			<?php else: ?>
				<option value="<?php echo esc_attr( $page_id ) ?>" selected="selected"><?php echo esc_html( get_the_title( $page_id ) )  ?></option>
			<?php endif; ?>
		</select>
		</p>

		<?php
	}



} //: END Solid_Options class ://

new Solid_Options();

endif;
