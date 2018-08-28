<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store site data
 */
class MUBR_Site {
	//vars
	protected $name;
	protected $url;
	protected $users;
	
	public function __construct( $name, $url ) {
		$this->name = $name;
		$this->url = $url;
		$this->users = array();
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

	public function users() {
		return $this->users;
	}

	public function addUser( $user ){
		$this->users[] = new MUBR_User( 
			$user->ID,
			$user->user_email,
			get_user_meta($user->ID, 'first_name', true),
			get_user_meta($user->ID, 'last_name', true),
			$user->roles
		);
		$this->users = $this->sortUsers( $this->users );
	}

	public function usersEmail() {
		$output = '';
		foreach ( $this->users as $user ) {
			$output .= $user->email();
			$output .= '<br>';
		}
		return $output;
	}

	public function usersLF() {
		$output = '';
		foreach ( $this->users as $user ) {
			$output .= $user->nameLF();
			$output .= '<br>';
		}
		return $output;
	}

	public function usersRole() {
		$output = '';
		foreach ( $this->users as $user ) {
			$output .= $user->role();
			$output .= '<br>';
		}
		return $output;
	}

	private function sortUsers( $data ) {
		usort( $data, array( $this, 'sortByLName' ) );
		return $data;
	}
	
	private function sortByLName( $a, $b) {
		return strnatcmp( $a->last_name(), $b->last_name() );
	}
}