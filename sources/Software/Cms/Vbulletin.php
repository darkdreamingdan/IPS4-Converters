<?php

/**
 * @brief		Converter vBulletin 4.x Pages Class
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

class _Vbulletin extends \IPS\convert\Software
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
		return "vBulletin CMS (4.x only)";
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
		return "vbulletin";
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
		$return = array(
			'convert_cms_blocks'				=> array(
				'table'								=> 'cms_widget',
				'where'								=> NULL,
			),
			'convert_cms_pages'					=> array(
				'table'								=> 'page',
				'where'								=> NULL,
			),
			'convert_cms_databases'				=> array(
				'table'								=> 'database',
				'where'								=> NULL,
			),
			'convert_cms_database_categories'	=> array(
				'table'								=> 'cms_category',
				'where'								=> NULL,
			),
			'convert_cms_database_records'		=> array(
				'table'								=> 'cms_article',
				'where'								=> NULL,
			),
		);
		
		return $return;
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
			case 'cms_widget':
				$blocksWeCanConvert = array();
				foreach( $this->db->select( 'widgettypeid', 'cms_widgettype', array( \IPS\Db::i()->in( 'class', array( 'Rss', 'Static' ) ) ) ) AS $typeid )
				{
					$blocksWeCanConvert[] = $typeid;
				}
				return $this->db->select( 'COUNT(*)', 'cms_widget', array( $this->db->in( 'widgettypeid', $blocksWeCanConvert ) ) )->first();
				break;
			
			case 'page':
			case 'database':
				return 1;
				break;
			
			default:
				return parent::countRows( $table, $where );
				break;
		}
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
		return array( 'core' => array( 'vbulletin' ) );
	}

	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 *
	 * @return	string		Message to display
	 */
	public function finish()
	{
		/* Content Rebuilds */
		return array();
	}
	
	/**
	 * Fix Post Data
	 *
	 * @param	string	Post
	 * @return	string	Fixed Posts
	 */
	public static function fixPostData( $post )
	{
		return \IPS\convert\Software\Core\Vbulletin::fixPostData( $post );
	}
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to start this conversion
	 *
	 * @return	string
	 */
	public static function getPreConversionInformation()
	{
		return "If you are also converting from vBulletin Forums, it is highly recommended that you convert from that application first so article to topic synchronization can occur correctly.";
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
	 */
	public function getMoreInfo( $method )
	{
		return array();
	}

	/**
	 * Get Setting Value - useful for global settings that need to be translated to group or member settings
	 *
	 * @param	$key	The setting key
	 * @return	mixed
	 */
	protected function _setting( $key )
	{
		if ( isset( $this->settingsCache[$key] ) )
		{
			return $this->settingsCache[$key];
		}
		
		try
		{
			$setting = $this->db->select( 'value, defaultvalue', 'setting', array( "varname=?", $key ) )->first();
			
			if ( $setting['value'] )
			{
				$this->settingsCache[$key] = $setting['value'];
			}
			else
			{
				$this->settingsCache[$key] = $setting['defaultvalue'];
			}
		}
		catch( \UnderflowException $e )
		{
			/* If we failed to find it, we probably will fail again on later attempts */
			$this->settingsCache[$key] = NULL;
		}
		
		return $this->settingsCache[$key];
	}
	
	public function convert_cms_blocks()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'widgetid' );
		
				/* We CAN bring over some blocks, like static widgets */
		$blocksWeCanConvert = array();
		$rssTypeId			= NULL;
		foreach( $this->db->select( 'widgettypeid, class', 'cms_widgettype', array( \IPS\Db::i()->in( 'class', array( 'Rss', 'Static' ) ) ) ) AS $type )
		{
			if ( $type['class'] == 'Rss' )
			{
				$rssTypeId = $type['widgettypeid'];
			}
			$blocksWeCanConvert[] = $type['widgettypeid'];
		}
		
		foreach( $this->fetch( 'cms_widget', 'widgetid', array( \IPS\Db::i()->in( 'widgettypeid', $blocksWeCanConvert ) ) ) AS $block )
		{
			$config = $this->db->select( 'name, value', 'widgetconfig', array( "widgetid=?", $block['widgetid'] ) )->setKeyField( 'name' )->setValueField( 'value' );
			
			$info = array(
				'block_id'				=> $block['widgetid'],
				'block_name'			=> $block['title'],
				'block_description'		=> $block['description'],
				'block_plugin'			=> ( $block['widgettypeid'] == $rssTypeId ) ? 'Rss' : NULL,
				'block_plugin_config'	=> ( $block['widgettypeid'] == $rssTypeId ) ? array(
					'block_rss_import_title'	=> $block['title'],
					'block_rss_import_url'		=> $config['url'],
					'block_rss_import_number'	=> $config['max_items'],
					'block_rss_import_cache'	=> 30,
					'block_type'				=> 'plugin',
					'block_editor'				=> 'html',
					'block_plugin'				=> 'Rss',
					'block_plugin_app'			=> 'cms',
					'template_params'			=> '',
					'type'						=> 'plugin',
					'plugin_app'				=> 'cms',
				) : NULL,
				'block_content'			=> ( $block['widgettypeid'] != $rssTypeId ) ? $config['statichtml'] : NULL,
			);
			
			$libraryClass->convert_cms_block( $info );
			
			$libraryClass->setLastKeyValue( $block['widgetid'] );
		}
	}
	
	/* We just need to create a page for the database */
	public function convert_cms_pages()
	{
		$this->getLibrary()->convert_cms_page( array(
			'page_id'		=> 1,
			'page_name'		=> 'vBulletin Articles',
		) );
		
		throw new \IPS\convert\Software\Exception;
	}
	
	/* And we need to create a database, which is a little more complex than the page */
	public function convert_cms_databases()
	{
		$convertedForums = FALSE;
		try
		{
			$this->app->checkForSibling( 'forums' );
			
			$convertedForums = TRUE;
		}
		catch( \OutOfRangeException $e ) {}
		$this->getLibrary()->convert_cms_database( array(
			'database_id'				=> 1,
			'database_name'				=> 'vBulletin Articles',
			'database_sln'				=> 'article',
			'database_pln'				=> 'articles',
			'database_scn'				=> 'Article',
			'database_pcn'				=> 'Articles',
			'database_ia'				=> 'an article',
			'database_record_count'		=> $this->db->select( 'COUNT(*)', 'cms_article' )->first(),
			'database_tags_enabled'		=> 1,
			'database_forum_record'		=> ( $convertedForums ) ? 1 : 0,
			'database_forum_comments'	=> ( $convertedForums ) ? 1 : 0,
			'database_forum_delete'		=> ( $convertedForums ) ? 1 : 0,
			'database_forum_prefix'		=> ( $convertedForums ) ? 'Article: ' : '',
			'database_forum_forum'		=> ( $convertedForums ) ? $this->_setting( 'vbcmsforumid' ) : 0,
			'database_page_id'			=> 1,
		), array(
			array(
				'field_id'				=> 1,
				'field_type'			=> 'Text',
				'field_name'			=> 'Title',
				'field_key'				=> 'article_title',
				'field_required'		=> 1,
				'field_user_editable'	=> 1,
				'field_position'		=> 1,
				'field_display_listing'	=> 1,
				'field_display_display'	=> 1,
				'field_is_title'		=> TRUE,
			),
			array(
				'field_id'				=> 2,
				'field_type'			=> 'Editor',
				'field_name'			=> 'Content',
				'field_key'				=> 'article_content',
				'field_required'		=> 1,
				'field_user_editable'	=> 1,
				'field_position'		=> 2,
				'field_display_listing'	=> 0,
				'field_display_display'	=> 1,
				'field_is_content'		=> TRUE
			)
		) );
		
		throw new \IPS\convert\Software\Exception;
	}
	
	public function convert_cms_database_categories()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'categoryid' );
		
		foreach( $this->fetch( 'cms_category', 'categoryid' ) AS $row )
		{
			$libraryClass->convert_cms_database_category( array(
				'category_id'			=> $row['categoryid'],
				'category_database_id'	=> 1,
				'category_name'			=> $row['category'],
				'category_desc'			=> $row['description'],
				'category_position'		=> $row['catleft'],
				'category_fields'		=> array( 'article_title', 'article_content' )
			) );
			
			$libraryClass->setLastKeyValue( $row['categoryid'] );
		}
	}
	
	/* Now we get to the real stuff */
	public function convert_cms_database_records()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'contentid' );
		
		foreach( $this->fetch( 'cms_article', 'contentid' ) AS $row )
		{
			try
			{
				$node		= $this->db->select( '*', 'cms_node', array( "contenttypeid=? AND contentid=?", $this->_articleContentType(), $row['contentid'] ) )->first();
				$nodeinfo	= $this->db->select( '*', 'cms_nodeinfo', array( "nodeid=?", $node['nodeid'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $row['contentid'] );
				continue;
			}
			
			try
			{
				$category	= $this->db->select( 'categoryid', 'cms_nodecategory', array( "nodeid=?", $node['nodeid'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				/* Create one */
				try
				{
					$this->app->getLink( '__orphan__', 'cms_database_categories' );
					
					$category = '__orphan__';
				}
				catch( \OutOfRangeException $e )
				{
					$libraryClass->convert_cms_database_category( array(
						'category_id'			=> '__orphan__',
						'category_database_id'	=> 1,
						'category_name'			=> "vBulletin Articles",
						'category_fields'		=> array( 'article_title', 'article_content' )
					) );
					
					$category = '__orphan__';
				}
			}
			
			$keywords = array();
			foreach( explode( ',', $nodeinfo['keywords'] ) AS $word )
			{
				$keywords[] = trim( $word );
			}
			
			$libraryClass->convert_cms_database_record( array(
				'record_id'					=> $row['contentid'],
				'record_database_id'		=> 1,
				'member_id'					=> $node['userid'],
				'rating_real'				=> $nodeinfo['ratingtotal'],
				'rating_hits'				=> $nodeinfo['ratingnum'],
				'rating_value'				=> $nodeinfo['rating'],
				'record_locked'				=> $node['comments_enabled'],
				'record_views'				=> $nodeinfo['viewcount'],
				'record_allow_comments'		=> $node['comments_enabled'],
				'record_saved'				=> $node['publishdate'],
				'record_updated'			=> $node['lastupdated'],
				'category_id'				=> $category,
				'record_approved'			=> ( $node['hidden'] ) ? -1 : 1,
				'record_static_furl'		=> $node['url'],
				'record_meta_keywords'		=> $keywords,
				'record_meta_description'	=> $nodeinfo['description'],
				'record_topicid'			=> $nodeinfo['associatedthreadid'],
				'record_publish_date'		=> $node['publishdate'],
			), array(
				1 => $nodeinfo['title'],
				2 => $row['pagetext']
			) );
			
			$libraryClass->setLastKeyValue( $row['contentid'] );
		}
	}
	
	protected $_articleContentType = NULL;
	
	protected function _articleContentType()
	{
		if ( $this->_articleContentType === NULL )
		{
			try
			{
				$this->_articleContentType = $this->db->select( 'contenttypeid', 'contenttype', array( "class=?", 'Article' ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$this->_articleContentType = 24; # default
			}
		}
		
		return $this->_articleContentType;
	}
}