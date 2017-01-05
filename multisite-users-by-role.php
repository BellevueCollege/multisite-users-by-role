<?php
/*
Plugin Name: Multisite Users by Role
Description: List all users with a certain role across a multisite network
Plugin URI: https://github.com/BellevueCollege/multisite-users-by-role/
Author: Taija Tevia-Clark
Version: 0.0.0.6
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

include( 'classes/Admin_Interface.php' );
include( 'classes/User_List.php' );
include( 'classes/User.php' );
include( 'classes/Site.php' );

add_action( 'wp_loaded', array ( MUBR_Admin_Interface::get_instance(), 'register' ) );