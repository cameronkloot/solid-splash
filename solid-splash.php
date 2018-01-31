<?php
/*
Plugin Name: Solid Splash
Plugin URI: http://cameronkloot.com/plugins/solid-splash
Description: A solid splash page implementation
Version: 0.1.1
Author: Cameron Kloot
Author URI: http://cameronkloot.com
*/

if( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'SolidSplash' ) ):

class SolidSplash {

	public $solid_post_type 					= 'solid_splash_page';
	public $solid_settings_key 					= 'solid_splash_settings';

    private $solid_page_shown_cookie_key 		= 'solid_splash_page_shown';
    private $solid_page_cookie_key_prefix 		= 'solid_splash_page_';

    private $solid_nonce_name 					= 'solid_redirect_nonce';
    private $solid_nonce_action 				= 'solid-save-page-meta';

	private $solid_page_meta_key_prefix 		= '_solid_page_';
	private $solid_page_meta_key_id 			= '_solid_page_id';
	private $solid_page_meta_key_unpublish 		= '_solid_page_unpublish';
	private $solid_page_meta_key_location 		= '_solid_page_location';
	private $solid_page_meta_key_location_parts = '_solid_page_location_parts';

    private $solid_page_search_action 			= 'solid_search_pages';

	/*
	 *:	__construct
	 *:	Dummy constructor
	 */
	function __construct() {
		//: empty ://
	}

	/*
	 *:	initialize
	 *:	Construct class, register action hooks, etc.
	 */
	public function initialize() {

		//: Register Post Type ://
		add_action( 'init', 			array( $this, 'wp_init' ) );

		//: Save meta ://
		add_action( 'save_post', 		array( $this, 'wp_save_post' ) );

		//: Add settings menu page, remove slug metabox ://
		add_action( 'admin_menu', 		array( $this, 'wp_admin_menu' ) );

		//: Register settings ://
		add_action( 'admin_init', 		array( $this, 'wp_admin_init' ) );

		//: Ajax actions ://
		add_action( 'wp_ajax_' . $this->solid_page_search_action, array( $this, 'wp_ajax_solid_search_pages_callback' ) );

		if ( $this->is_solid_splash_page() ):

			//: Style ://
			add_action( 'admin_print_styles', 			array( $this, 'wp_admin_print_styles' ) );

			add_action( 'admin_enqueue_scripts', 		array( $this, 'wp_admin_enqueue_scripts' ), 100 );
			add_action( 'admin_footer', 				array( $this, 'wp_admin_footer_ajax_page_load' ) );

			add_action( 'post_submitbox_misc_actions', 	array( $this, 'wp_submitbox_misc_actions' ) );

			add_action( 'admin_footer', 				array( $this, 'wp_admin_footer_location' ) );
		endif;


		//: Maybe redirect to splash ://
		add_action( 'parse_request', 	array( $this, 'wp_parse_request' ), 0 );

	}

	function wp_init() {

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
			'menu_icon'           => $this->solid_splash_icon_url(),
			'capability_type'     => 'post',
			'register_meta_box_cb' 	=> array( $this, 'solid_splash_page_metabox_callback' ),
			'supports'            	=> array( 'title' )
		);

		register_post_type( $this->solid_post_type, $args );

		register_post_status( 'unread', array(
			'label'                     => _x( 'Unread', 'post' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>' ),
		) );
	}

	function solid_splash_page_metabox_callback() {
		add_meta_box( 'solid_splash_page_options', esc_html__( 'Options', 'solid-splash' ), array( $this, 'solid_splash_page_metabox_output' ), $this->solid_post_type, 'normal', 'core' );
	}

	public function solid_splash_page_metabox_output( $post ) {
		wp_nonce_field( $this->solid_nonce_action, $this->solid_nonce_name );

		$page_id = get_post_meta( $post->ID, $this->solid_page_meta_key_id, true );

		?>
		<p><label for="solid-page-id"><strong>Page<span style="color:red">*</span></strong></label></p>
		<p>
		<select style="width:100%;" name="<?php echo esc_attr( $this->solid_page_meta_key_id ) ?>" id="solid-page-id" class="solid-select" required="true">
			<?php if ( $page_id === '' ): ?>
				<option value="" selected="selected">Search Pages...</option>
			<?php else: ?>
				<option value="<?php echo esc_attr( $page_id ) ?>" selected="selected"><?php echo esc_html( get_the_title( $page_id ) )  ?></option>
			<?php endif; ?>
		</select>
		</p>

		<?php if ( $this->get_plugin_option( 'maxmind_user_id' ) !== '' && $this->get_plugin_option( 'maxmind_license_key' ) !== '' ): ?>
		<p><label for="solid-page-location"><strong>Location</strong></label></p>
		<p><input id="solid-page-location" class="widefat" type="text" placeholder="Search Locations..." name="<?php echo esc_attr( $this->solid_page_meta_key_location ) ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $this->solid_page_meta_key_location, true ) ) ?>"></p>
		<p><input id="solid-page-location-parts" name="<?php echo esc_attr( $this->solid_page_meta_key_location_parts ) ?>" type="hidden" value="<?php echo esc_attr( get_post_meta( $post->ID, $this->solid_page_meta_key_location_parts, true ) ) ?>"></p>
		<?php endif; ?>

		<?php

	}

