<?php

/**
 * @brief		Converter Punbb Class
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

class _Punbb extends \IPS\convert\Software
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
		return "PunBB 1.x";
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
		return "punbb";
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
		return array( 'core' => array( 'punbb' ) );
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
		return \IPS\convert\Software\Core\Punbb::fixPostData( $post );
	}
	
	public function convert_forums_forums()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'forums', 'id' ) AS $row )
		{
			/* PunBB has separate concepts of categories versus forums - normally, we would do these in separate processes but that isn't really all that necessary */
			try
			{
				$catId = $this->app->getLink( '1000' . $row['cat_id'], 'forums_forums' );
			}
			catch( \OutOfRangeException $e )
			{
				try
				{
					$category = $this->db->select( '*', 'categories', array( "id=?", $row['cat_id'] ) )->first();
					
					$libraryClass->convert_forums_forum( array(
						'id'			=> '1000' . $category['id'],
						'name'			=> $category['cat_name'],
						'parent_id'		=> -1,
						'position'		=> $category['disp_position']
					) );
				}
				catch( \UnderflowException $e ) {}
			}
			
			/* They don't store the last poster ID. Makes me sad. */
			$last_poster_id = 0;
			try
			{
				/* Better hope they haven't changed their name */
				$last_poster_id = $this->db->select( 'id', 'users', array( "username=?", $row['last_poster'] ) )->first();
			}
			catch( \UnderflowException $e ) {}
			
			$info = array(
				'id'				=> $row['id'],
				'name'				=> $row['forum_name'],
				'description'		=> $row['forum_desc'],
				'topics'			=> $row['num_topics'],
				'posts'				=> $row['num_posts'],
				'last_post'			=> $row['last_post'],
				'last_poster_id'	=> $last_poster_id,
				'last_poster_name'	=> $row['last_poster'],
				'parent_id'			=> ( $row['parent_forum_id'] == 0 ) ? '1000' . $row['cat_id'] : $row['parent_forum_id'],
				'position'			=> $row['disp_position'],
				'redirect_url'		=> $row['redirect_url'],
			);
			
			$libraryClass->convert_forums_forum( $info );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	public function convert_forums_topics()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'topics', 'id' ) AS $row )
		{
			/* sigh */
			$last_poster_id = 0;
			try
			{
				$last_poster_id = $this->db->select( 'id', 'users', array( "username=?", $row['last_poster'] ) )->first();
			}
			catch( \UnderflowException $e ) {}
			
			$starter_id = 0;
			try
			{
				$starter_id = $this->db->select( 'id', 'users', array( "username=?", $row['poster'] ) )->first();
			}
			catch( \UnderflowException $e ) {}
			
			$moved_to = NULL;
			if ( $row['moved_to'] )
			{
				try
				{
					$moved_to_forum = $this->db->select( 'forum_id', 'topics', array( "id=?", $row['moved_to'] ) )->first();
					$moved_to = [ $row['moved_to'], $moved_to_forum ];
				}
				catch( \UnderflowException $e ) {}
			}
			
			$info = array(
				'tid'				=> $row['id'],
				'title'				=> $row['subject'],
				'forum_id'			=> $row['forum_id'],
				'state'				=> ( $row['closed'] ) ? 'closed' : 'open',
				'posts'				=> $row['num_replies'],
				'starter_id'		=> $starter_id,
				'start_date'		=> $row['posted'],
				'last_poster_id'	=> $last_poster_id,
				'last_post'			=> $row['last_post'],
				'starter_name'		=> $row['poster'],
				'last_poster_name'	=> $row['last_poster'],
				'pinned'			=> ( $row['sticky'] OR $row['announcement'] ) ? 1 : 0,
				'moved_to'			=> $moved_to,
				'moved_on'			=> ( !is_null( $moved_to ) ) ? $row['posted'] : 0,
			);
			
			$libraryClass->convert_forums_topic( $info );
			
			foreach( $this->db->select( '*', 'subscriptions', array( "topic_id=?", $row['id'] ) ) AS $follow )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'topic',
					'follow_rel_id'			=> $row['id'],
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
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	public function convert_forums_posts()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'posts', 'id' ) AS $row )
		{
			$info = array(
				'pid'			=> $row['id'],
				'topic_id'		=> $row['topic_id'],
				'post'			=> $row['message'],
				'append_edit'	=> ( $row['edited'] ) ? 1 : 0,
				'edit_time'		=> $row['edited'],
				'author_id'		=> $row['poster_id'], # I half expected them to not store this
				'author_name'	=> $row['poster'],
				'ip_address'	=> $row['poster_ip'],
				'post_date'		=> $row['posted'],
				'edit_name'		=> $row['edited_by'],
			);
			
			$libraryClass->convert_forums_post( $info );
			
			$libraryClass->setLastKeyvalue( $row['id'] );
		}
	}
}