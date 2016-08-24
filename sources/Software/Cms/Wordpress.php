<?php

/**
 * @brief		Converter Wordpress Class
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

class _Wordpress extends \IPS\convert\Software
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
		return "Wordpress (4.x)";
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
		return "wordpress";
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
			'convert_cms_pages'					=> array(
				'table'								=> 'posts',
				'where'								=> array( "post_type=?", 'page' )
			),
			'convert_cms_databases'				=> array(
				'table'								=> 'cms_database',
				'where'								=> NULL
			),
			'convert_cms_database_categories'	=> array(
				'table'								=> 'term_taxonomy',
				'where'								=> array( "taxonomy=?", 'category' )
			),
			'convert_cms_database_records'		=> array(
				'table'								=> 'posts',
				'where'								=> array( "post_type=?", 'post' ),
			),
			'convert_cms_database_comments'		=> array(
				'table'								=> 'comments',
				'where'								=> NULL
			),
			'convert_cms_media'					=> array(
				'table'								=> 'posts',
				'where'								=> array( "post_type=?", 'attachment' )
			)
		);
	}
	
	/**
	 * Count Rows
	 *
	 * @param	string		Table to count
	 * @param	array|NULL	Where clause
	 * @return	int			The count.
	 */
	public function countRows( $table, $where=NULL )
	{
		switch( $table )
		{
			case 'cms_database':
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
	 * Requires Parent?
	 *
	 * @return	bool
	 */
	public static function requiresParent()
	{
		return TRUE;
	}
	
	/**
	 * Available Parents
	 *
	 * @return	array
	 */
	public static function parents()
	{
		return array( 'core' => array( 'wordpress' ) );
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
		return array(
			'convert_cms_media'
		);
	}
	
	/**
	 * Get More Information
	 *
	 * @return	array
	 * @todo	Refactor. This isn't efficient anymore.
	 */
	public function getMoreInfo( $method )
	{
		$return = array();
		
		switch( $method )
		{
			case 'convert_cms_database_records':
				$return['convert_cms_database_records']['file_location'] = array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Text',
					'field_required'	=> TRUE,
					'field_default'		=> NULL,
					'field_extra'		=> array(),
					'field_hint'		=> "This is typically: /path/to/wordpress/wp-content/uploads",
					'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);
				break;
				
			case 'convert_cms_media':
				$return['convert_cms_media']['file_location'] = array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Text',
					'field_required'	=> TRUE,
					'field_default'		=> NULL,
					'field_extra'		=> array(),
					'field_hint'		=> "This is typically: /path/to/wordpress/wp-content/uploads",
					'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);
				break;
		}
		
		return $return[$method];
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 *
	 * @return	string		Message to display
	 */
	public function finish()
	{
		$database = $this->app->getLink( 1, 'cms_databases' );
		\IPS\Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\cms\Categories' . $database, 'count' => 0 ), 5, array( 'class' ) );
		
		return array( "Recounting Database Categories" );
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
	
	public function convert_cms_pages()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'ID' );
		
		foreach( $this->fetch( 'posts', 'ID', array( "post_type=?", 'page' ) ) AS $row )
		{
			$libraryClass->convert_cms_page( array(
				'page_id'		=> $row['ID'],
				'page_name'		=> $row['post_title'],
				'page_seo_name'	=> $row['post_name'],
				'page_content'	=> $row['post_content'],
			) );
			
			$libraryClass->setLastKeyValue( $row['ID'] );
		}
	}
	
	public function convert_cms_databases()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass->convert_cms_database( array(
			'database_id'			=> 1,
			'database_name'			=> "WordPress Posts",
			'database_sln'			=> 'post',
			'database_pln'			=> 'posts',
			'database_scn'			=> 'Post',
			'database_pcn'			=> 'Posts',
			'database_ia'			=> 'a post',
			'database_tags_enabled'	=> 1,
		), array(
			array(
				'field_id'				=> 1,
				'field_name'			=> 'Title',
				'field_type'			=> 'Text',
				'field_key'				=> 'post_title',
				'field_required'		=> 1,
				'field_position'		=> 1,
				'field_display_listing'	=> 1,
				'field_is_title'		=> 1,
			),
			array(
				'field_id'				=> 2,
				'field_name'			=> 'Content',
				'field_type'			=> 'Editor',
				'field_key'				=> 'post_content',
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
		
		$libraryClass::setKey( 'term_taxonomy.term_taxonomy_id' );
		
		foreach( $this->fetch( 'term_taxonomy', 'term_taxonomy.term_taxonomy_id', array( "term_taxonomy.taxonomy=?", 'category' ) )->join( 'terms', 'terms.term_id = term_taxonomy.term_id' ) AS $row )
		{
			$info = array(
				'category_id'			=> $row['term_taxonomy_id'],
				'category_database_id'	=> 1,
				'category_name'			=> $row['name'],
				'category_furl_name'	=> $row['slug'],
				'category_fields'		=> array( 'post_title', 'post_content' ),
			);
			
			$libraryClass->convert_cms_database_category( $info );
			$libraryClass->setLastKeyValue( $row['term_taxonomy_id'] );
		}
	}
	
	public function convert_cms_database_records()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'ID' );
		
		foreach( $this->fetch( 'posts', 'ID', array( "post_type=?", 'post' ) ) AS $row )
		{
			/* We only support one category per record - in this instance, fetch them all, pop off the last one, then convert the rest as tags later. */
			$categories = array();
			foreach( $this->db->select( '*', 'term_relationships', array( "term_relationships.object_id=? AND term_taxonomy.taxonomy=?", $row['ID'], 'category' ) )->join( 'term_taxonomy', 'term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id' ) AS $term )
			{
				$categories[] = $term['term_taxonomy_id'];
			}
			$category = array_pop( $categories );
			
			/* Post Meta */
			$meta = array();
			foreach( $this->db->select( '*', 'postmeta', array( "post_id=?", $row['ID'] ) ) AS $m )
			{
				$meta[$m['meta_key']] = $m['meta_value'];
			}
			
			/* Work out wacky approved status */
			switch( $row['post_status'] )
			{
				case 'publish':
					$approved = 1;
					break;
				
				case 'auto-draft':
				case 'draft':
					$approved = 0;
					break;
				
				case 'trash':
				case 'private':
					$approved = -1;
					break;
				
				default:
					$approved = 0; # play it on the safe side.
					break;
			}
			
			/* Record Image */
			$image = NULL;
			if ( isset( $meta['_thumbnail_id'] ) )
			{
				try
				{
					$location = $this->db->select( 'meta_value', 'postmeta', array( "post_id=? AND meta_key=?", $meta['_thumbnail_id'], '_wp_attached_file' ) )->first();
					$image = rtrim( $this->app->_session['more_info']['convert_cms_database_records']['file_location'], '/' ) . '/' . $location;
				}
				catch( \UnderflowException $e ) {}
			}
			
			$info = array(
				'record_id'				=> $row['ID'],
				'record_database_id'	=> 1,
				'member_id'				=> $row['post_author'],
				'record_locked'			=> ( $row['comment_status'] == 'closed' ) ? 1 : 0,
				'record_comments'		=> $row['comment_count'],
				'record_allow_comments'	=> 1,
				'record_saved'			=> new \IPS\DateTime( $row['post_date'] ),
				'record_updated'		=> new \IPS\DateTime( $row['post_modified'] ),
				'category_id'			=> $category,
				'record_approved'		=> $approved,
				'record_dynamic_furl'	=> $row['post_name'],
				'record_static_furl'	=> $row['post_name'],
				'record_publish_date'	=> new \IPS\DateTime( $row['post_date'] ),
				'record_image'			=> $image,
			);
			
			$fields = array(
				1 => $row['post_title'],
				2 => $row['post_content']
			);
			
			$libraryClass->convert_cms_database_record( $info, $fields );
			
			/* Tags */
			$tags = array();
			
			/* ... from categories */
			foreach( $this->db->select( 'term_id', 'term_taxonomy', array( $this->db->in( 'term_taxonomy_id', $categories ) ) ) AS $cat )
			{
				$text = $this->db->select( 'name', 'terms', array( "term_id=?", $cat ) )->first();
				$tags[] = $text;
			}
			
			/* ... from actual tags */
			foreach( $this->db->select( '*', 'term_relationships', array( "term_relationships.object_id=? AND term_taxonomy.taxonomy=?", $row['ID'], 'post_tag' ) )->join( 'term_taxonomy', 'term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id' ) AS $term )
			{
				$text = $this->db->select( 'name', 'terms', array( "term_id=?", $term['term_id'] ) )->first();
				$tags[] = $text;
			}
			
			/* Now convert them... we need the database ID. */
			$database = $this->app->getLink( 1, 'cms_databases' );
			foreach( $tags AS $tag )
			{
				$libraryClass->convert_tag( array(
					'tag_meta_app'			=> 'cms',
					'tag_meta_area'			=> "records{$database}",
					'tag_meta_parent_id'	=> $category,
					'tag_meta_id'			=> $row['ID'],
					'tag_text'				=> $tag,
					'tag_member_id'			=> $row['post_author'],
					'tag_prefix'			=> 0,
					'tag_meta_link'			=> 'cms_custom_database_' . $database,
					'tag_meta_parent_link'	=> 'cms_database_categories',
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['ID'] );
		}
	}
	
	public function convert_cms_database_comments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'comment_ID' );
		
		foreach( $this->fetch( 'comments', 'comment_ID' ) AS $row )
		{
			switch( $row['comment_approved'] )
			{
				case 1:
					$approved = 1;
					break;
				
				case 0:
					$approved = 0;
					break;
				
				case 'trash':
				case 'spam':
					$approved = -1;
					break;
			}
			
			$libraryClass->convert_cms_database_comment( array(
				'comment_id'			=> $row['comment_ID'],
				'comment_database_id'	=> 1,
				'comment_record_id'		=> $row['comment_post_ID'],
				'comment_date'			=> new \IPS\DateTime( $row['comment_date'] ),
				'comment_ip_address'	=> $row['comment_author_IP'],
				'comment_user'			=> $row['user_id'],
				'comment_author'		=> $row['comment_author'],
				'comment_approved'		=> $approved,
				'comment_post'			=> $row['comment_content'],
			) );
			
			$libraryClass->setLastKeyValue( $row['comment_ID'] );
		}
	}
	
	public function convert_cms_media()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'ID' );
		
		foreach( $this->fetch( 'posts', 'ID', array( "post_type=?", 'attachment' ) ) AS $row )
		{
			$meta = array();
			foreach( $this->db->select( '*', 'postmeta', array( "post_id=?", $row['ID'] ) ) AS $m )
			{
				$meta[$m['meta_key']] = $m['meta_value'];
			}
			
			$filename = explode( '/', $meta['_wp_attached_file'] );
			$filename = array_pop( $filename );
			
			$path = rtrim( $this->app->_session['more_info']['convert_cms_media']['file_location'], '/' );
			
			$info = array(
				'media_id'			=> $row['ID'],
				'media_filename'	=> $filename,
				'media_added'		=> new \IPS\DateTime( $row['post_date'] ),
			);
			
			$libraryClass->convert_cms_media( $info, $path . '/' . $meta['_wp_attached_file'] );
			
			$libraryClass->setLastKeyValue( $row['ID'] );
		}
	}
}