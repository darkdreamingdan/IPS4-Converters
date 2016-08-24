<?php

/**
 * @brief		Converter vBulletin 4.x Blog Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Blog;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Vbulletin extends \IPS\convert\Software
{
	/**
	 * @brief	vBulletin 4 Stores all attachments under one table - this will store the content type for the blog app.
	 */
	protected static $entryContentType		= NULL;
	
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
			if ( static::$entryContentType === NULL AND ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) ) )
			{
				static::$entryContentType = $this->db->select( 'contenttypeid', 'contenttype', array( "class=?", 'BlogEntry' ) )->first();
			}
		}
		catch( \Exception $e ) {}
		
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
		return "vBulletin Blog (3.x/4.x)";
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
		$attachmentTable = 'blog_attachment';
		$attachmentWhere = NULL;
		if ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) )
		{
			$attachmentTable = 'attachment';
			$attachmentWhere = array( "contenttypeid=?", static::$entryContentType );
		}
		
		return array(
			'convert_blogs'			=> array(
				'table'		=> 'blog_user',
				'where'		=> NULL,
			),
			'convert_blog_entries'	=> array(
				'table'		=> 'blog',
				'where'		=> NULL
			),
			'convert_blog_comments'	=> array(
				'table'		=> 'blog_text',
				'where'		=> NULL
			),
			'convert_attachments'	=> array(
				'table'		=> $attachmentTable,
				'where'		=> $attachmentWhere
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
			default:
				return parent::countRows( $table, $where );
				break;
			
			case 'blog_text':
				return parent::countRows( 'blog_text' ) - parent::countRows( 'blog' );
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
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'blog_entries', 'class' => 'IPS\blog\Entry' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'blog_comments', 'class' => 'IPS\blog\Entry\Comment' ), 2, array( 'app', 'link', 'class' ) );
		
		return array( "Blog Entries Rebuilding", "Entry Comments Rebuilding", "Blogs Recounting" );
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
	 * List of conversion methods that require additional information
	 *
	 * @return	array
	 */
	public static function checkConf()
	{
		return array( 'convert_attachments' );
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
			case 'convert_attachments':
				$return['convert_attachments'] = array(
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
						'field_validation'	=> function( $value ) { if ( $value != 'database' AND !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					)
				);
				break;
		}
		
		return $return[$method];
	}
	
	public function convert_blogs()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'bloguserid' );
		
		foreach( $this->fetch( 'blog_user', 'bloguserid' ) AS $blog )
		{
			try
			{
				$member = $this->db->select( '*', 'user', array( "userid=?", $blog['bloguserid'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				/* User no longer exists, so skip */
				$libraryClass->setLastKeyValue( $blog['bloguserid'] );
				continue;
			}
			
			$socialgroup = NULL;
			
			/* If this is a private blog, then we need to convert it to use a social group for invited users */
			if ( $blog['options_member'] == 0 AND $blog['options_buddy'] > 0 )
			{
				/* Fetch users friends */
				$friends = array();
				foreach( $this->db->select( 'relationid', 'userlist', array( "userid=?", $blog['bloguserid'] ) ) AS $friend )
				{
					$friends[$friend] = $friend;
				}
				
				$socialgroup = array( 'members' => $friends );
			}
			
			$info = array(
				'blog_id'					=> $blog['bloguserid'],
				'blog_member_id'			=> $blog['bloguserid'],
				'blog_allowguests'			=> ( $blog['options_guest'] > 0 ) ? 1 : 0, # we don't care about specific options
				'blog_rating_total'			=> $blog['ratingtotal'],
				'blog_rating_count'			=> $blog['ratingnum'],
				'blog_settings'				=> array( 'allowrss' => TRUE ),
				'blog_name'					=> $blog['title'] ?: $member['username'] . "'s Blog",
				'blog_description'			=> $blog['description'] ?: '',
				'blog_last_edate'			=> $blog['lastblog'],
				'blog_count_entries'		=> $blog['entries'],
				'blog_count_comments'		=> $blog['comments'],
				'blog_count_entries_hidden'	=> $blog['deleted'],
				'blog_rating_average'		=> $blog['rating'],
				'blog_count_entries_future'	=> $blog['pending'],
			);
			
			$libraryClass->convert_blog( $info, $socialgroup );
			
			foreach( $this->db->select( '*', 'blog_subscribeuser', array( "userid=?", $blog['bloguserid'] ) ) AS $follow )
			{
				$frequency = 'none';
				if ( $follow['type'] == 'email' )
				{
					$frequency = 'immediate';
				}
				
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'blog',
					'follow_area'			=> 'blogs',
					'follow_rel_id'			=> $blog['bloguserid'],
					'follow_rel_id_type'	=> 'blog_blogs',
					'follow_member_id'		=> $follow['userid'],
					'follow_is_anon'		=> 0,
					'follow_added'			=> time(),
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> $frequency,
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
					'follow_index_id'		=> NULL
				) );
			}
			
			$libraryClass->setLastKeyValue( $blog['bloguserid'] );
		}
	}
	
	public function convert_blog_entries()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'blogid' );
		
		foreach( $this->fetch( 'blog', 'blogid' ) AS $entry )
		{
			try
			{
				$text = $this->db->select( '*', 'blog_text', array( "blogtextid=?", $entry['firstblogtextid'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $entry['blogid'] );
				continue;
			}
			
			$lastcommenterid = 0;
			
			try
			{
				$lastcommenterid = $this->db->select( 'userid', 'blog_text', array( "blogtextid=?", $entry['lastblogtextid'] ) )->first();
			}
			catch( \UnderflowException $e ) {}
			
			$status = 'published';
			if ( $entry['state'] == 'draft' )
			{
				$status = 'draft';
			}
			
			$edittime = $editname = NULL;
			try
			{
				$editlog = $this->db->select( 'dateline, username', 'blog_editlog', array( "blogtextid=?", $entry['firstblogtextid'] ), "dateline DESC" )->first();
				$edittime = $editlog['dateline'];
				$editname = $editlog['username'];
			}
			catch( \UnderflowException $e ) {}
			
			$info = array(
				'entry_id'					=> $entry['blogid'],
				'entry_blog_id'				=> $entry['userid'],
				'entry_name'				=> $text['title'],
				'entry_content'				=> $text['pagetext'],
				'entry_author_id'			=> $entry['postedby_userid'],
				'entry_author_name'			=> $entry['postedby_username'],
				'entry_date'				=> $entry['dateline'],
				'entry_status'				=> $status,
				'entry_num_comments'		=> $entry['comments_visible'],
				'entry_queued_comments'		=> $entry['comments_moderation'],
				'entry_views'				=> $entry['views'],
				'entry_hidden_comments'		=> $entry['comments_deleted'],
				'entry_last_comment_mid'	=> $lastcommenterid,
				'entry_edit_time'			=> $edittime,
				'entry_edit_name'			=> $editname,
				'entry_ip_address'			=> $text['ipaddress']
			);
			
			$libraryClass->convert_blog_entry( $info );
			
			foreach( $this->db->select( '*', 'blog_rate', array( "blogid=?", $entry['blogid'] ) ) AS $rating )
			{
				$libraryClass->convert_rating( array(
					'id'		=> $rating['blograteid'],
					'class'		=> 'IPS\blog\Entry',
					'item_link'	=> 'blog_entries',
					'item_id'	=> $entry['blogid'],
					'rating'	=> $rating['vote'],
					'ip'		=> $rating['ipaddress'],
					'member'	=> $rating['userid'],
				) );
			}
			
			
			$categories = explode( ',', $entry['categories'] );
			if ( count( $categories ) )
			{
				foreach( $categories AS $category )
				{
					try
					{
						$data = $this->db->select( '*', 'blog_category', array( "blogcategoryid=?", $category ) )->first();
					}
					catch( \UnderflowException $e )
					{
						continue;
					}
					
					$libraryClass->convert_tag( array(
						'tag_meta_app'			=> 'blog',
						'tag_meta_area'			=> 'blogs',
						'tag_meta_parent_id'	=> $entry['userid'],
						'tag_meta_id'			=> $entry['blogid'],
						'tag_text'				=> $data['title'],
						'tag_member_id'			=> $data['userid'],
						'tag_prefix'			=> 0
					) );
				}
			}
			
			foreach( $this->db->select( '*', 'blog_subscribeentry', array( "blogid=?", $entry['blogid'] ) ) AS $follow )
			{
				$frequency = 'none';
				if ( $follow['type'] == 'email' )
				{
					$frequency = 'immediate';
				}
				
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'blog',
					'follow_area'			=> 'entries',
					'follow_rel_id'			=> $entry['blogid'],
					'follow_rel_id_type'	=> 'blog_entries',
					'follow_member_id'		=> $follow['userid'],
					'follow_is_anon'		=> 0,
					'follow_added'			=> time(),
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> $frequency,
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
					'follow_index_id'		=> NULL
				) );
			}
			
			$libraryClass->setLastKeyValue( $entry['blogid'] );
		}
	}
	
	public function convert_blog_comments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'blogtextid' );
		
		foreach( $this->fetch( 'blog_text', 'blogtextid' ) AS $comment )
		{
			/* Is this actually the entry? */
			try
			{
				$entry = $this->db->select( '*', 'blog', array( "firstblogtextid=?", $comment['blogtextid'] ) )->first();
				
				/* If that didn't throw an exception, then yes this is - skip */
				$libraryClass->setLastKeyValue( $comment['blogtextid'] );
				continue;
			}
			catch( \UnderflowException $e ) {} # nope, carry on
			
			$edittime = $editname = NULL;
			try
			{
				$editlog = $this->db->select( 'dateline, username', 'blog_editlog', array( "blogtextid=?", $comment['blogtextid'] ), "dateline DESC" )->first();
				$edittime = $editlog['dateline'];
				$editname = $editlog['username'];
			}
			catch( \UnderflowException $e ) {}
			
			switch( $comment['state'] )
			{
				case 'moderation':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
				
				case 'visible':
				default:
					$approved = 1;
					break;
			}
			
			$libraryClass->convert_blog_comment( array(
				'comment_id'					=> $comment['blogtextid'],
				'comment_entry_id'				=> $comment['blogid'],
				'comment_text'					=> $comment['pagetext'],
				'comment_member_id'				=> $comment['userid'],
				'comment_member_name'			=> $comment['username'],
				'comment_ip_address'			=> $comment['ipaddress'],
				'comment_date'					=> $comment['dateline'],
				'comment_edit_time'				=> $edittime,
				'comment_edit_member_name'		=> $editname,
				'comment_edit_show'				=> 1,
				'comment_approved'				=> $approved
			) );
		}
	}
	
	public function convert_attachments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'attachmentid' );
		
		$where			= NULL;
		$column			= NULL;
		
		if ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) )
		{
			$where			= array( "contenttypeid=?", static::$entryContentType );
			$column			= 'contentid';
			$table			= 'attachment';
		}
		else
		{
			$column			= 'blogid';
			$table			= 'blog_attachment';
		}
		
		foreach( $this->fetch( $table, 'attachmentid', $where ) AS $attachment )
		{
			if ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) )
			{
				$filedata = $this->db->select( '*', 'filedata', array( "filedataid=?", $attachment['filedataid'] ) )->first();
			}
			else
			{
				$filedata				= $attachment;
				$filedata['filedataid']	= $attachment['attachmentid'];
			}
			
			$map = array(
				'id1'		=> $attachment[$column],
				'id2'		=> NULL
			);
			
			$info = array(
				'attach_id'			=> $attachment['attachmentid'],
				'attach_file'		=> $attachment['filename'],
				'attach_date'		=> $attachment['dateline'],
				'attach_member_id'	=> $attachment['userid'],
				'attach_hits'		=> $attachment['counter'],
				'attach_ext'		=> $filedata['extension'],
				'attach_filesize'	=> $filedata['filesize'],
			);
			
			if ( $this->app->_session['more_info']['convert_attachments']['file_location'] == 'database' )
			{
				/* Simples! */
				$data = $filedata['filedata'];
				$path = NULL;
			}
			else
			{
				$data = NULL;
				$path = implode( '/', preg_split( '//', $filedata['userid'], -1, PREG_SPLIT_NO_EMPTY ) );
				$path = rtrim( $this->app->_session['more_info']['convert_attachments']['file_location'], '/' ) . '/' . $path . '/' . $attachment['filedataid'] . '.attach';
			}
			
			$attach_id = $libraryClass->convert_attachment( $info, $map, $path, $data );
			
			/* Do some re-jiggery on the post itself to make sure attachment displays */
			$ourAttachment = \IPS\File::get( 'core_Attachment', \IPS\Db::i()->select( 'attach_location', 'core_attachments', array( 'attach_id=?', $attach_id ) )->first() );
			
			$entry_id = $this->app->getLink( $attachment[$column], 'blog_entries' );
			
			$post = \IPS\Db::i()->select( 'entry_content', 'blog_entries', array( "entry_id=?", $entry_id ) )->first();
			
			if ( preg_match( "/\[ATTACH(.+?)?\]".$attachment['attachmentid']."\[\/ATTACH\]/i", $post ) )
			{
				$post = preg_replace( "/\[ATTACH=(.+?)?\]" . $attachment['attachmentid'] . "\[\/ATTACH\]/i", '<p><a href="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsAttachLink ipsAttachLink_image"><img data-fileid="' . $attach_id . '" src="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsImage ipsImage_thumbnailed" alt=""></a></p>', $post );
			}
			else
			{
				$post .= '<p><a href="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsAttachLink ipsAttachLink_image"><img data-fileid="' . $attach_id . '" src="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsImage ipsImage_thumbnailed" alt=""></a></p>';
			}
			
			\IPS\Db::i()->update( 'blog_entries', array( 'entry_content' => $post ), array( "entry_id=?", $entry_id ) );
		}
	}
}