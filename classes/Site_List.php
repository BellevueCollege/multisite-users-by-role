<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store list of all sites and their users
 */
class MUBR_Site_List {
	//vars
	protected $sites;
    protected $users;
    protected $role;
	
	public function __construct() {
        $this->sites = array();
		$this->users = array();
	}
	
	public function setRole( $role ) {
		$this->role = $role;
	}
	
	public function output( ) {
		
        $output = '';
        $output .= '<h1>List All Multisite Users by Multisite</h1>';
		$output .= '
		<table class="mubr-table widefat fixed posts">
			<thead>
                <tr>
					<th>Site</th>
					<th>Name</th>
					<th>Email</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Site</th>
					<th>Name</th>
					<th>Email</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {
				foreach( $this->sites as $site ) {
					$siteUsers = $site->usersEmail();
					if ( !empty( $siteUsers ) ) {
						$output .= '<tr><td>';
						$output .= $site->siteLink();
						$output .= '</td><td>';
						$output .= $site->usersLF();
						$output .= '</td><td>';
						$output .= $site->usersEmail();
						$output .= '</td></tr>';
					}
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

	private function sortSites( $data ) {
		usort( $data, array( $this, 'sortBySiteName' ) );
		return $data;
	}
	
	private function sortBySiteName( $a, $b) {
		return strnatcmp( $a->name(), $b->name() );
	}

	public function loadSites( ) {
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
                
                $info = get_blog_details( $blog_id );
                $this->sites[$blog_id] = new MUBR_Site(
                    $info->blogname,
                    $info->siteurl
				);
				
				foreach ( $users as $user ) {
					if ( ! array_key_exists( $user->ID, $this->users ) ) {
						$this->users[ $user->ID ] = new MUBR_User( 
							$user->ID,
							$user->user_email,
							get_user_meta($user->ID, 'first_name', true),
							get_user_meta($user->ID, 'last_name', true)
						);
					}
					$this->sites[ $blog_id ]->addUser( $user );
				}
            }
			$this->sites = $this->sortSites( $this->sites );
		}
    }
}