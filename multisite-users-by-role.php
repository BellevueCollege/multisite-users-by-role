<?php
/*
Plugin Name: Multisite Users by Role
Description: List all users with a certain role across a multisite network
Plugin URI: https://github.com/BellevueCollege/multisite-users-by-role/
Author: Taija Tevia-Clark
Version: 2.1.1
Author URI: http://www.bellevuecollege.edu
GitHub Plugin URI: BellevueCollege/multisite-users-by-role
Text Domain: mubr
*/

/**
 * Based on the following sources:
 * Shortcode to list all admins: http://wordpress.stackexchange.com/a/55997
 * Building a settings page:     http://wordpress.stackexchange.com/a/79899
 * Sorting arrays of objects:    http://www.the-art-of-web.com/php/sortarray/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require 'classes/Admin_Interface.php';
require 'classes/User_List.php';
require 'classes/Site_List.php';
require 'classes/User.php';
require 'classes/Site.php';

add_action( 'wp_loaded', array( MUBR_Admin_Interface::get_instance(), 'register' ) );

function MUBR_enqueue_admin_scripts() {
	global $pagenow;
	if ( ( 'users.php' === $pagenow ) && ( 'multisite_users_selected_role' === $_GET['page'] ) ) {
		//checks if page is /users.php?page=multisite_users_selected_role
		wp_enqueue_style( 'multisite_users_by_role_style', plugin_dir_url( __FILE__ ) . 'css/mubr.css', array(), '1.0.0' );
		wp_enqueue_script( 'multisite_users_by_role_script', plugin_dir_url( __FILE__ ) . 'js/mubr-script.js', array(), '1.0.0', true );
	}
}

add_action( 'admin_enqueue_scripts', 'MUBR_enqueue_admin_scripts' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/classes/class-mbur-commands.php';
	$instance = new MBUR_Commands();
	WP_CLI::add_command( 'mubr', $instance );
}
