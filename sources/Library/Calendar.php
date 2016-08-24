<?php

/**
 * @brief		Converter Library Calendar Class
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

class _Calendar extends Core
{
	/**
	 * @brief	Application
	 */
	public $app = 'calendar';
	
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
				case 'convert_calendar_calendars':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_calendars',
						'step_method'		=> 'convert_calendar_calendars',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_calendars' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'calendar_calendars',
					);
					break;
				
				case 'convert_calendar_events':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_events',
						'step_method'		=> 'convert_calendar_events',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_events' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_calendar_calendars' ),
						'link_type'			=> 'calendar_events',
					);
					break;
				
				case 'convert_calendar_comments':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_comments',
						'step_method'		=> 'convert_calendar_comments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_event_comments' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_calendar_events' ),
						'link_type'			=> 'calendar_event_comments',
					);
					break;
				
				case 'convert_calendar_reviews':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_reviews',
						'step_method'		=> 'convert_calendar_reviews',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_event_reviews' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_calendar_events' ),
						'link_type'			=> 'calendar_event_reviews',
					);
					break;
				
				case 'convert_calendar_rsvps':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_rsvps',
						'step_method'		=> 'convert_calendar_rsvps',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_event_rsvp' )->first(),
						'source_rows'		=> $this->sofrware->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_calendar_events' ),
						'link_type'			=> 'calendar_event_rsvp',
					);
					break;
				
				case 'convert_calendar_feeds':
					$return[$k] = array(
						'step_title'		=> 'convert_calendar_feeds',
						'step_method'		=> 'convert_calendar_feeds',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'calendar_import_feeds' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_calendar_calendars' ),
						'link_type'			=> 'calendar_import_feeds',
					);
					break;
				
				case 'convert_attachments':
					$return[$k] = array(
						'step_title'		=> 'convert_attachments',
						'step_method'		=> 'convert_attachments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments', array( "location_key=?", 'calendar_Events' ) )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_calendar_events', 'convert_calendar_comments', 'convert_calendar_reviews' ),
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
				case 'convert_calendar_calendars':
					$return['convert_calendar_calendars'] = array( 'calendar_calendars' => NULL );
					break;
				
				case 'convert_calendar_events':
					$return['convert_calendar_events'] = array( 'calendar_events' => NULL );
					break;
				
				case 'convert_calendar_comments':
					$return['convert_calendar_comments'] = array( 'calendar_event_comments' => NULL );
					break;
				
				case 'convert_calendar_reviews':
					$return['convert_calendar_reviews'] = array( 'calendar_event_reviews' => NULL );
					break;
				
				case 'convert_calendar_rsvps':
					$return['convert_calendar_rsvps'] = array( 'calendar_event_rsvp' => NULL );
					break;
				
				case 'convert_calendar_feeds':
					$return['convert_calendar_feeds'] = array( 'calender_import_feeds' => NULL );
					break;
				
				case 'convert_attachments':
					$return['convert_attachments'] = array( 'core_attachments' => array( "location_key=?", 'calendar_Events' ) );
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
	 * Convert a Calendar
	 *
	 * @param	array			$info	Data to insert
	 * @return	integer|boolean	The ID of the newly inserted calendar, or FALSE on failure.
	 */
	public function convert_calendar( $info=array() )
	{
		if ( !isset( $info['cal_id'] ) )
		{
			$this->software->app->log( 'calendar_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['cal_title'] ) )
		{
			$name = "Calendar {$info['cal_id']}";
			$this->software->app->log( 'calendar_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['cal_id'] );
		}
		else
		{
			$name = $info['cal_title'];
			unset( $info['cal_title'] );
		}
		
		if ( !isset( $info['cal_description'] ) )
		{
			$desc = '';
		}
		else
		{
			$desc = $info['cal_description'];
			unset( $info['cal_description'] );
		}
		
		$info['cal_title_seo'] = \IPS\Http\Url::seoTitle( $name );
		
		/* Zero Defaults */
		foreach( array( 'cal_moderate', 'cal_comment_moderate', 'cal_allow_reviews', 'cal_review_moderate' ) AS $zeroDefault )
		{
			if ( !isset( $info[$zeroDefault] ) )
			{
				$info[$zeroDefault] = 0;
			}
		}
		
		if ( !isset( $info['cal_allow_comments'] ) )
		{
			$info['cal_allow_comments'] = 1;
		}
		
		if ( !isset( $info['cal_position'] ) )
		{
			$position = \IPS\Db::i()->select( 'MAX(position)', 'calendar_calendars' )->first();
			$info['cal_position'] = $position + 1;
		}
		
		if ( !isset( $info['cal_color'] ) )
		{
			$genericCalendar = new \IPS\calendar\Calendar;
			$info['cal_color'] = $genericCalendar->_generateColor();
		}
		
		$id = $info['cal_id'];
		unset( $info['cal_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_calendars', $info );
		$this->software->app->addLink( $inserted_id, $id, 'calendar_calendars' );
		
		\IPS\Lang::saveCustom( 'calendar', "calendar_calendar_{$inserted_id}", $name );
		\IPS\Lang::saveCustom( 'calendar', "calendar_calendar_{$inserted_id}_desc", $desc );
		
		return $inserted_id;
	}
	
	/**
	 * Convert an event
	 *
	 * @param	array			$info		Data to insert
	 * @param	string|NULL		$filepath	Path to the event cover photo, or NULL.
	 * @param	string|NULL		$filedata	Cover photo binary data, or NULL
	 * @return	integer|boolean	The ID of the newly inserted event, or FALSE on failure.
	 */
	public function convert_calendar_event( $info=array(), $filepath=NULL, $filedata=NULL )
	{
		if ( !isset( $info['event_id'] ) )
		{
			$this->software->app->log( 'calendar_event_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['event_calendar_id'] ) )
		{
			try
			{
				$info['event_calendar_id'] = $this->software->app->getLink( $info['event_calendar_id'], 'calendar_calendars' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_event_missing_calendar', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['event_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_event_missing_calendar', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['event_id'] );
			return FALSE;
		}
		
		if ( isset( $info['event_member_id'] ) )
		{
			try
			{
				$info['event_member_id'] = $this->software->app->getLink( $info['event_member_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['event_member_id'] = 0;
			}
		}
		else
		{
			$info['event_member_id'] = 0;
		}
		
		if ( !isset( $info['event_title'] ) )
		{
			$event['event_title'] = "Untitled Event {$info['event_id']}";
			$this->software->app->log( 'calendar_event_missing_title', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['event_id'] );
		}
		
		/* Maye we can do this for other apps too? I have seen some complaints where content can be intentionally left blank in some softwares */
		if ( empty( $info['event_content'] ) )
		{
			$event['event_content'] = "<p>{$info['event_title']}</p>";
			$this->software->app->log( 'calendar_event_missing_content', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['event_id'] );
		}
		
		/* Zero Defaults! */
		foreach( array( 'event_comments', 'event_rsvp', 'event_rating', 'event_sequence' /* @todo see if this needs something special */, 'event_all_day', 'event_reviews', 'event_locked', 'event_featured', 'event_queued_comments', 'event_hidden_comments', 'event_unapproved_reviews', 'event_hidden_reviews' ) AS $zeroDefault )
		{
			if ( !isset( $info[$zeroDefault] ) )
			{
				$info[$zeroDefault] = 0;
			}
		}
		
		if ( isset( $info['event_saved'] ) )
		{
			if ( $info['event_saved'] instanceof \IPS\DateTime )
			{
				$info['event_saved'] = $info['event_saved']->getTimestamp();
			}
		}
		else
		{
			$info['event_saved'] = time();
		}
		
		if ( isset( $info['event_lastupdated'] ) )
		{
			if ( $info['event_lastupdated'] instanceof \IPS\DateTime )
			{
				$info['event_lastupdated'] = $info['event_lastupdated']->getTimestamp();
			}
		}
		else
		{
			$info['event_lastupdated'] = $info['event_saved'];
		}
		
		if ( isset( $info['event_recurring'] ) )
		{
			/* If we have an array, pass off to ICSParser so we can build it */
			if ( is_array( $info['event_recurring'] ) )
			{
				$info['event_recurring'] = \IPS\calendar\Icalendar\ICSParser::buildRrule( $info['event_recurring'] );
			}
			else
			{
				/* If we didn't, make sure it's valid */
				try
				{
					\IPS\calendar\Icalendar\ICSParser::parserRrule( $info['event_recurring'] );
				}
				catch( \Exception $e )
				{
					$this->software->app->log( 'calendar_event_recurring_invalid', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['event_id'] );
					$info['event_recurring'] = NULL;
				}
			}
		}
		else
		{
			$info['event_recurring'] = NULL;
		}
		
		if ( isset( $info['event_start_date'] ) )
		{
			if ( $info['event_start_date'] instanceof \IPS\calendar\Date )
			{
				$info['event_start_date'] = $info['event_start_date']->mysqlDatetime();
			}
			else if ( $info['event_start_date'] instanceof \IPS\DateTime )
			{
				$info['event_start_date'] = \IPS\calendar\Date::create( (string) $info['event_start_date'] )->mysqlDatetime();
			}
		}
		else
		{
			$this->software->app->log( 'calendar_event_missing_start_date', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['event_end_date'] ) )
		{
			if ( $info['event_end_date'] instanceof \IPS\calendar\Date )
			{
				$info['event_end_date'] = $info['event_end_date']->mysqlDatetime();
			}
			else if ( $info['event_end_date'] instanceof \IPS\DateTime )
			{
				$info['event_end_date'] = \IPS\calendar\Date::create( (string) $info['event_end_date'] )->mysqlDatetime();
			}
		}
		else
		{
			$info['event_end_date'] = NULL;
		}
		
		$info['event_title_seo']	= \IPS\Http\Url::seoTitle( $info['event_title'] );
		$info['event_post_key']		= md5( microtime() );
		
		if ( !isset( $info['event_ip_address'] ) OR filter_var( $info['event_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['event_ip_address'] = '127.0.0.1';
		}
		
		if ( isset( $info['event_last_comment'] ) )
		{
			if ( $info['event_last_comment'] instanceof \IPS\DateTime )
			{
				$info['event_last_comment'] = $info['event_last_comment']->getTimestamp();
			}
		}
		else
		{
			$info['event_last_comment'] = $info['event_saved'];
		}
		
		if ( isset( $info['event_last_review'] ) )
		{
			if ( $info['event_last_review'] instanceof \IPS\DateTime )
			{
				$info['event_last_review'] = $info['event_last_review']->getTimestamp();
			}
		}
		else
		{
			$info['event_last_review'] = $info['event_saved'];
		}
		
		if ( !isset( $info['event_approved'] ) )
		{
			$info['event_approved'] = 1;
		}
		
		if ( isset( $info['event_approved_by'] ) )
		{
			try
			{
				$info['event_approved_by'] = $this->software->app->getLink( $info['event_approved_by'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$info['event_approved_by'] = NULL;
			}
		}
		else
		{
			$info['event_approved_by'] = NULL;
		}
		
		if ( isset( $info['event_approved_on'] ) )
		{
			if ( $info['event_approved_on'] instanceof \IPS\DateTime )
			{
				$info['event_approved_on'] = $info['event_approved_on']->getTimestamp();
			}
		}
		else
		{
			$info['event_approved_on'] = NULL;
		}
		
		if ( isset( $info['event_location'] ) )
		{
			if ( is_array( $info['event_location'] ) AND isset( $info['event_location']['lat'] ) AND isset( $info['event_location']['long'] ) )
			{
				$info['event_location'] = (string) \IPS\GeoLocation::getFromLatLong( $info['event_location']['lat'], $info['event_location']['long'] );
			}
			else if ( $info['event_location'] instanceof \IPS\GeoLocation )
			{
				$info['event_location'] = (string) $info['event_location'];
			}
		}
		else
		{
			$info['event_location'] = NULL;
		}
		
		if ( !isset( $info['event_rsvp_limit'] ) )
		{
			$info['event_rsvp_limit'] = -1;
		}
		
		if ( isset( $info['event_album'] ) )
		{
			try
			{
				$info['event_album'] = $this->software->app->getSiblingLink( $info['event_album'], 'gallery_albums', 'gallery' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['event_album'] = NULL;
			}
		}
		else
		{
			$info['event_album'] = NULL;
		}
		
		if ( isset( $info['event_cover_photo'] ) AND ( !is_null( $filepath ) OR !is_null( $filedata ) ) )
		{
			try
			{
				if ( is_null( $filedata ) AND !is_null( $filepath ) )
				{
					$filedata = file_get_contents( $filepath );
				}
				
				$file = \IPS\File::create( 'calendar_Events', $info['event_cover_photo'], $filedata );
				$info['event_cover_photo'] = (string) $file;
			}
			catch( \Exception $e )
			{
				$info['event_cover_photo']	= NULL;
			}
			catch( \ErrorException $e )
			{
				$info['event_cover_photo']	= NULL;
			}
		}
		else
		{
			$info['event_cover_photo'] = NULL;
		}
		
		$id = $info['event_id'];
		unset( $info['event_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_events', $info );
		$this->software->app->addLink( $inserted_id, $id, 'calendar_events' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a comment
	 *
	 * @param	array			$info		Data to insert
	 * @return	integer|boolean	The ID of the newly inserted comment, or FALSE on failure.
	 */
	public function convert_calendar_comment( $info=array() )
	{
		if ( !isset( $info['comment_id'] ) )
		{
			$this->software->app->log( 'calendar_event_comment_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['comment_eid'] ) )
		{
			try
			{
				$info['comment_eid'] = $this->software->app->getLink( $info['comment_eid'], 'calendar_events' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_event_comment_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_event_comment_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
			return FALSE;
		}
		
		if ( empty( $info['comment_text'] ) )
		{
			$this->software->app->log( 'calendar_event_comment_empty', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['comment_id'] );
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
		
		if ( !isset( $info['comment_approved'] ) )
		{
			$info['comment_approved'] = 1;
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
		
		if ( !isset( $info['comment_edit_name'] ) )
		{
			$info['comment_edit_name'] = NULL;
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
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_event_comments', $info );
		$this->software->app->addLink( $inserted_id, $id, 'calendar_event_comments' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a review
	 *
	 * @param	array			$info		Data to insert
	 * @return	integer|boolean	The ID of the newly inserted review, or FALSE on failure.
	 */
	public function convert_calendar_review( $info=array() )
	{
		if ( !isset( $info['review_id'] ) )
		{
			$this->software->app->log( 'calendar_event_review_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['review_eid'] ) )
		{
			try
			{
				$info['review_eid'] = $this->software->app->getLink( $info['review_eid'], 'calendar_events' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_event_review_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_event_review_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		/* Unlike comments, guests cannot review */
		if ( isset( $info['review_mid'] ) )
		{
			try
			{
				$info['review_mid'] = $this->software->app->getLink( $info['review_mid'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_event_review_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_event_review_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		if ( empty( $info['review_text'] ) )
		{
			$this->software->app->log( 'calendar_event_review_empty', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		/* This seems silly, but we really do need a rating  */
		if ( !isset( $info['review_rating'] ) OR $info['review_rating'] < 1 )
		{
			$this->software->app->log( 'calendar_event_review_invalid_rating', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['review_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['review_append_edit'] ) )
		{
			$info['review_append_edit'] = 0;
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
			$info['review_edit_time'] = time();
		}
		
		if ( !isset( $info['review_edit_name'] ) )
		{
			$info['review_edit_name'] = NULL;
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
		
		if ( !isset( $info['review_author_name'] ) )
		{
			$author = \IPS\Member::load( $info['review_mid'] );
			
			if ( $author->member_id )
			{
				$info['review_author_name'] = $author->name;
			}
			else
			{
				$info['review_author_name'] = "Guest";
			}
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
		
		$id = $info['review_id'];
		unset( $info['review_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_event_reviews', $info );
		$this->software->app->addLink( $inserted_id, $id, 'calendar_event_reviews' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert an RSVP
	 *
	 * @param	array			$info		Data to insert
	 * @return	integer|boolean	The ID of the newly inserted RSVP, or FALSE on failure.
	 */
	public function convert_calendar_rsvp( $info=array() )
	{
		$hasId = TRUE;
		if ( !isset( $info['rsvp_id'] ) )
		{
			$hasId = FALSE;
		}
		
		if ( isset( $info['rsvp_event_id'] ) )
		{
			try
			{
				$info['rsvp_event_id'] = $this->software->app->getLink( $info['rsvp_event_id'], 'calendar_events' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_rsvp_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['rsvp_id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_rsvp_missing_event', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['rsvp_id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['rsvp_member_id'] ) )
		{
			try
			{
				$info['rsvp_member_id'] = $this->software->app->getLink( $info['rsvp_member_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_rsvp_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['rsvp_id'] : NULL );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_rsvp_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['rsvp_id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['rsvp_date'] ) )
		{
			if ( $info['rsvp_date'] instanceof \IPS\DateTime )
			{
				$info['rsvp_date'] = $info['rsvp_date']->getTimestamp();
			}
		}
		else
		{
			$info['rsvp_date'] = time();
		}
		
		if ( !isset( $info['rsvp_response'] ) OR !in_array( $info['rsvp_response'], array( 0, 1, 2 ) ) )
		{
			$this->software->app->log( 'calendar_rsvp_invalid_response', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['rsvp_id'] : NULL );
			return FALSE;
		}
		
		if ( $hasId )
		{
			$id = $info['rsvp_id'];
			unset( $info['rsvp_id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_event_rsvp', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'calendar_event_rsvp' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Calendar Import Feed
	 *
	 * @param	array			$info		Data to insert
	 * @return	integer|boolean	The ID of the newly inserted feed, or FALSE on failure.
	 */
	public function convert_calendar_feed( $info=array() )
	{
		if ( !isset( $info['feed_id'] ) )
		{
			$this->software->app->log( 'calendar_feed_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['feed_title'] ) )
		{
			$info['feed_title'] = "Untitled Feed {$info['feed_id']}";
			$this->software->app->log( 'calendar_feed_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
		}
		
		if ( !isset( $info['feed_url'] ) OR filter_var( $info['feed_url'], FILTER_VALIDATE_URL ) === FALSE )
		{
			$this->software->app->log( 'calendar_feed_invalid_url', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
			return FALSE;
		}
		
		if ( isset( $info['feed_added'] ) )
		{
			if ( $info['feed_added'] instanceof \IPS\DateTime )
			{
				$info['feed_added'] = $info['feed_added']->getTimestamp();
			}
		}
		else
		{
			$info['feed_added'] = time();
		}
		
		if ( isset( $info['feed_lastupdated'] ) )
		{
			if ( $info['feed_lastupdated'] instanceof \IPS\DateTime )
			{
				$info['feed_lastupdated'] = $info['feed_lastupdated']->getTimestamp();
			}
		}
		else
		{
			$info['feed_lastupdated'] = time();
		}
		
		if ( isset( $info['feed_calendar_id'] ) )
		{
			try
			{
				$info['feed_calendar_id'] = $this->software->app->getLink( $info['feed_calendar_id'], 'calendar_calendars' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_feed_missing_calendar', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_feed_missing_calendar', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
			return FALSE;
		}
		
		if ( isset( $info['feed_member_id'] ) )
		{
			try
			{
				$info['feed_member_id'] = $this->software->app->getLink( $info['feed_member_id'], 'core_members', TRUE );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'calendar_feed_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'calendar_feed_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['feed_id'] );
			return FALSE;
		}
		
		if ( isset( $info['feed_last_run'] ) )
		{
			if ( $info['feed_last_run'] instanceof \IPS\DateTime )
			{
				$info['feed_last_run'] = $info['feed_last_run']->getTimestamp();
			}
		}
		else
		{
			$info['feed_last_run'] = time();
		}
		
		if ( !isset( $info['feed_allow_rsvp'] ) )
		{
			$info['feed_allow_rsvp'] = 0;
		}
		
		$id = $info['feed_id'];
		unset( $info['feed_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'calendar_import_feeds', $info );
		$this->software->app->addLink( $inserted_id, $id, 'calendar_import_feeds' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert an attachment
	 *
	 * @param	array			$info		Data to insert
	 * @param	array			$map		Attachment Map Data
	 * @param	string|NULL		$filepath	Path to the file, or NULL.
	 * @param	string|NULL		$filedata	Binary data for the file, or NULL.
	 * @return	integer|boolean	The ID of the newly inserted attachment, or FALSE on failure.
	 */
	public function convert_attachment( $info=array(), $map=array(), $filepath=NULL, $filedata=NULL )
	{
		$map['location_key']	= 'calendar_Calendar';
		$map['id1_type']		= 'calendar_events';
		$map['id1_from_parent']	= FALSE;
		$map['id2_from_parent']	= FALSE;
		/* Some set up */
		if ( !isset( $info['id3'] ) )
		{
			$info['id3'] = NULL;
		}
		
		if ( is_null( $info['id3'] ) OR $info['id3'] != 'review' )
		{
			$map['id2_type'] = 'calendar_event_comments';
		}
		else
		{
			$map['id2_type'] = 'calendar_event_reviews';
		}
		
		return parent::convert_attachment( $info, $map, $filepath, $filedata );
	}
}