	function wp_submitbox_misc_actions( $post ) {

		$unpublish = get_post_meta( get_the_id(), $this->solid_page_meta_key_unpublish, true );
		$date = $unpublish === '' ? $date = 'never' : $unpublish;

		$stamp = 'Unpublish ';
		$datef = esc_html__( 'M j, Y @ H:i' );
		$select_date = current_time( 'Y-m-d H:i:s', 0 );
		if ( $date !== 'never' ) {
			$select_date = date( 'Y-m-d H:i:s', strtotime( $date ) );
			$date = date_i18n( $datef, strtotime( $date ) );
			$stamp = 'Unpublish on: ';
		}

		$mm = mysql2date( 'm', $select_date, false );
		$jj = mysql2date( 'd', $select_date, false );
		$aa = mysql2date( 'Y', $select_date, false );
		$hh = mysql2date( 'H', $select_date, false );
		$mn = mysql2date( 'i', $select_date, false );

		?>
		<div class="misc-pub-section curtime unpubtime misc-pub-unpubtime">
			<?php wp_nonce_field( $this->solid_nonce_action, $this->solid_nonce_name ); ?>
			<input type="hidden" id="unpublish" name="<?php echo esc_attr( $this->solid_page_meta_key_unpublish ) ?>" value="<?php echo esc_attr( $unpublish ) ?>">
			<span id="timestamp" class="unpubtimestamp">
			<?php echo esc_html( $stamp ) ?><b><?php echo esc_html( $date ) ?></b>
			</span>
			<a href="#edit_unpubtimestamp" class="edit-unpubtimestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span></a>

			<fieldset id="unpubtimestampdiv" class="hide-if-js" style="display: none;">
				<div class="unpubtimestamp-wrap">
				<label><select id="mm" name="unpub[mm]" style="height: 21px;line-height: 14px;padding: 0;vertical-align: top;font-size: 12px;">
					<option value="01"<?php selected( '01', $mm, true ) ?>>01-Jan</option>
					<option value="02"<?php selected( '02', $mm, true ) ?>>02-Feb</option>
					<option value="03"<?php selected( '03', $mm, true ) ?>>03-Mar</option>
					<option value="04"<?php selected( '04', $mm, true ) ?>>04-Apr</option>
					<option value="05"<?php selected( '05', $mm, true ) ?>>05-May</option>
					<option value="06"<?php selected( '06', $mm, true ) ?>>06-Jun</option>
					<option value="07"<?php selected( '07', $mm, true ) ?>>07-Jul</option>
					<option value="08"<?php selected( '08', $mm, true ) ?>>08-Aug</option>
					<option value="09"<?php selected( '09', $mm, true ) ?>>09-Sep</option>
					<option value="10"<?php selected( '10', $mm, true ) ?>>10-Oct</option>
					<option value="11"<?php selected( '11', $mm, true ) ?>>11-Nov</option>
					<option value="12"<?php selected( '12', $mm, true ) ?>>12-Dec</option>
			</select></label>
			<label>	<input 		type="text" id="jj" 		name="unpub[jj]" value="<?php echo esc_attr( $jj ) ?>" 	size="2" maxlength="2" autocomplete="off"></label>
			,<label><input 		type="text" id="aa" 		name="unpub[aa]" value="<?php echo esc_attr( $aa ) ?>"	size="4" maxlength="4" autocomplete="off"></label>
			 @ <label><input 	type="text" id="hh" 		name="unpub[hh]" value="<?php echo esc_attr( $hh ) ?>" 	size="2" maxlength="2" autocomplete="off"></label>
			:<label><input 		type="text" id="mn" 		name="unpub[mn]" value="<?php echo esc_attr( $mn ) ?>" 	size="2" maxlength="2" autocomplete="off"></label>
			<input type="hidden" id="ss" name="unpub[ss]" value="00">
			</div>
				<p>
				<a href="#edit_unpubtimestamp" class="save-unpubtimestamp hide-if-no-js button">OK</a>
				<a href="#edit_unpubtimestamp" class="never-unpubtimestamp hide-if-no-js button-never" style="margin-left:4px;">Never</a>
				<a href="#edit_unpubtimestamp" class="cancel-unpubtimestamp hide-if-no-js button-cancel">Cancel</a>
				</p>
			</fieldset>
		</div>
		<script type="text/javascript">
		;(function($) {
			var $unpubtimestampdiv = $('#unpubtimestampdiv');

			$('.edit-unpubtimestamp').click(function(e){
				e.preventDefault();
				$unpubtimestampdiv.slideDown('fast');
				$(this).hide();
			});
			$('.cancel-unpubtimestamp').click(function(e){
				e.preventDefault();
				$unpubtimestampdiv.slideUp('fast');
				$('.edit-unpubtimestamp').show();
			});
			$('.button-never').click(function(e){
				e.preventDefault();
				$('#unpublish').val('');
				$('.cancel-unpubtimestamp').trigger('click');
				// $('.unpubtimestamp b').text('never');
				$('#publishing-action input[type="submit"]').trigger('click');
			});

			$('.save-unpubtimestamp').click(function(e){
				e.preventDefault();
				setUnpublishValue();
				$('#publishing-action input[type="submit"]').trigger('click');
			});

			function setUnpublishValue() {
				var dateArray = {
					mm : $('#unpubtimestampdiv #mm').val(),
					jj : $('#unpubtimestampdiv #jj').val(),
					aa : $('#unpubtimestampdiv #aa').val(),
					hh : $('#unpubtimestampdiv #hh').val(),
					mn : $('#unpubtimestampdiv #mn').val(),
					ss : $('#unpubtimestampdiv #ss').val(),
				}
				$('#unpublish').val(JSON.stringify(dateArray));
			}
		})(jQuery);
		</script>
		<?php
	}

	public function wp_save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
		if ( 'revision' == get_post_type( $post_id ) )
			return;

