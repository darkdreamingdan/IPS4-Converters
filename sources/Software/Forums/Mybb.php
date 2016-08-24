<?php

/**
 * @brief		Converter MyBB Class
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

class _Mybb extends \IPS\convert\Software
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
		return "MyBB 1.8.x";
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
		return "mybb";
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
			'convert_forums_forums'		=> array(
				'table'						=> 'forums',
				'where'						=> NULL
			),
			'convert_forums_topics'		=> array(
				'table'						=> 'threads',
				'where'						=> NULL
			),
			'convert_forums_posts'		=> array(
				'table'						=> 'posts',
				'where'						=> NULL
			),
			'convert_attachments'		=> array(
				'table'						=> 'attachments',
				'where'						=> NULL
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
		return array( 'core' => array( 'mybb' ) );
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
			'convert_attachments'
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
			case 'convert_attachments':
				$return['convert_attachments'] = array(
					'attach_location'	=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> "This is typically: /path/to/mybb/uploads",
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					),
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
	 * Fix post data
	 *
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 **/
	public static function fixPostData( $post )
	{
		return $post;
	}
	
	public function convert_forums_forums()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'fid' );
		
		foreach( $this->fetch( 'forums', 'fid' ) AS $row )
		{
			$info = array(
				'id'					=> $row['fid'],
				'name'					=> $row['name'],
				'description'			=> $row['description'],
				'topics'				=> $row['threads'],
				'posts'					=> $row['posts'],
				'last_post'				=> $row['lastpost'],
				'last_poster_id'		=> $row['lastposteruid'],
				'last_poster_name'		=> $row['lastposter'],
				'parent_id'				=> $row['pid'] ?: -1,
				'position'				=> $row['disporder'],
				'password'				=> $row['password'] ?: NULL,
				'last_title'			=> $row['lastpostsubject'],
				'inc_postcount'			=> $row['usepostcounts'],
				'redirect_url'			=> $row['linkto'],
				'sub_can_post'			=> ( $row['type'] == 'c' ) ? 0 : 1,
				'queued_topics'			=> $row['unapprovedthreads'],
				'queued_posts'			=> $row['unapprovedposts'],
				'forum_allow_rating'	=> $row['allowtratings'],
			);
			
			$libraryClass->convert_forums_forum( $info );
			
			/* Followers */
			foreach( $this->db->select( '*', 'forumsubscriptions', array( "fid=?", $row['fid'] ) ) AS $follow )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'forum',
					'follow_rel_id'			=> $row['fid'],
					'follow_rel_id_type'	=> 'forums_forums',
					'follow_member_id'		=> $follow['uid'],
					'follow_is_anon'		=> 0,
					'follow_added'			=> time(),
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> 'immediate',
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
					'follow_index_id'		=> NULL
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['fid'] );
		}
	}
	
	public function convert_forums_topics()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'tid' );
		
		foreach( $this->fetch( 'threads', 'tid' ) AS $row )
		{
			/* Poll */
			$poll = NULL;
			if ( $row['poll'] > 0 )
			{
				try
				{
					$pollData = $this->db->select( '*', 'polls', array( "pid=?", $row['poll'] ) )->first();
					
					$choices	= array();
					$index		= 1;
					foreach( explode( '||~|~||', $pollData['options'] ) AS $choice )
					{
						$choices[$index] = trim( $choice );
						$index++;
					}
					
					/* Reset Index */
					$index		= 1;
					$votes		= array();
					$numvotes	= 0;
					foreach( explode( '||~|~||', $pollData['votes'] ) AS $vote )
					{
						$votes[$index] = $vote;
						$index++;
						
						$numvotes += $vote;
					}
					
					$poll = array();
					$poll['poll_data'] = array(
						'pid'				=> $pollData['pid'],
						'choices'			=> array( 1 => array(
							'question'			=> $pollData['question'],
							'multi'				=> $pollData['multiple'],
							'choice'			=> $choices,
							'votes'				=> $votes
						) ),
						'poll_question'		=> $pollData['question'],
						'start_date'		=> $pollData['dateline'],
						'starter_id'		=> $row['uid'],
						'votes'				=> $numvotes,
						'poll_view_voters'	=> $pollData['public']
					);
					
					$poll['vote_data'] = array();
					$ourVotes = array();
					foreach( $this->db->select( '*', 'pollvotes', array( "pid=?", $pollData['pid'] ) ) AS $vote )
					{
						if ( !isset( $ourVotes[$vote['uid']] ) )
						{
							/* Create our structure - vB stores each individual vote as a different row whereas we combine them per user */
							$ourVotes[$vote['uid']] = array( 'votes' => array() );
						}
						
						$ourVotes[$vote['uid']]['votes'][]		= $vote['voteoption'];
						
						/* These don't matter - just use the latest one */
						$ourVotes[$vote['uid']]['vid']			= $vote['vid'];
						$ourVotes[$vote['uid']]['vote_date'] 	= $vote['dateline'];
						$ourVotes[$vote['uid']]['member_id']		= $vote['uid'];
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
			
			/* Moved ?*/
			$moved		= explode( "|", $row['closed'] );
			$moved_to	= NULL;
			if ( isset( $moved[0] ) AND $moved[0] == 'moved' )
			{
				try
				{
					$moved_to = array(
						$moved[1],
						$this->db->select( 'fid', 'threads', array( "tid=?", $moved[1] ) )->first()
					);
				}
				catch( \UnderflowException $e )
				{
					$moved_to = NULL;
				}
			}
			
			$info = array(
				'tid'					=> $row['tid'],
				'title'					=> $row['subject'],
				'forum_id'				=> $row['fid'],
				'state'					=> ( $row['closed'] == 1 ) ? 'closed' : 'open',
				'starter_id'			=> $row['uid'],
				'start_date'			=> $row['dateline'],
				'last_poster_id'		=> $row['lastposteruid'],
				'last_post'				=> $row['lastpost'],
				'starter_name'			=> $row['username'],
				'last_poster_name'		=> $row['lastposter'],
				'poll_state'			=> $poll,
				'views'					=> $row['views'],
				'approved'				=> $row['visible'], # it's handled exactly the same
				'pinned'				=> $row['sticky'],
				'moved_to'				=> $moved_to,
				'topic_queuedposts'		=> $row['unapprovedposts'],
				'topic_rating_total'	=> $row['totalratings'],
				'topic_rating_hits'		=> $row['numratings'],
				'topic_hiddenposts'		=> $row['deletedposts']
			);
			
			$libraryClass->convert_forums_topic( $info );
			
			/* If we have a prefix, convert it */
			if ( $row['prefix'] > 0 )
			{
				try
				{
					$prefix = $this->db->select( 'prefix', 'threadprefixes', array( "pid=?", $row['prefix'] ) )->first();
					
					$libraryClass->convert_tag( array(
						'tag_meta_app'			=> 'forums',
						'tag_meta_area'			=> 'forums',
						'tag_meta_parent_id'	=> $row['fid'],
						'tag_meta_id'			=> $row['tid'],
						'tag_text'				=> $prefix,
						'tag_member_id'			=> $row['uid'],
						'tag_prefix'			=> 1,
					) );
				}
				catch( \UnderflowException $e ) {}
			}
			
			/* Follows */
			foreach( $this->db->select( '*', 'threadsubscriptions', array( "tid=?", $row['tid'] ) ) AS $follow )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'topic',
					'follow_rel_id'			=> $row['tid'],
					'follow_rel_id_type'	=> 'forums_topics',
					'follow_member_id'		=> $follow['uid'],
					'follow_is_anon'		=> 0,
					'follow_added'			=> time(),
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> 'immediate',
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
					'follow_index_id'		=> NULL
				) );
			}
			
			/* Ratings */
			foreach( $this->db->select( '*', 'threadratings', array( "tid=?", $row['tid'] ) ) AS $rating )
			{
				$libraryClass->convert_rating( array(
					'id'		=> $rating['rid'],
					'class'		=> 'IPS\\forums\\Topic',
					'item_link'	=> 'forums_topics',
					'item_id'	=> $rating['tid'],
					'ip'		=> $rating['ipaddress'],
					'rating'	=> $rating['rating'],
					'member'	=> $rating['uid']
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['tid'] );
		}
	}
	
	public function convert_forums_posts()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'pid' );
		
		foreach( $this->fetch( 'posts', 'pid' ) AS $row )
		{
			switch( $row['visible'] )
			{
				case 1:
					$queued = 0;
					break;
				
				case 0:
					$queued = 1;
					break;
				
				case -1:
					$queued = -1;
					break;
			}
			
			$info = array(
				'pid'				=> $row['pid'],
				'topic_id'			=> $row['tid'],
				'post'				=> $row['message'],
				'edit_time'			=> $row['edittime'],
				'author_id'			=> $row['uid'],
				'author_name'		=> $row['username'],
				'ip_address'		=> $row['ipaddress'],
				'post_date'			=> $row['dateline'],
				'queued'			=> $queued,
				'post_edit_reason'	=> $row['editreason'],
			);
			
			$libraryClass->convert_forums_post( $info );
			
			/* Reputation */
			foreach( $this->db->select( '*', 'reputation', array( "pid=?", $row['pid'] ) ) AS $rep )
			{
				$libraryClass->convert_reputation( array(
					'id'				=> $rep['rid'],
					'app'				=> 'forums',
					'type'				=> 'pid',
					'type_id'			=> $row['pid'],
					'member_id'			=> $rep['adduid'],
					'member_received'	=> $rep['uid'],
					'rep_date'			=> $rep['dateline'],
					'rep_rating'		=> $rep['reputation']
				) );
			}
			
			/* Warnings */
			foreach( $this->db->select( '*', 'warnings', array( "pid=?", $row['pid'] ) ) AS $warn )
			{
				$libraryClass->convert_warn_log( array(
					'wl_id'					=> $warn['wid'],
					'wl_member'				=> $warn['uid'],
					'wl_moderator'			=> $warn['issuedby'],
					'wl_date'				=> $warn['dateline'],
					'wl_points'				=> $warn['points'],
					'wl_note_mods'			=> $warn['notes'],
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['pid'] );
		}
	}
	
	public function convert_attachments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'aid' );
		
		foreach( $this->fetch( 'attachments', 'aid' ) AS $row )
		{
			try
			{
				$topic_id = $this->db->select( 'tid', 'posts', array( "pid=?", $row['pid'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				/* Post is orphaned */
				$libraryClass->setLastKeyValue( $row['aid'] );
				continue;
			}
			
			$map = array(
				'id1'	=> $topic_id,
				'id2'	=> $row['pid'],
			);
			
			$ext = explode( '.', $row['filename'] );
			$ext = array_pop( $ext );
			
			$info = array(
				'attach_id'			=> $row['aid'],
				'attach_file'		=> $row['filename'],
				'attach_date'		=> $row['dateuploaded'],
				'attach_member_id'	=> $row['uid'],
				'attach_hits'		=> $row['downloads'],
				'attach_ext'		=> $ext,
				'attach_filesize'	=> $row['filesize'],
			);
			
			$libraryClass->convert_attachment( $info, $map, rtrim( $this->app->_session['more_info']['convert_attachments']['attach_location'], '/' ) . '/' . $row['attachname'] );
			
			$libraryClass->setLastKeyValue( $row['aid'] );
		}
	}
}