<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Admin Interface Class
 *
 * Sets up Admin interface in WordPress
 */
class MUBR_Admin_Interface {
	protected static $instance = NULL;
	protected $action          = 'gen_multisite_user_list';
	protected $option_name     = 'multisite_user_list_selected_role';
	protected $page_id         = NULL;
	
	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook wp_loaded
	 * @return  object of this class
	 */
	public static function get_instance() {
		NULL === self::$instance and self::$instance = new self;
		return self::$instance;
	}
	
	/**
	 * Register menu pages 
	 */
	public function register() {
		add_action( 'network_admin_menu', array ( $this, 'add_menu' ) );
		add_action( "admin_post_$this->action", array ( $this, 'admin_post' ) );
	}

	public function add_menu() {
		$page_id = add_users_page(
			'List All Multisite Users by Role',
			'Network User List',
			'manage_network_options',
			'multisite_users_selected_role',
			array ( $this, 'render_options_page' )
		);
		add_action( "load-$page_id", array ( $this, 'parse_message' ) );
	}

	public function parse_message() {
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

	public function render_msg() {
		echo '<div class="' . esc_attr( $_GET['msg'] ) . '"><p>'
			. $this->msg_text . '</p></div>';
	}
	
	/*
	* An edit of wp_dropdown_roles() for checkboxes and to allow remembering selections after submitting
	*/

	public function mubr_checkbox_roles( ) {
		$r = '';
		$editable_roles = array_reverse( get_editable_roles() );
		$site_roles = get_site_option( $this->option_name );

		foreach ( $editable_roles as $role => $details ) {
			$name = translate_user_role($details['name'] );

			$checked = '';
			if ( is_array( $site_roles ) ) {
				$checked = in_array( $role, $site_roles ) ? ' checked="checked"' : ''; 	
			}
			$r .="\n\t<div>";
			$r .= "\n\t\t<input type='checkbox' id='" . esc_attr( $role ) . "' name='" . $this->option_name . "[]" . "' value='" . esc_attr( $role ) . "'$checked>";
			$r .= "\n\t\t<label for='" . esc_attr( $role ) . "'>$name</label>";
			$r .="\n\t</div>";
		}
		echo $r;
	}

	/**
	* Display content of network options page
	*/
	public function render_options_page() {
		//$option = esc_attr( stripslashes( get_site_option( $this->option_name ) ) );
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
		$redirect = urlencode( $_SERVER['REQUEST_URI'] ); ?>

		<div class="wrap">
			<h1><?php echo $GLOBALS['title']; ?></h1>
			<p>Select a role or multiple roles to generate a list of all users with that role, along with the sites to which they are assigned.</p>

			<div class="mubr tablenav top">
				<div class="actions bulkactions">
					<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
						<input type="hidden" name="action" value="<?php echo $this->action; ?>">
						<?php wp_nonce_field( $this->action, $this->option_name . '_nonce', FALSE ); ?>
						<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
						<label for="<?php echo $this->option_name; ?>" class="screen-reader-text">Select role</label>
						<fieldset name="<?php echo $this->option_name; ?>" id="<?php echo $this->option_name; ?>">
							<legend>Select Role(s)</legend>
							<?php $this->mubr_checkbox_roles( ); ?>
						</fieldset>
						<?php submit_button( 'Create Report', 'action', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'sort_by_user'; ?>

			<div id="mubr-nav">
				<h2 class="nav-tab-wrapper">
					<a href="?page=multisite_users_selected_role&tab=sort_by_user" class="nav-tab <?php echo $active_tab == 'sort_by_user' ? 'nav-tab-active' : ''; ?>">Sort By User</a>
					<a href="?page=multisite_users_selected_role&tab=sort_by_site" class="nav-tab <?php echo $active_tab == 'sort_by_site' ? 'nav-tab-active' : ''; ?>">Sort By Site</a>
					<a href="?page=multisite_users_selected_role&tab=emails" class="nav-tab <?php echo $active_tab == 'emails' ? 'nav-tab-active' : ''; ?>">Semicolon Separated Emails</a>
					<a href="?page=multisite_users_selected_role&tab=ids" class="nav-tab <?php echo $active_tab == 'ids' ? 'nav-tab-active' : ''; ?>">User IDs</a>
				</h2>
			</div>

			<?php if ( get_site_option( $this->option_name ) && is_network_admin() ) {

				if( $active_tab == 'sort_by_user' ) {
					$user_list = new MUBR_User_List();
					$user_list->setRoles( get_site_option( $this->option_name ) );
					$user_list->loadUsers();
					echo $user_list->output();
				} elseif( $active_tab == 'sort_by_site' ) { 
					$site_list = new MUBR_Site_List();
					$site_list->setRoles( get_site_option( $this->option_name ) );
					$site_list->loadSites();
					echo $site_list->output();
				} elseif ( $active_tab == 'emails' ) { // 'emails'
					$user_list = new MUBR_User_List();
					$user_list->setRoles( get_site_option( $this->option_name ) );
					$user_list->loadUsers();
					echo $user_list->email_output();
				} else {
					$user_list = new MUBR_User_List();
					$user_list->setRoles( get_site_option( $this->option_name ) );
					$user_list->loadUsers();
					echo $user_list->id_output();
				}
			} else {
				echo '<p>Please select a role to generate this report. If a role is already selected, please click generate.</p>';
			} ?>
		</div>
	<?php }
	
	public function admin_post() {
		if ( ! wp_verify_nonce( $_POST[ $this->option_name . '_nonce' ], $this->action ) )
			die( 'Invalid nonce.' . var_export( $_POST, true ) );
			
		if ( isset ( $_POST[ $this->option_name ] ) ) {
			update_site_option( $this->option_name, $_POST[ $this->option_name ] );
			$msg = 'updated';
		} else {
			delete_site_option( $this->option_name );
			$msg = 'deleted';
		}

		if ( ! isset ( $_POST['_wp_http_referer'] ) )
			die( 'Missing target.' );

		$url = add_query_arg( 'msg', $msg, urldecode( $_POST['_wp_http_referer'] ) );

		wp_safe_redirect( $url );
		exit;
	}
}