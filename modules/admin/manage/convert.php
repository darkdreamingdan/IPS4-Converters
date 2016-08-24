<?php


namespace IPS\convert\modules\admin\manage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * convert
 */
class _convert extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->responsive = FALSE;
		\IPS\Dispatcher::i()->checkAcpPermission( 'convert_manage' );
		parent::execute();
	}

	/**
	 * Show the conversion menu
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( ! isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/1' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V101/2', 404 );
		}
		
		$softwareClass				= $app->getSource();
		$libraryClass				= $softwareClass->getLibrary();
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'converting_x_to_x', FALSE, array( 'sprintf' => array( $softwareClass::softwareName(), \IPS\Application::load( $app->sw )->_title ) ) );
		
		/* Build our table. If I can do this using only the Table helper, I'll be impressed */
		try
		{
			$menuRows = $libraryClass->menuRows();
		}
		catch( \IPS\convert\Exception $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '1V101/3' );
		}
		
		$table						= new \IPS\Helpers\Table\Custom( $menuRows, \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert" ) );
		$table->rowsTemplate		= array( \IPS\Theme::i()->getTemplate( 'table' ), 'convertMenuRow' );
		$table->extra				= array( 'sessionData' => $app->_session, 'appClass' => $app, 'softwareClass' => $softwareClass, 'libraryClass' => $libraryClass, 'menuRows' => $menuRows );
		$table->mainColumn			= 'step_title';
		$table->showAdvancedSearch	= FALSE;
		$table->noSort				= array( 'step_title', 'ips_rows', 'source_rows', 'per_cycle', 'empty_local_data', 'step_method' );
		$table->include				= array( 'step_title', 'ips_rows', 'source_rows', 'per_cycle', 'empty_local_data', 'step_method' );
		$table->parsers				= array(
			'step_title' => function( $row )
			{
				return \IPS\Member::loggedIn()->language()->addToStack( $row );
			},
		);
		
		\IPS\Output::i()->output = '';
		
		if ( $softwareClass::canConvertSettings() !== FALSE )
		{
			\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'table' )->settingsMessage( $app );
		}
		
		\IPS\Output::i()->output	.= $table;
		
		if ( $libraryClass->getPostConversionInformation() != NULL )
		{
			\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'table' )->postConversionInformation( $libraryClass->getPostConversionInformation() );
		}
	}
	
	/**
	 * Remove Converted Data
	 *
	 * @return	void
	 */
	protected function emptyData()
	{
		if ( ! isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/4' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V101/5' );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'removing_data' );
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=emptyData&id={$app->app_id}&method=" . \IPS\Request::i()->method ),
		function( $data ) use ( $app )
		{
			try
			{
				return $app->getSource()->getLibrary()->emptyData( $data, \IPS\Request::i()->method );
			}
			catch( \IPS\convert\Exception $e )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=error&id={$app->app_id}" ) );
			}
		},
		function() use ( $app )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&id={$app->app_id}" ) );
		} );
	}
	
	/**
	 * Run a conversion step
	 *
	 * @return	void
	 */
	protected function runStep()
	{
		if ( ! isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/6' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V101/7', 404 );
		}
		
		/* Do we need more information? */
		$softwareClass	= $app->getSource();
		if ( ! isset( $app->_session['more_info'][ \IPS\Request::i()->method ] ) OR ( isset( \IPS\Request::i()->reconfigure ) AND \IPS\Request::i()->reconfigure == 1 ) )
		{
			if ( in_array( \IPS\Request::i()->method, $softwareClass::checkConf() ) )
			{
				$getMoreInfo	= $softwareClass->getMoreInfo( \IPS\Request::i()->method );
				if ( is_array( $getMoreInfo ) AND count( $getMoreInfo ) )
				{
					$form = new \IPS\Helpers\Form( \IPS\Request::i()->method . '_more_info', 'continue' );
					$form->hiddenValues['per_cycle'] = \IPS\Request::i()->per_cycle;
					
					if ( isset( \IPS\Request::i()->empty_local_data ) AND \IPS\Request::i()->empty_local_data == 1 )
					{
						$form->hiddenValues['empty_local_data'] = 1;
					}
					
					if ( isset( \IPS\Request::i()->reconfigure ) )
					{
						$form->hiddenValues['reconfigure'] = 1;
					}
					
					$hints = array();
					foreach( $getMoreInfo AS $key => $input )
					{
						$fieldClass = $input['field_class'];
						$form->add( new $fieldClass( $key, $input['field_default'], $input['field_required'], $input['field_extra'], isset( $input['field_validation'] ) ? $input['field_validation'] : NULL ) );
						if ( $input['field_hint'] !== NULL )
						{
							$form->addMessage( $input['field_hint'], 'ipsMessage ipsMessage_info' );
						}
					}
					
					if ( $values = $form->values() )
					{
						$per_cycle = $values['per_cycle'];
						unset( $values['per_cycle'] );
						
						$app->saveMoreInfo( \IPS\Request::i()->method, $values );
						$extra = '';
						if ( isset( \IPS\Request::i()->empty_local_data ) AND \IPS\Request::i()->empty_local_data == 1 )
						{
							$extra .= "&empty_local_data=1";
						}
						\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$app->app_id}&per_cycle={$per_cycle}&method=" . \IPS\Request::i()->method . $extra ) );
					}
					
					\IPS\Output::i()->title	= \IPS\Member::loggedIn()->language()->addToStack( 'more_info_needed' );
					\IPS\Output::i()->output = $form;
					return;
				}
			}
		}
		
		/* If we decided to truncate, do so now */
		if ( isset( \IPS\Request::i()->empty_local_data ) AND \IPS\Request::i()->empty_local_data == 1 )
		{
			$app->getSource()->getLibrary()->emptyLocalData( \IPS\Request::i()->method );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'converting' );
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$app->app_id}&per_cycle=" . \IPS\Request::i()->per_cycle . "&method=" . \IPS\Request::i()->method ),
		function( $data ) use ( $app )
		{
			try
			{
				return $app->getSource()->getLibrary()->process( $data, \IPS\Request::i()->method, \IPS\Request::i()->per_cycle );
			}
			catch( \IPS\convert\Exception $e )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=error&id={$app->app_id}" ) );
			}
		},
		function() use ( $app )
		{
			/* Do we have more steps to process? If so, go to those first. */
			$canConvert = $app->getSource()->canConvert();
			if ( isset( $canConvert[ \IPS\Request::i()->method ]['extra_steps'] ) )
			{
				/* See which one we need to run next. */
				$next = NULL;
				foreach( $canConvert[ \IPS\Request::i()->method ]['extra_steps'] AS $next )
				{
					if ( in_array( $next, $app->_session['completed'] ) )
					{
						$next = NULL;
						continue;
					}
				}
				
				/* Found one? */
				if ( !is_null( $next ) )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=runStep&id={$app->app_id}&per_cycle=" . \IPS\Request::i()->per_cycle . "&method={$next}" ) );
				}
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&id={$app->app_id}" ) );
		} );
	}
	
	/**
	 * Show an error
	 *
	 * @return	void
	 */
	public function error()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/8' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V101/9', 404 );
		}
		
		/* Load the last actual error logged */
		$error = \IPS\Db::i()->select( '*', 'convert_logs', array( 'log_app=?', $app->app_id ), 'log_id DESC', 1 )->first();
		
		/* Just use generic error wrapper */
		\IPS\Output::i()->error( $error['log_method'] . ': ' . $error['log_message'], '2V101/A' );
	}
	
	/**
	 * Convert Settings
	 *
	 * @return	void
	 */
	public function settings()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/B' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'conversion_app_not_found', '2V101/C', 404 );
		}
		
		$softwareClass = $app->getSource();
		
		if ( $softwareClass::canConvertSettings() === FALSE )
		{
			\IPS\Output::i()->error( 'settings_conversion_not_supported', '2V101/D' );
		}
		
		$form = new \IPS\Helpers\Form;
		foreach( $softwareClass->settingsMapList() AS $key => $setting )
		{
			$value = $setting['value'];
			if ( is_bool( ( $setting['value'] ) ) )
			{
				$value = $setting['value'] === TRUE ? 'On' : 'Off';
			}
			
			$form->add( new \IPS\Helpers\Form\Checkbox( $setting['our_key'], TRUE, FALSE, array( 'label' => $value ) ) );
		}
		
		if ( $values = $form->values() )
		{
			$softwareClass->convert_settings( $values );
			unset( \IPS\Data\Store::i()->settings );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&id={$app->app_id}" ), 'saved' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'converting_settings' );
		\IPS\Output::i()->output	= $form;
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->error( 'no_conversion_app', '2V101/E' );
		}
		
		try
		{
			$app = \IPS\convert\App::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\output::i()->error( 'conversion_app_not_found', '2V101/F', 404 );
		}

		/* Make sure the user confirmed they want to finish */
		\IPS\Request::i()->confirmedDelete( 'convert_finish_confirm_title', 'convert_finish_confirm', 'finish' );

		$softwareClass = $app->getSource();
		
		$messages = array();
		/* If we have a parent, run them. */
		try
		{
			if ( $app->parent )
			{
				$parent = \IPS\convert\App::load( $app->parent );
				
				if ( method_exists( $parent->getSource(), 'finish' ) )
				{
					$return = $parent->getSource()->finish();
					$parent->app->log( 'app_finished', __METHOD__, \IPS\convert\App::LOG_NOTICE );
					
					if ( is_array( $return ) )
					{
						$messages = array_merge( $messages, $return );
					}
				}
				
				/* Run siblings if need be */
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'convert_apps', array( "parent=? AND app_id!=?", $parent->app_id, $app->app_id ) ), 'IPS\convert\App' ) AS $sibling )
				{
					if ( method_exists( $sibling->getSource(), 'finish' ) )
					{
						$return = $sibling->getSource()->finish();
						$sibling->app->log( 'app_finished', __METHOD__, \IPS\convert\App::LOG_NOTICE );
						
						if ( is_array( $return ) )
						{
							$messages = array_merge( $messages, $return );
						}
					}
				}
			}
			else
			{
				/* No parent - bubble up to the exception */
				throw new \OutOfRangeException;
			}
		}
		catch( \OutOfRangeException $e )
		{
			/* This is a parent - run it's children */
			foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'convert_apps', array( "parent=?", $app->app_id ) ), 'IPS\convert\App' ) AS $child )
			{
				$childSoftwareClass = $child->getSource();
				
				if ( method_exists( $childSoftwareClass, 'finish' ) )
				{
					$return = $childSoftwareClass->finish();
					$childSoftwareClass->app->log( 'app_finished', __METHOD__, \IPS\convert\App::LOG_NOTICE );
					
					if ( is_array( $return ) )
					{
						$messages = array_merge( $messages, $return );
					}
				}
			}
		}
		
		/* And finally, run this one */
		if ( method_exists( $softwareClass, 'finish' ) )
		{
			$return = $softwareClass->finish();
			$softwareClass->app->log( 'app_finished', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			
			if ( is_array( $return ) )
			{
				$messages = array_merge( $messages, $return );
			}
		}
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=convert&module=manage&controller=manage" ), implode( ', ', $messages ) );
	}
}