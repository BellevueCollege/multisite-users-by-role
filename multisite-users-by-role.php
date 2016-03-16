<?php
/*
Plugin Name: Multisite Users by Role
Description: List all users with a certain role across a multisite network
Plugin URI: https://github.com/BellevueCollege/multisite-users-by-role/
Author: Taija Tevia-Clark
Version: 0.0.0.4
Author URI: http://www.bellevuecollege.edu
*/

/**
 * Based on the following sources:
 * Shortcode to list all admins: http://wordpress.stackexchange.com/a/55997
 * Building a settings page:     http://wordpress.stackexchange.com/a/79899
 */

add_action( 'wp_loaded', array ( Multisite_Users_By_Role::get_instance(), 'register' ) );

class Multisite_Users_By_Role {
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;
	protected $action	 = 'gen_multisite_user_list';
	protected $option_name	 = 'multisite_user_list_selected_role';
	protected $page_id = NULL;

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook wp_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}
	/**
	 * Register menu pages 
	 */
	public function register()
	{
		add_action( 'network_admin_menu', array ( $this, 'add_menu' ) );
		add_action( "admin_post_$this->action", array ( $this, 'admin_post' ) );
	}

	public function add_menu()
	{
		$page_id = add_users_page(
			'List All Multisite Users by Role',
			'Network User List',
			'manage_network_options',
			'multisite_users_selected_role',
			array ( $this, 'render_options_page' )
		);
	add_action( "load-$page_id", array ( $this, 'parse_message' ) );
	}

	public function parse_message()
	{
		if ( ! isset ( $_GET['msg'] ) )
			return;

		$text = FALSE;

		if ( 'updated' === $_GET['msg'] )
			$this->msg_text = 'Updated!';

		if ( 'deleted' === $_GET['msg'] )
			$this->msg_text = 'Deleted!';

		if ( $this->msg_text )
			add_action( 'admin_notices', array ( $this, 'render_msg' ) );
	}

	public function render_msg()
	{
		echo '<div class="' . esc_attr( $_GET['msg'] ) . '"><p>'
			. $this->msg_text . '</p></div>';
	}

	public function render_options_page()
	{
		$option = esc_attr( stripslashes( get_site_option( $this->option_name ) ) );
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
		$redirect = urlencode( $_SERVER['REQUEST_URI'] );

		?><h1><?php echo $GLOBALS['title']; ?></h1>
		<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
			<input type="hidden" name="action" value="<?php echo $this->action; ?>">
			<?php wp_nonce_field( $this->action, $this->option_name . '_nonce', FALSE ); ?>
			<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
			<p>Select a role to generate a list of all users with that role, along with the sites to which they are assigned.</p>
			<strong><label for="<?php echo $this->option_name; ?>">Selected role:</label></strong>
			<select name="<?php echo $this->option_name;
				?>" id="<?php echo $this->option_name;
				?>">
			   <?php wp_dropdown_roles( $option ); ?>
			</select>
			<?php submit_button( 'Generate' ); ?>
			<hr />
			<br />
			<?php 
			if ( get_site_option( $this->option_name ) && is_network_admin() ) {
				echo "<h2>All users with the role <em>'" . get_site_option( $this->option_name ) . "'</em></h2>";
				echo $this->list_users_with_role( get_site_option( $this->option_name ) );
			} else {
				echo '<p>Please select a role to generate this report. If a role is already selected, please click generate.</p>';
			}
			
			?>
		</form>
		<?php
	}

	public function admin_post()
	{
		if ( ! wp_verify_nonce( $_POST[ $this->option_name . '_nonce' ], $this->action ) )
			die( 'Invalid nonce.' . var_export( $_POST, true ) );

		if ( isset ( $_POST[ $this->option_name ] ) )
		{
			update_site_option( $this->option_name, $_POST[ $this->option_name ] );
			$msg = 'updated';
		}
		else
		{
			delete_site_option( $this->option_name );
			$msg = 'deleted';
		}

		if ( ! isset ( $_POST['_wp_http_referer'] ) )
			die( 'Missing target.' );

		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );

		wp_safe_redirect( $url );
		exit;
	}

	public function list_users_with_role( $role ) {
		/* Build array of users */
		$user_array = array();
		global $wpdb;
		$blogs = $wpdb->get_results("
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = '{$wpdb->siteid}'
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			AND mature = '0' 
			AND public = '1'
			OR public = '0'
		");
		$output = '';
		foreach ($blogs as $blog) 
		{
			switch_to_blog( $blog->blog_id );
			$users_query = new WP_User_Query( array( 
						'role' => $role, 
						) );
			$results = $users_query->get_results();
			//$site_admins .= 'Blog ID: ' . $blog->blog_id;
			foreach( $results as $user ) {
				$user_email = $user->user_email;
				//$site_admins .= 'email: ' . $user->user_email . '<br />';
				if ( ! array_key_exists( $user->user_email, $user_array ) ) {
					$user_array[$user_email] = array( get_site_url( $blog->blog_id ) );
				} else {
					$user_array[$user_email][] = get_site_url( $blog->blog_id );
				}
			}
		}
		restore_current_blog();
		
		/* Sort by key (in this case the user's email) */
		ksort( $user_array );
		
		/* Parse to HTML output */
		$output='<table class="wp-list-table widefat fixed striped users-by-site">
			<thead>
				<tr>
					<th>Email</th>
					<th>Sites</th>
				</tr>
			</thead>
			<tbody id="the-list">';
		foreach ( $user_array as $email_key => $site_array ) {
			$output .= '<tr>';
			$output .= "<td><a href='mailto:$email_key'>$email_key</a></td>";
			$output .= '<td>';
			foreach ( $site_array as $site ) {
				$output .= "<a href='$site'>$site</a><br />";
			}
			$output .= '</tr>';
			$output .= '</td>';

		}
		$output .= '</tbody></table>';
		/* Return HTML table */
		return $output;
	}
}
