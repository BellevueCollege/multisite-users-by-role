<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store list of all users
 */
class MUBR_User_List {
	//vars
	protected $users;
	protected $role;
	
	public function __construct() {
		$this->users = Array();
	}
	
	public function setRole( $role ) {
		$this->role = $role;
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
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Name</th>
					<th>Email</th>
					<th>Sites</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {

				foreach( $this->users as $row ) {
					$output .= '<tr><td>';
					$output .= $row->nameLF();
					$output .= '</td><td>';
					$output .= $row->email();
					$output .= '</td><td>';
					$output .= $row->sites();
					$output .= '</td></tr>';
				}
			} else {
				$output .= '<tr>
					<td colspan="3">No Data Found</td>
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
					<th>Comma Separated Emails</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Comma Separated Emails</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {

				$email_count = count($this->users);
				$count = 0;
				$output .= '<tr><td>';
				foreach( $this->users as $user ) {
					$count++;
					$output .= $user->email();
					if ($count != $email_count) {
						$output .= ', ';
					}
				}
				$output .= '</td></tr>';
			} else {
				$output .= '<tr>
					<td colspan="3">No Data Found</td>
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
		if ( isset( $this->role ) ) {
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
					'role'    => $this->role
				) );
				
				foreach ( $users as $user ) {
					if ( ! array_key_exists( $user->ID, $this->users ) ) {
						$this->users[ $user->ID ] = new MUBR_User( 
							$user->ID,
							$user->user_email,
							get_user_meta($user->ID, 'first_name', true),
							get_user_meta($user->ID, 'last_name', true)
						);
					}
					$this->users[ $user->ID ]->addSite( $blog_id );
				}
				
			}
			$this->users = $this->sortUsers( $this->users );
		}
	}
}