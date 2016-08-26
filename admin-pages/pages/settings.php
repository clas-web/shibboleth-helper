<?php
/**
 * Controls the admin page "Settings".
 * 
 * @package    shibboleth-helper
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */

if( !class_exists('ShibbolethHelper_SettingsAdminPage') ):
class ShibbolethHelper_SettingsAdminPage extends APL_AdminPage
{
	
	/**
	 * The main model for the Organization Hub.
	 * @var  ShibbolethHelper_Model
	 */	
	private $model = null;	
	

	/**
	 * Constructor.
	 */
	public function __construct(
		$name = 'settings',
		$menu_title = 'Settings',
		$page_title = 'Settings',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ShibbolethHelper_Model::get_instance();
	}
	
	
	/**
	 * Register each individual settings for the Settings API.
	 */
	public function register_settings()
	{
		$this->register_setting( SHIBBOLETH_HELPER_OPTIONS );
	}
	

	/**
	 * Add the sections used for the Settings API. 
	 */
	public function add_settings_sections()
	{
		$this->add_section(
			'shibboleth-helper-ldap-settings',
			'LDAP Settings',
			'print_section_ldap_settings'
		);
	}
	
	
	/**
	 * Add the settings used for the Settings API. 
	 */
	public function add_settings_fields()
	{
		$section = 'shibboleth-helper-ldap-settings';
		
		$fields = array(
			'server'             => 'Server',
			'port'               => 'Port',
			'voadmin_user_dn'    => 'voadmin_user_dn',
			'voadmin_pass'       => 'voadmin_pass',
			'base_dn'            => 'base_dn',
			'user_dn'            => 'user_dn',
			'username_attribute' => 'Username Attribute',
			'fullname_attribute' => 'Fullname Attribute',
			'email_attribute'    => 'Email Attribute',
			'group_attribute'    => 'Group Attribute',
		);

		foreach( $fields as $field_key => $field_title )
		{
			$this->add_field(
				$section,
				$field_key,
				$field_title,
				'print_field_textbox',
				array( $field_key, $field_title )
			);
		}
	}
	
	
	/**
	 * Print the any instructions for the LDAP Settings section.
	 * @param  Array  $args  The passed args.
	 */
	public function print_section_ldap_settings( $args )
	{
		apl_print('print_section_ldap_settings');
	}
	
	
	/**
	 * Print textbox for LDAP settings entry.
	 * @param  Array  $args  The passed args.
	 */
	public function print_field_textbox( $args )
	{
		$name = array( 
			SHIBBOLETH_HELPER_OPTIONS, 
			'ldap', 
			$args[0] );
		$ldap_options = $this->model->get_option( 'ldap' );
		$value = ( isset( $ldap_options[ $args[0] ] ) ? $ldap_options[ $args[0] ] : '' );
		?>
		<input type="text" value="<?php echo esc_attr( $value ); ?>" name="<?php apl_name_e( $name ); ?>">
		<?php
	}


	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		$this->print_settings();
	}
	
	
} // class ShibbolethHelper_SettingsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ShibbolethHelper_SettingsAdminPage') )

