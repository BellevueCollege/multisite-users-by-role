<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store list of all users
 */
class MUBR_User_List {
	//vars
	protected $users;
	protected $roles;
	
	public function __construct() {
		$this->users = array();
		$this->roles = array();
	}
	
	public function setRoles( $roles ) {
		$this->roles = $roles;
	}
	
	public function output( ) {
		$output = '';
		$output .= '
		<table class="mubr-table widefat fixed posts">
			<thead>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Sites</th>
					<th>Role</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Sites</th>
					<th>Role</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {
				foreach( $this->users as $user ) {
					$output .= '<tr><td>';
					$output .= $user->nameLF();
					$output .= '</td><td>';
					$output .= $user->email();
					$output .= '</td><td>';
					$output .= $user->sites();
					$output .= '</td><td>';
					$output .= $user->role();
					$output .= '</td></tr>';
				}
			} else {
				$output .= '<tr>
					<td colspan="4">No Data Found</td>
				</tr>';
			}
			$output .= '</tbody>
		</table>';
		return $output;
	}

	public function email_output( ) {

		$output = '';
		$output .= '
		<table class="mubr-table widefat fixed posts">
			<thead>
				<tr>
					<th>Semicolon Separated Emails</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Semicolon Separated Emails</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {

				$output .= '<tr><td>';
				$output .= '<textarea id="emails-textarea">';
				$emails = array();
				foreach( $this->users as $user ) {
					$emails[] = $user->emailOnly();
				}
				sort($emails);

				$email_count = count($this->users);
				$count = 0;
				foreach ($emails as $email){
					$count++;
					$output .= $email;
					if ($count != $email_count) {
						$output .= '; ';
					}
				}
				$output .= '</textarea>';
				$output .= '</td></tr>';
			} else {
				$output .= '<tr>
					<td>No Data Found</td>
				</tr>';
			}
			$output .= '</tbody>
		</table>';
		return $output;
	}
	/**
	 * Return an array of IDs of users with the specified role(s)
	 */
	public function get_ids() {
		$ids = array();
		foreach ( $this->users as $user ) {
			$ids[] = $user->id();
		}
		sort( $ids );
		return $ids;
	}

	/**
	 * Return a comma separated list of IDs of users with the specified role(s)
	 */
	public function get_ids_csv() {
		$ids = $this->get_ids();
		return implode( ',', $ids );
	}
	public function id_output() {

		$output = '';
		$output .= '
		<table class="mubr-table widefat fixed posts">
			<thead>
				<tr>
					<th>User IDs</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>User IDs</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {

				$output .= '<tr><td>';
				$output .= '<textarea id="ids-textarea">';
				$output .= $this->get_ids_csv();
				$output .= '</textarea>';
				$output .= '</td></tr>';
			} else {
				$output .= '<tr>
					<td>No Data Found</td>
				</tr>';
			}
			$output .= '</tbody>
		</table>';
		return $output;
	}
	
	private function sortUsers( $data ) {
		usort( $data, array( $this, 'sortByLName' ) );
		return $data;
	}
	
	private function sortByLName( $a, $b) {
		return strnatcmp( $a->last_name(), $b->last_name() );
	}
	
	public function loadUsers( ) {
		if ( isset( $this->roles ) ) {
			//Get array of public and private blogs
			global $wpdb;
			$blogs = get_sites( array(
				'number'   => 2048, // arbitrary large number
				'archived' => 0,
				'deleted'  => 0,
				
			) );
			foreach ( $blogs as $blog ) {
				$blog_id = $blog->blog_id;

				$users = get_users( array( 
					'blog_id' => $blog_id,
					'role__in' => $this->roles
				) );
				
				foreach ( $users as $user ) {
					if ( ! array_key_exists( $user->ID, $this->users ) ) {
						$this->users[ $user->ID ] = new MUBR_User( 
							$user->ID,
							$user->user_email,
							get_user_meta($user->ID, 'first_name', true),
							get_user_meta($user->ID, 'last_name', true),
							$user->roles
						);
					}
					$this->users[ $user->ID ]->addSite( $blog_id );
				}
			}
			$this->users = $this->sortUsers( $this->users );
		}
	}
}