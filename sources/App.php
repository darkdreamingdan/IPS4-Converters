<?php

/**
 * @brief		Converter Applications Class
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

class _App extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId		= 'app_id';
	
	/**
	 * @brief	[ActiveRecord] Database table
	 */
	public static $databaseTable		= 'convert_apps';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields	= array( 'name' );
	
	/**
	 * @brief	Array Storage of loaded ID links.
	 */
	protected $linkCache				= array();
	
	/**
	 * @brief	Flag to indicate the log is simply a notice, and that it is informational only. Useful for when data is missing, but can be covered via default values.
	 */
	const LOG_NOTICE					= 1;
	
	/**
	 * @brief	Flag to indicate the log is a warning, and should be checked to see if conversion happened correctly. Useful for indicating something cannot be converted due to being orphaned or otherwise having missing data (ex. no parent topic).
	 */
	const LOG_WARNING					= 2;
	
	/**
	 * @brief	Flag to indicate something went wrong, and the data did not convert correctly. Useful for indicating when something legitimately does not convert, but should.
	 */
	const LOG_ERROR						= 3;
	
	/**
	 * [ActiveRecord]	Save Record
	 *
	 * @return	void
	 */
	public function save()
	{
		if ( !$this->app_id )
		{
			$this->start_date = time();
			parent::save();
			
			\IPS\convert\Application::checkConvParent( $this->getSource()->getLibrary()->app );
			
			\IPS\Db::i()->insert( 'convert_app_sessions', array(
				'session_app_id'	=> $this->app_id,
				'session_app_data'	=> json_encode( array( 'completed' => array(), 'working' => array(), 'more_info' => array() ) ),
			) );
		}
		
		$classname			= get_class( $this->getSource() );
		$this->login		= ( $classname::loginEnabled() === TRUE ) ? 1 : 0;
		$this->db_driver	= 'mysql';  /* I was going to drop this, but it has the potential for expansion in the future, as all we do is select from the source */
		$this->app_merge	= 1;
		parent::save();
	}
	
	/**
	 * [ActiveRecord]	Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( array( 'convert_link', 'convert_link_pms', 'convert_link_topics', 'convert_link_posts' ) AS $table )
		{
			\IPS\Db::i()->delete( $table, array( 'app=?', $this->app_id ) );
		}
		
		\IPS\Db::i()->delete( 'convert_app_sessions', array( 'session_app_id=?', $this->app_id ) );
		\IPS\Db::i()->delete( 'convert_logs', array( 'log_app=?', $this->app_id ) );
		
		parent::delete();
	}
	
	/**
	 * Save Session Data for this application
	 *
	 * @param	array	Session Data
	 * @return	void
	 */
	public function set__session( $value )
	{
		\IPS\Db::i()->update( 'convert_app_sessions', array( 'session_app_data' => json_encode( $value ) ), array( 'session_app_id=?', $this->app_id ) );
	}
	
	/**
	 * Get Session Data for this application
	 *
	 * @return	array
	 */
	public function get__session()
	{
		try
		{
			return json_decode( \IPS\Db::i()->select( 'session_app_data', 'convert_app_sessions', array( 'session_app_id=?', $this->app_id ) )->first(), TRUE );
		}
		catch( \Exception $e )
		{
			/* If it doesn't exist, create it in the database and return an empty array. */
			\IPS\Db::i()->insert( 'convert_app_sessions', array(
				'session_app_id'	=> $this->app_id,
				'session_app_data'	=> json_encode( array( 'completed' => array(), 'working' => array(), 'more_info' => array() ) )
			) );
			return array( 'completed' => array(), 'working' => array(), 'more_info' => array() );
		}
	}
	
	/**
	 * [Legacy] Get Software. Automatically adjusts if legacy software which has since had its application key changed.
	 *
	 * @return	string
	 */
	public function get_sw()
	{
		switch( $this->_data['sw'] )
		{
			case 'board':
				return 'forums';
			break;
			
			case 'ccs':
				return 'cms';
			break;
			
			default:
				return $this->_data['sw'];
			break;
		}
	}
	
	/**
	 * [Legacy] Automatically fix any legacy application keys that have changed.
	 *
	 * @return	void
	 */
	public function set_sw( $value )
	{
		switch( $value )
		{
			case 'board':
				$this->_data['sw'] = 'forums';
			break;
			
			case 'ccs':
				$this->_data['sw'] = 'cms';
			break;
			
			default:
				$this->_data['sw'] = $value;
			break;
		}
	}
	
	/**
	 * @brief	Parent Store
	 */
	protected $parentStore = NULL;
	
	/**
	 * Get parent application
	 *
	 * @return	\IPS\convert\App
	 * @throws	\BadMethodCallException
	 */
	public function get__parent()
	{
		if ( is_null( $this->parentStore ) )
		{
			if ( ! $this->parent )
			{
				throw new \BadMethodCallException;
			}
			
			try
			{
				$this->parentStore = static::constructFromData( \IPS\Db::i()->select( '*', 'convert_apps', array( "app_id=?", $this->parent ) )->first() );
			}
			catch( \UnderflowException $e )
			{
				throw new \BadMethodCallException;
			}
			catch( \OutOfRangeException $e )
			{
				throw new \BadMethodCallException;
			}
		}
		
		return $this->parentStore;
	}
	
	/**
	 * Retrieves an IPS Community Suite ID from a Foreign ID.
	 *
	 * @param	mixed		$foreign_id		The Foreign ID
	 * @param	string		$type			The type of item.
	 * @param	boolean		$parent			If set to TRUE, then retrieves from the parent application if one is available.
	 * @param	boolean		$mainTable		If set to TRUE, and the type is of either 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', or 'forums_posts', then the ID is retrieved from convert_link, rather than the other link tables.
	 *
	 * @return	integer	The IPS Community Suite ID.
	 * @throws	\OutOfRangeException
	 */
	public function getLink( $foreign_id, $type, $parent=FALSE, $mainTable=FALSE )
	{
		if ( isset( $this->linkCache[$type][$foreign_id] ) )
		{
			return $this->linkCache[$type][$foreign_id];
		}
		
		$table = 'convert_link';
		
		if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', 'forums_posts' ) ) AND $mainTable === FALSE )
		{
			if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map' ) ) )
			{
				$table = 'convert_link_pms';
			}
			else
			{
				$tableType = str_replace( 'forums_', '', $type );
				$table = "convert_link_{$tableType}";
			}
		}
		
		try
		{
			$link = \IPS\Db::i()->select( 'ipb_id', $table, array( 'foreign_id=? AND type=? AND app=?', (string) $foreign_id, $type, ( $parent === TRUE ) ? $this->parent : $this->app_id ) )->first();
			$this->linkCache[$type][$foreign_id] = $link;
			return $this->linkCache[$type][$foreign_id];
		}
		catch( \UnderflowException $e )
		{
			/* If lookup failed, and we have a parent, try it anyway */
			try
			{
				$link = \IPS\Db::i()->select( 'ipb_id', $table, array( 'foreign_id=? AND type=? AND app=?', (string) $foreign_id, $type, $this->parent ) )->first();
				$this->linkCache[$type][$foreign_id] = $link;
				return $this->linkCache[$type][$foreign_id];
			}
			catch( \UnderflowException $e ) {}
			
			/* Still here? Throw the exception */
			throw new \OutOfRangeException( 'link_invalid' );
		}
	}
	
	/**
	 * @brief	Sibling Link Cache
	 */
	protected $siblingLinkCache = array();
	
	/**
	 * Retrieves an IPS Community Suite iD from a Foriegn ID in a Sibling Application
	 *
	 * @param	mixed		$foreign_id		The Foreign ID.
	 * @param	string		$type			The type of item.
	 * @param	string		$sibling		The sibling software library.
	 * @param	boolean		$mainTable		If set to TRUE, and the type is of either 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', or 'forums_posts', then the ID is retrieved from convert_link, rather than the other link tables.
	 *
	 * @return	inteer	The IPS Community Suite ID.
	 * @throws	\OutOfRangeException
	 */
	public function getSiblingLink( $foreign_id, $type, $sibling, $mainTable=FALSE )
	{
		if ( isset( $this->siblingLinkCache[$type][$sibling][$foreign_id] ) )
		{
			return $this->siblingLinkCache[$type][$sibling][$foreign_id];
		}
		
		try
		{
			$sibling = static::constructFromData( \IPS\Db::i()->select( '*', 'convert_apps', array( "sw=? AND parent=?", $sibling, $this->parent ) )->first() );
		}
		catch( \UnderflowException $e )
		{
			throw new \OutOfRangeException( 'sibling_invalid' );
		}
		
		return $sibling->getLink( $foreign_id, $type, FALSE, $mainTable );
	}
	
	/**
	 * Saves a foreign ID to IPS Community Suite ID reference to the convert_link tables.
	 *
	 * @param	integer		$ips_id			The IPS Community Suite ID
	 * @param	mixed		$foreign_id		The Foreign ID
	 * @param	string		$type			The type of item
	 * @param	boolean		$duplicate		If TRUE, then this item is a duplicate and was merged into existing $ips_id
	 * @param	boolean		$mainTable		If TRUE, then link will be stored in the main convert_link table even if $type is 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', or 'forums_posts'
	 *
	 * @return	void
	 */
	public function addLink( $ips_id, $foreign_id, $type, $duplicate=FALSE, $mainTable=FALSE )
	{
		$table = 'convert_link';
		
		if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', 'forums_posts' ) ) AND $mainTable === FALSE )
		{
			if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map' ) ) )
			{
				$table = 'convert_link_pms';
			}
			else
			{
				$tableType = str_replace( 'forums_', '', $type );
				$table = "convert_link_{$tableType}";
			}
		}
		
		\IPS\Db::i()->insert( $table, array(
			'ipb_id'		=> $ips_id,
			'foreign_id'	=> $foreign_id,
			'type'			=> $type,
			'duplicate'		=> ( $duplicate === TRUE ) ? 1 : 0,
			'app'			=> $this->app_id
		) );
		
		$this->linkCache[$type][$foreign_id] = $ips_id;
	}
	
	/**
	 * Checks to see if a link exists for an IPS Community Suite ID
	 *
	 * @param	integer	$ips_id	The IPS Community Suite ID.
	 * @return	void
	 * @throws	\OutOfRangeException
	 */
	public function checkLink( $ips_id, $type, $mainTable=FALSE )
	{
		$table = 'convert_link';
		
		if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map', 'forums_topics', 'forums_posts' ) ) AND $mainTable === FALSE )
		{
			if ( in_array( $type, array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map' ) ) )
			{
				$table = 'convert_link_pms';
			}
			else
			{
				$tableType = str_replace( 'forums_', '', $type );
				$table = "convert_link_{$tableType}";
			}
		}
		
		try
		{
			$link = \IPS\Db::i()->select( '*', $table, array( "app=? AND ipb_id=? AND type=?", $this->app_id, $ips_id, $type ) )->first();
		}
		catch( \UnderflowException $e )
		{
			throw new \OutOfRangeException;
		}
	}
	
	/**
	 * Checks to see if this application also has a sibling of a specific type
	 *
	 * @param	string	$software	The application key to look for
	 * @return	void
	 * @throws	\OutOfRangeException
	 */
	public function checkForSibling( $software )
	{
		try
		{
			\IPS\Db::i()->select( '*', 'convert_apps', array( "sw=? AND parent=?", $software, $this->parent ) )->first();
		}
		catch( \UnderflowException $e )
		{
			throw new \OutOfRangeException;
		}
	}
	
	/**
	 * @brief	Sibling Cache
	 */
	protected $siblingCache = array();
	
	/**
	 * Construct an \IPS\convert\App object for a sibling application.
	 *
	 * @param	string	$software	The application key to look for
	 * @return	\IPS\convert\APP
	 * @throws	\OutOfRangeException
	 */
	public function getSibling( $software )
	{
		if ( !isset( $this->siblingCache[$software] ) )
		{
			try
			{
				$this->siblingCache[$software] = static::constructFromData( \IPS\Db::i()->select( '*', 'convert_apps', array( "sw=? AND parent=?", $software, $this->parent ) )->first() );
			}
			catch( \Exception $e )
			{
				throw new \OutOfRangeException;
			}
		}
		
		return $this->siblingCache[$software];
	}
	
	/**
	 * Fetch the source application class file.
	 *
	 * @param	bool	Construct the object
	 * @param	bool	Establish a database connection ($construct must be TRUE)
	 * @return	\IPS\convert\Software|string
	 * @throws	\InvalidArgumentException
	 */
	public function getSource( $construct=TRUE, $needDB=TRUE )
	{
		if ( ! class_exists( 'IPS\\convert\\Software\\' . ucwords( $this->_data['sw'] ) . '\\' . ucwords( $this->_data['app_key'] ) ) )
		{
			throw new \InvalidArgumentException( 'invalid_source' );
		}
		
		$classname = 'IPS\\convert\\Software\\' . ucwords( $this->_data['sw'] ) . '\\' . ucwords( $this->_data['app_key'] );
		
		if ( $construct )
		{
			return new $classname( $this, $needDB );
		}
		else
		{
			return $classname;
		}
	}
	
	/**
	 * Log Something
	 *
	 * @param	string		$message	The message to log.
	 * @param	string		$method		The current conversion method (convert_posts, convert_topics, etc.)
	 * @param	integer		$severity	The severity level of the log. Default to LOG_NOTICE
	 * @return	void
	 * @throws \InvalidArgumentException
	 */
	public function log( $message, $method, $severity=1, $id=NULL )
	{
		if ( ! in_array( $severity, array( static::LOG_NOTICE, static::LOG_WARNING, static::LOG_ERROR ) ) )
		{
			throw new \InvalidArgumentException( 'invalid_severity' );
		}
		
		\IPS\Db::i()->insert( 'convert_logs', array(
			'log_message'	=> $message,
			'log_app'		=> $this->app_id,
			'log_severity'	=> $severity,
			'log_method'	=> $method,
			'log_item_id'	=> $id,
			'log_time'		=> time()
		) );
	}
	
	/**
	 * Callback function to return all dependencies not yet converted.
	 *
	 * @param	string	Value from depency array
	 * @return	boolean
	 */
	public function dependencies( $value )
	{
		if ( !in_array( $value, $this->_session['completed'] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Saves information about the current step to the session.
	 *
	 * @param	array	$values		Values from the form.
	 * @return	void
	 */
	public function saveMoreInfo( $method, $values=array() )
	{
		$sessionData = $this->_session;
		
		unset( $values['reconfigure'], $values['empty_local_data'] );
		
		$this->_session = array( 'working' => $sessionData['working'], 'completed' => $sessionData['completed'], 'more_info' => array_merge( $sessionData['more_info'], array( $method => $values ) ) );
	}
}