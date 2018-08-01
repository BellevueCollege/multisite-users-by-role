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
	
	/**
	* Display content of network options page
	*/
	public function render_options_page() {
		$option = esc_attr( stripslashes( get_site_option( $this->option_name ) ) );
		$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
		$redirect = urlencode( $_SERVER['REQUEST_URI'] ); ?>

		<div class="wrap">
			<h1><?php echo $GLOBALS['title']; ?></h1>
			<p>Select a role to generate a list of all users with that role, along with the sites to which they are assigned.</p>

			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
						<input type="hidden" name="action" value="<?php echo $this->action; ?>">
						<?php wp_nonce_field( $this->action, $this->option_name . '_nonce', FALSE ); ?>
						<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
						<label for="<?php echo $this->option_name; ?>" class="screen-reader-text">Select role</label>
						<select name="<?php echo $this->option_name; ?>" id="<?php echo $this->option_name; ?>">
							<option value="-1">Select Role</option>
							<?php wp_dropdown_roles( $option ); ?>
						</select>
						<?php submit_button( 'Create Report', 'action', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<?php if ( get_site_option( $this->option_name ) && is_network_admin() ) {

				$user_list = new MUBR_User_List();
				$user_list->setRole( get_site_option( $this->option_name ) );
				$user_list->loadUsers();

				echo $user_list->output();
				echo '<br/>';
				echo $user_list->email_output();
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