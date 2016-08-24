<?php

/**
 * @brief		Converter phpBB Class
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

class _Phpbb extends \IPS\convert\Software
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
		return "phpBB 3.x";
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
		return "phpbb";
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
			'convert_forums_forums'	=> array(
				'table'		=> 'forums',
				'where'		=> NULL,
			),
			'convert_forums_topics'	=> array(
				'table'		=> 'topics',
				'where'		=> NULL
			),
			'convert_forums_posts'	=> array(
				'table'		=> 'posts',
				'where'		=> NULL
			),
			'convert_attachments'	=> array(
				'table'		=> 'attachments',
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
			'convert_forums_forums',
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
			case 'convert_forums_forums':
				$return['convert_forums_forums'] = array();
				$forums = $this->db->select( '*', 'forums' );
				foreach( $forums AS $forum )
				{
					if ( $forum['forum_password'] )
					{
						\IPS\Member::loggedIn()->language()->words["forum_password_{$forum['forum_id']}"] = \IPS\Member::loggedIn()->language()->addToStack( 'forum_password', FALSE, array( 'sprintf' => array( $forum['forum_name'] ) ) );
						\IPS\Member::loggedIn()->language()->words["forum_password_{$forum['forum_id']}_desc"] = \IPS\Member::loggedIn()->language()->addToStack( 'forum_password_desc' );
						
						$return['convert_forums_forums']["forum_password_{$forum['forum_id']}"] = array(
							'field_class'		=> 'IPS\\Helpers\\Form\\Text',
							'field_default'		=> NULL,
							'field_required'	=> FALSE,
							'field_extra'		=> array(),
							'field_hint'		=> NULL,
						);
					}
				}
				break;
			case 'convert_attachments':
				$return['convert_attachments'] = array(
					'attach_location'	=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> "This is typically: /path/to/phpbb/files",
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					),
				);
				break;
		}
		
		return $return[$method];
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
		return array( 'core' => array( 'phpbb' ) );
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
	 * Clean PHPBB UIDs from BBcode
	 *
	 * @param 	string		raw post data
	 * @param 	string		BBCode UID
	 * @return 	string		parsed post data
	 **/	
	public function strip_uid ( $post, $uid )
	{
		return \IPS\convert\Software\Core\Phpbb::strip_uid( $post, $uid );
	}
	
	/**
	 * Fix post data
	 *
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 **/
	public static function fixPostData( $post )
	{
		return \IPS\convert\Software\Core\Phpbb::fixPostData( $post );
	}
	
	public function convert_forums_forums()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'forum_id' );
		
		foreach( $this->fetch( 'forums', 'forum_id' ) AS $row )
		{
			$info = array(
				'id'				=> $row['forum_id'],
				'name'				=> $row['forum_name'],
				'description'		=> $row['forum_desc'],
				'topics'			=> isset( $row['forum_topics_approved'] ) ? $row['forum_topics_approved'] : $row['forum_topics'],
				'posts'				=> isset( $row['forum_posts_approved'] ) ? $row['forum_posts_approved'] : $row['forum_posts'],
				'last_post'			=> $row['forum_last_post_time'],
				'last_poster_id'	=> $row['forum_last_poster_id'],
				'last_poster_name'	=> $row['forum_last_poster_name'],
				'parent_id'			=> ( $row['parent_id'] != 0 ) ? $row['parent_id'] : -1,
				'conv_parent'		=> ( $row['parent_id'] != 0 ) ? $row['parent_id'] : -1,
				'position'			=> $row['left_id'],
				'password'			=> ( isset( $this->app->_session['more_info']['convert_forums_forums']["forum_password_{$row['forum_id']}"] ) ) ? $this->app->_session['more_info']['convert_forums_forums']["forum_password_{$row['forum_id']}"] : NULL,
				'last_title'		=> $row['forum_last_post_subject'],
				'queued_topics'		=> isset( $row['forum_topics_unapproved'] ) ? $row['forum_topics_unapproved'] : 0,
				'queued_posts'		=> isset( $row['forum_posts_unapproved'] ) ? $row['forum_topics_unapproved'] : 0,
			);
			
			$libraryClass->convert_forums_forum( $info );
			
			/* Follows */
			foreach( $this->db->select( '*', 'forums_watch', array( "forum_id=?", $row['forum_id'] ) ) AS $follow )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'forum',
					'follow_rel_id'			=> $row['forum_id'],
					'follow_rel_id_type'	=> 'forums_forums',
					'follow_member_id'		=> $follow['user_id'],
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
			
			$libraryClass->setLastKeyValue( $row['forum_id'] );
		}
	}
	
	public function convert_forums_topics()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'topic_id' );
		
		foreach( $this->fetch( 'topics', 'topic_id' ) AS $row )
		{
			$poll = NULL;
			
			if ( $row['poll_title'] )
			{
				$poll = array();
				
				$choices	= array();
				$votes		= array();
				$index		= 1;
				$search		= array(); # make sure we actually assign the vote correctly
				foreach( $this->db->select( '*', 'poll_options', array( "topic_id=?", $row['topic_id'] ) ) AS $choice )
				{
					$choices[$index]	= $choice['poll_option_text'];
					$votes[$index]		= $choice['poll_option_total'];
					$search[$index]		= $choice['poll_option_id'];
					$index++;
				}
				
				$poll['poll_data'] = array(
					'pid'				=> $row['topic_id'],
					'choices'			=> array( 1 => array(
						'question'			=> $row['poll_title'],
						'multi'				=> ( $row['poll_max_options'] > 1 ) ? 1 : 0,
						'choice'			=> $choices,
						'votes'				=> $votes,
					) ),
					'poll_question'		=> $row['poll_title'],
					'start_date'		=> $row['poll_start'],
					'starter_id'		=> $row['topic_poster'],
					'votes'				=> array_sum( $votes ),
					'poll_view_voters'	=> 0,
				);
				
				$poll['vote_data']	= array();
				$ourVotes			= array();
				foreach( $this->db->select( '*', 'poll_votes', array( "topic_id=?", $row['topic_id'] ) ) AS $vote )
				{
					if ( !isset( $ourVotes[$vote['vote_user_id']] ) )
					{
						$ourVotes[$vote['vote_user_id']] = array( 'votes' => array() );
					}
					
					$ourVotes[$vote['vote_user_id']]['votes'][]		= array_search( $vote['poll_option_id'], $search );
					$ourVotes[$vote['vote_user_id']]['member_id']	= $vote['vote_user_id'];
				}
				
				foreach( $ourVotes AS $member_id => $vote )
				{
					$poll['vote_data'][$member_id] = array(
						'member_id'			=> $vote['member_id'],
						'member_choices'	=> array( 1 => $vote['votes'] ),
					);
				}
			}
			
			if ( isset( $row['topic_visibility'] ) )
			{
				$visibility = $row['topic_visibility'];
			}
			else
			{
				$visibility = $row['topic_approved'];
			}
			
			/* Global Topics */
			if ( !$row['forum_id'] )
			{
				try
				{
					$orphaned = $this->app->getLink( '__global__', 'forums_forums' );
				}
				catch( \OutOfRangeException $e )
				{
					/* Create a forum to store it in */
					$libraryClass->convert_forums_forum( array(
						'id'			=> '__global__',
						'name'			=> 'Global Topics',
					) );
				}
				
				$row['forum_id'] = '__global__';
			}
			
			$info = array(
				'tid'				=> $row['topic_id'],
				'title'				=> $row['topic_title'],
				'forum_id'			=> $row['forum_id'],
				'state'				=> ( $row['topic_status'] == 0 ) ? 'open' : 'closed',
				'posts'				=> isset( $row['topic_posts_approved'] ) ? $row['topic_posts_approved'] : $row['topic_replies'],
				'starter_id'		=> $row['topic_poster'],
				'start_date'		=> $row['topic_time'],
				'last_poster_id'	=> $row['topic_last_poster_id'],
				'last_post'			=> $row['topic_last_post_time'],
				'starter_name'		=> $row['topic_first_poster_name'],
				'last_poster_name'	=> $row['topic_last_poster_name'],
				'poll_state'		=> $poll,
				'last_vote'			=> $row['poll_last_vote'],
				'views'				=> $row['topic_views'],
				'approved'			=> ( $visibility <> 1 ) ? -1 : 1,
				'pinned'			=> ( $row['topic_type'] == 0 ) ? 0 : 1,
				'topic_hiddenposts'	=> ( isset( $row['topic_posts_unapproved'] ) AND isset( $row['topic_posts_softdeleted'] ) ) ? $row['topic_posts_unapproved'] + $row['topic_posts_softdeleted'] : 0,
			);
			
			$libraryClass->convert_forums_topic( $info );
			
			/* Follows */
			foreach( $this->db->select( '*', 'topics_watch', array( "topic_id=?", $row['topic_id'] ) ) AS $follow )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'topic',
					'follow_rel_id'			=> $row['topic_id'],
					'follow_rel_id_type'	=> 'forums_topics',
					'follow_member_id'		=> $follow['user_id'],
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
			
			$libraryClass->setLastKeyValue( $row['topic_id'] );
		}
	}
	
	public function convert_forums_posts()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'post_id' );
		
		foreach( $this->fetch( 'posts', 'post_id' ) AS $row )
		{
			if ( isset( $row['post_visibility'] ) )
			{
				$visibility = $row['post_visibility'];
			}
			else
			{
				$visibility = $row['post_approved'];
			}
			
			$info = array(
				'pid'			=> $row['post_id'],
				'topic_id'		=> $row['topic_id'],
				'post'			=> strip_uid( $row['post_text'], $row['bbcode_uid'] ),
				'append_edit'	=> ( $row['post_edit_user'] ) ? 1 : 0,
				'edit_time'		=> $row['post_edit_time'],
				'author_id'		=> $row['poster_id'],
				'ip_address'	=> $row['poster_ip'],
				'post_date'		=> $row['post_time'],
				'queued'		=> ( $visibility == 1 ) ? 0 : -1,
				'pdelete_time'	=> isset( $row['post_delete_time'] ) ? $row['post_delete_time'] : NULL,
			);
			
			$libraryClass->convert_forums_post( $info );
			
			/* Warnings */
			foreach( $this->db->select( '*', 'warnings', array( "post_id=?", $row['post_id'] ) ) AS $warning )
			{
				try
				{
					$log	= $this->db->select( '*', 'log', array( "log_id=?", $warning['log_id'] ) )->first();
					$data	= \unserialize( $log['log_data'] );
				}
				catch( \UnderflowException $e )
				{
					$log	= array( 'user_id' => 0 );
					$data	= array( 0 => NULL );
				}
				
				$libraryClass->convert_warn_log( array(
					'wl_id'					=> $warn['warning_id'],
					'wl_member'				=> $warn['user_id'],
					'wl_moderator'			=> $log['user_id'],
					'wl_date'				=> $warn['warning_time'],
					'wl_points'				=> 1,
					'wl_note_member'		=> isset( $data[0] ) ? $data[0] : NULL,
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['post_id'] );
		}
	}
	
	public function convert_attachments()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'attach_id' );
		
		foreach( $this->fetch( 'attachments', 'attach_id' ) AS $row )
		{
			$map = array(
				'id1'	=> $row['topic_id'],
				'id2'	=> $row['post_msg_id'],
			);
			
			$info = array(
				'attach_id'			=> $row['attach_id'],
				'attach_file'		=> $row['real_filename'],
				'attach_date'		=> $row['filetime'],
				'attach_member_id'	=> $row['poster_id'],
				'attach_hits'		=> $row['download_count'],
				'attach_ext'		=> $row['extension'],
				'attach_filesize'	=> $row['filesize'],
			);
			
			$libraryClass->convert_attachment( $info, $map, rtrim( $this->app->_session['more_info']['convert_attachments']['attach_location'], '/' ) . '/' . $row['physical_filename'] );
			
			$libraryClass->setLastKeyValue( $row['attach_id'] );
		}
	}
}