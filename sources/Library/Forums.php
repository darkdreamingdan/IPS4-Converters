<?php

/**
 * @brief		Converter Library Forums Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Library;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Inherit from the Core Library so we can access or overload methods as needed (for example, we do not need to redeclare convert_attachments again here, but we can overload to do extra things if needed).
 */
class _Forums extends Core
{
	/**
	 * @brief	Application
	 */
	public $app = 'forums';
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to complete this conversion
	 *
	 * @return	string
	 * @todo
	 */
	public function getPostConversionInformation()
	{
		return "Once all steps have been completed, you can <a href='" . (string) \IPS\Http\Url::internal( "app=convert&module=manage&controller=create&_new=1" ) . "'>begin another conversion</a> or <a href='" . (string) \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=finish&id={$this->software->app->parent}" ) . "'>finish</a> to rebuild data.";
	}
	
	/**
	 * Returns an array of items that we can convert, including the amount of rows stored in the Community Suite as well as the recommend value of rows to convert per cycle
	 *
	 * @return	array
	 * @todo Make sure the order of these makes sense.
	 */
	public function menuRows()
	{
		$return		= array();
		$classname	= get_class( $this->software );
		foreach( $classname::canConvert() AS $k => $v )
		{
			switch( $k )
			{
				case 'convert_forums_forums':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_forums',
						'step_method'	=> 'convert_forums_forums',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_forums' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 100,
						'dependencies'	=> array(),
						'link_type'		=> 'forums_forums',
					);
					break;
				
				case 'convert_forums_topics':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_topics',
						'step_method'	=> 'convert_forums_topics',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 1000,
						'dependencies'	=> array( 'convert_forums_forums' ),
						'link_type'		=> 'forums_topics',
					);
					break;
				
				case 'convert_forums_posts':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_posts',
						'step_method'	=> 'convert_forums_posts',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 1000,
						'dependencies'	=> array( 'convert_forums_topics' ),
						'link_type'		=> 'forums_posts',
					);
					break;
				
				case 'convert_forums_archive_posts':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_archive_posts',
						'step_method'	=> 'convert_forums_archive_posts',
						'ips_rows'		=> \IPS\forums\Topic\ArchivedPost::db()->select( 'COUNT(*)', 'forums_archive_posts' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 1000,
						'dependencies'	=> array( 'convert_forums_topics' ),
						'link_type'		=> 'forums_posts',
					);
					break;
				
