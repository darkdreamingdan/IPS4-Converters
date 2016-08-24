<?php

/**
 * @brief		Converter vBulletin 4.x Gallery Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Vbulletin extends \IPS\convert\Software
{
	/**
	 * @brief	vBulletin 4 Stores all attachments under one table - this will store the content type for the forums app.
	 */
	protected static $imageContentType		= NULL;
	
	/**
	 * @brief	The schematic for vB3 and vB4 is similar enough that we can make specific concessions in a sinle converter for either version.
	 */
	protected static $isVb3					= NULL;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\convert\App	The application to reference for database and other information.
	 * @param	bool				Establish a DB connection
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public function __construct( \IPS\convert\App $app, $needDB=TRUE )
	{
		$return = parent::__construct( $app, $needDB );
		
		/* Is this vB3 or vB4? */
		if ( $needDB )
		{
			try
			{
				if ( static::$isVb3 === NULL )
				{
					$version = $this->db->select( 'value', 'setting', array( "varname=?", 'templateversion' ) )->first();
					
					if ( mb_substr( $version, 0, 1 ) == '3' )
					{
						static::$isVb3 = TRUE;
					}
					else
					{
						static::$isVb3 = FALSE;
					}
				}
				
				
				/* If this is vB4, what is the content type ID for posts? */
				if ( static::$imageContentType === NULL AND ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) ) )
				{
					static::$imageContentType = $this->db->select( 'contenttypeid', 'contenttype', array( "class=?", 'Album' ) )->first();
				}
			}
			catch( \Exception $e ) {}
		}
		
		return $return;
	}
	
	/**
	 * Software Name
	 *
	 * @return	string
	 * @throws	\BadMethodCallException
	 */
	public static function softwareName()
	{
		/* Child classes must override this method */
		return "vBulletin Gallery (3.x/4.x)";
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
		$imageWhere = NULL;
		$imageTable = 'picture';
		
		if ( static::$isVb3 == FALSE )
		{
			$imageWhere = array( "contenttypeid=?", static::$imageContentType );
			$imageTable = 'attachment';
		}
		
		return array(
			'convert_gallery_albums'=> array(
				'table'		=> 'album',
				'where'		=> NULL,
			),
			'convert_gallery_images'	=> array(
				'table'		=> $imageTable,
				'where'		=> $imageWhere
			),
			'convert_gallery_comments'	=> array(
				'table'		=> 'picturecomment',
				'where'		=> NULL
			)
		);
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
		\IPS\Task::queue( 'convert', 'RebuildGalleryImages', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\gallery\Image' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\gallery\Album', 'count' => 0 ), 4, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\gallery\Category', 'count' => 0 ), 5, array( 'class' ) );
		
		return array( "Gallery Images Rebuilding", "Gallery Categories Recounting", "Gallery Albums Recounting", "Gallery Images Recounting" );
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
		return NULL;
	}
	
	/**
	 * Get More Information
	 *
	 * @return	array
	 */
	public function getMoreInfo( $method )
	{
		$return = array();
		switch( $method )
		{
			case 'convert_gallery_images':
				$return['convert_gallery_images'] = array(
					'file_location' => array(
						'field_class'			=> 'IPS\\Helpers\\Form\\Radio',
						'field_default'			=> 'database',
						'field_required'		=> TRUE,
						'field_extra'			=> array(
							'options'				=> array(
								'database'				=> \IPS\Member::loggedIn()->language()->addToStack( 'database' ),
								'file_system'			=> \IPS\Member::loggedIn()->language()->addToStack( 'file_system' ),
							),
							'userSuppliedInput'	=> 'file_system',
						),
						'field_hint'			=> NULL,
					)
				);
				break;
		}
		
		return $return[$method];
	}
	
	
	/**
	 * List of conversion methods that require additional information
	 *
	 * @return	array
	 */
	public static function checkConf()
	{
		return array( 'convert_gallery_images' );
	}
	
	public function convert_gallery_albums()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'albumid' );
		
		foreach( $this->fetch( 'album', 'albumid' ) AS $album )
		{
			$socialgroup = NULL;
			if ( $album['state'] == 'private' )
			{
				/* Fetch users friends */
				$friends = array();
				foreach( $this->db->select( 'relationid', 'userlist', array( "userid=?", $album['userid'] ) ) AS $friend )
				{
					$friends[$friend] = $friend;
				}
				
				$socialgroup = array( 'members' => $friends );
			}
			
			$info = array(
				'album_id'					=> $album['albumid'],
				'album_owner_id'			=> $album['userid'],
				'album_name'				=> $album['title'],
				'album_description'			=> $album['description'],
				'album_type'				=> ( $album['state'] == 'private' ) ? 3 : 1,
				'album_count_imgs'			=> $album['visible'],
				'album_count_imgs_hidden'	=> $album['moderation'],
				'album_last_img_date'		=> $album['lastpicturedate']
			);
			
			$libraryClass->convert_gallery_album( $info, $socialgroup );
			
			$libraryClass->setLastKeyValue( $album['albumid'] );
		}
	}
	
	public function convert_gallery_images()
	{
		$libraryClass = $this->getLibrary();
		
		/* Don't even bother trying to swap things out - just do different things based on version */
		if ( static::$isVb3 === TRUE )
		{
			$libraryClass::setKey( 'pictureid' );
			
			foreach( $this->fetch( 'picture', 'pictureid' ) AS $image )
			{
				$filedata = NULL;
				$filepath = NULL;
				
				if ( $this->app->_session['more_info']['convert_gallery_images']['file_location'] == 'database' )
				{
					/* Simples! */
					$filedata = $image['filedata'];
				}
				else
				{
					$filepath = implode( '/', preg_split( '//', $image['userid'], -1, PREG_SPLIT_NO_EMPTY ) );
					$filepath = rtrim( $this->app->_session['more_info']['convert_gallery_images']['file_location'], '/' ) . '/' . $filepath . '/' . $image['pictureid'] . '.picture';
				}
				
				$info = array(
					'image_id'			=> $image['pictureid'],
					'image_album_id'	=> $this->db->select( 'albumid', 'albumpicture', array( "pictureid=?", $image['pictureid'] ) )->first(),
					'image_member_id'	=> $image['userid'],
					'image_caption'		=> $image['caption'],
					'image_file_name'	=> $image['caption'] . '.' . $image['extension'],
					'image_date'		=> $this->db->select( 'dateline', 'albumpicture', array( "pictureid=?", $image['pictureid'] ) )->first(),
				);
				
				$libraryClass->convert_gallery_image( $info, $filepath, $filedata );
				
				$libraryClass->setLastKeyValue( $image['pictureid'] );
			}
		}
		else
		{
			$libraryClass::setKey( 'attachmentid' );
			
			foreach( $this->fetch( 'attachment', 'attachmentid', array( "contenttypeid=?", static::$imageContentType ) ) AS $image )
			{
				try
				{
					$data = $this->db->select( '*', 'filedata', array( "filedataid=?", $image['filedataid'] ) )->first();
				}
				catch( \UnderflowException $e )
				{
					$libraryClass->setLastKeyValue( $image['attachmentid'] );
					continue;
				}
				
				$filedata = NULL;
				$filepath = NULL;
				
				if ( $this->app->_session['more_info']['convert_gallery_images']['file_location'] == 'database' )
				{
					$filedata = $data['filedata'];
				}
				else
				{
					$filepath = implode( '/', preg_split( '//', $data['userid'], -1, PREG_SPLIT_NO_EMPTY ) );
					$filepath = rtrim( $this->app->_session['more_info']['convert_gallery_images']['file_location'], '/' ) . '/' . $filepath . '/' . $data['filedataid'] . '.attach';
				}
				
				$info = array(
					'image_id'			=> $data['filedataid'],
					'image_album_id'	=> $image['contentid'],
					'image_member_id'	=> $image['userid'],
					'image_caption'		=> $image['caption'],
					'image_file_name'	=> $image['filename'],
					'image_date'		=> $image['dateline']
				);
				
				$libraryClass->convert_gallery_image( $info, $filepath, $filedata );
				
				$libraryClass->setLastKeyValue( $image['attachmentid'] );
			}
		}
	}
	
	public function convert_gallery_comments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'commentid' );
		
		foreach( $this->fetch( 'picturecomment', 'commentid' ) AS $comment )
		{
			switch( $comment['state'] )
			{
				case 'visible':
					$approved = 1;
					break;
				
				case 'moderation':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
			}
			
			$image_id = 'pictureid';
			if ( static::$isVb3 === FALSE )
			{
				$image_id = 'filedataid';
			}
			
			$libraryClass->convert_gallery_comment( array(
				'comment_id'			=> $comment['commentid'],
				'comment_text'			=> $comment['pagetext'],
				'comment_img_id'		=> $comment[$image_id],
				'comment_author_id'		=> $comment['postuserid'],
				'comment_author_name'	=> $comment['postusername'],
				'comment_post_date'		=> $comment['dateline'],
				'comment_approved'		=> $approved,
			) );
			
			$libraryClass->setLastKeyValue( $comment['commentid'] );
		}
	}
}