<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Object to store list of all sites and their users
 */
class MUBR_Site_List {
	//vars
	protected $sites;
    protected $users;
	protected $roles;
	protected $theme;
	
	public function __construct() {
        $this->sites = array();
		$this->users = array();
		$this->theme = null;
	}
	
	public function setRoles( $roles ) {
		$this->roles = $roles;
	}
	
	public function setTheme( $theme ) {
		$this->theme = $theme;
	}
	
	public function output( ) {
		$output = '';
		$output .= '
		<table class="mubr-table widefat fixed posts">
			<thead>
				<tr>
					<th>Site</th>
					<th>Name</th>
					<th>Email</th>
					<th>Role</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Site</th>
					<th>Name</th>
					<th>Email</th>
					<th>Role</th>
				</tr>
			</tfoot>
			<tbody>';

			if ( !empty( $this->users ) ) {
				foreach( $this->sites as $site ) {	
					$siteUsers = $site->users();
					
					$output .= '<tr><td>';
					$output .= $site->siteLink();
					$output .= '</td><td>';
					$output .= (!empty($siteUsers) ? $site->usersLF() : 'No Data');
					$output .= '</td><td>';
					$output .= (!empty($siteUsers) ? $site->usersEmail() : 'No Data');
					$output .= '</td><td>';
					$output .= (!empty($siteUsers) ? $site->usersRole() : 'No Data');
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

	private function sortSites( $data ) {
		usort( $data, array( $this, 'sortBySiteName' ) );
		return $data;
	}
	
	private function sortBySiteName( $a, $b) {
		return strnatcmp( $a->name(), $b->name() );
	}

	public function loadSites( ) {
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

				// Skip sites that don't match the selected theme
				if ( $this->theme && $this->theme !== '' ) {
					switch_to_blog( $blog_id );
					$current_theme = get_option( 'stylesheet' ) ?: get_option( 'template' );
					restore_current_blog();
					
					if ( $current_theme !== $this->theme ) {
						continue; // Skip this site if theme doesn't match
					}
				}

				$info = get_blog_details( $blog_id );
				$this->sites[$blog_id] = new MUBR_Site(
					$info->blogname,
					$info->siteurl
				);

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
					$this->sites[ $blog_id ]->addUser( $user );
				}
            }
			$this->sites = $this->sortSites( $this->sites );
		}
    }
}