		if ( !empty( $_POST[$this->solid_nonce_name] ) && wp_verify_nonce( sanitize_key( $_POST[$this->solid_nonce_name] ), $this->solid_nonce_action ) ) {

			//: Page ID ://
			if ( isset( $_POST[$this->solid_page_meta_key_id] ) ) {
				if ( get_post( sanitize_key( $_POST[$this->solid_page_meta_key_id] ) ) !== null ) {
					update_post_meta( $post_id, $this->solid_page_meta_key_id, sanitize_key( $_POST[$this->solid_page_meta_key_id] ) );
				}
			}

			//: Location ://
			if ( isset( $_POST[$this->solid_page_meta_key_location] ) ) {
				update_post_meta( $post_id, $this->solid_page_meta_key_location, sanitize_text_field( $_POST[$this->solid_page_meta_key_location] ) );
			}

			//: Location Parts ://
			if ( isset( $_POST[$this->solid_page_meta_key_location_parts] ) ) {

				update_post_meta( $post_id, $this->solid_page_meta_key_location_parts, sanitize_text_field( $_POST[$this->solid_page_meta_key_location_parts] ) );
			}

			//: Unpublish Time ://
			if ( isset( $_POST[$this->solid_page_meta_key_unpublish] ) ) {
				if ( $_POST[$this->solid_page_meta_key_unpublish] === '' ) {
					update_post_meta( $post_id, $this->solid_page_meta_key_unpublish, '' );
				}
				else {
					$date_array = json_decode( str_replace( '\\', '', $_POST[$this->solid_page_meta_key_unpublish] ), true );

					if ( is_array( $date_array ) && count( $date_array ) == 6 ) {

						if ( isset( $date_array['aa'] ) && isset( $date_array['mm'] ) &&
							 isset( $date_array['jj'] ) && isset( $date_array['hh'] ) &&
							 isset( $date_array['mn'] ) && isset( $date_array['ss'] ) ) {

							$date_string = sprintf( '%1$s-%2$s-%3$s %4$s:%5$s:%6$s',
								preg_replace( '/[^0-9]/', '', $date_array['aa'] ),
								preg_replace( '/[^0-9]/', '', $date_array['mm'] ),
								preg_replace( '/[^0-9]/', '', $date_array['jj'] ),
								preg_replace( '/[^0-9]/', '', $date_array['hh'] ),
								preg_replace( '/[^0-9]/', '', $date_array['mn'] ),
								preg_replace( '/[^0-9]/', '', $date_array['ss'] ) );

							update_post_meta( $post_id, $this->solid_page_meta_key_unpublish, sanitize_text_field( $date_string ) );
						}
					}
				}

			}
		}
    }


	function wp_admin_menu() {
		remove_meta_box( 'slugdiv', 'solid_splash_page', 'normal' );

		add_submenu_page( 'edit.php?post_type=' . $this->solid_post_type, 'Solid Splash Settings', 'Settings', 'manage_options', 'solid_splash_settings', array( $this, 'settings_page' ) );
	}

	function settings_page(  ) {

		?>
		<div class="wrap" id="solid_splash_settings">
			<form action='options.php' method='post'>
				<h1>Solid Splash</h1>
				<?php
					settings_fields( 'solid_settings_page' );
					do_settings_sections( 'solid_settings_page' );
					submit_button();
				?>
			</form>
		</div>
		<?php

	}

	//: wp_admin_init ://

	function wp_admin_init(  ) {

		register_setting( 'solid_settings_page', 'solid_splash_settings' );


		add_settings_section( 'solid_settings_section_general', 'General', '', 'solid_settings_page' );

		add_settings_field( 'solid_enable_splash', 			esc_html__( 'Enable Solid Splash', 	'solid-splash' ), array( $this, 'solid_enable_splash_checkbox_render' ), 	'solid_settings_page', 'solid_settings_section_general' );


		add_settings_section( 'solid_settings_section_location', 'Location', '', 'solid_settings_page' );

		add_settings_field( 'solid_maxmind_service', 		esc_html__( 'Maxmind Service', 		'solid-splash' ), array( $this, 'solid_maxmind_service_render_field' ), 	'solid_settings_page', 'solid_settings_section_location' );
		add_settings_field( 'solid_maxmind_user_id', 		esc_html__( 'Maxmind User ID', 		'solid-splash' ), array( $this, 'solid_maxmind_user_id_render_field' ), 	'solid_settings_page', 'solid_settings_section_location' );
		add_settings_field( 'solid_maxmind_license_key', 	esc_html__( 'Maxmind License Key', 	'solid-splash' ), array( $this, 'solid_maxmind_license_key_render_field' ), 'solid_settings_page', 'solid_settings_section_location' );

	}

	function solid_enable_splash_checkbox_render(  ) {
		?>
		<input type="checkbox" name="<?php echo esc_attr( $this->solid_settings_key ) ?>[enable_splash]" <?php checked( $this->get_plugin_option( 'enable_splash' ), 'true' ) ?> value="true">
		<?php
	}

	function solid_maxmind_service_render_field() {
		?>
		<select name="<?php echo esc_attr( $this->solid_settings_key ) ?>[maxmind_service] ?>">
			<option value="city" <?php selected( $this->get_plugin_option( 'maxmind_service' ), 'city' ) ?>>City</option>
			<option value="insights" <?php selected( $this->get_plugin_option( 'maxmind_service' ), 'insights' ) ?>>Insights</option>
		</select>
		<?php
	}

	function solid_maxmind_user_id_render_field() {
		?>
		<input type="text" name="<?php echo esc_attr( $this->solid_settings_key ) ?>[maxmind_user_id]" value="<?php echo esc_attr( $this->get_plugin_option( 'maxmind_user_id' ) ) ?>">
		<?php
	}

	function solid_maxmind_license_key_render_field() {
		?>
		<input type="text" name="<?php echo esc_attr( $this->solid_settings_key ) ?>[maxmind_license_key]" value="<?php echo esc_attr( $this->get_plugin_option( 'maxmind_license_key' ) ) ?>">
		<?php
	}

	function wp_admin_print_styles() {
		if ( $this->is_solid_splash_page() ):
		?>
		<style type="text/css">
		.misc-pub-visibility,
		.inline-edit-col-left .inline-edit-col > label + label,
		.inline-edit-col-left .inline-edit-col > .inline-edit-group {
			display: none;
		}
		div.select2-container--default .select2-selection--single .select2-selection__rendered {
			line-height: 27px;
		}
		</style>
		<?php
		endif;
	}

	function wp_admin_enqueue_scripts() {
		//: Dequeue other plugins ://
		wp_dequeue_style( 'select2' );
        wp_deregister_style( 'select2' );
        wp_enqueue_style( 'select2', esc_url( $this->plugin_dir( 'inc/vendor/select2/select2.min.css' ) ) );

        wp_dequeue_script( 'select2' );
        wp_deregister_script( 'select2' );
		wp_enqueue_script( 'select2', esc_url( $this->plugin_dir( 'inc/vendor/select2/select2.min.js' ) ), array( 'jquery' ), false, true );
	}

	function wp_ajax_solid_search_pages_callback() {

		$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';

		if ( $search === '' ) return;

		$args = array(
			'post_type'			=> 'page',
			'post_status'		=> 'publish',
			'posts_per_page'	=> 50,
			's'					=> $search
		);

		$wp_query = new WP_Query( $args );

		$results = array();

    	if ( $wp_query->have_posts() ) {
    		while ( $wp_query->have_posts() ): $wp_query->the_post();
            	$results[] = array(
            		'id'	=> get_the_id(),
            		'text'	=> get_the_title()
        		);
        	endwhile;
        }
    	else {
    		$results[] = array(
    			'id'	=> '',
    			'text'	=> 'No pages found'
    		);
    	}

		wp_send_json( array(
			'results'	=> $results
		) );

		die();
	}

	function wp_admin_footer_ajax_page_load() {
		?>
		<script type="text/javascript">
		;(function($) {
			$(document).ready(function($) {
				$('#solid-page-id').select2({
					minimumInputLength: 3,
					delay: 250,
					ajax: {
						url: ajaxurl,
						data : function(params){
								return {
								action	: <?php echo wp_json_encode( $this->solid_page_search_action ) ?>,
								search 	: params.term,
	    					};
						},
						processResults: function (data, params) {
							return {
								results: data.results,
							};
						}
					}
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	function wp_admin_footer_location() {
		?>
		<script type="text/javascript">
		;(function($) {
			$.getScript('https://www.google.com/jsapi', function(){

				// load maps
			    google.load('maps', '3', { other_params: 'sensor=false&libraries=places', callback: function(){

			    	var placeSelected = true;
			    	addEmoj();

			    	$('#solid-page-location').data('oldVal', $('#solid-page-location').val() );
					var searchBox = new google.maps.places.SearchBox(document.getElementById('solid-page-location'));
					searchBox.addListener('places_changed', function() {
						var places = searchBox.getPlaces();
						if ( places[0]['address_components'] ) {
							$('#solid-page-location-parts').val(JSON.stringify(places[0]['address_components']));
							placeSelected = true;
						}
						else {
							placeSelected = false;
						}
						addEmoj();

  					});

  					$('#solid-page-location').on('focusout', function(){
  						if($(this).val()=='') {
  							placeSelected = true;
  							addEmoj();
  						}
  					});

					$('#solid-page-location').on("propertychange change click keyup input paste", function(event){
						if ($(this).val().length == 0 || $(this).data('oldVal') == $(this).val()) {
							placeSelected = true;
						}
						else {
							placeSelected = false;
						}
						addEmoj();
					});

  					$('#solid-page-location').on('keydown', function(e) {
  						if(e.which == 13) {
  							e.preventDefault();
  						}
					});

					function addEmoj() {
						$('#check').remove();
						$('#ex').remove();

						if (placeSelected) {
							$('#solid-page-location').parent().prepend('<span id="check" style="font-weight:bold;font-size20px;color:green;float:right;margin:3px 5px -22px 0;position:relative;z-index:99;">:-)</span>');
						}
						else {
							$('#solid-page-location').parent().prepend('<span id="ex" style="font-weight:bold;font-size20px;color:red;float:right;margin:4px 5px -23px 0;position:relative;z-index:99;">:-(</span>');
						}
					}
			    }});

			});
		})(jQuery);
		</script>
		<?php
	}

	//: wp_parse_request ://

	function wp_parse_request() {

		//: Front end only ://
		if ( is_admin() || in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) )
			return;

		if ( $this->get_plugin_option( 'enable_splash' ) !== 'true' )
			return;

		//: Don't redirect bots ://
		if ( $this->is_user_agent_bot() )
			return;

		//: Only show on home page (add option to change this) ://
		if ( !$this->is_home_page() )
			return;

		//: Checks if a page has been shown recently ://
		if ( $this->cookie_exists( $this->solid_page_shown_cookie_key, 'true' ) )
			return;

		$args = array(
			'post_type'			=> $this->solid_post_type,
			'post_status'		=> 'publish',
			'posts_per_page'	=> 50, //use pagination if needed. not sure about use case. prob limit more based on up/down dates
			'orderby'			=> 'date',
			'order'				=> 'DESC',
		);

		$splash_query = new WP_Query( $args );

		$splash_pages = array();

		foreach ( $splash_query->posts as $splash_page ) {

			$splash_id = $splash_page->ID;

    		$page_id = get_post_meta( $splash_id, $this->solid_page_meta_key_id, true );
    		$page_url = get_permalink( $page_id );

    		if ( $page_url === false ) continue;

    		//: Check if should be unpublished ://
    		//: Will add CRON at some point ://
    		$unpub = get_post_meta( $splash_id, $this->solid_page_meta_key_unpublish, true );
    		if ( $unpub !== '' ) {
    			$now = current_time( 'timestamp', 0 );
    			if ( $now > strtotime( $unpub ) ) {
					wp_update_post( array(
						'ID'			=> $splash_id,
						'post_status'	=> 'draft'
					) );
    				continue;
    			}
    		}

    		//: Has this page been shown before ://
    		if ( !$this->cookie_exists( $this->solid_page_cookie_key_prefix . $page_id, $page_url ) ) {

    			//: Check if location page ://

    			$condition = get_post_meta( $splash_id, '_solid_page_location', true ) === '' ? 'none' : 'location';

    			$splash_pages[$condition][] = array(
    				'splash_id'	=> $splash_id,
    				'page_id'	=> $page_id,
    				'page_url'	=> $page_url,
    			);

    		}
		}
    	wp_reset_query();

    	if ( count( $splash_pages ) > 0 ) {

    		//: If location condition exists, get user location ://
    		if ( isset( $splash_pages['location'] ) ) {

				foreach ( $splash_pages['location'] as $splash_page ) {

					$location_body = $this->get_maxmind_location_data();

					if ( $location_body === false ) break;

    				if ( $this->should_show_location_page( $splash_page['splash_id'], $location_body ) ) {
    					$this->redirect_to_splash_page( $splash_page['page_id'], $splash_page['page_url'] );

    				}
    			}

    		}

    		//: If no location page shown, check if other pages exist ://
    		if ( isset( $splash_pages['none'] ) ) {
    			$splash_page = $splash_pages['none'][0];
    			$this->redirect_to_splash_page( $splash_page['page_id'], $splash_page['page_url'] );
    		}

    	}

	}


	//: Helper Functions ://

	private function redirect_to_splash_page( $page_id, $page_url ) {
		//: Has now seen splash page, show again after set time ://
		$this->set_cookie( $this->solid_page_cookie_key_prefix . $page_id, $page_url, strtotime( '+30 days' ) );

		//: Don't show next splash page for a set time ://
		$this->set_cookie( $this->solid_page_shown_cookie_key, 'true', strtotime( '+1 hour' ) );

		//: Redirect to Splash Page ://
		wp_safe_redirect( $page_url );
    	exit();
	}

	private function should_show_location_page( $splash_id, $location_body ) {
		$location = get_post_meta( $splash_id, $this->solid_page_meta_key_location, true );
		$parts = json_decode( get_post_meta( $splash_id, $this->solid_page_meta_key_location_parts, true ), true );

		//: Maybe add location string parsing but for now, return if parts isn't set ://
		if ( !is_array( $parts ) || count( $parts ) == 0 ) return false;

		$location_parts = array();

		foreach ( $parts as $part ) {

			$location_part = array();

			$location_part['name'] = $part['long_name'];
			if ( $part['long_name'] != $part['short_name'] )
				$location_part['abr'] = $part['short_name'];

			if ( count( $part['types'] ) > 0 ) {
				$location_part['type'] = $part['types'][0];
				$location_parts[$part['types'][0]] = $location_part;
			}
		}

		//: If we don't have country, something's wrong ://
		if( !isset( $location_parts['country']['abr'] ) ) return false;

		//: Check CF country so we can exit early if not in the US ://
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			if ( isset( $location_parts['country']['abr'] ) && $_SERVER['HTTP_CF_IPCOUNTRY'] != $location_parts['country']['abr'] ) {
				return false;
			}
		}

		try {

		    //: Check country ://
		    if ( isset( $location_body['country']['iso_code'] ) && $location_body['country']['iso_code'] != $location_parts['country']['abr'] ) {
		    	return false;
		    }

		    if ( isset( $location_body['subdivisions'] ) && count( $location_body['subdivisions'] > 0 ) ) {
		    	$iso_code = $location_body['subdivisions'][0]['iso_code'];
		    	foreach ( $location_parts as $part ) {
		    		if ( isset( $part['abr'] ) && $part['abr'] == $iso_code ) {
		    			return true;
		    		}
		    	}
		    }
		}
		catch (Exception $e) {
			return false;
		}
		return false;
	}

	private function get_maxmind_location_data() {

		$maxmind_service 		= $this->get_plugin_option( 'maxmind_service' );
		$maxmind_user_id 		= $this->get_plugin_option( 'maxmind_user_id' );
		$maxmind_license_key 	= $this->get_plugin_option( 'maxmind_license_key' );

		//: Is API Info set? ://
		if ( $maxmind_user_id === '' || $maxmind_license_key === '' ) return false;

		//: Get client IP ://
		$client_ip = isset($_SERVER['HTTP_CF_CONNECTING_IP'] ) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

		//: Build maxmind URL ://
		$url = sprintf( 'https://%s:%s@geoip.maxmind.com/geoip/v2.1/%s/%s?pretty', $maxmind_user_id, $maxmind_license_key, $maxmind_service, $client_ip );

		//: Get maxmind data ://
		//: Eventually add MaxMind rate limit reached warning ://

		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get( $url, false, 5, 3, 30 );
		}
		else {
			$response = wp_remote_get( $url, array(
		    	'timeout'	=> 3
		    ) );
		}

		if ( is_wp_error( $response ) || !isset( $response['response']['code'] ) || $response['response']['code'] != 200 )
		    return false;

		$location_body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $location_body ) )
	    	return false;

	    $location_body = json_decode( $location_body, true );

	    return $location_body;
	}

	private function get_plugin_option( $option_name ) {
		$options = get_option( $this->solid_settings_key );
		if ( isset( $options[$option_name] ) )
			return $options[$option_name];
		return '';
	}

	private function is_user_agent_bot() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	private function is_home_page() {
		return untrailingslashit( get_home_url() ) === untrailingslashit( $this->get_request_protocol() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	}

	private function get_request_protocol() {
		// http://stackoverflow.com/questions/1175096/how-to-find-out-if-youre-using-https-without-serverhttps
		// http://stackoverflow.com/questions/14985518/cloudflare-and-logging-visitor-ip-addresses-via-in-php

		$is_secure = false;
		if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			$cf_visitor = json_decode( $_SERVER['HTTP_CF_VISITOR'] );
			if ( $cf_visitor->scheme == 'https' ) $is_secure = true;
		}
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		    $is_secure = true;
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
		    $is_secure = true;
		}
		return $is_secure === true ? 'https://' : 'http://';
	}

	private function cookie_exists( $key, $value ) {
		if ( isset( $_COOKIE[$key] ) && !empty( $_COOKIE[$key] && $_COOKIE[$key] == $value ) )
			return true;
		return false;
	}

	private function set_cookie( $key, $value, $expire ) {
		setcookie( sanitize_key( $key ), sanitize_text_field( $value ), intval( $expire ) );
	}

	private function is_solid_splash_page() {
		return (bool) ( isset( $_GET['post'] ) && get_post_type( sanitize_key( $_GET['post'] ) ) == $this->solid_post_type )
		|| ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->solid_post_type );
	}

	private function plugin_dir( $file = '' ) {
		return trailingslashit( plugin_dir_url( __FILE__ )  ) . untrailingslashit( $file );
	}

	private function solid_splash_icon_url() {
		return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxOS4xLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB2aWV3Qm94PSIwIDAgMzEwIDMxMCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMzEwIDMxMDsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4NCgkuc3Qwe2ZpbGw6I0ZGRkZGRjt9DQo8L3N0eWxlPg0KPGcgaWQ9InpFcHVhTC50aWYiPg0KCTxnPg0KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTUzLjYsMjk3LjFjLTYuMS01LjQtNS41LTEyLjUtNC43LTE5LjVjMS4xLTEwLjEsMi43LTIwLjEsNC4xLTMwLjJjMC42LTQsMC41LTgtMC42LTExLjkNCgkJCWMtMC43LTIuNi0yLjItNC42LTUtNC45Yy0yLjktMC40LTQuNywxLjQtNiwzLjdjLTEuNiwyLjgtMiw2LTIuNyw5LjFjLTAuNywzLjQtMS4zLDYuOS0zLjEsMTBjLTEuNywyLjgtNCw0LjUtNy41LDMuNQ0KCQkJYy0zLjctMS4xLTUuMS0zLjktNC40LTcuNGMwLjgtNC4xLDIuMi04LDMuMy0xMmMyLjEtNy45LDEuNS0xMC43LTIuOS0xMy4yYy02LjgtNC0xNC4xLTMuNi0xOSwxLjJjLTMuNCwzLjMtNS42LDcuNS03LjIsMTEuOQ0KCQkJYy0yLjgsNy43LTUuMiwxNS41LTcuOSwyMy4yYy0xLjUsNC4zLTMuMSw4LjUtNiwxMmMtNC4yLDUuMS05LjUsNS4yLTE1LjQsMC42Yy01LTQtNS42LTguMS0xLjYtMTMuNGM0LjYtNi4yLDEwLjktMTAuNywxNi44LTE1LjYNCgkJCWM2LjItNS4zLDEyLjYtMTAuNCwxNy41LTE3LjFjNS4xLTYuOSw1LjktMTUuNywyLTIxLjVjLTIuMi0zLjMtNC41LTMuNi03LjQtMC45Yy0zLjYsMy4zLTUuOSw3LjYtOC45LDExLjQNCgkJCWMtMi4yLDIuOC00LjMsNS41LTcuMiw3LjZjLTMuMiwyLjQtNi40LDIuNC05LjcsMGMtNS41LTQuMS01LjUtOC43LDAuNC0xMi4yYzUuMy0zLjEsMTEuMi01LjIsMTYuNS04LjNjNi42LTMuOCw2LjgtOC44LDEuNC0xNC4yDQoJCQljLTIuOS0yLjktNS45LTMuMS04LjktMC43Yy0zLjgsMy03LjIsNi40LTEwLjksOS41Yy0yLDEuNy00LjQsMy45LTYuOSwxLjJjLTIuNS0yLjYtMi40LTUuNywwLjEtOC42YzEuNy0yLDQtMyw2LjMtNA0KCQkJYzQuMy0xLjgsOC43LTMuMywxMi44LTUuNWM1LjEtMi43LDYuNi02LjYsNS4zLTEyLjJjLTEtNC41LTQuNS02LjktMTAuMS03LjNjLTkuNS0wLjUtMTguNCwyLjUtMjcuNCw0LjgNCgkJCWMtNy4xLDEuOS0xNC4xLDQuMS0yMS40LDQuN2MtNi4yLDAuNS05LjktMS45LTEwLjQtNi43Yy0wLjUtNS4xLDIuNy05LjEsOC41LTkuOGM4LjUtMS4xLDE3LDAuMSwyNS40LDEuM2M3LjMsMSwxNC41LDIsMjEuOSwxLjQNCgkJCWM0LjktMC40LDkuNy0xLjMsMTIuNi01LjljMi42LTQuMSwxLjUtNy40LTMuMS05Yy03LjMtMi42LTE1LTIuOC0yMi42LTMuNmMtOC42LTEtMTcuMy0xLjItMjUuOC0zYy0yLjEtMC40LTQuMi0xLTYuMi0xLjgNCgkJCWMtMi4xLTAuOC00LjEtMi4xLTMuNS00LjhjMC41LTIuNSwyLjUtMy40LDQuOC0zLjdjNS41LTAuNywxMSwwLjIsMTYuMywxLjRjOC4zLDIsMTYuNSwzLjksMjQuOCw1LjljMy44LDAuOSw3LjYsMC45LDExLjQsMC44DQoJCQljMi4yLTAuMSw0LjQtMC40LDUuNC0yLjhjMS4xLTIuNCwwLjItNC42LTEuMy02LjVjLTEuNi0yLTMuNi0zLjQtNS43LTQuOWMtMy41LTIuNS03LjItNC45LTEwLjQtNy43Yy0yLjctMi4zLTQtNS40LTEuNi04LjkNCgkJCWMyLjQtMy42LDUuNy0zLjgsOS4yLTEuOGMzLjYsMiw3LjEsNC40LDEwLjcsNi41YzMuNCwyLDYuOSwyLjgsOS45LTAuOGMyLjktMy41LDAuOC02LjYtMS40LTkuNGMtMi4yLTIuOS00LjktNS41LTctOC40DQoJCQljLTItMi43LTIuMy01LjUsMC41LThjMi44LTIuNiw1LjktMi40LDguNiwwYzIuMywxLjksNC4yLDQuMyw2LjIsNi41YzIuMiwyLjUsNCw1LjMsNi4zLDcuOGMyLjEsMi4zLDQuNCw1LjEsOC4xLDMuNg0KCQkJYzQuMS0xLjYsNi4zLTQuOSw2LjUtOS4zYzAuMi00LjktMS45LTkuMS00LjEtMTMuMmMtNC4xLTcuNC05LjUtMTQuMS0xNC40LTIxYy0zLjEtNC4zLTYuMy04LjYtNy44LTEzLjkNCgkJCWMtMS40LTUuMSwwLjItOC42LDQuNy0xMC42YzQuNy0yLjEsOS0wLjksMTEuNiwzLjZjMi41LDQuNCwzLjUsOS4zLDQuNSwxNC4yYzEuOSw5LDIuNywxOC4xLDYsMjYuOGMxLjcsNC4zLDQsOC4xLDguNSwxMA0KCQkJYzMuMSwxLjMsNi4yLDEuNyw5LTAuNWMyLjYtMi4xLDItNSwxLjMtNy45Yy0xLTQuMS0yLTguMS0yLjctMTIuMmMtMC44LTQuNSwxLjItNy42LDQuOC03LjljMy42LTAuMyw2LjEsMi4yLDYsNi45DQoJCQljLTAuMSw0LjUtMC43LDktMC45LDEzLjRjLTAuMSwzLjYsMC40LDcuMyw0LjksOGM0LjQsMC43LDYuMy0yLjQsNy40LTZjMS4zLTQuMSwyLjEtOC40LDMuNS0xMi41YzEuMS0zLjQsMi45LTYuNyw3LjQtNS45DQoJCQljMy44LDAuNyw1LjksNC4xLDUsOC45Yy0wLjcsNC4zLTIsOC40LTIuOSwxMi43Yy0wLjUsMi40LTEuMSw0LjktMS4zLDcuNGMtMC4yLDIuNywwLjgsNSwzLjUsNi4xYzIuNSwxLDQuNS0wLjIsNi4zLTEuOQ0KCQkJYzMuOC0zLjcsNS45LTguNSw4LjYtMTIuOGMxLjktMy4xLDMuNi02LjQsNi40LTguOWMyLjMtMiw0LjgtMy4xLDcuNy0xLjNjMywxLjksMy41LDQuNywyLjUsNy45Yy0xLjYsNS00LjgsOS4xLTcuNCwxMy42DQoJCQljLTEuOSwzLjMtNC4xLDYuNS00LjYsMTAuNGMtMC4zLDIuMi0wLjEsNC41LDIuMSw1LjdjMi4xLDEuMywzLjktMC4xLDUuNC0xLjNjNS41LTQuNSw5LjUtMTAuMywxNC0xNS42DQoJCQljNC4zLTUuMSw4LjQtMTAuNCwxNC4yLTEzLjljMy41LTIuMSw3LTIuOSwxMC44LTAuNWM1LjgsMy43LDYuNyw5LjgsMS45LDE0LjdjLTIuOSwzLTYuNiw1LjEtMTAuMyw3Yy02LjQsMy4zLTEyLjgsNi41LTE5LDEwLjENCgkJCWMtMi4zLDEuMy01LjIsMy40LTMuNiw2LjhjMS42LDMuMyw0LjgsNC42LDguNCw0YzMuOC0wLjYsNy4zLTIuNCwxMC44LTMuOGMzLjYtMS40LDcuMi0yLjEsOS40LDIuNGMxLjgsMy43LDAuNSw3LjItNCw5LjMNCgkJCWMtNC4zLDIuMS05LDMuNS0xMy40LDUuNGMtNS45LDIuNS03LjUsNS40LTYsMTAuM2MxLjgsNS43LDUuNCw3LjUsMTEuNyw2LjFjNi42LTEuNSwxMi40LTQuNywxOC4zLTcuOWMzLjgtMi4xLDcuNy00LDEyLTUNCgkJCWM1LjQtMS4zLDguNywwLjksOS45LDYuM2MwLjQsMS45LDAuNSw0LDAuNSw2YzAuMSw3LjItMywxMC42LTEwLjMsMTAuNGMtNS0wLjEtOS45LTEuMS0xNC45LTEuNWMtNi0wLjUtMTIuMS0wLjYtMTcsMy45DQoJCQljLTUsNC42LTQuNSw5LjEsMS41LDEyLjJjNi43LDMuNSwxNC4zLDQuNSwyMS41LDYuMmM3LDEuNiwxNC4yLDIuMywyMC44LDUuMWMzLjIsMS40LDYuOSwyLjcsNS40LDcuNWMtMS4yLDMuOC00LjYsNS4xLTkuNSwzLjgNCgkJCWMtNi45LTEuOS0xMi42LTUuOS0xOC44LTkuM2MtNi0zLjMtMTEuOS02LjgtMTguNy04LjJjLTYtMS4zLTkuNiwwLjUtMTIuMiw1LjljLTAuMiwwLjQtMC40LDAuOS0wLjYsMS40Yy01LDEwLTQuNSwxMi4xLDUsMTguNA0KCQkJYzYuOCw0LjUsMTMuOCw4LjcsMjAuNywxMy4xYzIuMywxLjQsNC4zLDMuMiw1LjYsNS42YzEuMSwxLjgsMS42LDMuOCwwLjMsNS43Yy0xLjQsMi0zLjYsMi40LTUuOCwxLjhjLTQuOC0xLjMtOC42LTQuMi0xMi4yLTcuNg0KCQkJYy01LjMtNS0xMC4yLTEwLjYtMTYtMTUuMWMtMi41LTItNS4zLTMuNS04LjQtNC4zYy0zLjQtMC44LTYuMywwLTguNSwyLjhjLTIuMSwyLjctMS43LDUuNiwwLDguNGMyLjYsNC40LDYuNCw3LjYsMTAuMywxMC44DQoJCQljNi44LDUuNywxNC4yLDEwLjYsMTkuOSwxNy41YzQuNyw1LjcsMy40LDExLjUtMywxNC4yYy00LjEsMS44LTcuNSwwLjYtMTAuMy0yLjZjLTMtMy40LTUuMi03LjMtNy4xLTExLjQNCgkJCWMtMi4zLTQuOC00LjUtOS42LTctMTQuNGMtMS43LTMuMy0zLjctNi40LTYuOC04LjVjLTMuMy0yLjItNi43LTEuOC05LjgsMC42Yy0yLjksMi4yLTMsNS4xLTEuNyw4LjFjMS44LDQuMSwzLjksOC4xLDUuOCwxMi4yDQoJCQljMS44LDMuNywxLjksNy4zLTIuNCw5LjNjLTQuMSwyLTcuMSwwLjEtOS0zLjdjLTItNC0zLjYtOC4yLTUuNi0xMi4yYy0xLjktMy45LTQuNC03LjMtOS42LTZjLTQsMS02LjIsNS4xLTUuNywxMC41DQoJCQljMC43LDguMiwzLjQsMTUuOSw1LjUsMjMuOGMxLjgsNi43LDMuOSwxMy40LDQuNiwyMC41YzAuNSw1LjYtMC43LDEwLjItNS41LDEzLjVjLTAuOCwwLjQtMi40LDEuMS00LjUsMS4xDQoJCQlDMTU1LjksMjk4LjIsMTU0LjQsMjk3LjUsMTUzLjYsMjk3LjF6Ii8+DQoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0zMDMuNiwxNzUuMWMtMi43LDIuMi01LjgsMy40LTkuMywzYy0xMy0xLjMtMjUtNi0zNy42LTExLjdjNC45LTIuOSw5LjYtMi40LDE0LjEtMi45DQoJCQljOC4xLTEsMTYuMi0xLjEsMjQuMywwLjVjMy4yLDAuNiw1LjksMi4xLDguNSw0YzAuMywwLjUsMC45LDEuOCwxLDMuNUMzMDQuNiwxNzMuMywzMDMuOSwxNzQuNSwzMDMuNiwxNzUuMXoiLz4NCgkJPHBhdGggY2xhc3M9InN0MCIgZD0iTTE5OC4yLDExLjhjNC4zLDAuMyw2LjMsMi41LDYuNCw2LjRjMCwyLjctMC44LDUuMy0xLjksNy43Yy00LjUsMTAuMS0xMSwxOS0xNy43LDI3LjcNCgkJCWMtMC42LDAuNy0xLjEsMS45LTIuMywxLjRjLTEuMi0wLjUtMC43LTEuNy0wLjYtMi42YzEuNS0xMS4xLDMuMy0yMi4xLDcuNy0zMi40QzE5MS41LDE2LjEsMTkzLjcsMTIuNiwxOTguMiwxMS44eiIvPg0KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTMuOCwyMzAuMmMtMy43LDAtNi0xLjEtNy4yLTMuOGMtMS4zLTIuOSwwLTUuMSwyLjEtNy4xYzIuMi0yLjEsNC45LTMuNCw3LjctNC40YzkuNy0zLjYsMTkuOS01LjIsMzAuMS02LjYNCgkJCWMxLjEtMC4xLDIuNC0wLjcsMy4xLDAuN2MwLjQsMC45LTAuNCwxLjUtMS4xLDJjLTkuNyw3LjMtMTkuMywxNC43LTMxLjEsMTguNUMxNiwyMjkuOSwxNC41LDIzMCwxMy44LDIzMC4yeiIvPg0KCQk8cGF0aCBjbGFzcz0ic3QwIiBkPSJNMjUyLjksMTIyLjFjLTMuNS0wLjEtNi40LTMuMS02LjItNi41YzAuMi0zLjQsMy4zLTYuMiw2LjctNS45YzMuNywwLjMsNS43LDIuNiw1LjksNi4yDQoJCQlDMjU5LjYsMTE5LjMsMjU2LjQsMTIyLjIsMjUyLjksMTIyLjF6Ii8+DQoJCTxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0xNy43LDEyNS43Yy0wLjMsMy40LTIuMyw1LjgtNi4yLDUuOWMtMy42LDAuMS02LTItNi4xLTUuN2MtMC4xLTMuOSwxLjktNi4zLDUuOC02LjcNCgkJCUMxNC42LDExOC44LDE3LjcsMTIxLjgsMTcuNywxMjUuN3oiLz4NCgk8L2c+DQo8L2c+DQo8L3N2Zz4NCg==';
	}

} //: END SolidSplash class ://

function SolidSplash() {
	global $SolidSplash;
	if( !isset( $SolidSplash ) ) {
		$SolidSplash = new SolidSplash();
		$SolidSplash->initialize();
	}
	return $SolidSplash;
} SolidSplash();

endif;
