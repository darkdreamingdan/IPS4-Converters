<?php

/**
 * @brief		Converter vBulletin 4.x Forums Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Forums;

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
	protected static $postContentType		= NULL;
	
	/**
	 * @brief	The schematic for vB3 and vB4 is similar enough that we can make specific concessions in a sinle converter for either version.
	 */
	protected static $isVb3					= NULL;
	
	/**
	 * @brief	Flag to indicate the post data has been fixed during conversion, and we only need to use Legacy Parser
	 */
	public static $contentFixed = TRUE;
	
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
		
		if ( $needDB )
		{
			try
			{
				/* Is this vB3 or vB4? */
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
				if ( static::$postContentType === NULL AND ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) ) )
				{
					static::$postContentType = $this->db->select( 'contenttypeid', 'contenttype', array( "class=?", 'Post' ) )->first();
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
		return "vBulletin Forums (3.x/4.x)";
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
		$attachmentWhere = NULL;
		if ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) )
		{
			$attachmentWhere = array( "contenttypeid=?", static::$postContentType );
		}
		
		return array(
			'convert_forums_forums'	=> array(
				'table'		=> 'forum',
				'where'		=> NULL,
			),
			'convert_forums_topics'	=> array(
				'table'		=> 'thread',
				'where'		=> NULL
			),
			'convert_forums_posts'	=> array(
				'table'		=> 'post',
				'where'		=> NULL
			),
			'convert_attachments'	=> array(
				'table'		=> 'attachment',
				'where'		=> $attachmentWhere
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
		/* @todo this needs to be a queue task */
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'forums_forums' ), 'IPS\forums\Forum' ) AS $forum )
		{
			$forum->setLastComment();

			$forum->queued_topics = \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics', array( 'forum_id=? AND approved=0', $forum->id ) )->first();
			$forum->queued_posts = \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts', array( 'forums_topics.forum_id=? AND forums_posts.queued=1', $forum->id ) )->join( 'forums_topics', 'forums_topics.tid=forums_posts.topic_id' )->first();
			$forum->save();
		}
		
		/* Content Rebuilds */
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'forums_posts', 'class' => 'IPS\forums\Topic\Post' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'convert', 'RebuildFirstPostIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		\IPS\Task::queue( 'convert', 'DeleteEmptyTopics', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );
		
		return array( "Forum Last Post Data Rebuilt", "Posts Rebuilding", "Forums Recounting", "Topics Recounting" );
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
						'field_validation'		=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					)
				);
				break;
		}
		
		return $return[$method];
	}
	
	public function convert_forums_forums()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'forumid' );
		
		foreach( $this->fetch( 'forum', 'forumid' ) AS $forum )
		{
			$self = $this;
			$checkpermission = function( $name, $perm ) use ( $forum, $self )
			{
				$key = $name;
				if ( $name == 'forumoptions' )
				{
					$key = 'options';
				}
				
				if ( $forum[$key] & $self::$bitOptions[$name][$perm] )
				{
					return TRUE;
				}
				
				return FALSE;
			};
			
			$info = array(
				'id'					=> $forum['forumid'],
				'name'					=> $forum['title'],
				'description'			=> $forum['description'],
				'topics'				=> $forum['threadcount'],
				'posts'					=> $forum['replycount'],
				'last_post'				=> $forum['lastpost'],
				'last_poster_id'		=> ( static::$isVb3 === FALSE or is_null( static::$isVb3 ) ) ? $forum['lastposterid'] : 0,
				'last_poster_name'		=> $forum['lastposter'],
				'parent_id'				=> $forum['parentid'],
				'position'				=> $forum['displayorder'],
				'password'				=> $forum['password'] ?: NULL,
				'last_title'			=> $forum['lastthread'],
				'preview_posts'			=> $checkpermission( 'forumoptions', 'moderatenewpost' ),
				'inc_postcount'			=> $checkpermission( 'forumoptions', 'countposts' ),
				'redirect_url'			=> $forum['link'],
				'sub_can_post'			=> $checkpermission( 'forumoptions', 'cancontainthreads' ),
				'forum_allow_rating'	=> $checkpermission( 'forumoptions', 'allowratings' ),
			);
			
			$libraryClass->convert_forums_forum( $info );
			
			/* Follows for this forum */
			foreach( $this->db->select( '*', 'subscribeforum', array( "forumid=?", $forum['forumid'] ) ) AS $follow )
			{
				$frequency = 'none';
				
				switch( $follow['emailupdate'] )
				{
					case 1:
						$frequency = 'immediate';
						break;
					
					case 2:
						$frequency = 'daily';
						break;
					
					case 3:
						$frequency = 'weekly';
						break;
				}
				
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'forum',
					'follow_rel_id'			=> $forum['forumid'],
					'follow_rel_id_type'	=> 'forums_forums',
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
			
			$libraryClass->setLastKeyValue( $forum['forumid'] );
		}
	}
	
	public function convert_forums_topics()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'threadid' );
		
		foreach( $this->fetch( 'thread', 'threadid' ) AS $topic )
		{
			/* Pesky Polls */
			$poll		= NULL;
			$lastVote	= 0;
			if ( $topic['pollid'] > 0 )
			{
				try
				{
					$pollData = $this->db->select( '*', 'poll', array( "pollid=?", $topic['pollid'] ) )->first();
					
					$lastVote = $pollData['lastvote'];
					
					$choices	= array();
					$index		= 1;
					foreach( explode( '|||', $pollData['options'] ) AS $choice )
					{
						$choices[$index] = trim( $choice );
						$index++;
					}
					
					/* Reset Index */
					$index		= 1;
					$votes		= array();
					$numvotes	= 0;
					foreach( explode( '|||', $pollData['votes'] ) AS $vote )
					{
						$votes[$index] = $vote;
						$index++;
						
						$numvotes += $vote;
					}
					
					$poll = array();
					$poll['poll_data'] = array(
						'pid'				=> $pollData['pollid'],
						'choices'			=> array( 1 => array(
							'question'			=> $pollData['question'],
							'multi'				=> $pollData['multiple'],
							'choice'			=> $choices,
							'votes'				=> $votes
						) ),
						'poll_question'		=> $pollData['question'],
						'start_date'		=> $pollData['dateline'],
						'starter_id'		=> $topic['postuserid'],
						'votes'				=> $numvotes,
						'poll_view_voters'	=> $pollData['public']
					);
					
					$poll['vote_data'] = array();
					$ourVotes = array();
					foreach( $this->db->select( '*', 'pollvote', array( "pollid=?", $pollData['pollid'] ) ) AS $vote )
					{
						if ( !isset( $ourVotes[$vote['userid']] ) )
						{
							/* Create our structure - vB stores each individual vote as a different row whereas we combine them per user */
							$ourVotes[$vote['userid']] = array( 'votes' => array() );
						}
						
						$ourVotes[$vote['userid']]['votes'][]		= $vote['voteoption'];
						
						/* These don't matter - just use the latest one */
						$ourVotes[$vote['userid']]['vid']			= $vote['pollvoteid'];
						$ourVotes[$vote['userid']]['vote_date'] 	= $vote['votedate'];
						$ourVotes[$vote['userid']]['member_id']		= $vote['userid'];
					}
					
					/* Now we need to re-wrap it all for storage */
					foreach( $ourVotes AS $member_id => $vote )
					{
						$poll['vote_data'][$member_id] = array(
							'vid'				=> $vote['vid'],
							'vote_date'			=> $vote['vote_date'],
							'member_id'			=> $vote['member_id'],
							'member_choices'	=> array( 1 => $vote['votes'] ),
						);
					}
				}
				catch( \UnderflowException $e ) {} # if the poll is missing, don't bother
			}
			
			$info = array(
				'tid'				=> $topic['threadid'],
				'title'				=> $topic['title'],
				'forum_id'			=> $topic['forumid'],
				'state'				=> $topic['open'] ? 'open' : 'closed',
				'posts'				=> $topic['replycount'],
				'starter_id'		=> $topic['postuserid'],
				'start_date'		=> $topic['dateline'],
				'last_poster_id'	=> ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) ) ? $topic['lastposterid'] : NULL,
				'last_post'			=> $topic['lastpost'],
				'starter_name'		=> $topic['postusername'],
				'last_poster_name'	=> $topic['lastposter'],
				'poll_state'		=> $poll,
				'last_vote'			=> $lastVote,
				'views'				=> $topic['views'],
				'approved'			=> $topic['visible'],
				'pinned'			=> $topic['sticky'],
				'topic_open_time'	=> $topic['dateline'],
				'topic_hiddenposts'	=> $topic['hiddencount'] + $topic['deletedcount']
			);
			
			unset( $poll );
			
			$topic_id = $libraryClass->convert_forums_topic( $info );
			
			/* Follows */
			foreach( $this->db->select( '*', 'subscribethread', array( "threadid=?", $topic['threadid'] ) ) AS $follow )
			{
				$frequency = 'none';
				switch( $follow['emailupdate'] )
				{
					case 1:
						$frequency = 'immediate';
						break;
					
					case 2:
						$frequency = 'daily';
						break;
					
					case 3:
						$frequency = 'weekly';
						break;
				}
				
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'topic',
					'follow_rel_id'			=> $topic['threadid'],
					'follow_rel_id_type'	=> 'forums_topics',
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
			
			/* Ratings */
			foreach( $this->db->select( '*', 'threadrate', array( "threadid=?", $topic['threadid'] ) ) AS $rating )
			{
				$libraryClass->convert_rating( array(
					'id'		=> $rating['threadrateid'],
					'class'		=> 'IPS\\forums\\Topic',
					'item_link'	=> 'forums_topics',
					'item_id'	=> $rating['threadid'],
					'ip'		=> $rating['ipaddress'],
					'rating'	=> $rating['vote'],
					'member'	=> $rating['userid']
				) );
			}
			
			/* Tag Prefix */
			try
			{
				$prefix	= $this->db->select( '*', 'prefix', array( "prefixid=?", $topic['prefixid'] ) )->first();
				$lang	= $this->db->select( '*', 'phrase', array( "varname=?", "prefix_{$prefix['prefixid']}_title_plain" ) )->first();
				
				$libraryClass->convert_tag( array(
					'tag_meta_app'			=> 'forums',
					'tag_meta_area'			=> 'forums',
					'tag_meta_parent_id'	=> $topic['forumid'],
					'tag_meta_id'			=> $topic['threadid'],
					'tag_text'				=> $lang['text'],
					'tag_member_id'			=> $topic['postuserid'],
					'tag_prefix'			=> 1, # key to this whole operation right here
				) );
			}
			catch( \UnderflowException $e ) {}
			
			/* Tags */
			$tags = explode( ',', $topic['taglist'] );
			if ( count( $tags ) )
			{
				foreach( $tags AS $tag )
				{
					$libraryClass->convert_tag( array(
						'tag_meta_app'			=> 'forums',
						'tag_meta_area'			=> 'forums',
						'tag_meta_parent_id'	=> $topic['forumid'],
						'tag_meta_id'			=> $topic['threadid'],
						'tag_text'				=> $tag,
						'tag_member_id'			=> $topic['postuserid'],
						'tag_prefix'			=> 0,
					) );
				}
			}
			
			$libraryClass->setLastKeyValue( $topic['threadid'] );
		}
	}
	
	public function convert_forums_posts()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'postid' );
		
		foreach( $this->fetch( 'post', 'postid' ) AS $post )
		{
			$info = array(
				'pid'				=> $post['postid'],
				'topic_id'			=> $post['threadid'],
				'post'				=> static::fixPostData( $post['pagetext'] ),
				'author_id'			=> $post['userid'],
				'author_name'		=> $post['username'],
				'ip_address'		=> $post['ipaddress'],
				'post_date'			=> $post['dateline'],
				'queued'			=> ( $post['visible'] >= 2 ) ? -1 : ( ( $post['visible'] == 0 ) ? 1 : 0 ),
				'post_htmlstate'	=> ( static::$isVb3 === FALSE AND in_array( $post['htmlstate'], array( 'on', 'on_nl2br' ) ) ) ? 1 : 0,
			);
			
			$post_id = $libraryClass->convert_forums_post( $info );
			
			/* Reputation */
			foreach( $this->db->select( '*', 'reputation', array( "postid=?", $post['postid'] ) ) AS $rep )
			{
				$libraryClass->convert_reputation( array(
					'id'				=> $rep['reputationid'],
					'app'				=> 'forums',
					'type'				=> 'pid',
					'type_id'			=> $post['postid'],
					'member_id'			=> $rep['whoadded'],
					'member_received'	=> $rep['userid'],
					'rep_date'			=> $rep['dateline'],
					'rep_rating'		=> $rep['reputation']
				) );
			}
			
			/* Edit History */
			$latestedit = 0;
			$reason		= NULL;
			$name		= NULL;
			foreach( $this->db->select( '*', 'postedithistory', array( "postid=?", $post['postid'] ) ) AS $edit )
			{
				$libraryClass->convert_edit_history( array(
					'id'			=> $edit['postedithistoryid'],
					'class'			=> 'IPS\\forums\\Topic\\Post',
					'comment_id'	=> $post['postid'],
					'member'		=> $edit['userid'],
					'time'			=> $edit['dateline'],
					'old'			=> $edit['pagetext']
				) );
				
				if ( $edit['dateline'] > $latestedit )
				{
					$latestedit = $edit['dateline'];
					$reason		= $edit['reason'];
					$name		= $edit['username'];
				}
			}
			
			/* Warnings */
			foreach( $this->db->select( '*', 'infraction', array( "postid=?", $post['postid'] ) ) AS $warn )
			{
				$libraryClass->convert_warn_log( array(
					'wl_id'					=> $warn['infractionid'],
					'wl_member'				=> $warn['userid'],
					'wl_moderator'			=> $warn['whoadded'],
					'wl_date'				=> $warn['dateline'],
					'wl_points'				=> $warn['points'],
					'wl_note_member'		=> $warn['note'],
					'wl_note_mods'			=> $warn['customreason'],
				) );
			}
			
			/* If we have a latest edit, then update the main post - this should really be in the library, as the converters should not be altering data */
			if ( $latestedit )
			{
				\IPS\Db::i()->update( 'forums_posts', array( 'append_edit' => 1, 'edit_time' => $latestedit, 'edit_name' => $name, 'post_edit_reason' => $reason ), array( "pid=?", $post_id ) );
			}
			
			$libraryClass->setLastKeyValue( $post['postid'] );
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
			$where			= array( "contenttypeid=?", static::$postContentType );
			$column			= 'contentid';
		}
		else
		{
			$column			= 'postid';
		}
		
		foreach( $this->fetch( 'attachment', 'attachmentid', $where ) AS $attachment )
		{
			try
			{
				$topic_id = $this->db->select( 'threadid', 'post', array( "postid=?", $attachment[$column] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				/* If the topic is missing, there isn't much we can do. */
				$libraryClass->setLastKeyValue( $attachment['attachmentod'] );
				continue;
			}
			
			if ( static::$isVb3 === FALSE OR is_null( static::$isVb3 ) )
			{
				try
				{
					$filedata = $this->db->select( '*', 'filedata', array( "filedataid=?", $attachment['filedataid'] ) )->first();
				}
				catch( \UnderflowException $e )
				{
					/* If the filedata row is missing, there isn't much we can do. */
					$libraryClass->setLastKeyValue( $attachment['attachmentid'] );
					continue;
				}
			}
			else
			{
				$filedata				= $attachment;
				$filedata['filedataid']	= $attachment['attachmentid'];
			}
			
			$map = array(
				'id1'		=> $topic_id,
				'id2'		=> $attachment[$column]
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
				$path = rtrim( $this->app->_session['more_info']['convert_attachments']['file_location'], '/' ) . '/' . $path . '/' . $filedata['filedataid'] . '.attach';
			}
			
			$attach_id = $libraryClass->convert_attachment( $info, $map, $path, $data );
			
			/* Do some re-jiggery on the post itself to make sure attachment displays */
			if ( $attach_id !== FALSE )
			{
				try
				{
					$ourAttachment = \IPS\File::get( 'core_Attachment', \IPS\Db::i()->select( 'attach_location', 'core_attachments', array( 'attach_id=?', $attach_id ) )->first() );
					
					$pid = $this->app->getLink( $attachment[$column], 'forums_posts' );
					
					$post = \IPS\Db::i()->select( 'post', 'forums_posts', array( "pid=?", $pid ) )->first();
					
					if ( preg_match( "/\[ATTACH(.+?)?\]".$attachment['attachmentid']."\[\/ATTACH\]/i", $post ) )
					{
						$post = preg_replace( "/\[ATTACH=(.+?)?\]" . $attachment['attachmentid'] . "\[\/ATTACH\]/i", '<p><a href="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsAttachLink ipsAttachLink_image"><img data-fileid="' . $attach_id . '" src="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsImage ipsImage_thumbnailed" alt=""></a></p>', $post );
					}
					else
					{
						$post .= '<p><a href="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsAttachLink ipsAttachLink_image"><img data-fileid="' . $attach_id . '" src="<fileStore.core_Attachment>/' . (string) $ourAttachment . '" class="ipsImage ipsImage_thumbnailed" alt=""></a></p>';
					}
					
					\IPS\Db::i()->update( 'forums_posts', array( 'post' => $post ), array( "pid=?", $pid ) );
				}
				catch( \UnderflowException $e ) {}
				catch( \OutOfRangeException $e ) {}
			}
			
			$libraryClass->setLastKeyValue( $attachment['attachmentid'] );
		}
	}
	
	/* !vBulletin Stuff */
	
	/**
	 * @brief	Silly Bitwise
	 */
	public static $bitOptions = array (
		'forumoptions' => array(
			'active' => 1,
			'allowposting' => 2,
			'cancontainthreads' => 4,
			'moderatenewpost' => 8,
			'moderatenewthread' => 16,
			'moderateattach' => 32,
			'allowbbcode' => 64,
			'allowimages' => 128,
			'allowhtml' => 256,
			'allowsmilies' => 512,
			'allowicons' => 1024,
			'allowratings' => 2048,
			'countposts' => 4096,
			'canhavepassword' => 8192,
			'indexposts' => 16384,
			'styleoverride' => 32768,
			'showonforumjump' => 65536,
			'prefixrequired' => 131072,
			'allowvideos' => 262144,
			'bypassdp' => 524288,
			'displaywrt' => 1048576,
			'canreputation' => 2097152,
		),
	);
}