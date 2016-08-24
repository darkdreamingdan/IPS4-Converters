<?php

/**
 * @brief		Converter XenForoRm Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Cms;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Xenfororm extends \IPS\convert\Software
{
	/**
	 * Software Name
	 *
	 * @return	string
	 * @throws	\BadMethodCallException
	 */
	public static function softwareName()
	{
		/* Child classes must override this method */
		return "XenForo Resource Manager Articles";
	}
	
	/**
	 * Software Key
	 *
	 * @return	string
	 * @throws	\BadMethodCalLException
	 */
	public static function softwareKey()
	{
		/* Child classes must override this method */
		return "xenfororm";
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return	array
	 * @throws	\BadMethodCallException
	 * @todo
	 */
	public static function canConvert()
	{
		return array(
			'convert_cms_databases'				=> array(
				'table'								=> 'cms_databases',
				'where'								=> NULL,
			),
			'convert_cms_database_categories'	=> array(
				'table'								=> 'xf_resource_category',
				'where'								=> array( "allow_fileless=?", 1 )
			),
			'convert_cms_database_records'		=> array(
				'table'								=> 'xf_resource',
				'where'								=> array( "is_fileless=?", 1 )
			)
		);
	}
	
	/**
	 * Count Source Rows for a specific step
	 *
	 * @param	string		$table		The table containing the rows to count.
	 * @param	array|NULL	$where		WHERE clause to only count specific rows, or NULL to count all.
	 * @return	integer
	 */
	public function countRows( $table, $where=NULL )
	{
		switch( $table )
		{
			case 'cms_databases':
				return 1;
				break;
				
			default:
				return parent::countRows( $table, $where );
				break;
		}
	}
	
	/**
	 * Uses Prefix
	 *
	 * @return	bool
	 */
	public static function usesPrefix()
	{
		return FALSE;
	}
	
	/**
	 * Can we convert passwords from this software.
	 *
	 * @return 	boolean
	 */
	public static function loginEnabled()
	{
		return FALSE;
	}
	
	/**
	 * Can we convert settings?
	 *
	 * @return	boolean
	 */
	public static function canConvertSettings()
	{
		return FALSE;
	}
	
	/**
	 * Settings Map
	 *
	 * @return	array
	 */
	public function settingsMap()
	{
		return array();
	}
	
	/**
	 * Settings Map Listing
	 *
	 * @return	array
	 */
	public function settingsMapList()
	{
		return array();
	}
	
	/**
	 * Requires Parent
	 *
	 * @return	boolean
	 */
	public static function requiresParent()
	{
		return TRUE;
	}
	
	/**
	 * Possible Parent Conversions
	 *
	 * @return	NULL|array
	 */
	public static function parents()
	{
		return array( 'core' => array( 'xenforo' ) );
	}
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to start this conversion
	 *
	 * @return	string
	 */
	public static function getPreConversionInformation()
	{
		return NULL;
	}
	
	/**
	 * List of conversion methods that require additional information
	 *
	 * @return	array
	 */
	public static function checkConf()
	{
		return array();
	}
	
	/**
	 * Get More Information
	 *
	 * @return	array
	 * @todo	Refactor. This isn't efficient anymore.
	 */
	public function getMoreInfo( $method )
	{
		return array();
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 *
	 * @return	string		Message to display
	 */
	public function finish()
	{
		
	}
	
	/**
	 * Fix post data
	 *
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 **/
	public static function fixPostData( $post )
	{
		return $post;
	}
	
	/**
	 * Helper to fetch a xenforo phrase
	 *
	 * @param $title
	 *
	 * @return string|null	Phrase
	 */
	protected function getPhrase( $title )
	{
		try
		{
			return $this->db->select( 'phrase_text', 'xf_phrase', array( "title=?", $title ) )->first();
		}
		catch( \UnderflowException $e )
		{
			return NULL;
		}
	}
	
	public function convert_cms_databases()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass->convert_cms_database( array(
			'database_id'			=> 1,
			'database_name'			=> "Resources",
			'database_sln'			=> 'resource',
			'database_pln'			=> 'resources',
			'database_scn'			=> 'Resource',
			'database_pcn'			=> 'Resources',
			'database_ia'			=> 'a resource',
		), array(
			array(
				'field_id'				=> 1,
				'field_name'			=> 'Title',
				'field_type'			=> 'Text',
				'field_key'				=> 'resource_title',
				'field_required'		=> 1,
				'field_position'		=> 1,
				'field_display_listing'	=> 1,
				'field_is_title'		=> 1,
			),
			array(
				'field_id'				=> 2,
				'field_name'			=> 'Tag Line',
				'field_type'			=> 'Text',
				'field_key'				=> 'resource_tagline',
				'field_required'		=> 1,
				'field_position'		=> 2,
			),
			array(
				'field_id'				=> 3,
				'field_name'			=> 'Content',
				'field_type'			=> 'Editor',
				'field_key'				=> 'resource_content',
				'field_required'		=> 1,
				'field_position'		=> 3,
				'field_is_content'		=> 1,
			)
		) );
		
		/* Throw an exception here to tell the library that we're done with this step */
		throw new \IPS\convert\Software\Exception;
	}
	
	public function convert_cms_database_categories()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'resource_category_id' );
		
		foreach( $this->fetch( 'xf_resource_category', 'resource_category_id', array( 'allow_fileless=?', 1 ) ) AS $row )
		{
			$forumoverride	= 0;
			$forumid		= 0;
			if ( $row['thread_node_id'] )
			{
				$forumoverride	= 1;
				$forumid		= $row['thread_node_id'];
			}
			
			$info = array(
				'category_id'			=> $row['resource_category_id'],
				'category_database_id'	=> 1,
				'category_name'			=> $row['category_title'],
				'category_position'		=> $row['display_order'],
				'category_fields'		=> array( 'resource_title', 'resource_tagline', 'resource_content' ),
			);
			
			$libraryClass->convert_cms_database_category( $info );
			
			$libraryClass->setLastKeyValue( $row['resource_category_id'] );
		}
	}
	
	public function convert_cms_database_records()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'resource_id' );
		
		foreach( $this->fetch( 'xf_resource', 'resource_id', array( 'is_fileless=?', 1 ) ) AS $row )
		{
			$post = $this->db->select( 'message, post_date', 'xf_resource_update', array( "resource_id=?", $row['resource_id'] ), "post_date DESC" )->first();
			
			switch( $row['resource_state'] )
			{
				case 'visible':
					$approved = 1;
					break;
				
				case 'moderated':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
			}
			
			$info = array(
				'record_id'				=> $row['resource_id'],
				'record_database_id'	=> 1,
				'member_id'				=> $row['user_id'],
				'record_saved'			=> $row['resource_date'],
				'record_updated'		=> $post['post_date'],
				'category_id'			=> $row['resource_category_id'],
				'record_approved'		=> $approved,
				'record_topicid'		=> $row['discussion_thread_id'],
				'record_publish_date'	=> $row['resource_date'],
			);
			
			$fields = array( 1 => $row['title'], 2 => $row['tag_line'], 3 => $post['message'] );
			
			$libraryClass->convert_cms_database_record( $info, $fields );
			
			$libraryClass->setLastKeyValue( $row['resource_id'] );
		}
	}
}