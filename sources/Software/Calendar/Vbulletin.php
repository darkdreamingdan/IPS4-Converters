<?php

/**
 * @brief		Converter vBulletin 4.x Calendar Class
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

class _Vbulletin extends \IPS\convert\Software
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
		return "vBulletin Calendar (3.x/4.x)";
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
		return array(
			'convert_calendar_calendars'=> array(
				'table'		=> 'calendar',
				'where'		=> NULL,
			),
			'convert_calendar_events'	=> array(
				'table'		=> 'event',
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
		/* Content Rebuilds */
		\IPS\Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'calendar_events', 'class' => 'IPS\calendar\Event' ), 2, array( 'app', 'link', 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\calendar\Event' ), 3, array( 'class' ) );
		
		return array( "Event Content Rebuilding", "Calendars Recounting", "Events Recounting" );
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
		return array();
	}
	
	/**
	 * Get More Information
	 *
	 * @return	array
	 */
	public function getMoreInfo( $method )
	{
		return array();
	}
	
	public function convert_calendar_calendars()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'calendarid' );
		
		foreach( $this->fetch( 'calendar', 'calendarid' ) AS $calendar )
		{
			$libraryClass->convert_calendar( array(
				'cal_id'		=> $calendar['calendarid'],
				'cal_title'		=> $calendar['title'],
				'cal_moderate'	=> $calendar['moderatenew'],
				'cal_position'	=> $calendar['displayorder']
			) );
			
			$libraryClass->setLastkeyValue( $calendar['calendarid'] );
		}
	}
	
	public function convert_calendar_events()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'eventid' );
		
		foreach( $this->fetch( 'event', 'eventid' ) AS $event )
		{
			/* We need to properly work out recurring, if possible - vBulletin stores basic recurring information versus our ICS Rules */
			$recurring = NULL;
			if ( $event['recurring'] )
			{
				switch( $event['recurring'] )
				{
					case 1: # daily recurring
						$recurring = array(
							'event_repeat'		=> 1,
							'event_repeats'		=> 'daily',
							'event_repeat_freq'	=> $event['recuroption']
						);
						break;
					
					case 2: # weekday recurring
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
					
					case 3: # weekly recurring
						$option = explode( "|", $event['recuroption'] );
						
						$daybits = array(
							'SU'		=> 1,
							'MO'		=> 2,
							'TU'		=> 4,
							'WE'		=> 8,
							'TH'		=> 16,
							'FR'		=> 32,
							'SA'		=> 64
						);
						
						$recurring = array(
							'event_repeat'		=> 1,
							'event_repeats'		=> 'weekly',
							'event_repeat_freq'	=> $option[0],
							'repeat_freq_on_SU'	=> ( $option[1] & $daybits['SU'] ) ? 1 : 0,
							'repeat_freq_on_MO'	=> ( $option[1] & $daybits['MO'] ) ? 1 : 0,
							'repeat_freq_on_TU'	=> ( $option[1] & $daybits['TU'] ) ? 1 : 0,
							'repeat_freq_on_WE'	=> ( $option[1] & $daybits['WE'] ) ? 1 : 0,
							'repeat_freq_on_TH'	=> ( $option[1] & $daybits['TH'] ) ? 1 : 0,
							'repeat_freq_on_FR'	=> ( $option[1] & $daybits['FR'] ) ? 1 : 0,
							'repeat_freq_on_SA'	=> ( $option[1] & $daybits['SA'] ) ? 1 : 0,
						);
						break;
					
					case 4: # monthly recurring, specific day (day 16 of every month)
						/* We don't support specific day recurrence. */
						$option = explode( "|", $event['recuroption'] );
						$recurring = array(
							'event_repeat'		=> 1,
							'event_repeats'		=> 'monthly',
							'event_repeat_freq'	=> $option[1],
						);
						break;
					
					case 5: # monthly recurring, specific day (third Friday of every month)
						/* We don't support specific day recurrence. */
						$option = explode( "|", $event['recuroption'] );
						$recurring = array(
							'event_repeat'		=> 1,
							'event_repeats'		=> 'monthly',
							'event_repeat_freq'	=> $option[2]
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
			}
			
			$info = array(
				'event_id'			=> $event['eventid'],
				'event_calendar_id'	=> $event['calendarid'],
				'event_member_id'	=> $event['userid'],
				'event_title'		=> $event['title'],
				'event_content'		=> $event['event'],
				'event_saved'		=> $event['dateline'],
				'event_start_date'	=> \IPS\calendar\Date::ts( $event['dateline_from'] ),
				'event_end_date'	=> \IPS\calendar\Date::ts( $event['dateline_to'] ),
				'event_approved'	=> $event['visible'],
				'event_recurring'	=> $recurring
			);
			
			$libraryClass->convert_calendar_event( $info );
			
			$libraryClass->setLastKeyValue( $event['eventid'] );
		}
	}
}