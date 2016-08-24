<?php

/**
 * @brief		Converter Library Blog Class
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
 * @note	We must extend the Core Library here so we can access methods like convert_attachment, convert_follow, etc
 */
class _Blog extends Core
{
	/**
	 * @brief	Application
	 */
	public $app = 'blog';
	
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
	 * @todo
	 */
	public function menuRows()
	{
		$return		= array();
		$classname	= get_class( $this->software );
		foreach( $classname::canConvert() AS $k => $v )
		{
			switch( $k )
			{
				case 'convert_blogs':
					$return[$k] = array(
						'step_title'		=> 'convert_blogs',
						'step_method'		=> 'convert_blogs',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'blog_blogs' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1000,
						'dependencies'		=> array(),
						'link_type'			=> 'blog_blogs',
					);
				break;
				
				case 'convert_blog_entries':
					$return[$k] = array(
						'step_title'		=> 'convert_blog_entries',
						'step_method'		=> 'convert_blog_entries',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'blog_entries' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_blogs' ),
						'link_type'			=> 'blog_entries',
					);
				break;
				
				case 'convert_blog_comments':
					$return[$k] = array(
						'step_title'		=> 'convert_blog_comments',
						'step_method'		=> 'convert_blog_comments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'blog_comments' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_blog_entries' ),
						'link_type'			=> 'bog_comments',
					);
				break;
				
				case 'convert_blog_rss_import':
					$return[$k] = array(
						'step_title'		=> 'convert_blog_rss_import',
						'step_method'		=> 'convert_blog_rss_import',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'blog_rss_import' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_blogs' ),
						'link_type'			=> 'blog_rss_import',
					);
				break;
				
				case 'convert_attachments':
					$return[$k] = array(
						'step_title'		=> 'convert_blog_attachments',
						'step_method'		=> 'convert_attachments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments_map', array( 'location_key=? AND id1<>? AND id2 IS NULL', 'blog_Entries', 0 ) )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 250,
						'dependencies'		=> array( 'convert_blog_entries', 'convert_blog_comments' ),
						'link_type'			=> 'core_attachments',
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
				/* Should return multiple array members for each table that needs to be truncated. The key should be the table, while the value should be a WHERE clause, or NULL to completely empty the table */
				case 'convert_blogs':
					$return['convert_blogs']							= array( 'blog_blogs' => NULL );
				break;
				
				case 'convert_blog_entries':
					$return['convert_blog_entries']						= array( 'blog_entries' => NULL );
				break;
				
				case 'convert_blog_comments':
					$return['convert_blog_comments']					= array( 'blog_comments' => NULL );
				break;
				
				case 'convert_blog_rss_import':
					$return['convert_blog_rss_import']					= array( 'blog_rss_import' => NULL, 'blog_rss_imported' => NULL );
				break;
				
				case 'convert_attachments':
					$return['convert_attachments']			= array(
						'core_attachments'		=> \IPS\Db::i()->in( \IPS\Db::i()->select( 'attachment_id', 'core_attachments_map', array( "location_key=?", 'blog_Entries' ) ) ),
						'core_attachments_map'	=> array( "location_key=?", 'blog_Entries' )
					);
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
	 * Convert a Blog
	 *
	 * @param	array				$info			Array of blog information
	 * @param	array|NULL			$socialgroup	Array of data to be stored in core_sys_social_groups and core_sys_social_group_members
	 * @param	string|NULL|boolean	$filepath		URL/Path to cover photo, NULL to use raw file data, or FALSE to not convert
	 * @param	string|NULL			$filedata		If $filepath is NULL, this should contain the raw contents of the cover photo
	 * @return	integer|boolean		The ID of the inserted blog, or FALSE on failure.
	 */
	public function convert_blog( array $info, $socialgroup=NULL, $filepath=FALSE, $filedata=NULL )
	{
		if ( !isset( $info['blog_id'] ) )
		{
			$this->software->app->log( 'blog_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['blog_member_id'] ) AND $info['blog_member_id'] )
		{
			try
			{
				$info['blog_member_id'] = $this->software->app->getLink( $info['blog_member_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['blog_member_id'] = 0;
			}
		}
		else
		{
			$info['blog_member_id'] = 0;
		}
		
		if ( !isset( $info['blog_num_views'] ) )
		{
			$info['blog_num_views'] = 0;
		}
		
		if ( !isset( $info['blog_pinned'] ) )
		{
			$info['blog_pinned'] = 0;
		}
		
		if ( !isset( $info['blog_disabled'] ) )
		{
			$info['blog_disabled'] = 0;
		}
		
		if ( !isset( $info['blog_allowguests'] ) )
		{
			$info['blog_allowguests'] = 1;
		}
		
		if ( !isset( $info['blog_rating_total'] ) )
		{
			$info['blog_rating_total'] = 0;
		}
		
		if ( !isset( $info['blog_rating_count'] ) )
		{
			$info['blog_rating_count'] = 0;
		}
		
		if ( isset( $info['blog_settings'] ) )
		{
			if ( !is_array( $info['blog_settings'] ) )
			{
				$info['blog_settings'] = json_decode( $info['blog_settings'], TRUE );
			}
			
			if ( !is_null( $info['blog_settings'] ) )
			{
				$newSettings = array();
				
				/* This looks funny, but allow for easy further expansion later */
				foreach( array( 'allowrss' ) AS $key )
				{
					switch( $key )
					{
						case 'allowrss':
							if ( !isset( $info['blog_settings'][$key] ) )
							{
								$newSettings[$key] = true;
							}
							else
							{
								$newSettings[$key] = (bool) $info['blog_settings'][$key];
							}
						break;
					}
				}
				
				$info['blog_settings'] = json_encode( $newSettings );
			}
			else
			{
				$info['blog_settings'] = json_encode( array( 'allowrss' => TRUE ) );
			}
		}
		else
		{
			$info['blog_settings'] = json_encode( array( 'allowrss' => TRUE ) );
		}
		
		/* No Longer Used */
		$info['blog_last_visitors']		= NULL;
		$info['blog_editors']			= NULL;
		
		/* Name and Description */
		if ( !isset( $info['blog_name'] ) )
		{
			$owner = \IPS\Member::load( $info['blog_member_id'] );
			
			if ( $owner->member_id )
			{
				$name = "{$member->name}'s Blog";
			}
			else
			{
				$name = "Untitled Blog {$info['blog_id']}";
			}
		}
		else
		{
			$name = $info['blog_name'];
			unset( $info['blog_name'] );
		}
		
		$info['blog_seo_name'] = \IPS\Http\Url::seoTitle( $name );
		
		if ( isset( $info['blog_description'] ) )
		{
			$desc = $info['blog_description'];
			unset( $info['blog_description'] );
		}
		else
		{
			$desc = '';
		}
		
		if ( isset( $info['blog_groupblog_ids'] ) )
		{
			if ( !is_array( $info['blog_groupblog_ids'] ) )
			{
				$info['blog_groupblog_ids'] = explode( ',', $info['blog_groupblog_ids'] );
			}
			
			$newGroups = array();
			if ( count( $info['blog_groupblog_ids'] ) )
			{
				foreach( $info['blog_groupblog_ids'] AS $group )
				{
					try
					{
						$newGroups[] = $this->software->app->getLink( $group, 'core_groups', TRUE );
					}
					catch( \OutOfRangeException $e )
					{
						continue;
					}
				}
			}
			
			if ( count( $newGroups ) )
			{
				$info['blog_groupblog_ids'] = implode( ',', $newGroups );
			}
			else
			{
				$info['blog_groupblog_ids'] = '';
			}
		}
		else
		{
			$info['blog_groupblog_ids'] = '';
		}
		
		if ( isset( $info['blog_last_edate'] ) )
		{
			if ( $info['blog_last_edate'] instanceof \IPS\DateTime )
			{
				$info['blog_last_edate'] = $info['blog_last_edate']->getTimestamp();
			}
		}
		else
		{
			$info['blog_last_edate'] = 0;
		}
		
		/* Counts */
		foreach( array( 'blog_count_entries', 'blog_count_comments', 'blog_count_entries_hidden', 'blog_count_comments_hidden', 'blog_rating_average', 'blog_count_entries_future' ) AS $column )
		{
			if ( !isset( $info[$column] ) )
			{
				$info[$column] = 0;
			}
		}
		
		/* Well update this later if we need too */
		$info['blog_social_group'] = NULL;
		
		if ( !is_null( $socialgroup ) AND is_array( $socialgroup ) )
		{
			$socialGroupId = \IPS\Db::i()->insert( 'core_sys_social_groups', array( 'owner_id' => $info['blog_member_id'] ) );
			$members	= array();
			$members[]	= array( 'group_id' => $socialGroupId, 'member_id' => $info['blog_member_id'] );
			foreach( $socialgroup['members'] AS $member )
			{
				try
				{
					$members[] = array( 'group_id' => $socialGroupId, 'member_id' => $this->software->app->getLink( $member, 'core_members', TRUE ) );
				}
				catch( \OutOfRangeExceptin $e )
				{
					continue;
				}
			}
			\IPS\Db::i()->insert( 'core_sys_social_group_members', $members );
			
			$info['blog_social_group'] = $socialGroupId;
		}
		
		/* And now cover photo */
		if ( isset( $info['blog_cover_photo'] ) AND ( !is_null( $filedata ) OR !is_null( $filepath ) ) )
		{
			try
			{
				if ( is_null( $filedata ) AND !is_null( $filepath ) )
				{
					$filedata = file_get_contents( $filepath );
				}
				
				$file = \IPS\File::create( 'blog_Blogs', $info['blog_cover_photo'], $filedata );
				$info['blog_cover_photo']			= (string) $file;
				$info['blog_cover_photo_offset']	= 0;
			}
			catch( \Exception $e )
			{
				$info['blog_cover_photo']			= NULL;
				$info['blog_cover_photo_offset']	= NULL;
			}
			catch( \ErrorException $e )
			{
				$info['blog_cover_photo']			= NULL;
				$info['blog_cover_photo_offset']	= NULL;
			}
		}
		else
		{
			$info['blog_cover_photo']			= NULL;
			$info['blog_cover_photo_offset']	= NULL;
		}
		
		$id = $info['blog_id'];
		unset( $info['blog_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'blog_blogs', $info );
		$this->software->app->addLink( $inserted_id, $id, 'blog_blogs' );
		
		\IPS\Lang::saveCustom( 'blog', "blogs_blog_{$inserted_id}", $name );
		\IPS\Lang::saveCustom( 'blog', "blogs_blog_{$inserted_id}_desc", $desc );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a blog entry
	 *
	 * @param	array				$info		Array of entry information
	 * @param	string|NULL			$filepath	Path to the Cover Photo, or NULL
	 * @param	string|NULL			$filedata	Binary data for cover photo, or NULL
	 * @return	integer|boolean		The ID of the inserted entry, or FALSE on failure
	 */
	public function convert_blog_entry( array $info, $filepath=NULL, $filedata=NULL )
	{
		if ( !isset( $info['entry_id'] ) )
		{
			$this->software->app->log( 'blog_entry_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['entry_blog_id'] ) )
		{
			try
			{
				$info['entry_blog_id'] = $this->software->app->getLink( $info['entry_blog_id'], 'blog_blogs' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'blog_entry_missing_blog', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['entry_id'] );
				return FALSE;
			}
		}
		
		if ( !isset( $info['entry_name'] ) )
		{
			$info['entry_name'] = "Untitled Blog Entry {$info['entry_id']}";
			$this->software->app->log( 'blog_entry_missing_name', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['entry_id'] );
		}
		
		$info['entry_name_seo'] = \IPS\Http\Url::seoTitle( $info['entry_name'] );
		
		if ( empty( $info['entry_content'] ) )
		{
			$this->software->app->log( 'blog_entry_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['entry_content'] );
			return FALSE;
		}
		
		if ( isset( $info['entry_author_id'] ) )
		{
			try
			{
				$info['entry_author_id'] = $this->software->app->getLink( $info['entry_author_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['entry_author_id'] = 0;
			}
		}
		else
		{
			$info['entry_author_id'] = 0;
		}
		
		if ( !isset( $info['entry_author_name'] ) )
		{
			$author = \IPS\Member::load( $info['entry_author_id'] );
			
			if ( $author->member_id )
			{
				$info['entry_author_name'] = $author->name;
			}
			else
			{
				$info['entry_author_name'] = "Guest";
			}
		}
		
		if ( isset( $info['entry_date'] ) )
		{
			if ( $info['entry_date'] instanceof \IPS\DateTime )
			{
				$info['entry_date'] = $info['entry_date']->getTimestamp();
			}
		}
		else
		{
			$info['entry_date'] = time();
		}
		
		if ( !isset( $info['entry_status'] ) OR !in_array( $info['entry_status'], array( 'published', 'draft' ) ) )
		{
			$info['entry_status'] = 'published';
		}
		
		/* Zero Defaults */
		foreach( array( 'entry_locked', 'entry_num_comments', 'entry_queued_comments', 'entry_featured', 'entry_views', 'entry_pinned', 'entry_hidden_comments', 'entry_is_future_entry' ) AS $zeroDefault )
		{
			if ( !isset( $info[$zeroDefault] ) )
			{
				$info[$zeroDefault] = 0;
			}
		}
		
		if ( isset( $info['entry_last_comment_mid'] ) )
		{
			try
			{
				$info['entry_last_comment_mid'] = $this->software->app->getLink( $info['entry_last_comment_mid'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['entry_last_comment_mid'] = 0;
			}
		}
		else
		{
			$info['entry_last_comment_mid'] = 0;
		}
		
		if ( !isset( $info['entry_hidden'] ) )
		{
			$info['entry_hidden'] = 1; # backwards
		}
		
		$info['entry_post_key'] = NULL;
		
		if ( isset( $info['entry_edit_time'] ) )
		{
			if ( $info['entry_edit_time'] instanceof \IPS\DateTime )
			{
				$info['entry_edit_time'] = $info['entry_edit_time']->getTimestamp();
			}
		}
		else
		{
			$info['entry_edit_time'] = NULL;
		}
		
		if ( !isset( $info['entry_edit_name'] ) )
		{
			$info['entry_edit_name'] = NULL;
		}
		
		if ( isset( $info['entry_last_update'] ) )
		{
			if ( $info['entry_last_update'] instanceof \IPS\DateTime )
			{
				$info['entry_last_update'] = $info['entry_last_update']->getTimestamp();
			}
		}
		else
		{
			$info['entry_last_update'] = $info['entry_date'];
		}
		
		if ( isset( $info['entry_gallery_album'] ) )
		{
			try
			{
				$info['entry_gallery_album'] = $this->software->app->getSiblingLink( $info['entry_gallery_album'], 'gallery_albums', 'gallery' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['entry_gallery_album'] = NULL;
			}
		}
		else
		{
			$info['entry_gallery_album'] = NULL;
		}
		
		if ( isset( $info['entry_poll_state'] ) AND is_array( $info['entry_poll_state'] ) )
		{
			if ( $poll = $this->convert_poll( $info['entry_poll_state']['poll_data'], $info['entry_poll_state']['vote_data'] ) )
			{
				$info['entry_poll_state'] = $poll;
			}
			else
			{
				$info['entry_poll_state']	= NULL;
			}
		}
		else
		{
			$info['entry_poll_state']	= NULL;
		}
		
		if ( isset( $info['entry_publish_date'] ) )
		{
			if ( $info['entry_publish_date'] instanceof \IPS\DateTime )
			{
				$info['entry_publish_date'] = $info['entry_publish_date']->getTimestamp();
			}
		}
		else
		{
			$info['entry_publish_date'] = 0;
		}
		
		$info['entry_image'] = '';
		
		if ( !isset( $info['entry_ip_address'] ) OR filter_var( $info['entry_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['entry_ip_address'] = '127.0.0.1';
		}
		
		if ( isset( $info['entry_cover_photo'] ) AND ( !is_null( $filepath ) OR !is_null( $filedata ) ) )
		{
			try
			{
				if ( is_null( $filedata ) AND !is_null( $filepath ) )
				{
					$filedata = file_get_contents( $filepath );
				}
				
				$file = \IPS\File::create( 'blog_Entries', $info['entry_cover_photo'], $filedata );
				$info['entry_cover_photo']			= (string) $file;
				$info['entry_cover_photo_offset']	= 0;
			}
			catch( \Exception $e )
			{
				$info['entry_cover_photo']			= NULL;
				$info['entry_cover_photo_offset']	= NULL;
			}
		}
		else
		{
			$info['entry_cover_photo']			= NULL;
			$info['entry_cover_photo_offset']	= NULL;
		}
		
		$id = $info['entry_id'];
		unset( $info['entry_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'blog_entries', $info );
		$this->software->app->addLink( $inserted_id, $id, 'blog_entries' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a blog comment
	 *
	 * @param	array				$info		Array of comment information
	 * @return	integer|boolean		The ID of the inserted comment, or FALSE on failure.
	 */
	public function convert_blog_comment( array $info )
	{
		if ( !isset( $info['comment_id'] ) )
		{
			$this->software->app->log( 'blog_comment_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['comment_entry_id'] ) )
		{
			try
			{
				$info['comment_entry_id'] = $this->software->app->getLink( $info['comment_entry_id'], 'blog_entries' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'blog_comment_missing_entry', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'blog_comment_missing_entry', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
			return FALSE;
		}
		
		if ( empty( $info['comment_text'] ) )
		{
			$this->software->app->log( 'blog_comment_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
			return FALSE;
		}
		
		if ( isset( $info['comment_member_id'] ) )
		{
			try
			{
				$info['comment_member_id'] = $this->software->app->getLink( $info['comment_member_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['comment_member_id'] = 0;
			}
		}
		else
		{
			$info['comment_member_id'] = 0;
		}
		
		if ( !isset( $info['comment_member_name'] ) )
		{
			$author = \IPS\Member::load( $info['comment_member_id'] );
			
			if ( $author->member_id )
			{
				$info['comment_member_name'] = $author->name;
			}
			else
			{
				$info['comment_member_name'] = "Guest";
			}
		}
		
		if ( !isset( $info['comment_ip_address'] ) OR filter_var( $info['comment_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['comment_ip_address'] = '127.0.0.1';
		}
		
		if ( isset( $info['comment_date'] ) )
		{
			if ( $info['comment_date'] instanceof \IPS\DateTime )
			{
				$info['comment_date'] = $info['comment_date']->getTimestamp();
			}
		}
		else
		{
			$info['comment_date'] = time();
		}
		
		if ( isset( $info['comment_edit_time'] ) )
		{
			if ( $info['comment_edit_time'] instanceof \IPS\DateTime )
			{
				$info['comment_edit_time'] = $info['comment_edit_time']->getTimestamp();
			}
		}
		else
		{
			$info['comment_edit_time'] = NULL;
		}
		
		if ( !isset( $info['comment_approved'] ) )
		{
			$info['comment_approved'] = 1;
		}
		
		if ( !isset( $info['comment_edit_member_name'] ) )
		{
			$info['comment_edit_member_name'] = NULL;
		}
		
		if ( !isset( $info['comment_edit_show'] ) )
		{
			$info['comment_edit_show'] = 0;
		}
		
		$id = $info['comment_id'];
		unset( $info['comment_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'blog_comments', $info );
		$this->software->app->addLink( $inserted_id, $id, 'blog_comments' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a blog RSS import
	 *
	 * @param	array				$info		Array of RSS Import information
	 * @return	integer|boolean		The ID of the inserted RSS import, or FALSE on failure
	 */
	public function convert_blog_rss_import( array $info )
	{
		if ( !isset( $info['rss_id'] ) )
		{
			$this->software->app->log( 'blog_rss_import_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['rss_blog_id'] ) )
		{
			try
			{
				$info['rss_blog_id'] = $this->software->app->getLink( $info['rss_blog_id'], 'blog_blogs' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'blog_rss_import_missing_blog', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'blog_rss_import_missing_blog', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['rss_url'] ) OR filter_var( $info['rss_url'], FILTER_VALIDATE_URL ) === FALSE )
		{
			$this->software->app->log( 'blog_rss_import_missing_url', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['rss_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['rss_auth_user'] ) OR !isset( $info['rss_auth_pass'] ) )
		{
			$info['rss_auth_user'] = '';
			$info['rss_auth_pass'] = '';
		}
		
		if ( isset( $info['rss_last_import'] ) )
		{
			if ( $info['rss_last_import'] instanceof \IPS\DateTime )
			{
				$info['rss_last_import'] = $info['rss_last_import']->getTimestamp();
			}
		}
		else
		{
			$info['rss_last_import'] = time();
		}
		
		if ( isset( $info['rss_tags'] ) )
		{
			if ( is_array( $info['rss_tags'] ) )
			{
				$info['rss_tags'] = json_encode( $info['rss_tags'] );
			}
		}
		else
		{
			$info['rss_tags'] = NULL;
		}
		
		if ( isset( $info['rss_member'] ) )
		{
			try
			{
				$info['rss_member'] = $this->software->app->getLink( $info['rss_member'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['rss_member'] = NULL;
			}
		}
		else
		{
			$info['rss_member'] = NULL;
		}
		
		if ( is_null( $info['rss_member'] ) )
		{
			$blogOwner = \IPS\Db::i()->select( 'blog_member_id', 'blog_blogs', array( "blog_id=?", $info['rss_blog_id'] ) )->first();
			$info['rss_member'] = $blogOwner;
		}
		
		if ( !isset( $info['rss_import_show_link'] ) )
		{
			$info['rss_import_show_link'] = "View the full article";
		}
		
		$id = $info['rss_id'];
		unset( $info['rss_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'blog_rss_import', $info );
		$this->software->app->addLinK( $inserted_id, $id, 'blog_rss_import' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a blog entry attachment
	 *
	 * @param	array				$info		Array of entry attachment information
	 * @param	string|NULL			$filepath	URL/Path to attachment or NULL to use raw file data
	 * @param	string|NULL			$filedata	If $filepath is NULL, this should contain the raw contents of the attachment
	 * @return	integer|boolean		The ID of the inserted attachment, or FALSE on failure.
	 */
	public function convert_attachment( $info=array(), $map=array(), $filepath = NULL, $filedata = NULL ) 
	{
		$map['id1_type']		= 'blog_entries';
		$map['id1_from_parent']	= FALSE;
		if ( !isset( $info['id2'] ) OR is_null( $info['id2'] ) )
		{
			$map['location_key'] = 'blog_Entries';
		}
		else
		{
			$map['location_key']	= 'blog_Blogs';
			$map['id2_type']		= 'blog_comments';
			$map['id2_from_parent']	= FALSE;
		}
		
		return parent::convert_attachment( $info, $map, $filepath, $filedata );
	}
}