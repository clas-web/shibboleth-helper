<?php
/*
Plugin Name: Shibboleth Helper
Plugin URI: https://github.com/clas-web/shibboleth-helper
Description: The Shibboleth Helper is a colleciton of admin pages and filters designed to work the Shibboleth plugin.
Version: 0.1.1
Author: Crystal Barton
Author URI: https://www.linkedin.com/in/crystalbarton
Network: True
GitHub Plugin URI: https://github.com/clas-web/shibboleth-helper
*/



if( !defined('SHIBBOLETH_HELPER') ):

/**
 * The full title of the Shibboleth Helper plugin.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER', 'Shibboleth Helper' );

/**
 * True if debug is active, otherwise False.
 * @var  bool
 */
define( 'SHIBBOLETH_HELPER_DEBUG', false );

/**
 * The path to the plugin.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_PLUGIN_PATH', __DIR__ );

/**
 * The url to the plugin.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_PLUGIN_URL', plugins_url('', __FILE__) );

/**
 * The version of the plugin.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_VERSION', '1.0.1' );

/**
 * The database version of the plugin.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_DB_VERSION', '1.1' );

/**
 * The database options key for the Shibboleth Helper version.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_VERSION_OPTION', 'shibboleth-helper-version' );

/**
 * The database options key for the Shibboleth Helper database version.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_DB_VERSION_OPTION', 'shibboleth-helper-db-version' );

/**
 * The database options key for the Shibboleth Helper options.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_OPTIONS', 'shibboleth-helper-options' );

/**
 * The full path to the log file used for debugging.
 * @var  string
 */
define( 'SHIBBOLETH_HELPER_LOG_FILE', __DIR__.'/log.txt' );

endif;


require_once( __DIR__ . '/model/shibboleth.php' );


/* Hook into Organization Hub's Create User Types filter */
add_filter( 'orghub_create_users_types', 'shibhelper_create_user_types', 10 );

/* Hook into Organization Hub's Create User filter */
add_filter( 'orghub_create_user-shibboleth', 'shibhelper_create_user', 10, 4 );


/* Admin Pages */
if( is_admin() ):
	add_action( 'wp_loaded', 'shibboleth_load' );
endif;


/* Hook into allowed_redirect_hosts filter to allow multi-domain plugin to work. */
add_filter( 'allowed_redirect_hosts', 'shibhelper_allowed_redirect_hosts', 10, 2 );
add_filter( 'shibboleth_session_initiator_url', 'shibhelper_session_initiator_url' );

/* Remove @uncc.edu from usernames */
add_filter( 'sanitize_user', 'shibhelper_sanitize_user', 10, 3 );

/* Modify the login/logout url */
add_filter( 'site_option_shibboleth_login_url', 'shibhelper_url', 10, 2 );
add_filter( 'site_option_shibboleth_logout_url', 'shibhelper_url', 10, 2 );
add_filter( 'option_shibboleth_login_url', 'shibhelper_url', 10, 2 );
add_filter( 'option_shibboleth_logout_url', 'shibhelper_url', 10, 2 );


/**
 * 
 */
if( ! function_exists('shibhelper_create_user_types') ):
function shibhelper_create_user_types( $types ) {
	$types[] = 'shibboleth';
	return $types;
}
endif;


/**
 * 
 */
if( ! function_exists('shibhelper_create_user') ):
function shibhelper_create_user( $user, $username, $password, $email )
{
	if( ! is_plugin_active_for_network( 'shibboleth/shibboleth.php' ) )
	{
		return new WP_Error( 'orghub_shib_error', 'Shibboleth plugin not active.' );
	}
	
	$model = ShibbolethHelper_Model::get_instance();

	if( !$user_data = $model->search_ldap( $username ) ) 
	{
		return new WP_Error( 'orghub_shib_error', 'Unable to find user in LDAP: '.$username );
	}
	
	if( ($user_id = username_exists( $username ) ) &&
		(add_user_to_blog( $user_id, 1, 'subscriber' ) ) )
	{
		break;
	}

	$user = $model->add_user( $username, $user_data );
	
	if( is_wp_error( $user ) ) 
	{
		$this->last_error = $user->get_error_message();
		return null;
	}
	
	if( ( is_a( $user, 'WP_User' ) ) &&
		( $user_id = username_exists($username) ) ) 
	{
		add_user_to_blog( $blog_id, $user_id, 'subscriber' );
		update_usermeta( $user_id, 'primary_blog', $blog_id );
	}
	
	return $user;
}
endif;


