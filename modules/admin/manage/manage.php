<?php


namespace IPS\convert\modules\admin\manage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * manage
 */
class _manage extends \IPS\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->responsive = FALSE;
		\IPS\Dispatcher::i()->checkAcpPermission( 'manage_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'convert_apps', \IPS\Http\Url::internal( 'app=convert&module=manage&controller=manage' ) );
		$table->langPrefix = 'convert_';
		$table->include = array( 'sw', 'app_key', 'name', 'parent' );
		
		$table->parsers = array(
			'sw'				=> function( $val, $row )
			{
				switch( $val )
				{
					case 'board':
						$val = 'forums';
					break;
					
					case 'ccs':
						$val = 'cms';
					break;
				}
				
				return \IPS\Application::load( $val )->_title;
			},
			'app_key'			=> function( $val, $row )
			{
				$app = \IPS\convert\App::constructFromData( $row );
				try
				{
					$classname = get_class( $app->getSource() );
					return $classname::softwareName();
				}
				catch( \Exception $ex )
				{
					return $app->name;
				}
			},
			'parent'			=> function( $val, $row )
			{
				try
				{
					if ( ! $val )
					{
						return \IPS\Member::loggedIn()->language()->addToStack( 'convert_no_parent' );
					}
					else
					{
						return \IPS\convert\App::load( $val )->name;
					}
				}
				catch( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'convert_cannot_parent' );
				}
			}
		);
		
		$table->rootButtons = array(
			'start'	=> array(
				'icon'	=> 'plus',
				'title'	=> 'convert_start',
				'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=create&_new=1" ),
			)
		);
		
		if ( \IPS\IN_DEV )
		{
			$table->rootButtons += array(
				'software' => array(
					'icon'	=> 'plus',
					'title'	=> 'new_software',
					'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage&do=software" ),
				),
				'library' => array(
					'icon'	=> 'plus',
					'title'	=> 'new_library',
					'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage&do=library" ),
				)
			);
		}
		
		$table->rowButtons = function( $row )
		{
			try
			{
				/* Try to load the app class - if exception, the converter no longer exists */
				get_class( \IPS\convert\App::constructFromData( $row )->getSource() );
				
				$return = array();

				$return[] = array(
					'icon'	=> 'chevron-circle-right',
					'title'	=> 'continue',
					'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&id={$row['app_id']}" )
				);

				if ( $row['parent'] == 0 )
				{
					$return[] = array(
						'icon'	=> 'check',
						'title'	=> 'finish',
						'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=finish&id={$row['app_id']}" ),
						'data'	=> array(
							'confirm' => '', 'confirmSubMessage' => \IPS\Member::loggedIn()->language()->get( 'convert_finish_confirm' )
						)
					);
				}

				$return[] = array(
					'icon'	=> 'pencil',
					'title'	=> 'edit',
					'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage&do=edit&id={$row['app_id']}" ),
				);

				$return[] = array(
					'icon'	=> 'times-circle',
					'title'	=> 'delete',
					'link'	=> \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage&do=delete&id={$row['app_id']}" ),
					'data'		=> array( 'delete' => '' ),
				);

				return $return;
			}
			catch( \InvalidArgumentException $e )
			{
				return array();
			}
		};

		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'menu__convert_manage' );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}
	
	/**
	 * Create New Converter
	 *
	 * @return	void
	 */
	public function software()
	{
		if ( \IPS\IN_DEV === FALSE )
		{
			\IPS\Output::i()->error( 'new_sofware_not_in_dev', '1V100/1', 403 );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'classname', NULL, TRUE ) );
		
		$apps = array();
		foreach( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/convert/sources/Library' ) AS $file )
		{
			if ( !$file->isDir() AND !$file->isDot() AND ( \substr( $file->getFilename(), -4 ) == '.php' ) AND $file->getFilename() != 'Board.php' AND $file->getFilename() != 'Ccs.php' )
			{
				$apps[ str_replace( '.php', '', $file->getFilename() ) ] = str_replace( '.php', '', $file->getFilename() );
			}
		}
		
		$form->add( new \IPS\Helpers\Form\Select( 'library', NULL, FALSE, array( 'options' => $apps ) ) );
		
		if ( $values = $form->values() )
		{
			$default	= file_get_contents( \IPS\ROOT_PATH . '/applications/convert/data/defaults/Software.txt' );
			$code		= str_replace( array( '<#CLASS#>', '<#APP#>', '<#CLASS_LOWER#>' ), array( $values['classname'], $values['library'], mb_strtolower( $values['classname'] ) ), $default );
			\file_put_contents( \IPS\ROOT_PATH . '/applications/convert/sources/Software/' . $values['library'] . '/' . $values['classname'] . '.php', $code );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage" ), 'saved' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'new_software' );
		\IPS\Output::i()->output	= (string) $form;
	}
	
	/**
	 * Create New Library
	 *
	 * @return	void
	 */
	public function library()
	{
		if ( \IPS\IN_DEV === FALSE )
		{
			\IPS\Output::i()->error( 'new_library_not_in_dev', '1V100/2', 403 );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'classname', NULL, TRUE ) );
		
		if ( $values = $form->values() )
		{
			$default	= file_get_contents( \IPS\ROOT_PATH . '/applications/convert/data/defaults/Library.txt' );
			$code		= str_replace( array( '<#CLASS#>', '<#CLASS_LOWER#>' ), array( $values['classname'], mb_strtolower( $values['classname'] ) ), $default );
			\file_put_contents( \IPS\ROOT_PATH . '/applications/convert/sources/Library/' . $values['classname'] . '.php', $code );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=convert&module=manage&controller=manage' ), 'saved' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'new_library' );
		\IPS\Output::i()->output	= (string) $form;
	}
	
	/**
	 * Edit a conversion
	 *
	 * @return	void
	 */
	public function edit()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V100/3' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V100/4', 404 );
		}
		
		$classname = $app->getSource( FALSE );
		
		$form = new \IPS\Helpers\Form;
		$form->addMessage( $classname::getPreConversionInformation(), '', FALSE );
		$form->add( new \IPS\Helpers\Form\Text( 'db_host', $app->db_host, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'db_port', $app->db_port, FALSE, array( 'max' => 65535, 'min' => 1 ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'db_db', $app->db_db, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'db_user', $app->db_user, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Password( 'db_pass', $app->db_pass, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'db_prefix', $app->db_prefix, FALSE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'db_charset', $app->db_charset, FALSE ) );
		
		if ( $values = $form->values() )
		{
			$app->db_host		= $values['db_host'];
			$app->db_port       = $values['db_port'];
			$app->db_db			= $values['db_db'];
			$app->db_user		= $values['db_user'];
			$app->db_pass		= $values['db_pass'];
			$app->db_prefix		= $values['db_prefix'];
			$app->db_charset	= $values['db_charset'];
			$app->save();
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage" ), 'saved' );
		}
		
		\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack( 'edit_conversion' );
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Delete a Conversion
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V100/5' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );

			/* Check for children */
			try
			{
				\IPS\DB::i()->select( '*', 'convert_apps', array( 'parent=?', $app->app_id ) )->first();
				\IPS\Output::i()->error( 'conversion_app_has_children', '2V100/6' );
			}
			catch( \UnderflowException $ex ) { }
			
			$app->delete();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=convert&module=manage&controller=manage' ), 'conversion_deleted' );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V100/6', 404 );
		}
	}
}