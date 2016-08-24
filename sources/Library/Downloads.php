<?php

/**
 * @brief		Converter Library Downloads Class
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

class _Downloads extends Core
{
	/**
	 * @brief	Application
	 */
	public $app = 'downloads';
	
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
				case 'convert_downloads_cfields':
					$return[$k] = array(
						'step_method'		=> 'convert_downloads_cfields',
						'step_title'		=> 'convert_downloads_cfields',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'downloads_cfields' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'downloads_cfields',
					);
					break;
				
				case 'convert_downloads_categories':
					$dependencies = array();
					
					if ( array_key_exists( 'convert_downloads_cfields', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_downloads_cfields';
					}
					
					$return[$k] = array(
						'step_method'		=> 'convert_downloads_categories',
						'step_title'		=> 'convert_downloads_categories',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'downloads_categories' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'downloads_categories',
					);
					break;
				
				case 'convert_downloads_files':
					$dependencies = array();
					if ( array_key_exists( 'convert_downloads_categories', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_downloads_categories';
					}
					
					if ( array_key_exists( 'convert_downloads_cfields', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_downloads_cfields';
					}
				
					$return[$k] = array(
						'step_method'		=> 'convert_downloads_files',
						'step_title'		=> 'convert_downloads_files',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'downloads_files' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 250,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'downloads_files',
					);
					break;
				
				case 'convert_downloads_comments':
					$return[$k] = array(
						'step_method'		=> 'convert_downloads_comments',
						'step_title'		=> 'convert_downloads_comments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'downloads_comments' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_downloads_files' ),
						'link_type'			=> 'downloads_comments',
					);
					break;
				
				case 'convert_downloads_reviews':
					$return[$k] = array(
						'step_method'		=> 'convert_downloads_reviews',
						'step_title'		=> 'convert_downloads_reviews',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'downloads_reviews' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_downloads_files' ),
						'link_type'			=> 'downloads_reviews',
					);
					break;
				
				case 'convert_attachments':
					$return[$k] = array(
						'step_method'		=> 'convert_attachments',
						'step_title'		=> 'convert_attachments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments_map', array( "location_key=?", 'downloads_Downloads' ) )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_downloads_files', 'convert_downloads_comments', 'convert_downloads_reviews' ),
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
				case 'convert_downloads_categories':
					$return['convert_downloads_categories'] = array( 'downloads_categories' => NULL );
					break;
				
				case 'convert_downloads_cfields':
					if ( $method == $k )
					{
						foreach( \IPS\Db::i()->select( '*', 'downloads_cfields' ) AS $field )
						{
							\IPS\Db::i()->dropColumn( 'downloads_ccontent', "field_{$field['cf_id']}" );
						}
					}
					$return['convert_downloads_cfields'] = array( 'downloads_cfields' => NULL );
					break;
				
				case 'convert_downloads_files':
					$return['convert_downloads_files'] = array( 'downloads_files' => NULL, 'downloads_files_records' => NULL );
					break;
				
				case 'convert_downloads_comments':
					$return['convert_downloads_comments'] = array( 'downloads_comments' => NULL );
					break;
				
				case 'convert_downloads_reviews':
					$return['convert_downloads_reviews'] = array( 'downloads_reviews' => NULL );
					break;
				
				case 'convert_attachments':
					$return['convert_attachments'] = array( 'core_attachments' => \IPS\Db::i()->select( 'attachment_id', 'core_attachments_map', array( "location_key=?", 'downloads_Downloads' ) ), 'core_attachments_map' => array( "location_key=?", 'downloads_Downloads' ) );
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
	 * Convert a Custom Field
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted field, or FALSE on failure.
	 */
	public function convert_downloads_cfield( $info=array() )
	{
		$validFields = array_merge( static::$fieldTypes, \IPS\downloads\Field::$additionalFieldTypes );
		
		if ( !isset( $info['cf_id'] ) )
		{
			$this->software->app->log( 'downloads_cfield_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['cf_type'] ) OR !in_array( $info['cf_type'], $validFields ) )
		{
			$this->software->app->log( 'downloads_cfield_invalid_type', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['cf_id'] );
			return FALSE;
		}
		
		if ( isset( $info['cf_content'] ) )
		{
			if ( is_array( $info['cf_content'] ) )
			{
				$info['cf_content'] = json_encode( $info['cf_content'] );
			}
		}
		else
		{
			$info['cf_content'] = json_encode( array() );
		}
		
		if ( !isset( $info['cf_not_null'] ) )
		{
			$info['cf_not_null'] = 1;
		}
		
		if ( !isset( $info['cf_max_input'] ) )
		{
			$info['cf_max_input'] = 0;
		}
		
		if ( !isset( $info['cf_input_format'] ) )
		{
			$info['cf_input_format'] = '';
		}
		
		if ( !isset( $info['cf_position'] ) )
		{
			$position = \IPS\Db::i()->select( 'MAX(cf_position)', 'downloads_cfields' )->first();
			
			$info['cf_position'] = $position + 1;
		}
		
		if ( !isset( $info['cf_topic'] ) )
		{
			$info['cf_topic'] = 0;
		}
		
		if ( !isset( $info['cf_search_type'] ) )
		{
			$info['cf_search_type'] = 'loose';
		}
		
		if ( !isset( $info['cf_multiple'] ) )
		{
			$info['cf_multiple'] = 0;
		}
		
		if ( !isset( $info['cf_format'] ) )
		{
			$info['cf_format'] = '';
		}
		
		$id = $info['cf_id'];
		unset( $info['cf_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'downloads_cfields', $info );
		$this->software->app->addLink( $inserted_id, $id, 'downloads_cfields' );
		
		/* Now... create our column */
		$columnDefinition = array( 'name' => "field_{$inserted_id}" );
		
		switch( $info['cf_type'] )
		{
				case 'CheckboxSet':
				case 'Member':
					if ( $info['cf_multiple'] )
					{
						$columnDefinition['type']	= 'TEXT';
					}
					else
					{
						$columnDefinition['type']	= 'INT';
						$columnDefinition['length']	= 10;
					}
					break;
					
				case 'Date':
				case 'Poll':
					$columnDefinition['type'] = 'INT';
					$columnDefinition['length'] = 10;
					break;
				
				case 'Editor':
				case 'TextArea':
				case 'Upload':
				case 'Address':
				case 'Codemirror':
				case 'Select':
					$columnDefinition['type'] = 'TEXT';
					break;
				
				case 'Email':
				case 'Password':
				case 'Tel':
				case 'Text':
				case 'Url':
				case 'Color':
				case 'Radio':
				case 'Number':
					$columnDefinition['type'] = 'VARCHAR';
					$columnDefinition['length'] = 255;
					break;
				
				case 'YesNo':
				case 'Checkbox':
				case 'Rating':
					$columnDefinition['type'] = 'TINYINT';
					$columnDefinition['length'] = 1;
					break;
		}
		
		if ( isset( $info['cf_max_input'] ) AND $info['cf_max_input'] )
		{
			$columnDefinition['length'] = $info['cf_max_input'];
		}
		
		\IPS\Db::i()->addColumn( 'downloads_ccontent', $columnDefinition );
		
		if ( $info['cf_type'] != 'Upload' )
		{
			if ( $columnDefinition['type'] == 'TEXT' )
			{
				\IPS\Db::i()->addIndex( 'downloads_ccontent', array( 'type' => 'fulltext', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
			}
			else
			{
				\IPS\Db::i()->addIndex( 'downloads_ccontent', array( 'type' => 'key', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
			}
		}
		
		return $inserted_id;
	}
	
	/**
	 * Format Downloads Custom Field Content
	 *
	 * @param	int		$file_id		The ID of the file to insert data for.
	 * @param	array	$fieldInfo		The Custom Field Information to format. This SHOULD be in $foreign_id => $content format, however field_$foreign_id => $content is also accepted.
	 * @return	array					An array of data formatted for downloads_ccontent
	 */
	protected function _formatCustomFieldContent( $file_id, $fieldInfo )
	{
		$return = array( 'file_id' => $file_id );
		
		if ( count( $fieldInfo ) )
		{
			foreach( $fieldInfo AS $key => $value )
			{
				if ( preg_match( '/^field_(\d+)/i', $key, $matches ) )
				{
					$id = str_replace( 'field_', '', $matches[1] );
				}
				else
				{
					$id = $key;
				}
				
				try
				{
					$link = $this->software->app->getLink( $id, 'downloads_cfields' );
				}
				catch( \OutOfRangeException $e )
				{
					/* Does not exist - skip */
					continue;
				}
				
				$return[ 'field_' . $link ] = $value;
			}
		}
		
		return $return;
	}
	
	/**
	 * Convert a category
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted category, or FALSE on failure.
	 */
	public function convert_downloads_category( $info=array() )
	{
		if ( !isset( $info['cid'] ) )
		{
			$this->software->app->log( 'downloads_category_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['cparent'] ) )
		{
			try
			{
				$info['cparent'] = $this->software->app->getLink( $info['cparent'], 'downloads_categories' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['cconv_parent'] = $info['cparent'];
			}
		}
		else
		{
			$info['cparent'] = 0;
		}
		
		if ( !isset( $info['cname'] ) )
		{
			$name = "Untitled Category {$info['cid']}";
		}
		else
		{
			$name = $info['cname'];
			unset( $info['cname'] );
		}
		
		if ( !isset( $info['cdesc'] ) )
		{
			$desc = '';
		}
		else
		{
			$desc = $info['cdesc'];
			unset( $info['cdesc'] );
		}
		
		if ( !isset( $info['copen'] ) )
		{
			$info['copen'] = 1;
		}
		
		
		if ( !isset( $info['cposition'] ) )
		{
			$position = \IPS\Db::i()->select( 'MAX(cposition)', 'downloads_categories' )->first();
			
			$info['cposition'] = $position + 1;
		}
		
		if ( isset( $info['ccfields'] ) )
		{
			if ( !is_array( $info['ccfields'] ) )
			{
				$info['ccfields'] = explode( ',', $info['ccfields'] );
			}
			
			$newCfields = array();
			if ( count( $info['ccfields'] ) )
			{
				foreach( $info['ccfields'] AS $field )
				{
					try
					{
						$newCfields[] = $this->software->app->getLink( $field, 'downloads_cfields' );
					}
					catch( \OutOfRangeException $e )
					{
						continue;
					}
				}
			}
			
			if ( count( $newCfields ) )
			{
				$info['ccfields'] = implode( ',', $newCfields );
			}
			else
			{
				$info['ccfields'] = NULL;
			}
		}
		else
		{
			$info['ccfields'] = NULL;
		}
		
		$info['cname_furl'] = \IPS\Http\Url::seoTitle( $name );
		
		if ( !isset( $info['ctags_disabled'] ) )
		{
			$info['ctags_disabled'] = 0;
		}
		
		if ( !isset( $info['ctags_noprefixes'] ) )
		{
			$info['ctags_noprefixes'] = 0;
		}
		
		/* Not Used */
		$info['ctags_predefined'] = NULL;
		
		$bitoptions = 0;
		foreach( \IPS\downloads\Category::$bitOptions['bitoptions']['bitoptions'] AS $key => $value )
		{
			if ( isset( $info['cbitoptions'][$key] ) AND $info['cbitoptions'][$key] )
			{
				$bitoptions += $value;
			}
		}
		$info['cbitoptions'] = $bitoptions;
		
		if ( isset( $info['ctypes'] ) )
		{
			if ( is_array( $info['ctypes'] ) )
			{
				$info['ctypes'] = implode( ',', $info['ctypes'] );
			}
		}
		else
		{
			$info['ctypes'] = NULL;
		}
		
		if ( !isset( $info['csortorder'] ) OR !in_array( $info['csortoder'], array( 'updated', 'last_comment', 'title', 'rating', 'date', 'num_comments', 'num_reviews', 'views' ) ) )
		{
			$info['csortorder'] = 'file_updated';
		}
		
		if ( !isset( $info['cmaxfile'] ) )
		{
			$info['cmaxfile'] = NULL;
		}
		
		if ( !isset( $info['cmaxss'] ) )
		{
			$info['cmaxss'] = 0;
		}
		
		if ( isset( $info['cmaxdims'] ) )
		{
			if ( is_array( $info['cmaxdims'] ) )
			{
				$info['cmaxdims'] = implode( 'x', $info['cmaxdims'] );
			}
		}
		else
		{
			$info['cmaxdims'] = '0x0';
		}
		
		if ( !isset( $info['cversioning'] ) )
		{
			$info['cversioning'] = NULL;
		}
		
		if ( !isset( $info['clog'] ) )
		{
			$info['clog'] = 1;
		}
		
		if ( isset( $info['cforum_id'] ) )
		{
			try
			{
				$info['cforum_id'] = $this->software->app->getSiblingLink( $info['cforum_id'], 'forums_forums', 'forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['cforum_id'] = 0;
			}
		}
		else
		{
			$info['cforum_id'] = 0;
		}
		
		if ( !isset( $info['ctopic_prefix'] ) )
		{
			$info['ctopic_prefix'] = '';
		}
		
		if ( !isset( $info['ctopic_suffix'] ) )
		{
			$info['ctopic_suffix'] = '';
		}
		
		/* Can't know this */
		$info['clast_file_id']		= 0;
		$info['clast_file_date']	= 0;
		
		$id = $info['cid'];
		unset( $info['cid'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'downloads_categories', $info );
		$this->software->app->addLink( $inserted_id, $id, 'downloads_categories' );
		
		
		\IPS\Lang::saveCustom( 'downloads', "downloads_category_{$inserted_id}", $name );
		\IPS\Lang::saveCustom( 'downloads', "downloads_category_{$inserted_id}_desc", $desc );
		
		\IPS\Db::i()->update( 'downloads_categories', array( "cparent" => $inserted_id ), array( "cconv_parent=? AND cconv_parent<>?", $id, 0 ) );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a file.
	 *
	 * @param	array			$info			Data to insert
	 * @param	array			$records		Record Data to insert
	 * @param	array			$customFields	Custom Field Data to insert
	 * @return	integer|boolean	The ID of the newly inserted file, or FALSE on failure.
	 */
	public function convert_downloads_file( $info=array(), $records=array(), $customFields=array() )
	{
		if ( !isset( $info['file_id'] ) )
		{
			$this->software->app->log( 'downloads_file_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !count( $records ) )
		{
			$this->software->app->log( 'downloads_file_no_records', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['file_id'] );
			return FALSE;
		}
		
		if ( empty( $info['file_desc'] ) )
		{
			$this->software->app->log( 'downloads_file_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['file_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['file_name'] ) )
		{
			$info['file_name'] = "Untitled File {$info['file_id']}";
			$this->software->app->log( 'downloads_file_no_title', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['file_id'] );
		}
		
		if ( isset( $info['file_cat'] ) )
		{
			try
			{
				$info['file_cat'] = $this->software->app->getLink( $info['file_cat'], 'downloads_categories' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['file_cat'] = $this->_orphanedFilesCategory();
			}
		}
		else
		{
			$info['file_cat'] = $this->_orphanedFilesCategory();
		}
		
		if ( !isset( $info['file_open'] ) )
		{
			$info['file_open'] = 1;
		}
		
		/* No longer used? */
		$info['file_broken']		= 0;
		$info['file_broken_reason']	= NULL;
		$info['file_broken_info']	= NULL;
		$info['file_votes']			= NULL;
		$info['file_new']			= 0;
		$info['file_topicseoname']	= NULL;
		$info['file_post_key']		= NULL;
		
		if ( !isset( $info['file_rating'] ) )
		{
			$info['file_rating']		= 0;
		}
		
		/* Let's get the counts out of the way */
		foreach( array( 'file_views', 'file_downloads', 'file_pendcomments', 'file_comments', 'file_reviews', 'file_unapproved_comments', 'file_hidden_comments', 'file_unapproved_reviews', 'file_hidden_reviews' ) AS $count )
		{
			if ( !isset( $info[$count] ) )
			{
				$info[$count] = 0;
			}
		}
		
		if ( isset( $info['file_submitted'] ) )
		{
			if ( $info['file_submitted'] instanceof \IPS\DateTime )
			{
				$info['file_submitted'] = $info['file_submitted']->getTimestamp();
			}
		}
		else
		{
			$info['file_submitted'] = time();
		}
		
		if ( isset( $info['file_updated'] ) )
		{
			if ( $info['file_updated'] instanceof \IPS\DateTime )
			{
				$info['file_updated'] = $info['file_updated']->getTimestamp();
			}
		}
		else
		{
			$info['file_updated'] = $info['file_submitted'];
		}
		
		/* We recalculate this later anyway */
		$info['file_size'] = 0;
		
		if ( isset( $info['file_submitter'] ) )
		{
			try
			{
				$info['file_submitter'] = $this->software->app->getLink( $info['file_submitter'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['file_submitter'] = 0;
			}
		}
		else
		{
			$info['file_submitter'] = 0;
		}
		
		if ( isset( $info['file_approver'] ) )
		{
			try
			{
				$info['file_approver'] = $this->software->app->getLink( $info['file_approver'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['file_approver'] = 0;
			}
		}
		else
		{
			$info['file_approver'] = 0;
		}
		
		if ( isset( $info['file_approvedon'] ) )
		{
			if ( $info['file_approvedon'] instanceof \IPS\DateTime )
			{
				$info['file_approvedon'] = $info['file_approvedon']->getTimestamp();
			}
		}
		else
		{
			$info['file_approvedon'] = 0;
		}
		
		if ( isset( $info['file_topicid'] ) )
		{
			try
			{
				$info['file_topicid'] = $this->software->app->getSiblingLink( $info['file_topicid'], 'forums_topics', 'forums' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['file_topicid'] = 0;
			}
		}
		else
		{
			$info['file_topicid'] = 0;
		}
		
		if ( !isset( $info['file_ipaddress'] ) OR filter_var( $info['file_ipaddress'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['file_ipaddress'] = '127.0.0.1';
		}
		
		$info['file_name_furl'] = \IPS\Http\url::seoTitle( $info['file_name'] );
		
		if ( \IPS\Application::appIsEnabled( 'nexus' ) )
		{
			// @todo I need the Commerce Libraries for this
		}
		else
		{
			$info['file_cost']			= NULL;
			$info['file_nexus']			= NULL;
			$info['file_renewal_term']	= 0;
			$info['file_renewal_units']	= NULL;
			$info['file_renewal_price']	= NULL;
		}
		
		if ( !isset( $info['file_version'] ) )
		{
			$info['file_version'] = NULL;
		}
		
		if ( !isset( $info['file_changelog'] ) )
		{
			$info['file_changelog'] = NULL;
		}
		
		if ( !isset( $info['file_featured'] ) )
		{
			$info['file_featured'] = 0;
		}
		
		if ( !isset( $info['file_pinned'] ) )
		{
			$info['file_pinned'] = 0;
		}
		
		/* We'll set this later */
		$info['file_primary_screenshot'] = 0;
		
		if ( !isset( $info['file_locked'] ) )
		{
			$info['file_locked'] = 0;
		}
		
		if ( isset( $info['file_last_comment'] ) )
		{
			if ( $info['file_last_comment'] instanceof \IPS\DateTime )
			{
				$info['file_last_comment'] = $info['file_last_comment']->getTimestamp();
			}
		}
		else
		{
			$info['file_last_comment'] = $info['file_submitted'];
		}
		
		if ( isset( $info['file_last_review'] ) )
		{
			if ( $info['file_last_review'] instanceof \IPS\DateTime )
			{
				$info['file_last_review'] = $info['file_last_review']->getTimestamp();
			}
		}
		else
		{
			$info['file_last_review'] = $info['file_submitted'];
		}
		
		$id = $info['file_id'];
		unset( $info['file_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'downloads_files', $info );
		$this->software->app->addLink( $inserted_id, $id, 'downloads_files' );
		
		/* Now our records */
		$totalFileSize = 0;
		$primaryScreenshot = FALSE;
		foreach( $records AS $record )
		{
			/* We don't really need this */
			$hasId = TRUE;
			if ( !isset( $record['record_id'] ) )
			{
				$hasId = FALSE;
			}
			
			$record['record_post_key']		= NULL;
			$record['record_file_id']		= $inserted_id;
			$record['record_link_type']		= NULL;
			$record['record_no_watermark']	= NULL;
			
			if ( !isset( $record['record_type'] ) OR !in_array( $record['record_type'], array( 'upload', 'ssupload', 'link' ) ) )
			{
				$record['record_type'] = 'upload';
			}
			
			if ( !isset( $record['record_backup'] ) )
			{
				$record['record_backup'] = 0;
			}
			
			if ( !isset( $record['record_default'] ) OR $record['record_type'] == 'upload' OR $record['record_type'] == 'link' OR $primaryScreenshot !== FALSE )
			{
				$record['record_default'] = 0;
			}
			
			if ( !isset( $record['record_realname'] ) )
			{
				if ( isset( $record['file_path'] ) AND !is_null( $record['file_path'] ) )
				{
					$fileName = explode( '/', $record['file_path'] );
					$fileName = array_pop( $fileName );
					$record['record_realname'] = $fileName;
				}
			}
			
			try
			{
				if ( !isset( $record['file_data'] ) OR is_null( $record['file_data'] ) )
				{
					$record['file_data'] = file_get_contents( $record['file_path'] );
				}
				
				if ( $record['record_type'] != 'link' )
				{
					unset( $record['file_path'] );
				}
				
				if ( $record['record_type'] == 'link' )
				{
					$record['record_location'] = $record['file_path'];
					unset( $record['file_path'] );
					
					$record['record_size'] = 0;
				}
				else if ( $record['record_type'] == 'upload' )
				{
					$file = \IPS\File::create( 'downloads_Files', $record['record_realname'], $record['file_data'] );
					$record['record_location']	= (string) $file;
					$record['record_size']		= $file->filesize();
					
					if ( !$record['record_backup'] )
					{
						$totalFileSize += $record['record_size'];
					}
				}
				else
				{
					$file = \IPS\File::create( 'downloads_Screenshots', $record['record_realname'], $record['file_data'] );
					$record['record_location']	= (string) $file;
					$record['record_thumb']		= (string) $file->thumbnail( 'downloads_Files' );
					$record['record_size']		= $file->filesize();
				}
			}
			catch( \Exception $e )
			{
				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $record['record_id'] : NULL );
				continue;
			}
			catch( \ErrorException $e )
			{
				$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $record['record_id'] : NULL );
				continue;
			}
			
			unset( $record['file_data'] );
			
			if ( isset( $record['record_time'] ) )
			{
				if ( $record['record_time'] instanceof \IPS\DateTime )
				{
					$record['record_time'] = $record['record_time']->getTimestamp();
				}
			}
			else
			{
				$record['record_time'] = $info['file_submitted'];
			}
			
			if ( $hasId )
			{
				$recordId = $record['record_id'];
				unset( $record['record_id'] );
			}
			
			$recordInsertedId = \IPS\Db::i()->insert( 'downloads_files_records', $record );
			
			if ( $hasId )
			{
				$this->software->app->addLink( $recordInsertedId, $recordId, 'downloads_files_records' );
			}
			
			if ( $record['record_type'] == 'ssupload' AND $record['record_default'] > 0 AND $primaryScreenshot === FALSE )
			{
				$primaryScreenshot = $recordInsertedId;
			}
		}
		
		\IPS\Db::i()->update( 'downloads_files', array( 'file_primary_screenshot' => $primaryScreenshot ), array( "file_id=?", $inserted_id ) );
		\IPS\Db::i()->replace( 'downloads_ccontent', $this->_formatCustomFieldContent( $inserted_id, $customFields ), TRUE );
		
		return $inserted_id;
	}
	
	/**
	 * Get Orphaned Files Category
	 *
	 * @return	integer	The Category ID.
	 */
	protected function _orphanedFilesCategory()
	{
		try
		{
			return $this->software->app->getLink( '__orphaned__', 'downloads_categories' );
		}
		catch( \OutOfRangeException $e )
		{
			return $this->convert_downloads_category( array(
				'cid'		=> '__orphaned__',
				'cname'		=> "Converted Files",
			) );
		}
	}
	
	/**
	 * Convert a comment
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted comment, or FALSE on failure.
	 */
	public function convert_downloads_comment( $info=array() )
	{
		if ( !isset( $info['comment_id'] ) )
		{
			$this->software->app->log( 'downloads_comment_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['comment_fid'] ) )
		{
			try
			{
				$info['comment_fid'] = $this->software->app->getLink( $info['comment_fid'], 'downloads_files' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'downloads_comment_missing_file', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'downloads_comment_missing_file', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
			return FALSE;
		}
		
		if ( empty( $info['comment_text'] ) )
		{
			$this->software->app->log( 'downloads_comment_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
			return FALSE;
		}
		
		if ( isset( $info['comment_mid'] ) )
		{
			try
			{
				$info['comment_mid'] = $this->software->app->getLink( $info['comment_mid'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['comment_mid'] = 0;
			}
		}
		else
		{
			$info['comment_mid'] = 0;
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
		
		if ( !isset( $info['comment_open'] ) )
		{
			$info['comment_open'] = 1;
		}
		
		if ( !isset( $info['comment_append_edit'] ) )
		{
			$info['comment_append_edit'] = 0;
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
			$info['comment_edit_time'] = 0;
		}
		
		if ( !isset( $info['comment_ip_address'] ) OR filter_var( $info['comment_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['comment_ip_address'] = '127.0.0.1';
		}
		
		if ( !isset( $info['comment_author'] ) )
		{
			$author = \IPS\Member::load( $info['comment_mid'] );
			
			if ( $author->member_id )
			{
				$info['comment_author'] = $author->name;
			}
			else
			{
				$info['comment_author'] = "Guest";
			}
		}
		
		$id = $info['comment_id'];
		unset( $info['comment_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'downloads_comments', $info );
		$this->software->app->addLink( $inserted_id, $id, 'downloads_comments' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a review
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted review, or FALSE on failure.
	 */
	public function convert_downloads_review( $info=array() )
	{
		if ( !isset( $info['review_id'] ) )
		{
			$this->software->app->log( 'download_review_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( empty( $info['review_text'] ) )
		{
			$this->software->app->log( 'download_review_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['review_rating'] ) OR $info['review_rating'] < 1 )
		{
			$this->software->app->log( 'download_review_missing_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		if ( isset( $info['review_mid'] ) )
		{
			try
			{
				$info['review_mid'] = $this->software->app->getLink( $info['review_mid'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'download_review_missing_author', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'download_review_missing_author', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		if ( isset( $info['review_fid'] ) )
		{
			try
			{
				$info['review_fid'] = $this->software->app->getLink( $info['review_fid'], 'downloads_files' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'download_review_missing_image', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
				return FALSE;
			}
		}
		
		if ( !isset( $info['review_author_name'] ) )
		{
			$author = \IPS\Member::load( $info['review_mid'] );
			$info['review_author_name'] = $author->name;
		}
		
		if ( isset( $info['review_date'] ) )
		{
			if ( $info['review_date'] instanceof \IPS\DateTime )
			{
				$info['review_date'] = $info['review_date']->getTimestamp();
			}
		}
		else
		{
			$info['review_date'] = time();
		}
		
		if ( !isset( $info['review_ip'] ) OR filter_var( $info['review_ip'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['review_ip'] = '127.0.0.1';
		}
		
		if ( isset( $info['review_edit_time'] ) )
		{
			if ( $info['review_edit_time'] instanceof \IPS\DateTime )
			{
				$info['review_edit_time'] = $info['review_edit_time']->getTimestamp();
			}
		}
		else
		{
			$info['review_edit_time'] = 0;
		}
		
		if ( !isset( $info['review_edit_name'] ) )
		{
			$info['review_edit_name'] = '';
		}
		
		if ( !isset( $info['review_append_edit'] ) )
		{
			$info['review_append_edit'] = 0;
		}
		
		if ( isset( $info['review_votes_data'] ) )
		{
			if ( !is_array( $info['review_votes_data'] ) )
			{
				$info['review_votes_data'] = json_decode( $info['review_votes_data'], TRUE );
			}
			
			$newVoters = array();
			if ( !is_null( $info['review_votes_data'] ) AND count( $info['review_votes_data'] ) )
			{
				foreach( $info['review_votes_data'] AS $member => $vote )
				{
					try
					{
						$memberId = $this->software->app->getLink( $member, 'core_members', TRUE );
					}
					catch( \OutOfRangeException $e )
					{
						continue;
					}
					
					$newVoters[$memberId] = $vote;
				}
			}
			
			if ( count( $newVoters ) )
			{
				$info['review_votes_data'] = json_encode( $newVoters );
			}
			else
			{
				$info['review_votes_data'] = NULL;
			}
		}
		else
		{
			$info['review_votes_data'] = NULL;
		}
		
		if ( !isset( $info['review_votes'] ) )
		{
			if ( is_null( $info['review_votes_data'] ) )
			{
				$info['review_votes'] = 0;
			}
			else
			{
				$info['review_votes'] = count( json_decode( $info['review_votes_data'], TRUE ) );
			}
		}
		
		if ( !isset( $info['review_votes_helpful'] ) )
		{
			if ( is_null( $info['review_votes_data'] ) )
			{
				$info['review_votes_helpful'] = 0;
			}
			else
			{
				$helpful = 0;
				foreach( json_decode( $info['review_votes_data'], TRUE ) AS $member => $vote )
				{
					if ( $vote == 1 )
					{
						$helpful += 1;
					}
				}
				
				$info['review_votes_helpful'] = $helpful;
			}
		}
		
		if ( !isset( $info['review_approved'] ) )
		{
			$info['review_approved'] = 1;
		}
		
		if ( !isset( $info['review_version'] ) )
		{
			$info['review_version'] = NULL;
		}
		
		$id = $info['review_id'];
		unset( $info['review_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'downloads_reviews', $info );
		$this->software->app->addLink( $inserted_id, $id, 'downloads_reviews' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert an attachment
	 *
	 * @param	array			$info		Data to insert
	 * @param	array			$map		Map Data to insert
	 * @param	string|NULL		$filepath	Path to the file, or NULL.
	 * @param	string|NULL		$filedata	Binary data for the file, or NULL.
	 * @return	integer|boolean	The ID of the newly inserted attachment, or FALSE on failure.
	 */
	public function convert_attachment( $info=array(), $map=array(), $filepath=NULL, $filedata=NULL )
	{
		$map['location_key']	= 'downloads_Downloads';
		$map['id1_type']		= 'downloads_files';
		$map['id1_from_parent']	= FALSE;
		$map['id2_from_parent']	= FALSE;
		/* Some set up */
		if ( !isset( $info['id3'] ) )
		{
			$info['id3'] = NULL;
		}
		
		if ( is_null( $info['id3'] ) OR $info['id3'] != 'review' )
		{
			$map['id2_type'] = 'downloads_comments';
		}
		else
		{
			$map['id2_type'] = 'downloads_reviews';
		}
		
		return parent::convert_attachment( $info, $map, $filepath, $filedata );
	}
}