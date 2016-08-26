<?php
/**
 * Utility class that handles LDAP authenication.
 *
 * @package Shibboleth Helper
 * @author Alex Chapin
 * @author Crystal Barton
 * @version 1.0.0
 * @since 1.0.0
 * @copyright Copyright &copy; 2013, Alex Chapin
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License (GPL)
 */

class ShibbolethHelper_LDAP
{
	/*
	 * Example Settings
	 * 
	 * $this->settings['server'] = "ldap.uncc.edu";
	 * $this->settings['port'] = 389;
	 * $this->settings['voadmin_user_dn'] = "cn=jdoe";
	 * $this->settings['voadmin_pass'] = "secret";
	 * 
	 * $this->settings['username_attribute'] = "uid";
	 * $this->settings['fullname_attribute'] = "cn";
	 * $this->settings['email_attribute'] = "mail";
	 * $this->settings['group_attribute'] = "memberOf";
 	 */
	private $settings;
	public $ErrorMessage;


	/**
	 * Constructor.  
	 * Store LDAP settings.
	 */
	public function __construct( $settings )
	{
		$this->settings = $settings;
		$this->ErrorMessage = null;
	}



	/**
	 * Connect to the LDAP server.
	 *
	 * @return LDAP link|null  A link to the LDAP server.
	 */
	private function connect()
	{
		$c = ldap_connect( $this->settings['server'].':'.$this->settings['port'] );
		$r = @ldap_bind( $c, $this->settings['voadmin_user_dn'], $this->settings['voadmin_pass'] );

		if( ! $r ) {
			return null;
		}
		
		return $c;
	}


	
	/**
	 * Searches for a user via the LDAP server.
	 *
	 * @param  string  $usernmae  The username of the user.
	 * @return  array|null  The user's information: 'user','fullname','email','groups'
	 */
	public function search( $username )
	{
		$c = $this->connect();
		if( null == $c ) {
			$this->ErrorMessage = 'LDAP Error: Could not connect to server.';
			return null;
		}

		$user_search_dn = ( $this->settings['user_dn'] ? $this->settings['user_dn'] . ',' : '' ) . $this->settings['base_dn'];
		$search_filter = '(' . $this->settings['username_attribute'] . '=' . strtolower( $username ) . ")";
		$search_resource = ldap_search( $c, $user_search_dn, $search_filter );
		
		$search_results = ldap_get_entries( $c, $search_resource );
		if( ! is_array( $search_results ) || count( $search_results ) == 0 ) {
			return null;
		}
		
		$search_results = array_change_key_case( $search_results[0], CASE_LOWER );
		ldap_unbind( $c );
		
		return array( 
			'user' => $search_results[ strtolower( $this->settings['username_attribute'] ) ][0],
			'fullname' => $search_results[ strtolower( $this->settings['fullname_attribute'] ) ][0],
			'email' => $search_results[ strtolower( $this->settings['email_attribute'] ) ][0],
			'groups' => $search_results[ strtolower( $this->settings['group_attribute'] ) ][0],
		);
	}
}

?>

