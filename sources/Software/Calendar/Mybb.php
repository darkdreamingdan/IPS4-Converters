<?php

/**
 * @brief		Converter MyBB Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Calendar;

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
			'convert_calendar_calendars'	=> array(
				'table'							=> 'calendars',
				'where'							=> NULL
			),
			'convert_calendar_events'		=> array(
				'table'							=> 'events',
				'where'							=> NULL
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
		return array( 'core' => array( 'mybb' ) );
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
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'calendar_events', 'class' => 'IPS\calendar\Event' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\calendar\Event' ), 3, array( 'class' ) );
		
		return array( "Event Content Rebuilding", "Calendars Recounting", "Events Recounting" );
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
	
	public function convert_calendar_calendars()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'cid' );
		
		foreach( $this->fetch( 'calendars', 'cid' ) AS $row )
		{
			$libraryClass->convert_calendar( array(
				'cal_id'		=> $row['cid'],
				'cal_title'		=> $row['name'],
				'cal_position'	=> $row['disporder'],
			) );
			
			$libraryClass->setLastKeyValue( $row['cid'] );
		}
	}
	
	public function convert_calendar_events()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'eid' );
		
		foreach( $this->fetch( 'events', 'eid' ) AS $row )
		{
			$repeats	= \unserialize( $row['repeats'] );
			$recurring	= NULL;
			
			switch( $repeats['repeats'] )
			{
				case 1: # daily
					$recurring = array(
						'event_repeat'		=> 1,
						'event_repeats'		=> 'daily',
						'event_repeat_freq'	=> $repeats['days'],
					);
					break;
				
				case 2: # Monday through Friday
					$recurring = array(
						'event_repeat'		=> 1,
						'event_repeats'		=> 'daily',
						'event_repeat_freq'	=> 1,
						'repeat_freq_on_MO'	=> 1,
						'repeat_freq_on_TU'	=> 1,
						'repeat_freq_on_WE'	=> 1,
						'repeat_freq_on_TH'	=> 1,
						'repeat_freq_on_FR'	=> 1
					);
					break;
				
				case 3: # weekly
					$recurring = array(
						'event_repeat'		=> 1,
						'event_repeats'		=> 'weekly',
						'event_repeat_freq'	=> $repeats['weeks'],
						'repeat_freq_on_SU'	=> isset( $repeats['days'][0] ) ? 1 : 0,
						'repeat_freq_on_MO'	=> isset( $repeats['days'][1] ) ? 1 : 0,
						'repeat_freq_on_TU'	=> isset( $repeats['days'][2] ) ? 1 : 0,
						'repeat_freq_on_WE'	=> isset( $repeats['days'][3] ) ? 1 : 0,
						'repeat_freq_on_TH'	=> isset( $repeats['days'][4] ) ? 1 : 0,
						'repeat_freq_on_FR'	=> isset( $repeats['days'][5] ) ? 1 : 0,
						'repeat_freq_on_SA'	=> isset( $repeats['days'][6] ) ? 1 : 0,
					);
					break;
				
				case 4: # monthly recurring, specific day (day 16 of every month)
					/* We don't support specific day recurrence. */
					$recurring = array(
						'event_repeat'		=> 1,
						'event_repeats'		=> 'monthly',
						'event_repeat_freq'	=> $repeats['months'],
					);
					break;
				
				case 6: # yearly, specific day (October 16th, every year)
				case 7: # yearly, specific day (Every third Friday of October)
					/* We don't support specific days, and vBulletin doesn't support yearly frequencies more than 1 */
					$recurring = array(
						'event_repeat'		=> 1,
						'event_repeats'		=> 'yearly',
						'event_repeat_freq'	=> 1,
					);
					break;
			}
			
			$libraryClass->convert_calendar_event( array(
				'event_id'			=> $row['eid'],
				'event_calendar_id'	=> $row['cid'],
				'event_member_id'	=> $row['uid'],
				'event_title'		=> $row['name'],
				'event_content'		=> $row['description'],
				'event_saved'		=> $row['dateline'],
				'event_start_date'	=> \IPS\calendar\Date::ts( $row['starttime'] ),
				'event_end_date'	=> \IPS\calendar\Date::ts( $row['endtime'] ),
				'event_approved'	=> $row['visible'],
				'event_recurring'	=> $recurring,
			) );
			
			$libraryClass->setLastKeyValue( $row['eid'] );
		}
	}
}