<?php
/**
 * @brief		Converter Application Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 * @version		
 */
 
namespace IPS\convert;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Converter Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Install Other
	 *
	 * @return	void
	 */
	public function installOther()
	{
		static::checkConvParent();
		
		try
		{
			\IPS\Db::i()->select( '*', 'core_login_handlers', array( "login_key=?", 'Convert' ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$position = \IPS\Db::i()->select( 'MAX(login_order)', 'core_login_handlers' )->first();
			\IPS\Db::i()->insert( 'core_login_handlers', array(
				'login_key'			=> "Convert",
				'login_enabled'		=> 1,
				'login_settings'	=> json_encode( array( 'auth_types' => \IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL ) ),
				'login_order'		=> $position + 1,
				'login_acp'			=> 1
			) );
			
			if ( isset( \IPS\Data\Store::i()->loginHandlers ) )
			{
				unset( \IPS\Data\Store::i()->loginHandlers );
			}
		}
	}
	
	/**
	 * Ensure the appropriate tables have a conv_parent column for internal references
	 *
	 * @param	string|NULL		The application to check, or NULL to check all.
	 * @return	void
	 */
	public static function checkConvParent( $application=NULL )
	{
		$parents = array(
			'downloads'	=> array(
				'tables'	=> array(
					'downloads_categories'	=> array(
						'prefix'	=> 'c',
						'column'	=> 'conv_parent'
					)
				)
			),
			'forums'	=> array(
				'tables'	=> array(
					'forums_forums'			=> array(
						'prefix'	=> '',
						'column'	=> 'conv_parent'
					)
				)
			),
			'gallery'	=> array(
				'tables'	=> array(
					'gallery_categories'	=> array(
						'prefix'	=> 'category_',
						'column'	=> 'conv_parent'
					)
				)
			),
			'cms'		=> array(
				'tables'	=> array(
					'cms_containers'			=> array(
						'prefix'	=> 'container_',
						'column'	=> 'conv_parent'
					),
					'cms_database_categories'	=> array(
						'prefix'	=> 'category_',
						'column'	=> 'conv_parent'
					),
					'cms_folders'				=> array(
						'prefix'	=> 'folder_',
						'column'	=> 'conv_parent'
					)
				)
			),
			'nexus'		=> array(
				'tables'	=> array(
					'nexus_alternate_contacts'	=> array(
						'prefix'	=> '',
						'column'	=> 'conv_alt_id',
					),
					'nexus_package_groups'		=> array(
						'prefix'	=> 'pg_',
						'column'	=> 'conv_parent'
					),
					'nexus_packages'			=> array(
						'prefix'	=> 'p_',
						'column'	=> 'conv_associable'
					),
					'nexus_purchases'			=> array(
						'prefix'	=> 'ps_',
						'column'	=> 'conv_parent'
					)
				)
			)
		);
		
		foreach( $parents AS $app => $tables )
		{
			if ( !is_null( $application ) )
			{
				if ( $application != $app )
				{
					continue;
				}
			}
			
			if ( static::appisEnabled( $app ) )
			{
				foreach( $tables['tables'] AS $table => $data )
				{
					$column = $data['prefix'] . $data['column'];
					if ( \IPS\Db::i()->checkForColumn( $table, $column ) === FALSE )
					{
						\IPS\Db::i()->addColumn( $table, array(
							'name'		=> $column,
							'type'		=> 'BIGINT',
							'length'	=> 20,
							'default'	=> 0,
						) );
					}
				}
			}
		}
	}
}