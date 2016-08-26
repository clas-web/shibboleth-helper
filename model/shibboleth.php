<?php
require_once( __DIR__ . '/ldap.php' );


/**
 * The main model for the Shibboleth Helper plugin.
 * 
 * @package    shibboleth-helper
 * @subpackage classes/model
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ShibbolethHelper_Model') ):
class ShibbolethHelper_Model
{
	/**
	 * The only instance of the current model.
	 * @var  ShibbolethHelper_Model
	 */	
	private static $instance = null;
	
	/**
	 * The last error saved by the model.
	 * @var  string
	 */	
	public $last_error = null;
	
	
	/**
	 * An instance of the LDAP class.
	 * @var  ShibbolethHelper_LDAP
	 */
	public $ldap = null;
		
	
	/**
	 * Private Constructor.  Needed for a Singleton class.
	 */
	protected function __construct() { }
	
	
	/**
	 * Sets up the "children" models used by this model.
	 */
	protected function setup_models()
	{
		$this->ldap = new ShibbolethHelper_LDAP( $this->get_option('ldap') );
	}
	

	/**
	 * Get the only instance of this class.
	 * @return  ShibbolethHelper_Model  A singleton instance of the model class.
	 */
	public static function get_instance()
	{
		if( self::$instance	=== null )
		{
			self::$instance = new ShibbolethHelper_Model();
			self::$instance->setup_models();
		}
		return self::$instance;
	}



//========================================================================================
//========================================================================= Log file =====


	/**
	 * Clear the log.
	 */
	public function clear_log()
	{
		file_put_contents( SHIBBOLETH_HELPER_LOG_FILE );
	}
	

	/**
	 * Write the username followed by a log line.
	 * @param  string  $username  The user's username.
	 * @param  string  $text  The line of text to insert into the log.
	 * @param  bool  $newline  True if a new line character should be inserted after the line, otherwise False.
	 */
	public function write_to_log( $username = '', $text = '', $newline = true )
	{
		$text = print_r( $text, true );
		if( $newline ) $text .= "\n";
		$text = str_pad( $username, 8, ' ', STR_PAD_RIGHT ).' : '.$text;
		file_put_contents( SHIBBOLETH_HELPER_LOG_FILE, $text, FILE_APPEND );
	}	



//========================================================================================
//========================================================================== Options =====
	
	
	/**
	 * Gets the complete array of Shibboleth Helper options.
	 * @return array  The Shibboleth Helper option array.
	 */
	public function get_options()
	{
		return get_site_option( SHIBBOLETH_HELPER_OPTIONS, array() );
	}
	
	
	/**
	 * Get an Shibboleth Helper option.
	 * @param  string  $name  The name of the option.
	 * @param  bool|string  $default  The default value for the option used if the option 
	 *                                doesn't currently exist.
	 * @return  bool|string  The value of the option, if it exists, otherwise the default.
	 */
	public function get_option( $name, $default = false )
	{
		$options = $this->get_options();
		
		if( isset($options[$name]) ) return $options[$name];
		return $default;
	}


	/**
	 * Updates the current value(s) of the Shibboleth Helper options.
	 * @param  array  $options  The new values.
	 * @param  bool  $merge  True if the new values should be merged into the existing
	 *                       options, otherwise the options are overwrited.
	 */
	public function update_options( $options, $merge = false )
	{
		if( $merge === true )
			$options = array_merge( $this->get_options(), $options );
		
		update_site_option( SHIBBOLETH_HELPER_OPTIONS, $options );
	}
	
	
	/**
	 * Updates the current value(s) of the Shibboleth Helper options.
	 * @param  string  $key  The key name of the option.
	 * @param  string  $value  The string value of the option.
	 */
	public function update_option( $key, $value )
	{
		$this->update_options( array( $key => $value ), true );
	}


//========================================================================================
//================================================================ Utility functions =====


	/**
	 * Determines if a key/value pair is present in an array.
	 * @param  mixed  $value  The value to use in the comparison.
	 * @param  string  $key  The key to use in the comparison.
	 * @param  array  $array  The array to use in the comparison.
	 * @param  bool  $strict  Use a strict comparison (eg. case-sensitive string).
	 * @return  bool  True if a match, otherwise false.
	 */
	public function in_array_by_key( $value, $key, $array, $strict = false )
	{ 
		if( $strict )
		{
			foreach( $array as $item )
			{
				if( isset($item[$key]) && $item[$key] === $value ) return true;
			}
		}
		else
		{
			foreach( $array as $item )
			{
				if( isset($item[$key]) && $item[$key] == $value ) return true; 
			}
		}
		
		return false; 
	}
	
	
	/**
	 *
	 */
	public function search_ldap( $username )
	{
		$user_data = $this->ldap->search( $username );
		
		if( null == $user_data ) {
			return new WP_Error( 'shib_error', $this->ldap->ErrorMessage );
		}
		
		return $user_data;
	}
	
	
	/**
	 *
	 */
	public function add_user( $username, $user_data )
	{
		$ud = array(
			'user_login' => $user_data['user'],
			'display_name' => $user_data['fullname'],
			'user_email' => $user_data['email'],
			'user_pass' => wp_generate_password(),
			'role' => 'subscriber',
		);
		
		$user_id = wp_insert_user( $ud );
		
		if( ! is_wp_error( $user_id ) ) {
			update_usermeta( $user_id, 'shibboleth_account', true );
			$user_id = get_user_by( 'id', $user_id );
		}
		
		return $user_id;
	}
	
	
	/**
	 *
	 */
	public function add_shibboleth_user( $username )
	{	
		$user_id = get_user_by( 'slug', $username );
		if( $user_id ) {
			return new WP_Error( 'already_exists', 'User already exists.' );
		}
		
		$user_data = $this->search_ldap( $username );
		if( is_wp_error( $user_data ) ) {
			return $user_data;
		}
		
		return $this->add_user( $username, $user_data );
	}
	
	
} // class ShibbolethHelper_Model
endif; // if( !class_exists('ShibbolethHelper_Model') ):

