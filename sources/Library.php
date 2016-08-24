<?php

/**
 * @brief		Converter Library Master Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class _Library
{
	/**
	 * @brief	Flag to indicate that we are using a specific key, and should do WHERE static::$currentKeyName > static::$currentKeyValue rather than LIMIT 0,1000
	 */
	public static $usingKeys			= FALSE;
	
	/**
	 * @brief	The name of the current key in the database for this step
	 */
	public static $currentKeyName	= NULL;
	
	/**
	 * @brief	The current value of the key
	 */
	public static $currentKeyValue	= 0;
	
	/**
	 * @brief	Amount of data being processed per cycle.
	 */
	public static $perCycle				= 2000;
	
	/**
	 * @brief	If not using keys, the current start value for LIMIT clause.
	 */
	public static $startValue		= 0;
	
	/**
	 * @brief	The current conversion step
	 */
	public static $action			= NULL;
	
	/**
	 * @brief	\IPS\convert\Software instance for the software we are converting from
	 */
	public $software					= NULL;
	
	/**
	 * @brief	Array of field types
	 */
	protected static $fieldTypes = array( 'Address', 'Checkbox', 'CheckboxSet', 'Codemirror', 'Color', 'Date', 'Editor', 'Email', 'Member', 'Number', 'Password', 'Poll', 'Radio', 'Rating', 'Select', 'Tel', 'Text', 'TextArea', 'Upload', 'Url', 'YesNo' );
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\convert\Software	$software	Software Instance we are converting from
	 * @return	void
	 */
	public function __construct( \IPS\convert\Software $software )
	{
		$this->software = $software;
	}
	
	/**
	 * When called at the start of a conversion step, indicates that we are using a specific key for WHERE clauses which nets performance improvements
	 *
	 * @param	string	$key	The key to use
	 * @return	void
	 */
	public static function setKey( $key )
	{
		static::$usingKeys		= TRUE;
		static::$currentKeyName	= $key;
	}
	
	/**
	 * When using a key reference, sets the current value of that key for the WHERE clause
	 *
	 * @param	mixed	$value	The current value
	 * @return	void
	 */
	public function setLastKeyValue( $value )
	{
		$_SESSION['currentKeyValue'] = $value;
		static::$currentKeyValue = $value;
	}
	
	/**
	 * Processes a conversion cycle.
	 *
	 * @param	integer									$data	Data from the previous step.
	 * @param	\IPS\convert\App	$app	Application Class for the current conversion
	 * @return	array|NULL	Data for the MultipleRedirect
	 */
	public function process( $data, $method, $perCycle=NULL )
	{
		if ( !is_null( $perCycle ) )
		{
			static::$perCycle = $perCycle;
		}
		
		/* temp */
		$classname			= get_class( $this->software );
		$canConvert			= $classname::canConvert();
		static::$action		= $canConvert[$method]['table'];
		static::$startValue	= $data;
		$total = $this->software->countRows( static::$action, $canConvert[$method]['where'] );
		
		if ( $data >= $total )
		{
			$completed	= $this->software->app->_session['completed'];
			$more_info	= $this->software->app->_session['more_info'];
			if ( !in_array( $method, $completed ) )
			{
				$completed[] = $method;
			}
			$this->software->app->_session = array( 'working' => array(), 'more_info' => $more_info, 'completed' => $completed );
			unset( $_SESSION['currentKeyValue'] );
			return NULL;
		}
		else
		{
			/* Fetch data from the software */
			try
			{
				$this->software->$method();
			}
			catch( \IPS\convert\Software\Exception $e )
			{
				/* A Software Exception indicates we are done */
				$completed	= $this->software->app->_session['completed'];
				$more_info	= $this->software->app->_session['more_info'];
				if ( !in_array( $method, $completed ) )
				{
					$completed[] = $method;
				}
				$this->software->app->_session = array( 'working' => array(), 'more_info' => $more_info, 'completed' => $completed );
				unset( $_SESSION['currentKeyValue'] );
				return NULL;
			}
			catch( \Exception $e )
			{
				try
				{
					\IPS\Log::i( \LOG_CRIT )->write( get_class( $e ) . "\n" . $e->getCode() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'convert_exception' );
				}
				catch ( \Exception $e ){}

				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING );
				throw new \IPS\convert\Exception;
			}
			catch( \ErrorException $e )
			{
				try
				{
					\IPS\Log::i( \LOG_CRIT )->write( get_class( $e ) . "\n" . $e->getCode() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'convert_exception' );
				}
				catch ( \Exception $e ){}

				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING );
				throw new \IPS\convert\Exception;
			}
			
			$this->software->app->_session = array_merge( $this->software->app->_session, array( 'working' => array( $method => $data + static::$perCycle ) ) );
			return array( $data + static::$perCycle, sprintf( \IPS\Member::loggedIn()->language()->get( 'converted_x_of_x' ), ( $data + static::$perCycle > $total ) ? $total : $data + static::$perCycle, \IPS\Member::loggedIn()->language()->addToStack( $method ), $total ), 100 / $total * ( $data + static::$perCycle ) );
		}
	}
	
	/**
	 * Empty Conversion Data
	 *
	 * @param	integer									$data	Data from the previous step.
	 * @param	\IPS\convert\App	$app	Application Class for the current conversion
	 * @return	array|NULL	Data for the MultipleRedirect
	 */
	public function emptyData( $data, $method )
	{
		$perCycle = 500;
		
		/* temp */
		$classname			= get_class( $this->software );
		$canConvert			= $this->menuRows();
		
		if ( !isset( $canConvert[$method]['link_type'] ) )
		{
			return NULL;
		}
		
		$type = $canConvert[$method]['link_type'];
		
		if ( !isset( $_SESSION['emptyConvertedDataCount'] ) )
		{
			$count = 0;
			/* Just one type? */
			if ( !is_array( $type ) )
			{
				foreach( array( 'convert_link', 'convert_link_pms', 'convert_link_posts', 'convert_link_topics' ) AS $table )
				{
					$count += \IPS\Db::i()->select( 'COUNT(*)', $table, array( "type=? AND app=?", $type, $this->software->app->app_id ) )->first();
				}
			}
			else
			{
				foreach( $type AS $t )
				{
					foreach( array( 'convert_link', 'convert_link_pms', 'convert_link_posts', 'convert_link_topics' ) AS $table )
					{
						$count += \IPS\Db::i()->select( 'COUNT(*)', $table, array( "type=? AND app=?", $t, $this->software->app->app_id ) )->first();
					}
				}
			}
			
			$_SESSION['emptyConvertedDataCount'] = $count;
		}
		
		if ( $data >= $_SESSION['emptyConvertedDataCount'] )
		{
			unset( $_SESSION['emptyConvertedDataCount'] );
			return NULL;
		}
		else
		{
			/* Fetch data from the software */
			try
			{
				/* If we're dealing with more than one type, then we can just delete from any one at random until we're done */
				if ( is_array( $type ) )
				{
					$type = array_rand( $type );
				}
				
				switch( $type )
				{
					case 'forums_topics':
						$table = 'convert_link_topics';
						break;
					
					case 'forums_posts':
						$table = 'convert_link_posts';
						break;
					
					case 'core_message_topics':
					case 'core_message_posts':
					case 'core_message_topic_user_map':
						$table = 'convert_link_pms';
						break;
					
					default:
						$table = 'convert_link';
						break;
				}
				
				$total	= (int) \IPS\Db::i()->select( 'COUNT(*)', $table, array( "type=? AND app=?", $type, $this->software->app->app_id ) )->first();
				$rows	= iterator_to_array( \IPS\Db::i()->select( 'link_id, ipb_id', $table, array( "type=? AND app=?", $type, $this->software->app->app_id ), "link_id ASC", array( 0, $perCycle ) )->setKeyField( 'link_id' )->setValueField( 'ipb_id' ) );
				$def	= \IPS\Db::i()->getTableDefinition( $type );
				
				if ( isset( $def['indexes']['PRIMARY']['columns'] ) )
				{
					$id = array_pop( $def['indexes']['PRIMARY']['columns'] );
				}
				
				\IPS\Db::i()->delete( $type, array( \IPS\Db::i()->in( $id, array_values( $rows ) ) ) );
				\IPS\Db::i()->delete( $table, array( \IPS\Db::i()->in( 'link_id', array_keys( $rows ) ) ) );
			}
			catch( \Exception $e )
			{
				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING );
				\IPS\Log::i( \LOG_CRIT )->write( get_class( $e ) . "\n" . $e->getCode() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'convert_error' );
				throw new \IPS\convert\Exception;
			}
			catch( \ErrorException $e )
			{
				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_ERROR );
				\IPS\Log::i( \LOG_CRIT )-write( get_class( $e ) . "\n", $e->getCode() . ": " . $e->getMessage() . "\n" . $e->getTraceAsString(), 'convert_error' );
				throw new \IPS\convert\Exception;
			}
			
			return array( $data + $perCycle, sprintf( \IPS\Member::loggedIn()->language()->get( 'removed_x_of_x' ), ( $data + $perCycle > $total ) ? $_SESSION['emptyConvertedDataCount'] : $data + $perCycle, \IPS\Member::loggedIn()->language()->addToStack( $method ), $_SESSION['emptyConvertedDataCount'] ), 100 / $_SESSION['emptyConvertedDataCount'] * ( $data + $perCycle ) );
		}
	}
	
	/**
	 * Truncates data from local database
	 *
	 * @param	string	Convert method to run truncate call for.
	 * @return	void
	 */
	public function emptyLocalData( $method )
	{
		$truncate = $this->truncate( $method );
		foreach( $truncate AS $table => $where )
		{
			/* Kind of a hacky way to make sure we truncate the right forums archive table */
			if ( $table === 'forums_archive_posts' )
			{
				\IPS\forums\Topic\ArchivedPost::db()->delete( $table, $where );
			}
			else
			{
				\IPS\Db::i()->delete( $table, $where );
			}
			
			/* Remove links... we don't really care about which table they are in right now */
			foreach( array( 'convert_link', 'convert_link_pms', 'convert_link_posts', 'convert_link_topics' ) AS $linkTable )
			{
				\IPS\Db::i()->delete( $linkTable, array( "type=? AND app=?", $table, $this->software->app->app_id ) );
			}
		}
		unset( $_SESSION['currentKeyValue'] );
	}
	
	/**
	 * Magic __call() method
	 *
	 * @param	string	$name			The method to call without convert_ prefix.
	 * @param	mixed	$arguements		Arguments to pass to the method
	 * @return 	mixed
	 */
	public function __call( $name, $arguments )
	{
		if ( method_exists( $this, 'convert_' . $name ) )
		{
			return call_user_func( array( $this, 'convert_' . $name ), $arguments );
		}
		else
		{
			if ( method_exists( $this, $name ) )
			{
				return call_user_func( array( $this, $name ), $arguments );
			}
			else
			{
				\IPS\Log::i( \LOG_CRIT )->write( "Call to undefined method in " . get_class( $this ) . "::{$name}" );
				return NULL;
			}
		}
	}
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to complete this conversion
	 *
	 * @return	string
	 */
	abstract public function getPostConversionInformation();
	
	/**
	 * Returns an array of items that we can convert, including the amount of rows stored in the Community Suite as well as the recommend value of rows to convert per cycle
	 *
	 * @return	array
	 */
	abstract public function menuRows();
	
	/**
	 * Returns an array of tables that need to be truncated when Empty Local Data is used
	 *
	 * @param	string	The method to truncate
	 * @return	array
	 */
	abstract protected function truncate( $method );
}