/**
 * Setup the network admin pages.
 */
if( !function_exists('shibboleth_load') ):
function shibboleth_load()
{
	require_once( __DIR__.'/admin-pages/require.php' );
	
	$shib_pages = new APL_Handler( true );

	$menu = $shib_pages->add_menu( new APL_AdminMenu( 'shibboleth-helper', 'Shibboleth Helper' ) );
	$menu->add_page( new ShibbolethHelper_AddUsersAdminPage );
	$menu->add_page( new ShibbolethHelper_SettingsAdminPage );
	
	$shib_pages->setup();

	if( $shib_pages->controller )
	{
//		add_action( 'admin_enqueue_scripts', 'shibboleth_helper_enqueue_scripts' );
		add_action( 'network_admin_menu', 'shibboleth_helper_update', 5 );
	}
}
endif;


/**
 * Update the database if a version change.
 */
if( !function_exists('shibboleth_helper_update') ):
function shibboleth_helper_update()
{
	$version = get_site_option( SHIBBOLETH_HELPER_DB_VERSION_OPTION );
	if( $version !== SHIBBOLETH_HELPER_DB_VERSION )
	{
		$model = ShibbolethHelper_Model::get_instance();
	}
		
	update_site_option( SHIBBOLETH_HELPER_VERSION_OPTION, SHIBBOLETH_HELPER_VERSION );
	update_site_option( SHIBBOLETH_HELPER_DB_VERSION_OPTION, SHIBBOLETH_HELPER_DB_VERSION );
}
endif;


/**
 * 
 */
if( ! function_exists( 'shibhelper_allowed_redirect_hosts' ) ):
function shibhelper_allowed_redirect_hosts( $hosts, $requested_host )
{
	$md_domains = get_site_option( 'md_domains' );
	if( $md_domains ) {
		foreach( $md_domains as $domain ) {
			if( array_key_exists('domain_name', $domain) &&
			    array_key_exists('domain_status', $domain) &&
			    $domain['domain_status'] == 'public' ) {
			    $hosts[] = $domain['domain_name'];
			}
		}
		return array_unique( $hosts );
	}
	
	return $hosts;	
}
endif;


/**
 * 
 */
if( ! function_exists( 'shibhelper_session_initiator_url' ) ):
function shibhelper_session_initiator_url( $url )
{
	$all = array();
	parse_str( $url, $all );
	
	$target = '';
	if( array_key_exists( 'target', $all ) ) {
		$target = $all['target'];
	}
	
	$initiator_url = network_site_url() . 'Shibboleth.sso/Login';
	$initiator_url = add_query_arg( 'return', $target, $initiator_url );
	$initiator_url = add_query_arg( 'next', $target, $initiator_url );
	
	foreach( $all as $key => $value ) {
		$initiator_url = add_query_arg( $key, $value, $initiator_url );
	}
	
	return $initiator_url;
}
endif;


/**
 * 
 */
if( ! function_exists( 'shibhelper_sanitize_user' ) ):
function shibhelper_sanitize_user( $username, $raw_username, $strict )
{
	if( false !== ( $i = strpos( $username, '@uncc.edu' ) ) ) {
		$username = substr( $username, 0, $i );
	}
	
	return $username;
}
endif;


/**
 *
 */
if( ! function_exists( 'shibhelper_url' ) ):
function shibhelper_url( $url, $option_name )
{
	if( function_exists('get_current_screen') ) {
	 	$screen = get_current_screen();
	 	if( 'settings_page_shibboleth-options-network' === $screen->base ) {
	 	    return $url;
	 	}
	 }
	
// 	apl_print( $url, '$url' );
	return shibhelper_get_parsed_url( $url );
}
endif;


/**
 *
 */
if( ! function_exists( 'shibhelper_get_parsed_url' ) ):
function shibhelper_get_parsed_url( $url )
{
	$i = strpos( $url, '{home}' );
	if( false !== $i )
	{
		$home_url = get_option( 'home' );
		$parse = parse_url( $home_url );
		
		$home_url = $parse['host'];
		if( ! empty( $parse['path'] ) && '/' !== $parse['path'] ) {
			$home_url .= $parse['path'];
		}
		
		$url = str_replace( '{home}', $home_url, $url );
	}
	
	$i = strpos( $url, '{domain}' );
	if( false !== $i )
	{
		$url = str_replace( '{domain}', $_SERVER['HTTP_HOST'], $url );
	}
	
	return $url;
}
endif;


