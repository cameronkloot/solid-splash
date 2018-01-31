<?php

if ( !class_exists( 'Solid_Settings' ) ):

class Solid_Settings {
	
	function __construct() {
		//: Empty ://
	}

	public function initialize() {
		
	}



} //: END Solid_Settings class ://

function Solid_Settings() {
	
	global $Solid_Settings;

	if( !isset( $Solid_Settings ) ) {
		$Solid_Settings = new Solid_Settings();
		$Solid_Settings->initialize();	
	}
	return $Solid_Settings;

} Solid_Settings();

endif;
