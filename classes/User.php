<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store user information
 */
class MUBR_User {

	protected $id;
	protected $email;
	protected $first_name;
	protected $last_name;
	protected $sites;
	protected $role;
	
	public function __construct( $id, $email, $first_name, $last_name, $role ) {
		$this->id = $id;
		$this->email = $email;
		$this->first_name = $first_name;
		$this->last_name = $last_name;
		$this->role = $role;
	}
	
	public function addSite( $site ) {
		$info = get_blog_details( $site );
		$this->sites[]= new MUBR_Site(
			$info->blogname,
			$info->siteurl
		);
		$this->sites = $this->sortSites( $this->sites );
	}
	public function email() {
		return '<a href="mailto:' . $this->email . '">' . $this->email . '</a>';
	}
	public function emailOnly() {
		return $this->email;
	}

	public function id() {
		return $this->id;
	}

	public function last_name() {
		return $this->last_name;
	}
	public function first_name() {
		return $this->first_name;
	}
	public function role() {
		$this->role = reset($this->role);
		return ucfirst($this->role);
	}
	public function nameLF() {
		if ( $this->last_name && $this->first_name ) {
			return '<a href="' . get_edit_user_link( $this->id ) . '">' . $this->last_name . ', ' . $this->first_name . '</a>';
		} else {
			return '<a href="' . get_edit_user_link( $this->id ) . '">' . '<i>undefined</i>' . '</a>';
		}
	}
	public function sites() {
		$output = '';
		foreach ( $this->sites as $site ) {
			$output .= $site->siteLink();
			$output .= '<br>';
		}
		return $output;
	}
	
	private function sortSites( $data ) {
		usort( $data, array( $this, 'sortBySiteName' ) );
		return $data;
	}
	
	private function sortBySiteName( $a, $b) {
		return strnatcmp( $a->name(), $b->name() );
	}
	
	
}