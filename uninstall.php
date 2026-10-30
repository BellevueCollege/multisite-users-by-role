<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_name = 'multisite_user_list_selected_role';
$theme_option_name = 'multisite_user_list_selected_theme';

delete_option( $option_name );

// For site options in Multisite
delete_site_option( $option_name );
delete_site_option( $theme_option_name );
