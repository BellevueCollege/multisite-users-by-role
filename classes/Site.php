<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store site data
 */
class MUBR_Site {
	//vars
	protected $name;
	protected $url;
	
	public function __construct( $name, $url ) {
		$this->name = $name;
		$this->url = $url;
	}
	
	public function siteLink() {
		return "<a href='$this->url'>$this->name</a>";
	}
	
	public function url() {
		return $this->url;
	}
	
	public function name() {
		return $this->name;
	}
}