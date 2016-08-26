<?php
/**
 * Controls the admin page "Add Users".
 * 
 * @package    shibboleth-helper
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */

if( !class_exists('ShibbolethHelper_AddUsersAdminPage') ):
class ShibbolethHelper_AddUsersAdminPage extends APL_AdminPage
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
		$name = 'add-users',
		$menu_title = 'Add Users',
		$page_title = 'Add Users',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ShibbolethHelper_Model::get_instance();
		$this->use_custom_settings = false;
	}
	
	
	/**
	 * Process the settings.
	 */
	public function process()
	{
		if( empty($_REQUEST['action']) ) return;
		
		switch( $_REQUEST['action'] )
		{
			case 'add-user':
				if( empty( $_REQUEST['user'] ) ) {
					break;
				}
				
				$user_id = $this->model->add_shibboleth_user( $_REQUEST['user'] );
				if( is_wp_error( $user_id ) ) {
					$this->add_error( $_REQUEST['user'] . ': '. $user_id->get_error_message() );
				} else {
					$this->add_notice( $_REQUEST['user'] . ': Successfully added.' );
				}
				break;
			
			case 'add-users':
				if( empty( $_REQUEST['users'] ) ) {
					break;
				}
				
				$users = explode( "\n", $_REQUEST['users'] );
				foreach( $users as $user )
				{
					$user = strtolower( trim( $user ) );
					if( empty( $user ) ) {
						continue;
					}
					
					$user_id = $this->model->add_shibboleth_user( $user );
					if( is_wp_error( $user_id ) ) {
						$this->add_error( $user . ': ' . $user_id->get_error_message() );
					} else {
						$this->add_notice( $_REQUEST['user'] . ': Successfully added.' );
					}
				}
				break;
		}
	}
	
	
	/**
	 * Display the settings.
	 */
	public function display()
	{
		?>
		<h2>Add User</h2>
		
		<?php $this->form_start( 'add-user', '', 'add-user' ); ?>
		
		<input type="textbox" name="user" />
		
		<?php
		submit_button( 'Add User', 'primary' );
		$this->form_end();
		?>
		
		<h2>Add Bulk Users</h2>
		
		<?php $this->form_start( 'add-users', '', 'add-users' ); ?>
		
		<textarea name="users"></textarea>

		<?php
		submit_button( 'Add Users', 'primary' );
		$this->form_end();
	}
	
	
} // class ShibbolethHelper_SettingsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ShibbolethHelper_SettingsAdminPage') )

