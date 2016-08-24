<?php
/**
 * @brief		Converter UBBthreads Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2015 Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 * @version		
 */

namespace IPS\convert\Software\Forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _UBBthreads extends \IPS\convert\Software
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
		return "UBBthreads";
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
		return "UBBthreads";
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
			'convert_forums_forums'             => array(
				'table'                             => 'pseudo_forums_forums',
				'where'                             => NULL,
				'extra_steps'                       => array( 'convert_forums_forums_followers' )
			),
			'convert_forums_forums_followers'   => array(
				'table'                             => 'WATCH_LISTS',
				'where'                             => array( 'WATCH_TYPE=?', 'f' )
			),
			'convert_forums_topics'             => array(
				'table'                             => 'TOPICS',
				'where'                             => NULL,
				'extra_steps'                       => array( 'convert_forums_topics_ratings', 'convert_forums_topics_followers' )
			),
			'convert_forums_topics_ratings'     => array(
				'table'                             => 'RATINGS',
				'where'                             => array( 'RATING_TYPE=?', 't' )
			),
			'convert_forums_topics_followers'   => array(
				'table'                             => 'WATCH_LISTS',
				'where'                             => array( 'WATCH_TYPE=?', 't' )
			),
			'convert_forums_posts'              => array(
				'table'                             => 'POSTS',
				'where'                             => NULL
			),
			'convert_attachments'               => array(
				'table'                             => 'FILES',
				'where'                             => NULL
			)
		);
	}

	/**
	 * Count Source Rows for a specific step
	 *
	 * @param    string     $table The table containing the rows to count.
	 * @param    array|NULL $where WHERE clause to only count specific rows, or NULL to count all.
	 * @return int
	 * @throws \IPS\convert\Exception
	 */
	public function countRows( $table, $where=NULL )
	{
		switch ( $table )
		{
			case 'pseudo_forums_forums':
				return parent::countRows( 'CATEGORIES' ) + parent::countRows( 'FORUMS' );
		}

		return parent::countRows( $table, $where );
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
		return array( 'core' => array( 'UBBthreads' ) );
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
						'attach_location'   => array(
						'field_class'       => 'IPS\\Helpers\\Form\\Text',
						'field_default'	    => NULL,
						'field_required'    => TRUE,
						'field_extra'       => array(),
						'field_hint'        => "This is typically: /path/to/ubb/uploads/attachments",
					),
				);
				break;
		}

		return $return[ $method ];
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
			$forum->queued_posts  = \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts', array( 'forums_topics.forum_id=? AND forums_posts.queued=1', $forum->id ) )->join( 'forums_topics', 'forums_topics.tid=forums_posts.topic_id' )->first();
			$forum->save();
		}

		/* Content Rebuilds */
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'forums_posts', 'class' => 'IPS\forums\Topic\Post' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'convert', 'RebuildFirstPostIds', array( 'dummy' => 'placeholder' ), 2, array( 'dummy' ) );

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
		return \IPS\convert\Software\Core\UBBthreads::fixPostData( $post );
	}
	
	// Insert convert_ methods here

	public function convert_forums_forums()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();

		/**
		 * Categories in UBB are abstracted. So, before we actually convert any real "forums", we will want to convert
		 * the categories they are contained in.
		 */
		foreach ( $this->db->select( '*', 'CATEGORIES' ) as $row )
		{
			$libraryClass->convert_forums_forum( array(
				'id'            => 10000 + $row['CATEGORY_ID'],
				'name'          => $row['CATEGORY_TITLE'],
				'description'   => $row['CATEGORY_DESCRIPTION'],
				'position'      => $row['CATEGORY_SORT_ORDER'],
				'sub_can_post'  => 0,
				'parent_id'		=> -1
			) );
		}

		/* Here is where we actually convert the real forums */
		foreach( $this->db->select( '*', 'FORUMS' ) AS $row )
		{
			$libraryClass->convert_forums_forum( array(
				'id'					=> $row['FORUM_ID'],
				'name'					=> $row['FORUM_TITLE'],
				'description'			=> $row['FORUM_DESCRIPTION'],
				'topics'				=> $row['FORUM_TOPICS'],
				'posts'					=> $row['FORUM_POSTS'],
				'last_post'				=> \IPS\DateTime::create()->setTimestamp( $row['FORUM_LAST_POST_TIME'] ),
				'last_poster_id'		=> $row['FORUM_LAST_POSTER_ID'],
				'last_poster_name'		=> $row['FORUM_LAST_POSTER_NAME'],
				'parent_id'				=> $row['FORUM_PARENT_ID'] ?: ( 10000 + $row['CATEGORY_ID'] ),
				'position'				=> $row['FORUM_SORT_ORDER'],
				'last_title'			=> $row['FORUM_LAST_POST_SUBJECT'],
				'allow_poll'            => $row['FORUM_ALLOW_POLLS']
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_forums_forums_followers()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach ( $this->fetch( 'WATCH_LISTS', 'WATCH_ID', array( 'WATCH_TYPE=?', 'f' ) ) as $row )
		{
			$libraryClass->convert_follow( array(
				'follow_app'            => 'forums',
				'follow_area'           => 'forum',
				'follow_rel_id'         => $row['WATCH_ID'],
				'follow_rel_id_type'    => 'forums_forums',
				'follow_member_id'      => $row['USER_ID'],
				'follow_notify_freq'    => $row['WATCH_NOTIFY_IMMEDIATE'] ? 'immediate' : 'none',
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_forums_topics()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'TOPIC_ID' );

		foreach ( $this->fetch( 'TOPICS', 'TOPIC_ID' ) as $row )
		{
			/* Poll */
			$poll = NULL;
			if ( $row['TOPIC_HAS_POLL'] > 0 )
			{
				try
				{
					$pollData = $this->db->select( '*', 'POLL_DATA', array( "POST_ID=?", $row['POST_ID'] ) )->first();

					$choices    = array();
					$index      = 1;
					foreach( $this->db->select( '*', 'POLL_OPTIONS', array( 'POLL_ID=?', $pollData['POLL_ID'] ), 'OPTION_ID ASC' ) AS $choice )
					{
						$choices[ $index ] = strip_tags( $choice['CHOICE_BODY'] );  // Unlike forum titles, I don't believe we allow HTML in poll choices
						$index++;
					}

					/* Reset Index */
					$index          = 1;
					$votes          = array();
					$rawVotes = iterator_to_array( $this->db->select( '*', 'POLL_VOTES', array( 'POLL_ID=?', $pollData['POLL_ID'] ) ) );
					foreach( $rawVotes AS $vote )
					{
						$votes[ $index ] = $vote['OPTION_ID'];
						$index++;
					}

					$poll = array();
					$poll['poll_data'] = array(
						'pid'               => $pollData['POLL_ID'],
						'choices'           => array( 1 => array(
							'question'          => strip_tags( $pollData['POLL_BODY'] ),
							'multi'             => ( $pollData['POLL_TYPE'] != 'one' ),
							'choice'            => $choices,
							'votes'             => $votes
						) ),
						'poll_question'     => strip_tags( $pollData['POLL_BODY'] ),
						'start_date'        => \IPS\DateTime::create()->setTimestamp( $row['POLL_START_TIME'] ),
						'starter_id'        => $row['USER_ID'],
					);

					$poll['vote_data'] = array();
					$ourVotes = array();
					foreach( $rawVotes AS $vote )
					{
						/* "Votes need a member account", apparently, so we will probably lose guest votes </sigh> */
						if ( !isset( $ourVotes[ $vote['VOTES_USER_ID_IP'] ] ) )
						{
							$ourVotes[ $vote['VOTES_USER_ID_IP'] ] = array( 'votes' => array() );
						}

						$ourVotes[ $vote['uid'] ]['votes'][]    = $vote['OPTION_ID'];
						$ourVotes[ $vote['uid'] ]['member_id']  = $vote['VOTES_USER_ID_IP'];
					}

					/* Now we need to re-wrap it all for storage */
					foreach( $ourVotes AS $member_id => $vote )
					{
						$poll['vote_data'][ $member_id ] = array(
							'vote_date'         => $vote['vote_date'],
							'member_id'         => $vote['member_id'],
							'member_choices'    => array( 1 => $vote['votes'] ),
						);
					}
				}
				catch( \UnderflowException $e ) {} # if the poll is missing, don't bother
			}

			$libraryClass->convert_forums_topic( array(
				'tid'               => $row['TOPIC_ID'],
				'title'             => mb_substr( $row['TOPIC_SUBJECT'], 0, 250 ),
				'forum_id'          => $row['FORUM_ID'],
				'state'             => ( $row['TOPIC_STATUS'] == 'C' ) ? 'closed' : 'open',
				'posts'             => $row['TOPIC_TOTAL_REPLIES'],
				'starter_id'        => $row['USER_ID'],
				'start_date'        => \IPS\DateTime::create()->setTimestamp( $row['TOPIC_CREATED_TIME'] ),
				'last_poster_id'    => $row['TOPIC_LAST_POSTER_ID'],
				'last_post'         => \IPS\DateTime::create()->setTimestamp( $row['TOPIC_LAST_REPLY_TIME'] ),
				'last_poster_name'  => $row['TOPIC_LAST_POSTER_NAME'],
				'views'             => $row['TOPIC_VIEWS'],
				'approved'          => $row['TOPIC_IS_APPROVED'],
				'pinned'            => $row['TOPIC_IS_STICKY'],
				'poll_state'        => $poll
			) );

			$libraryClass->setLastKeyValue( $row['TOPIC_ID'] );
		}
	}

	public function convert_forums_topics_ratings()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach ( $this->db->select( '*', 'RATINGS', array( 'RATING_TYPE=?', 't' ) ) as $row )
		{
			$libraryClass->convert_rating( array(
				'class'     => 'IPS\\forums\\Topic',
				'item_link' => 'forums_topics',
				'item_id'   => $row['RATING_TARGET'],
				'rating'    => $row['RATING_VALUE'],
				'member'    => $row['RATING_RATER']
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_forums_topics_followers()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach ( $this->fetch( 'WATCH_LISTS', 'WATCH_ID', array( 'WATCH_TYPE=?', 't' ) ) as $row )
		{
			$libraryClass->convert_follow( array(
				'follow_app'            => 'forums',
				'follow_area'           => 'topic',
				'follow_rel_id'         => $row['WATCH_ID'],
				'follow_rel_id_type'    => 'forums_topics',
				'follow_member_id'      => $row['USER_ID'],
				'follow_notify_freq'    => $row['WATCH_NOTIFY_IMMEDIATE'] ? 'immediate' : 'none',
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_forums_posts()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'POST_ID' );

		foreach ( $this->fetch( 'POSTS', 'POST_ID' ) as $row )
		{
			$libraryClass->convert_forums_post( array(
				'pid'           => $row['POST_ID'],
				'topic_id'      => $row['TOPIC_ID'],
				'post'          => $this->fixPostData( $row['POST_DEFAULT_BODY'] ),
				'append_edit'   => ( (int) $row['POST_LAST_EDITED_TIME'] > 0 ),
				'edit_time'     => $row['POST_LAST_EDITED_TIME'],
				'edit_name'     => $row['POST_LAST_EDITED_BY'],
				'author_id'     => $row['USER_ID'],
				'author_name'   => $row['POST_POSTER_NAME'],
				'ip_address'    => $row['POST_POSTER_IP'],
				'post_date'     => \IPS\DateTime::create()->setTimestamp( $row['POST_POSTED_TIME'] ),
				'queued'        => ( ! $row['POST_IS_APPROVED'] ),
				'new_topic'     => $row['POST_IS_TOPIC'],
			) );

			$libraryClass->setLastKeyValue( $row['POST_ID'] );
		}
	}

	public function convert_attachments()
	{
		/** @var \IPS\convert\Library\Forums $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'FILE_ID' );

		foreach( $this->fetch( 'FILES', 'FILE_ID' ) AS $row )
		{
			$topicId = $this->db->select( 'TOPIC_ID', 'POSTS', array( "POST_ID=?", $row['POST_ID'] ) )->first();

			$map = array(
				'id1'   => $topicId,
				'id2'   => $row['POST_ID'],
			);

			$info = array(
				'attach_id'			=> $row['FILE_ID'],
				'attach_file'		=> $row['FILE_NAME'],
				'attach_date'		=> \IPS\DateTime::create()->setTimestamp( $row['FILE_ADD_TIME'] ),
				'attach_member_id'	=> $row['USER_ID'],
				'attach_hits'		=> $row['FILE_DOWNLOADS'],
				'attach_ext'		=> pathinfo( $row['FILE_NAME'], \PATHINFO_EXTENSION ),
				'attach_filesize'	=> $row['FILE_SIZE'],  // Note: Apparently not always readily available?
			);

			$libraryClass->convert_attachment( $info, $map, rtrim( $this->app->_session['more_info']['convert_attachments']['attach_location'], '/' ) . '/' . $row['FILE_NAME'] );
			$libraryClass->setLastKeyValue( $row['FILE_ID'] );
		}
	}
}