				case 'convert_forums_topic_multimods':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_topic_multimods',
						'step_method'	=> 'convert_forums_topic_multimods',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_topic_mmod' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 100,
						'dependencies'	=> array( 'convert_forums_forums' ),
						'link_type'		=> 'forums_topic_mmod',
					);
					break;
				
				case 'convert_forums_rss_imports':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_rss_imports',
						'step_method'	=> 'convert_forums_rss_imports',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_rss_import' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 100,
						'dependencies'	=> array( 'convert_forums_forums' ),
						'link_type'		=> 'forums_rss_import',
					);
					break;
				
				case 'convert_forums_rss_imported':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_rss_imported',
						'step_method'	=> 'convert_forums_rss_imported',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_rss_imported' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 2000,
						'dependencies'	=> array( 'convert_forums_topics', 'convert_forums_rss_imports' ),
						'link_type'		=> 'forums_rss_imported',
					);
					break;
				
				case 'convert_forums_question_ratings':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_question_ratings',
						'step_method'	=> 'convert_forums_question_ratings',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_question_ratings' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 2000,
						'dependencies'	=> array( 'convert_forums_topics' ),
						'link_type'		=> 'forums_question_ratings',
					);
					break;
				
				case 'convert_forums_answer_ratings':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_answer_ratings',
						'step_method'	=> 'convert_forums_answer_ratings',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'forums_answer_ratings' )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 2000,
						'dependencies'	=> array( 'convert_forums_posts' ),
						'link_type'		=> 'forums_answer_ratings',
					);
					break;
				
				case 'convert_attachments':
					$return[$k] = array(
						'step_title'	=> 'convert_forums_attachments',
						'step_method'	=> 'convert_attachments',
						'ips_rows'		=> \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments_map', array( "location_key=?", 'forums_Forums' ) )->first(),
						'source_rows'	=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'		=> 50,
						'dependencies'	=> array( 'convert_forums_posts' ),
						'link_type'		=> 'core_attachments',
					);
					break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Returns an array of tables that need to be truncated when Empty Local Data is used
	 *
	 * @return	array
	 * @todo
	 */
	protected function truncate( $method )
	{
		$return		= array();
		$classname	= get_class( $this->software );
		foreach( $classname::canConvert() AS $k => $v )
		{
			switch( $k )
			{
				case 'convert_forums_forums':
					$return['convert_forums_forums'] = array( 'forums_forums' => NULL );
					break;
				
				case 'convert_forums_topics':
					$return['convert_forums_topics'] = array( 'forums_topics' => NULL );
					break;
				
				case 'convert_forums_posts':
					$return['convert_forums_posts'] = array( 'forums_posts' => NULL );
					break;
				
				case 'convert_forums_archive_posts':
					$return['convert_forums_archive_posts'] = array( 'forums_archive_posts' => NULL );
					break;
				
				case 'convert_forums_topic_multimods':
					$return['convert_forums_topic_multimods'] = array( 'forums_topic_mmod' => NULL );
					break;
				
				case 'convert_forums_rss_imports':
					$return['convert_forums_rss_imports'] = array( 'forums_rss_imports' => NULL );
					break;
				
				case 'convert_forums_rss_imported':
					$retun['convert_forums_rss_imported'] = array( 'forums_rss_imported' => NULL );
					break;
				
				case 'convert_forums_question_ratings':
					$retun['convert_forums_question_ratings'] = array( 'forums_question_ratings' => NULL );
					break;
				
				case 'convert_forums_answer_ratings':
					$return['convert_forums_answer_ratings'] = array( 'forums_answer_ratings' => NULL );
					break;
				
				case 'convert_attachments':
					$attachIds = array();
					foreach( \IPS\Db::i()->select( 'attachment_id', 'core_attachments_map', array( 'location_key=?', 'forums_Forums' ) ) AS $attachment )
					{
						$attachIds[] = $attachment;
					}
					$return['convert_attachments'] = array( 'core_attachments' => \IPS\Db::i()->in( 'attach_id', $attachIds ), 'core_attachments_map' => array( "location_key=?", 'forums_Forums' ) );
					break;
			}
		}
		return $return[$method];
	}
	
	/**
	 * This is how the insert methods will work - basically like 3.x, but we should be using the actual classes to insert the data unless there is a real world reason not too.
	 * Using the actual routines to insert data will help to avoid having to resynchronize and rebuild things later on, thus resulting in less conversion time being needed overall.
	 * Anything that parses content, for example, may need to simply insert directly then rebuild via a task over time, as HTML Purifier is slow when mass inserting content.
	 */
	
	/**
	 * A note on logging -
	 * If the data is missing and it is unlikely that any source software would be able to provide this, we do not need to log anything and can use default data (for example, group_layout in convert_leader_groups).
	 * If the data is missing and it is likely that a majority of the source software can provide this, we should log a NOTICE and use default data (for example, a_casesensitive in convert_acronyms).
	 * If the data is missing and it is required to convert the item, we should log a WARNING and return FALSE.
	 * If the conversion absolutely cannot proceed at all (filestorage locations not writable, for example), then we should log an ERROR and throw an \IPS\convert\Exception to completely halt the process and redirect to an error screen showing the last logged error.
	 */
	
	/**
	 * Convert a Forum
	 *
	 * @param	array			$info		Data to insert
	 * @param	array|NULL		$perms		Permissions or NULL to insert no permissions.
	 * @param	string|NULL		$iconpath	The path to the forums icon, or NULL.
	 * @param	string|NULL		$icondata	The binary data for the forums icon, or NULL.
	 * @return	integer|boolean	The ID of the newly inserted forum, or FALSE on failure.
	 */
	public function convert_forums_forum( $info=array(), $perms=NULL, $iconpath=NULL, $icondata=NULL )
	{
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'forums_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['name'] ) )
		{
			$name = "Untitled Forum {$info['id']}";
			$this->software->app->log( 'forum_missing_name', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
		}
		else
		{
			$name = $info['name'];
			unset( $info['name'] );
		}
		
		if ( !array_key_exists( 'description', $info ) )
		{
			$desc = '';
		}
		else
		{
			/* Make sure the description isn't empty/null */
			$desc = $info['description'] ?: '';
			unset( $info['description'] );
		}
		
		if ( !isset( $info['topics'] ) )
		{
			$info['topics'] = 0;
		}
		
		if ( !isset( $info['posts'] ) )
		{
			$info['posts'] = 0;
		}
		
		if ( isset( $info['last_post'] ) )
		{
			if ( $info['last_post'] instanceof \IPS\DateTime )
			{
				$info['last_post'] = $info['last_post']->getTimestamp();
			}
		}
		else
		{
			$info['last_post'] = NULL;
		}
		
		if ( isset( $info['last_poster_id'] ) )
		{
			try
			{
				$info['last_poster_id'] = $this->software->app->getLink( $info['last_poster_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['last_poster_id'] = 0;
			}
		}
		else
		{
			$info['last_poster_id'] = 0;
		}
		
		if ( !isset( $info['last_poster_name'] ) )
		{
			$info['last_poster_name'] = '';
		}
		
		/* Do parent_id now - we'll need it later */
		if ( isset( $info['parent_id'] ) )
		{
			if ( $info['parent_id'] != -1 )
			{
				try
				{
					$info['parent_id'] = $this->software->app->getLink( $info['parent_id'], 'forums_forums' );
				}
				catch( \OutOfRangeException $e )
				{
					/* Does the ID exist in the source? */
					$info['conv_parent'] = $info['parent_id'];
					
					/* Default to a category unless a later converted forum updates the parent_id */
					$info['parent_id'] = -1;
				}
			}
		}
		else
		{
			$info['parent_id'] = $this->_getOrphanedForumCategory();
		}
		
		if ( !isset( $info['position'] ) )
		{
			try
			{
				$position = \IPS\Db::i()->select( 'MAX(position)', 'forums_forums', array( "parent_id=?", $info['parent_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$position = 0;
			}
			
			$info['position'] = $position + 1;
		}
		
		if ( !isset( $info['password'] ) )
		{
			$info['password'] = NULL;
		}
		
		if ( isset( $info['password_override'] ) )
		{
			/* Array? */
			if ( !is_array( $info['password_override'] ) )
			{
				$info['password_override'] = explode( ',', $info['password_override'] );
			}
			
			$passwordGroupsToSave = array();
			foreach( $info['password_override'] AS $group )
			{
				try
				{
					$passwordGroupsToSave[] = $this->software->app->getLink( $group, 'core_groups', TRUE );
				}
				catch( \OutOfRangeException $e )
				{
					continue;
				}
			}
			$info['password_override'] = implode( ',', $passwordGroupsToSave );
		}
		else
		{
			$info['password_override'] = '';
		}
		
		if ( !isset( $info['last_title'] ) )
		{
			$info['last_title'] = NULL;
		}
		
		/* We can't know this yet */
		$info['last_id'] = 0;
		
		if ( !isset( $info['sort_key'] ) )
		{
			$info['sort_key'] = 'last_post';
		}
		
		if ( !isset( $info['show_rules'] ) )
		{
			$info['show_rules'] = 0;
		}
		
		if ( !isset( $info['preview_posts'] ) )
		{
			$info['preview_posts'] = 0;
		}
		
		if ( !isset( $info['allow_poll'] ) )
		{
			$info['allow_poll'] = 1;
		}
		
		if ( !isset( $info['inc_postcount'] ) )
		{
			$info['inc_postcount'] = 1;
		}
		
		/* Can't convert themes */
		$info['skin_id'] = 0;
		
		if ( !isset( $info['redirect_url'] ) OR filter_var( $info['redirect_url'], FILTER_VALIDATE_URL ) === FALSE )
		{
			$info['redirect_url'] = NULL;
		}
		
		if ( !isset( $info['redirect_on'] ) )
		{
			if ( !is_null( $info['redirect_url'] ) )
			{
				$info['redirect_on'] = 1;
			}
			else
			{
				$info['redirect_on'] = 0;
			}
		}
		
		if ( !isset( $info['redirect_hits'] ) )
		{
			$info['redirect_hits'] = 0;
		}
		
		if ( !isset( $info['sub_can_post'] ) )
		{
			if ( $info['parent_id'] == -1 )
			{
				$info['sub_can_post'] = 0;
			}
			else
			{
				$info['sub_can_post'] = 1;
			}
		}
		
		if ( !isset( $info['permission_showtopic'] ) )
		{
			$info['permission_showtopic'] = 0;
		}
		
		if ( !isset( $info['queued_topics'] ) )
		{
			$info['queued_topics'] = 0;
		}
		
		if ( !isset( $info['queued_posts'] ) )
		{
			$info['queued_posts'] = 0;
		}
		
		if ( !isset( $info['forum_allow_rating'] ) )
		{
			$info['forum_allow_rating'] = 0;
		}
		
		if ( !isset( $info['min_posts_post'] ) )
		{
			$info['min_posts_post'] = 0;
		}
		
		if ( !isset( $info['min_posts_view'] ) )
		{
			$info['min_posts_view'] = 0;
		}
		
		if ( !isset( $info['can_view_others'] ) )
		{
			$info['can_view_others'] = 1;
		}
		
		/* Just reset this */
		$info['name_seo'] = \IPS\Http\url::seoTitle( $name );
		
		if ( !isset( $info['seo_last_title'] ) )
		{
			if ( !is_null( $info['last_title'] ) )
			{
				$info['seo_last_title'] = \IPS\Http\Url::seoTitle( $info['last_title'] );
			}
			else
			{
				$info['seo_last_title'] = '';
			}
		}
		
		if ( !isset( $info['seo_last_name'] ) )
		{
			if ( !is_null( $info['last_poster_name'] ) )
			{
				$info['seo_last_name'] = \IPS\Http\Url::seoTitle( $info['last_poster_name'] );
			}
			else
			{
				$info['seo_last_name'] = '';
			}
		}
		
		/* No longer used */
		$info['last_x_topic_ids'] = NULL;
		
		$bitoptions = 0;
		if ( isset( $info['forum_bitoptions'] ) )
		{
			foreach( \IPS\forums\Forum::$bitOptions['forums_bitoptions']['forums_bitoptions'] AS $key => $value )
			{
				if ( isset( $info['forums_bitoptions'][$key] ) AND $info['forums_bitoptions'][$key] )
				{
					$bitoptions += $value;
				}
			}
			$info['forums_bitoptions'] = $bitoptions;
		}
		
		if ( !isset( $info['disable_sharelinks'] ) )
		{
			$info['disable_sharelinks'] = 0;
		}
		
		if ( !isset( $info['tag_predefined'] ) )
		{
			$info['tag_predefined'] = NULL;
		}
		
		if ( !isset( $info['archived_topics'] ) )
		{
			$info['archived_topics'] = 0;
		}
		
		if ( !isset( $info['archived_posts'] ) )
		{
			$info['archived_posts'] = 0;
		}
		
		if ( !isset( $info['ipseo_priority'] ) )
		{
			$info['ipseo_priority'] = -1;
		}
		
		if ( !isset( $info['viglink'] ) )
		{
			$info['viglink'] = 1;
		}
		
		if ( isset( $info['qa_rate_questions'] ) )
		{
			if ( $info['qa_rate_questions'] != '*' )
			{
				if ( !is_array( $info['qa_rate_questions'] ) )
				{
					$info['qa_rate_questions'] = explode( ',', $info['qa_rate_questions'] );
				}
				
				if ( count( $info['qa_rate_questions'] ) )
				{
					$qaQuestionGroups = array();
					foreach( $info['qa_rate_questions'] AS $group )
					{
						try
						{
							$qaQuestionGroups[] = $this->software->app->getLink( $group, 'core_groups', TRUE );
						}
						catch( \OutOfRangeException $e )
						{
							continue;
						}
					}
					
					if ( count( $qaQuestionGroups ) )
					{
						$info['qa_rate_questions'] = implode( ',', $qaQuestionGroups );
					}
					else
					{
						$info['qa_rate_questions'] = '*';
					}
				}
				else
				{
					$info['qa_rate_questions'] = '*';
				}
			}
		}
		else
		{
			$info['qa_rate_questions'] = '*';
		}
		
		if ( isset( $info['qa_rate_answers'] ) )
		{
			if ( $info['qa_rate_answers'] != '*' )
			{
				if ( !is_array( $info['qa_rate_answers'] ) )
				{
					$info['qa_rate_answers'] = explode( ',', $info['qa_rate_answers'] );
				}
				
				if ( count( $info['qa_rate_anwsers'] ) )
				{
					$qaAnswerGroups = array();
					foreach( $info['qa_rate_answers'] AS $group )
					{
						try
						{
							$qaAnswerGroups[] = $this->software->app->getLink( $group, 'core_groups', TRUE );
						}
						catch( \OutOfRangeException $e )
						{
							continue;
						}
					}
					
					if ( count( $qaAnswerGroups ) )
					{
						$info['qa_rate_answers'] = implode( ',', $qaAnswerGroups );
					}
					else
					{
						$info['qa_rate_answers'] = '*';
					}
				}
				else
				{
					$info['qa_rate_answers'] = '*';
				}
			}
		}
		else
		{
			$info['qa_rate_answers'] = '*';
		}
		
		try
		{
			if ( isset( $info['icon'] ) AND ( !is_null( $iconpath ) OR !is_null( $icondata ) ) )
			{
				if ( is_null( $icondata ) AND !is_null( $iconpath ) )
				{
					$icondata = file_get_contents( $iconpath );
				}
				$file = \IPS\File::create( 'forums_Icons', $info['icon'], $filedata );
				$info['icon'] = (string) $file;
			}
			else
			{
				$info['icon'] = NULL;
			}
		}
		catch( \Exception $e )
		{
			$info['icon'] = NULL;
		}
		catch( \ErrorException $e )
		{
			$info['icon'] = NULL;
		}
		
		$id = $info['id'];
		unset( $info['id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_forums', $info );
		$this->software->app->addLink( $inserted_id, $id, 'forums_forums' );
		
		\IPS\Lang::saveCustom( 'forums', "forums_forum_{$inserted_id}", $name );
		\IPS\Lang::saveCustom( 'forums', "forums_forum_{$inserted_id}_desc", $desc );
		
		\IPS\Db::i()->update( 'forums_forums', array( "parent_id" => $inserted_id ), array( "conv_parent=? AND conv_parent<>?", $id, 0 ) );
		
		return $inserted_id;
	}
	
	/**
	 * Get Parent Forum ID for Orphaned Forums
	 *
	 * @return	integer		The ID of the forum created for forums that do not have a parent forum.
	 */
	protected function _getOrphanedForumCategory()
	{
		try
		{
			$id = $this->software->app->getLink( '__orphaned__', 'forums_forums' );
		}
		catch( \OutOfRangeException $e )
		{
			$id = $this->convert_forums_forum( array(
				'id'		=> '__orphaned__',
				'name'		=> "Converted Forums",
				'parent_id'	=> -1,
			) );
		}
		
		return $id;
	}
	
	/**
	 * Convert a topic
	 *
	 * @param	array	$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted topic, or FALSE on failure
	 */
	public function convert_forums_topic( $info=array() )
	{
		if ( !isset( $info['tid'] ) )
		{
			$this->software->app->log( 'topic_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['title'] ) )
		{
			$info['title'] = "Untitled Topic {$info['tid']}";
			$this->software->app->log( 'topic_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING );
		}
		
		if ( isset( $info['forum_id'] ) )
		{
			try
			{
				$info['forum_id'] = $this->software->app->getLink( $info['forum_id'], 'forums_forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'topic_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['tid'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'topic_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['tid'] );
			return FALSE;
		}
		
		if ( !isset( $info['state'] ) OR !in_array( $info['state'], array( 'open', 'closed' ) ) )
		{
			$info['state'] = 'open';
		}
		
		if ( !isset( $info['posts'] ) )
		{
			$info['posts'] = 0;
		}
		
		if ( isset( $info['starter_id'] ) )
		{
			try
			{
				$info['starter_id'] = $this->software->app->getLink( $info['starter_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['starter_id'] = 0;
			}
		}
		else
		{
			$info['starter_id'] = 0;
		}
		
		if ( isset( $info['start_date'] ) )
		{
			if ( $info['start_date'] instanceof \IPS\DateTime )
			{
				$info['start_date'] = $info['start_date']->getTimestamp();
			}
		}
		else
		{
			$info['start_date'] = time();
		}
		
		if ( isset( $info['last_poster_id'] ) )
		{
			try
			{
				$info['last_poster_id'] = $this->software->app->getLink( $info['last_poster_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['last_poster_id'] = $info['starter_id'];
			}
		}
		else
		{
			$info['last_poster_id'] = $info['starter_id'];
		}
		
		if ( isset( $info['last_post'] ) )
		{
			if ( $info['last_post'] instanceof \IPS\DateTime )
			{
				$info['last_post'] = $info['last_post']->getTimestamp();
			}
		}
		else
		{
			$info['last_post'] = $info['start_date'];
		}
		
		if ( !isset( $info['starter_name'] ) )
		{
			$starter = \IPS\Member::load( $info['starter_id'] );
			
			if ( $starter->member_id )
			{
				$info['starter_name'] = $starter->name;
			}
			else
			{
				$info['starter_name'] = NULL;
			}
		}
		
		if ( !isset( $info['last_poster_name'] ) )
		{
			$last = \IPS\Member::load( $info['last_poster_id'] );
			
			if ( $last->member_id )
			{
				$info['last_poster_name'] = $last->name;
			}
			else
			{
				$info['last_poster_name'] = $info['starter_name'];
			}
		}
		
		/* Polls - pass off to the core library. Unlike ratings and follows, we need to do this here as we need to know the Poll ID. */
		if ( isset( $info['poll_state'] ) AND is_array( $info['poll_state'] ) )
		{
			if ( $info['poll_state'] = $this->convert_poll( $info['poll_state']['poll_data'], $info['poll_state']['vote_data'] ) )
			{
				if ( !isset( $info['last_vote'] ) )
				{
					try
					{
						$lastVote = \IPS\Db::i()->select( 'vote_date', 'core_voters', array( "poll=?", $info['poll_state'] ) )->first();
					}
					catch( \UnderflowException $e )
					{
						$lastVote = time();
					}
					
					$info['last_vote'] = $lastVote;
				}
			}
			else
			{
				$info['poll_state']	= NULL;
				$info['last_vote']	= NULL;
			}
		}
		else
		{
			$info['poll_state']	= NULL;
			$info['last_vote']	= NULL;
		}
		
		if ( !isset( $info['views'] ) )
		{
			$info['views'] = 0;
		}
		
		if ( !isset( $info['approved'] ) )
		{
			$info['approved'] = 1;
		}
		
		/* Not Used */
		$info['author_mode'] = 0;
		
		if ( !isset( $info['pinned'] ) )
		{
			$info['pinned'] = 0;
		}
		
		if ( isset( $info['moved_to'] ) )
		{
			if ( !is_array( $info['moved_to'] ) )
			{
				list( $topic, $forum ) = explode( '&', $info['moved_to'] );
			}
			else
			{
				list( $topic, $forum ) = $info['moved_to'];
			}
			
			try
			{
				$topic = $this->software->app->getLink( $topic, 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$topic = NULL;
			}
			
			try
			{
				$forum = $this->software->app->getLink( $forum, 'forums_forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$forum = NULL;
			}
			
			if ( is_null( $topic ) OR is_null( $forum ) )
			{
				$info['moved_to'] = NULL;
			}
			else
			{
				$info['moved_to'] = $topic . '&' . $forum;
			}
		}
		else
		{
			$info['moved_to'] = NULL;
		}
		
		/* Can't know this */
		$info['topic_firstpost'] = 0;
		
		if ( !isset( $info['topic_queuedposts'] ) )
		{
			$info['topic_queuedposts'] = 0;
		}
		
		if ( isset( $info['topic_open_time'] ) )
		{
			if ( $info['topic_open_time'] instanceof \IPS\DateTime )
			{
				$info['topic_open_time'] = $info['topic_open_time']->getTimestamp();
			}
		}
		else
		{
			$info['topic_open_time'] = 0;
		}
		
		if ( isset( $info['topic_close_time'] ) )
		{
			if ( $info['topic_close_time'] instanceof \IPS\DateTime )
			{
				$info['topic_close_time'] = $info['topic_close_time']->getTimestamp();
			}
		}
		else
		{
			$info['topic_close_time'] = 0;
		}
		
		if ( !isset( $info['topic_rating_total'] ) )
		{
			$info['topic_rating_total'] = 0;
		}
		
		if ( !isset( $info['topic_rating_hits'] ) )
		{
			$info['topic_rating_hits'] = 0;
		}
		
		if ( !isset( $info['title_seo'] ) )
		{
			$info['title_seo'] = \IPS\Http\Url::seoTitle( $info['title'] );
		}
		
		if ( isset( $info['moved_on'] ) )
		{
			if ( $info['moved_on'] instanceof \IPS\DateTime )
			{
				$info['moved_on'] = $info['moved_on']->getTimestamp();
			}
		}
		else
		{
			$info['moved_on'] = 0;
		}
		
		if ( !isset( $info['topic_archive_status'] ) )
		{
			$info['topic_archive_status'] = 0;
		}
		
		if ( isset( $info['last_real_post'] ) )
		{
			if ( $info['last_real_post'] instanceof \IPS\DateTime )
			{
				$info['last_real_post'] = $info['last_real_post']->getTimestamp();
			}
		}
		else
		{
			$info['last_real_post'] = $info['last_post'];
		}
		
		/* Can't knew these */
		$info['topic_answered_pid']	= 0;
		$info['popular_time']		= 0;
		
		if ( !isset( $info['featured'] ) )
		{
			$info['featured'] = 0;
		}
		
		if ( !isset( $info['question_rating'] ) )
		{
			$info['question_rating'] = NULL;
		}
		
		if ( !isset( $info['topic_hiddenposts'] ) )
		{
			$info['topic_hiddenposts'] = 0;
		}
		
		$id = $info['tid'];
		unset( $info['tid'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_topics', $info );
		$this->software->app->addLink( $inserted_id, $id, 'forums_topics' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Forum Post
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted post, or FALSE on failure.
	 */
	public function convert_forums_post( $info=array() )
	{
		if ( !isset( $info['pid'] ) )
		{
			$this->software->app->log( 'post_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['topic_id'] ) )
		{
			try
			{
				$info['topic_id'] = $this->software->app->getLink( $info['topic_id'], 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'post_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['pid'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'post_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['pid'] );
			return FALSE;
		}
		
		if ( empty( $info['post'] ) )
		{
			$this->software->app->log( 'post_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['pid'] );
			return FALSE;
		}
		
		if ( !isset( $info['append_edit'] ) )
		{
			$info['append_edit'] = 0;
		}
		
		if ( isset( $info['edit_time'] ) )
		{
			if ( $info['edit_time'] instanceof \IPS\DateTime )
			{
				$info['edit_time'] = $info['edit_time']->getTimestamp();
			}
		}
		else
		{
			$info['edit_time'] = NULL;
		}
		
		if ( isset( $info['author_id'] ) )
		{
			try
			{
				$info['author_id'] = $this->software->app->getLink( $info['author_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['author_id'] = 0;
			}
		}
		else
		{
			$info['author_id'] = 0;
		}
		
		if ( !isset( $info['author_name'] ) )
		{
			$author = \IPS\Member::load( $info['author_id'] );
			
			if ( $author->member_id )
			{
				$info['author_name'] = $author->name;
			}
		}
		
		if ( !isset( $info['ip_address'] ) OR filter_var( $info['ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['ip_address'] = '127.0.0.1';
		}
		
		if ( isset( $info['post_date'] ) )
		{
			if ( $info['post_date'] instanceof \IPS\DateTime )
			{
				$info['post_date'] = $info['post_date']->getTimestamp();
			}
		}
		else
		{
			$info['post_date'] = time();
		}
		
		if ( !isset( $info['queued'] ) )
		{
			$info['queued'] = 0;
		}
		
		if ( !isset( $info['new_topic'] ) )
		{
			$info['new_topic'] = 0;
		}
		
		if ( !isset( $info['edit_name'] ) )
		{
			$info['edit_name'] = NULL;
		}
		
		/* Meh */
		$info['post_key'] = 0;
		
		if ( !isset( $info['post_htmlstate'] ) )
		{
			$info['post_htmlstate'] = 0;
		}
		
		if ( !isset( $info['post_edit_reason'] ) )
		{
			$info['post_edit_reason'] = '';
		}
		
		/* The Bit Options contain where or not this post is marked as best answer. Set a flag accordingly so we can update the topic later. */
		$isBestAnswer	= FALSE;
		$bitoptions		= 0;
		if ( isset( $info['post_bwoptions'] ) )
		{
			foreach( \IPS\forums\Topic\Post::$bitOptions['post_bwoptions']['post_bwoptions'] AS $key => $value )
			{
				if ( isset( $info['post_bwoptions'][$key] ) AND $info['post_bwoptions'][$key] )
				{
					$bitoptions += $value;
					if ( $key === 'best_answer' )
					{
						$isBestAnswer = TRUE;
					}
				}
			}
			$info['post_bwoptions'] = $bitoptions;
		}
		
		if ( isset( $info['pdelete_time'] ) )
		{
			if ( $info['pdelete_time'] instanceof \IPS\DateTime )
			{
				$info['pdelete_time'] = $info['pdelete_time']->getTimestamp();
			}
		}
		else
		{
			$info['pdelete_time'] = 0;
		}
		
		/* Are these even used yet? */
		if ( !isset( $info['post_field_int'] ) )
		{
			$info['post_field_int'] = 0;
		}
		
		if ( !isset( $info['post_field_t1'] ) )
		{
			$info['post_field_t1'] = NULL;
		}
		
		if ( !isset( $info['post_field_t2'] ) )
		{
			$info['post_field_t2'] = NULL;
		}
		
		$id = $info['pid'];
		unset( $info['pid'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_posts', $info );
		$this->software->app->addLink( $inserted_id, $id, 'forums_posts' );
		
		$topicUpdate = array();
		
		if ( $info['new_topic'] == 1 )
		{
			$topicUpdate['topic_firstpost'] = $inserted_id;
		}
		
		if ( $isBestAnswer == TRUE )
		{
			$topicUpdate['topic_answered_pid'] = $inserted_id;
		}
		
		if ( count( $topicUpdate ) )
		{
			\IPS\Db::i()->update( 'forums_topics', $topicUpdate, array( "tid=?", $info['topic_id'] ) );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert an Archived Post
	 *
	 * @param	array			$info	Data to insert
	 * @param	integer|boolean	The ID of the newly inserted Archived Post, or FALSE on failure.
	 */
	public function convert_forums_archived_post( $info=array() )
	{
		/* This works a little differently... we actually need to insert it into the main posts table first, then move it. As such, the $info array should match that of the main posts method EXCEPT for a few keys which we'll extract out. */
		if ( isset( $info['archive_added'] ) )
		{
			$added = $info['archive_added'];
			unset( $info['archive_added'] );
		}
		else
		{
			$added = time();
		}
		
		$return = $this->convert_forums_post( $info );
		
		/* If $return is FALSE, stop here. Something went wrong. */
		if ( $return === FALSE )
		{
			return $return;
		}
		
		/* Re-extract the post. We need the topic too. */
		try
		{
			$post	= \IPS\Db::i()->select( '*', 'forums_posts', array( "pid=?", $return ) )->first();
			$topic	= \IPS\Db::i()->select( '*', 'forums_topics', array( "tid=?", $post['topic_id'] ) )->first();
		}
		catch( \UnderflowException $e )
		{
			/* Um... something really bad happened. */
			$this->software->app->log( 'forums_archive_posts_omgonoes', __METHOD__, \IPS\convert\App::LOG_ERROR, $return );
			throw new \IPS\convert\Exception;
		}
		
		/* Remap everything so it can fit into the archive table */
		$archive = array(
			'archive_id'				=> $post['pid'],
			'archive_author_id'			=> $post['author_id'],
			'archive_author_name'		=> $post['author_name'],
			'archive_ip_address'		=> $post['ip_address'],
			'archive_content_date'		=> $post['post_date'],
			'archive_content'			=> $post['post'],
			'archive_queued'			=> $post['queued'],
			'archive_topic_id'			=> $post['topic_id'],
			'archive_is_first'			=> $post['new_topic'],
			'archive_bwoptions'			=> $post['post_bwoptions'],
			'archive_attach_key'		=> $post['post_key'],
			'archive_html_mode'			=> $post['post_htmlstate'],
			'archive_show_signature'	=> $post['use_sig'],
			'archive_show_emoticons'	=> $post['use_emo'],
			'archive_show_edited_by'	=> $post['append_edit'],
			'archive_edit_time'			=> $post['edit_time'],
			'archive_edit_name'			=> $post['edit_name'],
			'archive_edit_reason'		=> $post['edit_reason'],
			'archive_added'				=> $added,
			'archive_restored'			=> 0,
			'archive_forum_id'			=> $topic['forum_id'],
			'archive_field_int'			=> $post['post_field_int']
		);
		
		\IPS\forums\Topic\ArchivedPost::db()->insert( 'forums_archive_posts', $archive );
		\IPS\Db::i()->delete( 'forums_posts', array( "pid=?", $archive['archive_id'] ) );
		
		return $archive['archive_id'];
	}
	
	/**
	 * Convert a forums topic multimod
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted Multi Mod, or FALSE on failure.
	 */
	public function convert_forums_topic_multimod( $info=array() )
	{
		/* Another instance where we do not necessarily need this, but will store anyway if we have it */
		$hasId = TRUE;
		if ( !isset( $info['mm_id'] ) )
		{
			$hasId = FALSE;
		}
		
		if ( !isset( $info['mm_name'] ) )
		{
			$name = "Untitled Multi-Mod";
			$this->software->app->log( 'forums_topic_mmod_missing_name', __METHOD__, \IPS\convert\App::LOG_NOTICE, ( $hasId ) ? $info['mm_id'] : NULL );
		}
		else
		{
			$name = $info['mm_name'];
			unset( $info['mm_name'] );
		}
		
		if ( !isset( $info['mm_enabled'] ) )
		{
			$info['mm_enabled'] = 1;
		}
		
		if ( !isset( $info['topic_state'] ) OR !in_array( $info['topic_state'], array( 'leave', 'open', 'close' ) ) )
		{
			$info['topic_state'] = 'leave';
		}
		
		if ( !isset( $info['topic_pin'] ) OR !in_array( $info['topic_pin'], array( 'leave', 'pin', 'unpin' ) ) )
		{
			$info['topic_pin'] = 'leave';
		}
		
		if ( isset( $info['topic_move'] ) )
		{
			try
			{
				$info['topic_move'] = $this->software->app->getLink( $info['topic_move'], 'forums_forums' );
				
				if ( !isset( $info['topic_move_link'] ) )
				{
					$info['topic_move_link'] = 0;
				}
			}
			catch( \OutOfRangeException $e )
			{
				$info['topic_move']			= 0;
				$info['topic_move_link']	= 0;
			}
		}
		else
		{
			$info['topic_move']			= 0;
			$info['topic_move_link']	= 0;
		}
		
		if ( !isset( $info['topic_title_st'] ) )
		{
			$info['topic_title_st'] = '';
		}
		
		if ( !isset( $info['topic_title_end'] ) )
		{
			$info['topic_title_end'] = '';
		}
		
		if ( isset( $info['topic_reply'] ) )
		{
			if ( empty( $info['topic_reply_content'] ) )
			{
				$info['topic_reply']			= 0;
				$info['topic_reply_content']	= NULL; # empty account for !isset so make sure it's actually set
			}
			else
			{
				$info['topic_reply'] = 1;
			}
		}
		else
		{
			$info['topic_reply']			= 0;
			$info['topic_reply_content']	= NULL;
		}
		
		if ( !isset( $info['topic_reply_postcontent'] ) )
		{
			$info['topic_reply_postcount'] = 1;
		}
		
		if ( isset( $info['mm_forums'] ) )
		{
			if ( $info['mm_forums'] != '*' )
			{
				if ( !is_array( $info['mm_forums'] ) )
				{
					$info['mm_forums'] = explode( ',', $info['mm_forums'] );
				}
				
				$forums = array();
				if ( count( $info['mm_forums'] ) )
				{
					foreach( $info['mm_forums'] AS $forum )
					{
						try
						{
							$forums[] = $this->software->app->getLink( $forum, 'forums_forums' );
						}
						catch( \OutOfRangeException $e )
						{
							continue;
						}
					}
				}
				
				if ( count( $forums ) )
				{
					$info['mm_forums'] = implode( ',', $forums );
				}
				else
				{
					$info['mm_forums'] = '*';
				}
			}
		}
		else
		{
			$info['mm_forums'] = '*';
		}
		
		if ( !isset( $info['topic_approve'] ) OR !in_array( $info['topic_approve'], array( 0, 1, 2 ) ) )
		{
			$info['topic_approve'] = 0;
		}
		
		if ( $hasId )
		{
			$id = $info['mm_id'];
			unset( $info['mm_id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_topic_mmod', $info );
		\IPS\Lang::saveCustom( 'forums', "forums_mmod_{$inserted_id}", $name );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'forums_topic_multimod' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert an RSS Import Feed
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted import feed, or FALSE on failure.
	 */
	public function convert_forums_rss_import( $info=array() )
	{
		if ( !isset( $info['rss_import_id'] ) )
		{
			$this->software->app->log( 'rss_import_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		/* Required Stuff */
		if ( !isset( $info['rss_import_url'] ) OR filter_var( $info['rss_import_url'], FILTER_VALIDATE_URL ) === FALSE )
		{
			$this->software->app->log( 'rss_import_invalid_url', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_import_id'] );
			return FALSE;
		}
		
		if ( isset( $info['rss_import_forum_id'] ) )
		{
			try
			{
				$info['rss_import_forum_id'] = $this->software->app->getLink( $info['rss_import_forum_id'], 'forums_forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'rss_import_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_import_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'rss_import_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_import_id'] );
			return FALSE;
		}
		
		if ( isset( $info['rss_import_mid'] ) )
		{
			try
			{
				$info['rss_import_mid'] = $this->software->app->getLink( $info['rss_import_mid'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'rss_import_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_import_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'rss_import_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_import_id'] );
			return FALSE;
		}
		
		/* Stuff we can use defaults for */
		if ( !isset( $info['rss_import_title'] ) )
		{
			$info['rss_import_title'] = "Untitled Feed {$info['rss_import_id']}";
		}
		
		if ( !isset( $info['rss_import_pergo'] ) )
		{
			$info['rss_import_pergo'] = 10;
		}
		
		if ( !isset( $info['rss_import_time'] ) )
		{
			$info['rss_import_time'] = 30;
		}
		
		if ( isset( $info['rss_import_last_import'] ) )
		{
			if ( $info['rss_import_last_import'] instanceof \IPS\DateTime )
			{
				$info['rss_import_last_import'] = $info['rss_import_last_import']->getTimestamp();
			}
		}
		else
		{
			$info['rss_import_last_import'] = time();
		}
		
		if ( !isset( $info['rss_import_showlink'] ) )
		{
			$info['rss_import_showlink'] = "View the full article";
		}
		
		if ( !isset( $info['rss_import_topic_open'] ) )
		{
			$info['rss_import_topic_open'] = 1;
		}
		
		if ( !isset( $info['rss_import_topic_hide'] ) )
		{
			$info['rss_import_topic_hide'] = 0;
		}
		
		if ( !isset( $info['rss_import_topic_pre'] ) )
		{
			$info['rss_import_topic_pre'] = '';
		}
		
		if ( !isset( $info['rss_import_allow_html'] ) )
		{
			$info['rss_import_allow_html'] = 0;
		}
		
		if ( isset( $info['rss_import_auth'] ) AND isset( $info['rss_import_auth_user'] ) AND isset( $info['rss_import_auth_pass'] ) )
		{
			if ( $info['rss_import_auth'] == 0 )
			{
				$info['rss_import_auth_user'] = NULL;
				$info['rss_import_auth_pass'] = NULL;
			}
		}
		else
		{
			$info['rss_import_auth']		= 0;
			$info['rss_import_auth_user']	= NULL;
			$info['rss_import_auth_pass']	= NULL;
		}
		
		$id = $info['rss_import_id'];
		unset( $info['rss_import_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_rss_import', $info );
		$this->software->app->addLink( $inserted_id, $id, 'forums_rss_import' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert an RSS Imported Topic... or try too, anyway.
	 *
	 * @param	array		$info	Data to insert
	 * @return	boolean		TRUE on success, or FALSE on failure.
	 */
	public function convert_forums_rss_imported( $info=array() )
	{
		/* Godspeed */
		if ( isset( $info['rss_imported_impid'] ) )
		{
			try
			{
				$info['rss_imported_impid'] = $this->software->app->getLink( $info['rss_imported_impid'], 'forums_rss_import' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'rss_imported_missing_feed', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'rss_imported_missing_feed', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['rss_imported_tid'] ) )
		{
			try
			{
				$info['rss_imported_tid'] = $this->software->app->getLink( $info['rss_imported_tid'], 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'rss_imported_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'rss_imported_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['rss_imported_guid'] ) )
		{
			$this->software->app->log( 'rss_imported_missing_guid', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		\IPS\Db::i()->insert( 'forums_rss_imported', $info );
		return TRUE;
	}
	
	/**
	 * Convert a question rating
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted rating, or FALSE on failure.
	 */
	public function convert_forums_question_rating( $info=array() )
	{
		/* We do not need to store an ID if one isn't present */
		$hasId = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$hasId = FALSE;
		}
		
		/* Everything but the date is required */
		if ( isset( $info['topic'] ) )
		{
			try
			{
				$info['topic'] = $this->software->app->getLink( $info['topic'], 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'question_rating_missing_question', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'question_rating_missing_question', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['forum'] ) )
		{
			try
			{
				$info['forum'] = $this->software->app->getLink( $info['forum'], 'forums_forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'question_rating_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'question_rating_missing_forum', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['member'] ) )
		{
			try
			{
				$info['member'] = $this->software->app->getLink( $info['member'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'question_rating_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'question_rating_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['rating'] ) )
		{
			$this->software->app->log( 'question_rating_missing_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		else
		{
			if ( $info['rating'] > 1 )
			{
				$info['rating'] = 1;
			}
			else if ( $info['rating'] < -1 ) # that always looks so weird
			{
				$info['rating'] = -1;
			}
			else
			{
				$this->software->app->log( 'question_rating_invalid_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		
		if ( isset( $info['date'] ) )
		{
			if ( $info['date'] instanceof \IPS\DateTime )
			{
				$info['date'] = $info['date']->getTimestamp();
			}
		}
		else
		{
			$info['date'] = time();
		}
		
		if ( $hasId )
		{
			$id = $info['id'];
			unset( $info['id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_question_ratings', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'forums_question_ratings' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert an Answer Rating
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted rating, or FALSE on failure.
	 */
	public function convert_forums_answer_rating( $info=array() )
	{
		/* We do not need to store an ID if one is not present */
		$hasId = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$hasId = FALSE;
		}
		
		/* Everything but the date is required */
		if ( isset( $info['post'] ) )
		{
			try
			{
				$info['posts'] = $this->software->app->getLink( $info['post'], 'forums_posts' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'answer_rating_missing_answer', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'answer_rating_missing_answer', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['member'] ) )
		{
			try
			{
				$info['member'] = $this->software->app->getLink( 'answer_rating_missing_member', 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'answer_rating_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'answer_rating_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['rating'] ) )
		{
			$this->software->app->log( 'answer_rating_missing_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		else
		{
			if ( $info['rating'] > 1 )
			{
				$info['rating'] = 1;
			}
			else if ( $info['rating'] < -1 )
			{
				$info['rating'] = -1;
			}
			else
			{
				$this->software->app->log( 'answer_rating_invalid_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		
		if ( isset( $info['date'] ) )
		{
			if ( $info['date'] instanceof \IPS\DateTime )
			{
				$info['date'] = $info['date']->getTimestamp();
			}
		}
		else
		{
			$info['date'] = time();
		}
		
		if ( isset( $info['topic'] ) )
		{
			try
			{
				$info['topic'] = $this->software->app->getLink( $info['topic'], 'forums_topics' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'answer_rating_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'answer_rating_missing_topic', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( $hasId )
		{
			$id = $info['id'];
			unset( $info['id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'forums_answer_ratings', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'forums_answer_ratings' );
		}
		
		return $inserted_id;
	}
	
	public function convert_attachment( $info=array(), $map=array(), $filepath=NULL, $filedata=NULL )
	{
		/* The Core Library handles most of this. We just need to make sure forum specific data is set. */
		$map['id1_type']		= 'forums_topics';
		$map['id1_from_parent']	= FALSE;
		$map['id2_type']		= 'forums_posts';
		$map['id2_from_parent']	= FALSE;
		$map['location_key']	= 'forums_Forums';
		return parent::convert_attachment( $info, $map, $filepath, $filedata );
	}
}