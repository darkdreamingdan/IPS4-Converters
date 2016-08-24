<?php


namespace IPS\convert\setup\upg_100000;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Make sure conv_password and conv_password_extra are present and accurate.
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if ( \IPS\Db::i()->checkForColumn( 'core_members', 'conv_password' ) )
		{
			\IPS\Db::i()->changeColumn( 'core_members', 'conv_password', array( 'name' => 'conv_password', 'type' => 'VARCHAR', 'length' => 255, 'null' => TRUE, 'default' => NULL ) );
		}
		else
		{
			\IPS\Db::i()->addColumn( 'core_members', array( 'name' => 'conv_password', 'type' => 'VARCHAR', 'length' => 255, 'null' => TRUE, 'default' => NULL ) );
		}
		
		if ( \IPS\Db::i()->checkForColumn( 'core_members', 'misc' ) )
		{
			\IPS\Db::i()->changeColumn( 'core_members', 'misc', array( 'name' => 'conv_password_extra', 'type' => 'VARCHAR', 'length' => 255, 'null' => TRUE, 'default' => NULL ) );
		}
		else
		{
			\IPS\Db::i()->addColumn( 'core_members', 'misc', array( 'name' => 'conv_password_extra', 'type' => 'VARCHAR', 'length' => 255, 'null' => TRUE, 'default' => NULL ) );
		}
		
		return TRUE;
	}
}