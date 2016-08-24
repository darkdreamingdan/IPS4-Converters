<?php

/**
 * @brief		Converter Library Core Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 * @todo IPS4 has a concept of "everything centralized" whereas most other software do not - we will need to cover that for things like Moderators and Edit History.
 */

namespace IPS\convert\Library;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Core extends \IPS\convert\Library
{
	/**
	 * @brief	Application
	 */
	public $app = 'core';
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to complete this conversion
	 *
	 * @return	string
	 * @todo
	 */
	public function getPostConversionInformation()
	{
		return "Once all steps have been completed, you can <a href='" . (string) \IPS\Http\Url::internal( "app=convert&module=manage&controller=create&_new=1" ) . "'>begin another conversion</a> or <a href='" . (string) \IPS\Http\Url::internal( "app=convert&module=manage&controller=convert&do=finish&id={$this->software->app->app_id}" ) . "'>finish</a> to rebuild data.";
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
				case 'convert_acronyms':
					$return[$k] = array(
						'step_title'		=> 'convert_acronyms',
						'step_method'		=> 'convert_acronyms',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_acronyms' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1000,
						'dependencies'		=> array(),
						'link_type'			=> 'core_acronyms',
					);
					break;
				
				case 'convert_administrators':
					$dependencies = arrau();
					
					if ( array_key_exists( 'convert_groups', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_groups';
					}
					
					$dependencies[] = 'convert_members';
					
					$return[$k] = array(
						'step_title'		=> 'convert_administrators',
						'step_method'		=> 'convert_administrators',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_admin_permission_rows' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'core_administrators',
					);
					break;
					
				case 'convert_announcements':
					$return[$k] = array(
						'step_title'		=> 'convert_announcements',
						'step_method'		=> 'convert_announcements',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_announcements' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_announcements',
					);
					break;
					
				case 'convert_attachments':
					$return[$k] = array(
						'step_title'		=> 'convert_attachments',
						'step_method'		=> 'convert_attachments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_attachments',
					);
					break;
				
				case 'convert_banfilters':
					$return[$k] = array(
						'step_title'		=> 'convert_banfilters',
						'step_method'		=> 'convert_banfilters',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_banfilters' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_banfilters',
					);
					break;
				
				case 'convert_dname_changes':
					$return[$k] = array(
						'step_title'		=> 'convert_dname_changes',
						'step_method'		=> 'convert_dname_changes',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_dnames_change' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_dname_changes',
					);
					break;
				
				case 'convert_edit_history':
					$return[$k] = array(
						'step_title'		=> 'convert_edit_history',
						'step_method'		=> 'convert_edit_history',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_edit_history' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_edit_history',
					);
					break;
				
				case 'convert_emoticons':
					$return[$k] = array(
						'step_title'		=> 'convert_emoticons',
						'step_method'		=> 'convert_emoticons',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_emoticons' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_emoticons',
					);
					break;
				
				case 'convert_groups':
					$return[$k] = array(
						'step_title'		=> 'convert_groups',
						'step_method'		=> 'convert_groups',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_groups' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_groups',
					);
					break;
				
				case 'convert_ignored_users':
					$return[$k] = array(
						'step_title'		=> 'convert_ignored_users',
						'step_method'		=> 'convert_ignored_users',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_ignored_users' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_ignored_users',
					);
					break;
				
				case 'convert_leader_groups':
					$return[$k] = array(
						'step_title'		=> 'convert_leader_groups',
						'step_method'		=> 'convert_leader_groups',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_leaders_groups' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_leader_groups',
					);
					break;
				
				case 'convert_leaders':
					/* Dependencies change based on what we can convert */
					$dependencies = array();
					
					if ( array_key_exists( 'core_leader_groups', $classname::canConvert() ) )
					{
						$dependencies[] = 'core_leader_groups';
					}
					
					if ( array_key_exists( 'core_groups', $classname::canConvert() ) )
					{
						$dependencies[] = 'core_groups';
					}
					
					$dependencies[] = 'core_members';
					
					$return [$k] = array(
						'step_title'		=> 'convert_leaders',
						'step_method'		=> 'convert_leaders',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_leaders' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'core_leaders',
					);
					break;
				
				case 'convert_ranks':
					$return[$k] = array(
						'step_title'		=> 'convert_ranks',
						'step_method'		=> 'convert_ranks',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_member_ranks' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_member_ranks',
					);
					break;
				
				case 'convert_statuses':
					$return[$k] = array(
						'step_title'		=> 'convert_statuses',
						'step_method'		=> 'convert_statuses',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_member_status_updates' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_member_status_updates',
					);
					break;
				
				case 'convert_status_replies':
					$return[$k] = array(
						'step_title'		=> 'convert_status_replies',
						'step_method'		=> 'convert_status_replies',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_member_status_replies' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array( 'convert_members', 'convert_statuses' ),
						'link_type'			=> 'core_member_status_replies',
					);
					break;
					
				case 'convert_members':
					$dependencies = array();
					
					if ( array_key_exists( 'convert_groups', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_groups';
					}
					
					if ( array_key_exists( 'convert_profile_fields', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_profile_fields';
					}
					
					$return[$k] = array(
						'step_title'		=> 'convert_members',
						'step_method'		=> 'convert_members',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_members' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 250,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'core_members',
					);
					break;
				
				case 'convert_warn_actions':
					$return[$k] = array(
						'step_title'		=> 'convert_warn_actions',
						'step_method'		=> 'convert_warn_actions',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_members_warn_actions' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_members_warn_actions',
					);
					break;
				
				case 'convert_warn_reasons':
					$return[$k] = array(
						'step_title'		=> 'convert_warn_reasons',
						'step_method'		=> 'convert_warn_reasons',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_members_warn_reasons' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_members_warn_reasons',
					);
					break;
				
				case 'convert_private_messages':
					$return[$k] = array(
						'step_title'		=> 'convert_private_messages',
						'step_method'		=> 'convert_private_messages',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_message_topics' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1000,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> array( 'core_message_topics', 'core_message_posts', 'core_message_topic_user_map' ),
					);
					break;
				
				case 'convert_moderators':
					$return[$k] = array(
						'step_title'		=> 'convert_moderators',
						'step_method'		=> 'convert_moderators',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_moderators' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array( 'convert_members' ),
						'link_type'			=> 'core_members',
					);
					break;
				
				case 'convert_profile_field_groups':
					$return[$k] = array(
						'step_title'			=> 'convert_profile_field_groups',
						'step_method'		=> 'convert_profile_field_groups',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_pfields_groups' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> array(),
						'link_type'			=> 'core_pfields_groups',
					);
					break;
				
				case 'convert_profile_fields':
					$dependencies = array();
					
					if ( array_key_exists( 'convert_profile_field_groups', $classname::canConvert() ) )
					{
						$dependencies[] = 'convert_profile_field_groups';
					}
					
					$return[$k] = array(
						'step_title'		=> 'convert_profile_fields',
						'step_method'		=> 'convert_profile_fields',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_pfields_data' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 100,
						'dependencies'		=> $dependencies,
						'link_type'			=> 'core_pfields_data',
					);
					break;
				
				case 'convert_profanity_filters':
					$return[$k] = array(
						'step_title'		=> 'convert_profanity_filters',
						'step_method'		=> 'convert_profanity_filters',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_profanity_filters' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array(),
						'link_type'			=> 'core_profanity_filters',
					);
					break;
				
				case 'convert_question_and_answers':
					$return[$k] = array(
						'step_title'		=> 'convert_question_and_answers',
						'step_method'		=> 'convert_question_and_answers',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_question_and_answer' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array(),
						'link_type'			=> 'core_question_and_answer',
					);
					break;
				
				case 'convert_report_comments':
					$return[$k] = array(
						'step_title'		=> 'convert_report_comments',
						'step_method'		=> 'convert_report_comments',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_rc_comments' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 1500,
						'dependencies'		=> array(),
						'link_type'			=> 'core_report_comments',
					);
					break;
				
				case 'convert_reputation_levels':
					$return[$k] = array(
						'step_title'		=> 'convert_reputation_levels',
						'step_method'		=> 'convert_reputation_levels',
						'ips_rows'			=> \IPS\Db::i()->select( 'COUNT(*)', 'core_reputation_levels' )->first(),
						'source_rows'		=> $this->software->countRows( $v['table'], $v['where'] ),
						'per_cycle'			=> 2000,
						'dependencies'		=> array(),
						'link_type'			=> 'core_reputation_levels',
					);
					break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Returns an array of tables that need to be truncated when Empty Local Data is used
	 *
	 * @param	string	The method to truncate
	 * @return	array
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
				case 'convert_acronyms':
					$return['convert_acronyms'] = array( 'core_acronyms' => NULL );
					break;
				
				case 'convert_administrators':
					$return['convert_administrators'] = array( 'core_administrators', array( "( row_id!=? AND row_id_type!=? ) OR ( row_id!=? AND row_id_type!=?", \IPS\Member::loggedIn()->member_id, 'member', \IPS\Member::i()->loggedIn()->member_group_id, 'group' ) );
					break;
				
				case 'convert_announcements':
					$return['convert_announcements'] = array( 'core_announcements' => NULL );
					break;
				
				case 'convert_attachments':
					$return['convert_attachments'] = array( 'core_attachments' => NULL, 'core_attachments_map' => NULL );
					break;
				
				case 'convert_banfilters':
					$return['convert_banfilters'] = array( 'core_banfilters' => NULL );
					break;
				
				case 'convert_dname_changes':
					$return['convert_dname_changes'] = array( 'core_dnames_change' => NULL );
					break;
				
				case 'convert_edit_history':
					$return['convert_edit_history'] = array( 'core_edit_history' => NULL );
					break;
				
				case 'convert_emoticons':
					$return['convert_emoticons'] = array( 'core_emoticons' => NULL );
					break;
				
				case 'convert_groups':
					$return['convert_groups'] = array( 'core_groups' => array( "g_id NOT IN(?)", implode( ',', array( \IPS\Settings::i()->member_group, \IPS\Settings::i()->guest_group, \IPS\Settings::i()->admin_group ) ) ) );
					break;
				
				case 'convert_ignored_users':
					$return['convert_ignored_users'] = array( 'core_ignored_users' => NULL );
					break;
				
				case 'convert_leaders':
					$return['convert_leaders'] = array( 'core_leaders' => NULL );
					break;
				
				case 'convert_leader_groups':
					$return['convert_leader_groups'] = array( 'core_leaders_groups' => NULL );
					break;
				
				case 'convert_ranks':
					$return['convert_ranks'] = array( 'core_member_ranks' => NULL );
					break;
				
				case 'convert_status_replies':
					$return['convert_status_replies'] = array( 'core_member_status_replies' => NULL );
					break;
				
				case 'convert_status_updates':
					$return['convert_status_updates'] = array( 'core_member_status_updates' => NULL );
					break;
					
				case 'convert_members':
					$return['convert_members'] = array( 'core_members' => array( "member_id<>?", \IPS\Member::loggedIn()->member_id ), 'core_pfields_content' => array( "member_id<>?", \IPS\Member::loggedIn()->member_id ) );
					break;
				
				case 'convert_warn_actions':
					$return['convert_warn_actions'] = array( 'core_members_warn_actions' => NULL );
					break;
				
				case 'convert_warn_reason':
					$return['convert_warn_reason'] = array( 'core_members_warn_reasons' => NULL );
					break;
				
				case 'convert_private_messages':
					$return['convert_private_messages'] = array( 'core_message_topics' => NULL, 'core_message_posts' => NULL, 'core_message_topic_user_map' => NULL );
					break;
				
				case 'convert_moderators':
					$return['convert_moderators'] = array( 'core_moderators' => NULL );
					break;
				
				case 'convert_profile_field_groups':
					$return['convert_profile_field_groups'] = array( 'core_pfields_groups' => NULL );
					break;
				
				case 'convert_profile_fields':
					if ( $method == $k )
					{
						foreach( \IPS\Db::i()->select( 'pf_id', 'core_pfields_data' ) AS $field )
						{
							\IPS\Db::i()->dropIndex( 'core_pfields_content', 'field_' . $field );
							\IPS\Db::i()->dropColumn( 'core_pfields_content', 'field_' . $field );
						}
					}
					
					$return['convert_profile_fields'] = array( 'core_pfields_data' => NULL );
					break;
				
				case 'convert_profanity_filters':
					$return['convert_profanity_filters'] = array( 'core_profanity_filters' => NULL );
					break;
				
				case 'convert_question_and_answers':
					$return['convert_question_and_answers'] = array( 'core_question_and_answer' => NULL );
					break;
				
				case 'convert_report_comments':
					$return['convert_report_comments'] = array( 'core_rc_comments' => NULL );
					break;
				
				case 'convert_reputation_levels':
					$return['convert_reputation_levels'] = array( 'core_reputation_levels' => NULL );
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
	 * Convert an Acronym
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted acronym, or FALSE on failure
	 */
	public function convert_acronym( $info=array() )
	{
		/**
		 * The below are examples of when we should use an error or a notice, depending on the situation.
		 *
		 * For example, it matters that a_id, a_short, or a_long is missing because this is required information, but we do not need to necessarily halt everything, thus a warning.
		 * a_casesensitive, however, is not and can be assumed 0 and a simple notice issued explaining.
		 */
		if ( !isset( $info['a_id'] ) )
		{
			$this->software->app->log( 'acronym_missing_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['a_short'] ) )
		{
			$this->software->app->log( 'acronym_missing_short', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['a_long'] ) )
		{
			$this->software->app->log( 'acronym_missing_long', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['a_casesensitive'] ) )
		{
			$this->software->app->log( 'acronym_missing_casesensitive', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$info['a_casesensitive'] = 0;
		}
		
		$obj					= new \IPS\core\Acronym;
		$obj->a_short			= $info['a_short'];
		$obj->a_long			= $info['a_long'];
		$obj->a_casesensitive	= $info['a_casesensitive'];
		$obj->save();
		
		$this->software->app->addLink( $obj->a_id, $info['a_id'], 'core_acronyms' );
		return $obj->a_id;
	}
	
	/**
	 * Convert an Administrator
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean	TRUE on success, FALSE on failure
	 */
	public function convert_administrator( $info=array() )
	{
		if ( !isset( $info['row_id'] ) )
		{
			$this->software->app->log( 'administrator_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['row_id_type'] ) OR !in_array( $info['row_id_type'], array( 'member', 'group' ) ) )
		{
			$this->software->app->log( 'administrator_invalid_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['row_perm_cache'] ) )
		{
			if ( is_array( $info['row_perm_cache'] ) )
			{
				$info['row_perm_cache'] = json_encode( $info['row_perm_cache'] );
			}
		}
		else
		{
			$this->software->app->log( 'administrator_perms_missing', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['row_updated'] ) )
		{
			if ( $info['row_update'] instanceof \IPS\DateTIme )
			{
				$info['row_updated'] = $info['row_updated']->getTimestamp();
			}
		}
		else
		{
			$info['row_updated'] = time();
		}
		
		switch( $info['row_id_type'] )
		{
			case 'member':
				try
				{
					$info['row_id'] = $this->software->app->getLink( $info['row_id'], 'core_members' );
				}
				catch( \OutOfRangeException $e )
				{
					$this->software->app->log( 'administrator_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING );
					return FALSE;
				}
				break;
			
			case 'group':
				try
				{
					$info['row_id'] = $this->software->app->getLink( $info['row_id'], 'core_groups' );
				}
				catch( \OutOfRangeException $e )
				{
					$this->software->app->log( 'administrator_missing_group', __METHOD__, \IPS\convert\App::LOG_WARNING );
					return FALSE;
				}
				break;
		}
		
		\IPS\Db::i()->insert( 'core_admin_permission_rows', $info );
	}
	
	/**
	 * Convert an Announcement
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted announcement, or FALSE on failure
	 */
	public function convert_announcement( $info=array() )
	{
		if ( !isset( $info['announce_id'] ) )
		{
			$this->software->app->log( 'announcement_missing_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['announce_title'] ) )
		{
			$this->software->app->log( 'announcement_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['announce_content'] ) )
		{
			$this->software->app->log( 'announcement_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['announce_member_id'] ) )
		{
			$this->software->app->log( 'announcement_missing_member_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['announce_views'] ) )
		{
			$info['announce_views'] = 0;
		}

		if ( !isset( $info['announce_start'] ) )
		{
			$info['announce_start'] = time();
		}

		if ( !isset( $info['announce_end'] ) )
		{
			$info['announce_end'] = 0;
		}

		if ( !isset( $info['announce_active'] ) )
		{
			$info['announce_active'] = 0;
		}

		if ( !isset( $info['announce_seo_title'] ) )
		{
			$info['announce_seo_title'] = \IPS\Http\Url::seoTitle( $info['announce_title'] );
		}

		if ( !isset( $info['announce_ids'] ) )
		{
			$info['announce_ids'] = NULL;
		}

		if ( !isset( $info['announce_app'] ) )
		{
			$info['announce_app'] = '*';
		}

		if ( !isset( $info['announce_location'] ) )
		{
			$info['announce_location'] = '*';
		}

		$obj = new \IPS\core\Announcements\Announcement;
		foreach ( $info AS $field => $value)
		{
			if ( $field !== 'announce_id' )
			{
				$field = str_replace( 'announce_', '', $field );
				$obj->$field = $value;
			}

		}
		$obj->save();

		$this->software->app->addLink( $obj->id, $info['announce_id'], 'core_announcement' );
		return $obj->a_id;

	}
	
	/**
	 * Convert an Attachment
	 *
	 * @param	array			$info		Data to insert
	 * @param	array			$map		Array of Map data
	 * @param	NULL|string		$filepath	The path to the attachment files or NULL if loading from the database.
	 * @param	NULL|string		$filedata	If loading from the database, the content of the Binary column.
	 * @return	boolean|integer	The ID of the newly inserted attachment, or FALSE on failure.
	 */
	public function convert_attachment( $info=array(), $map=array(), $filepath=NULL, $filedata=NULL )
	{
		if ( !isset( $info['attach_id'] ) )
		{
			$this->software->app->log( 'attachment_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( is_null( $filepath ) AND is_null( $filedata ) )
		{
			$this->software->app->log( 'attachment_missing_data', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
		}

		if ( is_null( $filedata ) AND !file_exists( $filepath ) )
		{
			$this->software->app->log( 'attachment_file_missing', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
			return FALSE;
		}
		
		/* All attachments must have at least a location key and id1 OR id3 */
		if ( !isset( $map['location_key'] ) AND ( !isset( $map['id1'] ) OR !isset( $map['id3'] ) ) )
		{
			$this->software->app->log( 'attachment_missing_map_data', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
			return FALSE;
		}
		
		/* Make sure our location key is valid */
		$haveExtension = FALSE;
		foreach( array_keys( \IPS\Application::allExtensions( 'core', 'EditorLocations', FALSE, NULL, NULL, FALSE ) ) AS $extension )
		{
			if ( $map['location_key'] == $extension )
			{
				$haveExtension = TRUE;
				break;
			}
		}
		
		if ( $haveExtension === FALSE )
		{
			$this->software->app->log( 'attachment_invalid_location', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
			return FALSE;
		}
		
		/* Most of the rest of the information we need can be automatically determined if it's not present */
		if ( !isset( $info['attach_file'] ) )
		{
			$fileName				= explode( '/', $info['attach_location'] );
			$fileName				= array_pop( $fileName );
			$info['attach_file']	= $fileName;
		}
		
		if ( !isset( $info['attach_ext'] ) )
		{
			$extension			= explode( ',', $info['attach_file'] );
			$extension			= array_pop( $extension );
			$info['attach_ext']	= $extension;
		}
		
		if ( !isset( $info['attach_date'] ) )
		{
			$info['attach_date'] = time();
		}
		
		/* These don't matter */
		$info['attach_post_key']	= '';
		$info['attach_is_archived']	= 0;
		
		if ( isset( $info['attach_member_id'] ) )
		{
			try
			{
				$info['attach_member_id'] = $this->software->app->getLink( $info['attach_member_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['attach_member_id'] = 0;
			}
		}
		else
		{
			$info['attach_member_id'] = 0;
		}
		
		if ( !isset( $info['attach_hits'] ) )
		{
			$info['attach_hits'] = 0;
		}
		
		/* Create the file */
		try
		{
			$done = FALSE;
			/* We need to do some re-jiggery here - loading the entire file into memory can cause exhaustion and other issue... so, if the core_Attachments extension is using the local file system, lets copy it temporarily and move it that way */
			$upload_settings = json_decode( \IPS\Settings::i()->upload_settings, TRUE );
			if ( isset( $upload_settings['filestorage__core_Attachment'] ) AND $filepath )
			{
				try
				{
					$storage = \IPS\Db::i()->select( '*', 'core_file_storage', array( "id=?", $upload_settings['filestorage__core_Attachment'] ) )->first();
					
					if ( $storage['method'] == 'FileSystem' )
					{
						$configuration = json_decode( $storage['configuration'], TRUE );
						
						if ( isset( $configuration['dir'] ) )
						{
							$storage_dir = str_replace( '{root}', \IPS\ROOT_PATH, $configuration['dir'] );
							
							if ( !@is_dir( $storage_dir . '/convert_temp' ) )
							{
								if ( @mkdir( $storage_dir . '/convert_temp' ) )
								{
									@chmod( $storage_dir . '/convert_temp', \IPS\IPS_FOLDER_PERMISSION );
								}
								else
								{
									$last_error = @error_get_last();
									throw new \RuntimeException( $last_error['message'] );
								}
							}
							
							if ( !@is_dir( $storage_dir . '/convert_temp' ) )
							{
								$last_error = @error_get_last();
								throw new \RuntimeException( $last_error['message'] );
							}
							/* Now copy the file to our temp directory */
							if ( @copy( $filepath, $storage_dir . '/convert_temp/' . $info['attach_file'] ) )
							{
								/* Good, now let's move it properly. Exceptions will bubble up. */
								$file = \IPS\File::create( 'core_Attachments', $info['attach_file'], NULL, 'monthly_' . date( 'Y', $info['attach_date'] ) . '_' . date( 'm', $info['attach_date'] ), TRUE, $storage_dir . '/convert_temp/' . $info['attach_file'] );
								@unlink( $storage_dir . '/convert_temp/' . $info['attach_file'] );
								$done = TRUE;
								
								/* Unset filedata to conserve memory */
								unset( $filedata );
							}
							else
							{
								$last_error = @error_get_last();
								throw new \RuntimeException( $last_error['message'] );
							}
						}
					}
				}
				/* We specifically look for exceptions here, but do not halt the process - we can still try the old method with the file data in memory. */
				catch( \UnderflowException $exception )
				{
					try
					{
						\IPS\Log::i( \LOG_CRIT )->write( get_class( $exception ) . "\n" . $exception->getCode() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString(), 'attachments' );
					}
					catch( \Exception $e ) {}
				}
				catch( \RunimeException $exception )
				{
					try
					{
						\IPS\Log::i( \LOG_CRIT )->write( get_class( $exception ) . "\n" . $exception->getCode() . ": " . $exception->getMessage() . "\n" . $exception->getTraceAsString(), 'attachments' );
					}
					catch( \Exception $e ) {}
				}
			}
			
			if ( $done === FALSE )
			{
				/* Last ditch effort to use what is in memory */
				try
				{
					if ( is_null( $filedata ) AND !is_null( $filepath ) )
					{
						$filedata = file_get_contents( $filepath );
					}
				}
				catch( \ErrorException $e )
				{
					$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
					return FALSE;
				}
				$file = \IPS\File::create( 'core_Attachments', $info['attach_file'], $filedata, 'monthly_' . date( 'Y', $info['attach_date'] ) . '_' . date( 'm', $info['attach_date'] ), TRUE );
			}
			
			$info['attach_location'] = (string) $file;
		}
		catch( \Exception $e )
		{
			$this->software->app->log( 'attachment_creation_fail', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
			return FALSE;
		}
		catch( \ErrorException $e )
		{
			$this->software->app->log( $e->getMessage(), __METHOD__, \IPS\convert\App::LOG_WARNING, $info['attach_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['attach_filesize'] ) )
		{
			$info['attach_filesize'] = $file->filesize();
		}
		
		/* If this is an image, we need to do stuff */
		if ( $file->isImage() )
		{
			/* Make sure the image flag is set */
			$info['attach_is_image'] = 1;
			
			/* Height and Width of the main image */
			if ( !isset( $info['attach_img_width'] ) OR !isset( $info['attach_img_height'] ) )
			{
				try
				{
					$dimensions = $file->getImageDimensions();
					
					if ( !isset( $info['attach_img_width'] ) )
					{
						$info['attach_img_width'] = $dimensions[0];
					}
					
					if ( !isset( $info['attach_img_height'] ) )
					{
						$info['attach_img_height'] = $dimensions[1];
					}
				}
				catch( \InvalidArgumentException $e )
				{
					/* File isn't actually an image. */
					$info['attach_is_image']		= 0;
					$info['attach_img_width']		= 0;
					$info['attach_img_height']		= 0;
				}
			}
		}
		else
		{
			$info['attach_thumb_location']	= '';
			$info['attach_thumb_width']		= 0;
			$info['attach_thumb_height']	= 0;
			$info['attach_is_image']		= 0;
			$info['attach_img_width']		= 0;
			$info['attach_img_height']		= 0;
		}
		
		$id = $info['attach_id'];
		unset( $info['attach_id'] );
		
		try
		{
			$inserted_id = \IPS\Db::i()->insert( 'core_attachments', $info );
			$this->software->app->addLink( $inserted_id, $id, 'core_attachments' );
		}
		catch( \IPS\Db\Exception $e )
		{
			$this->software->app->log( 'attahcment_invalid_data', __METHOD__, \IPS\convert\App::LOG_WARNING, $id );
			return FALSE;
		}
		
		/* Now we need to figure out map information */
		if ( !isset( $map['id1'] ) AND isset( $map['id3'] ) )
		{
			/* This is just a key - we can directly insert it. The converter will need to determine how to translate */
			\IPS\Db::i()->insert( 'core_attachments_map', array(
				'attachment_id'	=> $inserted_id,
				'location_key'	=> $map['location_key'],
				'id1'			=> NULL,
				'id2'			=> NULL,
				'temp'			=> NULL,
				'id3'			=> $map['id3']
			) );
			
			return $inserted_id;
		}
		
		/* This gets a bit tricky... we cannot automatically determine where our ID is from, so we need extra information to be passed by the converter */
		try
		{
			$id1 = $this->software->app->getLink( $map['id1'], $map['id1_type'], $map['id1_from_parent'] );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Db::i()->delete( 'core_attachments', array( "attach_id=?", $id ) );
			$this->software->app->log( 'attachment_missing_parent', __METHOD__, \IPS\convert\App::LOG_WARNING, $id );
			return;
		}
		
		if ( isset( $map['id2'] ) )
		{
			try
			{
				$id2 = $this->software->app->getLink( $map['id2'], $map['id2_type'], $map['id2_from_parent'] );
			}
			catch( \OutOfRangeException $e )
			{
				$id2 = NULL;
			}
		}
		else
		{
			$id2 = NULL;
		}
		
		if ( isset( $map['id3'] ) AND ( !isset( $map['id3_skip_link'] ) OR $map['id3_skip_link'] === FALSE ) )
		{
			try
			{
				$id3 = $this->software->app->getLink( $map['id3'], $map['id3_type'], $map['id3_from_parent'] );
			}
			catch( \OutOfRangeException $e )
			{
				$id3 = NULL;
			}
		}
		else
		{
			$id3 = NULL;
		}

		\IPS\Db::i()->insert( 'core_attachments_map', array(
			'attachment_id'	=> $inserted_id,
			'location_key'	=> $map['location_key'],
			'id1'			=> $id1,
			'id2'			=> $id2,
			'temp'			=> NULL,
			'id3'			=> $id3,
		) );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Ban Filter
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted ban filter, or FALSE on failure
	 */
	public function convert_banfilter( $info=array() )
	{
		if ( !isset( $info['ban_id'] ) )
		{
			$this->software->app->log( 'banfilter_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['ban_type'] ) OR !in_array( $info['ban_type'], array( 'ip', 'email', 'name' ) ) )
		{
			$this->software->app->log( 'banfilter_missing_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['ban_content'] ) )
		{
			$this->software->app->log( 'banfilter_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['ban_date'] ) )
		{
			$this->software->app->log( 'banfilter_missing_date', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$info['ban_date'] = time();
		}
		
		if ( !isset( $info['ban_reason'] ) )
		{
			$info['ban_reason'] = '';
		}
		
		$old_id = $info['ban_id'];
		unset( $info['ban_id'] );
		$inserted_id = \IPS\Db::i()->insert( 'core_banfilters', $info );
		$this->software->app->addLink( $inserted_id, $old_id, 'core_banfilters' );
		return $inserted_id;
	}
	
	/**
	 * Convert Display Name History
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Display Name History, or FALSE on failure.
	 */
	public function convert_dname_change( $info=array() )
	{
		// not sure if we really need this?
		if ( !isset( $info['old_id'] ) )
		{
			$this->software->app->log( 'dname_change_missing_old_id', __METHOD__, \IPS\convert\App::LOG_NOTICE );
		}

		if ( !isset( $info['member_id'] ) )
		{
			$this->software->app->log( 'dname_change_missing_member_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		try
		{
			$newMemberId = $this->software->app->getLink( $info['member_id'], 'core_member' );
		}
		catch ( \OutOfRangeException $e )
		{
			$this->software->app->log( 'dname_change_not_existing_member', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}


		if ( !isset( $info['dname_previous'] ) )
		{
			$this->software->app->log( 'dname_change_missing_dname_previous', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['dname_current'] ) )
		{
			$this->software->app->log( 'dname_change_missing_dname_current', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}

		if ( !isset( $info['dname_date'] ) )
		{
			$this->software->app->log( 'dname_change_missing_dname_current', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$info['dname_date'] = time();
		}

		if ( !isset( $info['dname_ip_address'] ) )
		{
			$this->software->app->log( 'dname_change_missing_dname_ip_address', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$info['dname_ip_address'] =\IPS\Request::i()->ipAddress();
		}

		$inserted_id = \IPS\Db::i()->insert( 'core_dnames_change', array(
					'dname_member_id'	=> $newMemberId,
					'dname_date'		=> $info['dname_date'],
					'dname_ip_address'	=> $info['dname_ip_address'],
					'dname_previous'	=> $info['dname_previous'],
					'dname_current'		=> $info['dname_current'],
					'dname_discount'	=> FALSE,
				)
		);


		$this->software->app->addLink( $inserted_id, $info['old_id'], 'core_dname_change' );
		return $inserted_id;
	}
	
	/**
	 * Convert an Edit History Log
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Edit History, or FALSE on failure.
	 */
	public function convert_edit_history( $info=array() )
	{
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'edit_history_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['class'] ) OR !in_array( $info['class'], \IPS\Content::routedClasses() ) )
		{
			$this->software->app->log( 'edit_history_missing_class', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( !in_array( 'IPS\Content\EditHistory', class_implements( $info['class'] ) ) )
		{
			$this->software->app->log( 'edit_history_not_supported', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		$classname = $info['class'];
		
		if ( isset( $info['comment_id'] ) )
		{
			try
			{
				$info['comment_id'] = $this->software->app->getLink( $info['comment_id'], $classname::$databaseTable );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'edit_history_missing_comment', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'edit_history_missing_comment', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( isset( $info['member'] ) )
		{
			try
			{
				$info['member'] = $this->software->app->getLink( $info['member'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['member'] = 0;
			}
		}
		else
		{
			$info['member'] = 0;
		}
		
		if ( isset( $info['time'] ) )
		{
			if ( $info['time'] instanceof \IPS\DateTime )
			{
				$info['time'] = $info['time']->getTimestamp();
			}
		}
		else
		{
			$info['time'] = time();
		}
		
		if ( !isset( $info['old'] ) )
		{
			$this->software->app->log( 'edit_history_missing_old', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['id'] );
			$info['old'] = '';
		}
		
		if ( !isset( $info['new'] ) )
		{
			$this->software->app->log( 'edit_history_missing_new', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['id'] );
			$info['new'] = '';
		}
		
		if ( isset( $info['public'] ) )
		{
			$info['public'] = (boolean) $info['public'];
		}
		else
		{
			$info['public'] = 0;
		}
		
		if ( !isset( $info['reason'] ) )
		{
			$info['reason'] = NULL;
		}
		
		$id = $info['id'];
		unset( $info['id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_edit_history', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_edit_history' );
		return $inserted_id;
	}
	
	/**
	 * Convert an Emoticon
	 *
	 * @param	array			$info		Data to insert
	 * @param	NULL|array		$set		Set to store this emoticon in.
	 * @param	boolean			$merge		If TRUE, then if an emoticon code already exists in our database, we'll keep that one. FALSE means overwrite it.
	 * @param	NULL|string		$filepath	Path to files, or NULL if loading from the database.
	 * @param	NULL|string		$filedata	If loading from the database, the content of the Binary column.
	 * @param	NULL|string		$filepathx2	Path to the x2 size emoticon, or NULL if it doesn't exist.
	 * @param	NULL|string		$filedatax2 File Data for the x2 size emoticon, or NULL if it doesn't exist.
	 * @return	boolean|integer	The ID of the newly inserted emoticon, or FALSE on failure.
	 * @todo	Handle emoticons without a defined set.
	 */
	public function convert_emoticon( $info=array(), $set=NULL, $keepExisting=TRUE, $filepath=NULL, $filedata=NULL, $filepathx2=NULL, $filedatax2=NULL )
	{
		/* We don't really need an ID for these */
		$haveID = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'emoticon_missing_ids', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$haveID = FALSE;
		}
		
		/* We do need these, though */
		if ( !isset( $info['typed'] ) )
		{
			$this->software->app->log( 'emoticon_missing_code', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $haveID ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( $keepExisting === TRUE )
		{
			/* Do we already have an emoticon for this code? */
			try
			{
				$existing = \IPS\Db::i()->select( '*', 'core_emoticons', array( "typed=?", $info['typed'] ) )->first();
				
				if ( $haveID )
				{
					$this->software->app->addLink( $existing['id'], $info['id'], 'core_emoticons', TRUE );
				}
				
				return $existing['id'];
			}
			catch( \UnderflowException $e ) {} # lookup failed, don't do anything as it means we need to insert normally
		}
		else
		{
			/* We are using the source - we need to remove any for this typed code */
			\IPS\Db::i()->delete( 'core_emoticons', array( "typed=?", $info['typed'] ) );
		}
		
		if ( is_null( $filepath ) AND is_null( $filedata ) )
		{
			$this->software->app->log( 'emoticon_no_file', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $haveID ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( is_null( $filedata ) AND !is_null( $filepath ) )
		{
			if ( file_exists( rtrim( $filepath, '/' ) . '/' . $info['filename'] ) )
			{
				$filedata = @file_get_contents( rtrim( $filepath, '/' ) . '/' . $info['filename'] );
				$filepath = NULL;
			}
			else
			{
				$this->software->app->log( 'emoticon_no_file', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $haveID ) ? $info['id'] : NULL );
				return FALSE;
			}
		}
		
		if ( !isset( $info['filename'] ) )
		{
			$this->software->app->log( 'emoticon_no_filename', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $haveID ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['clickable'] ) )
		{
			$info['clickable'] = 1;
		}
		
		$info['emo_set']			= $set['set'];
		$info['emo_set_position']	= $set['position'];
		
		if ( \IPS\Member::loggedIn()->language()->checkKeyExists( "core_emoticon_group_{$info['emo_set']}" ) == FALSE )
		{
			\IPS\Lang::saveCustom( 'core', "core_emoticon_group_{$info['emo_set']}", $set['title'] );
		}
		
		if ( !isset( $info['emo_position'] ) )
		{
			$newPosition = (int) \IPS\Db::i()->select( 'MAX(emo_position) + 1', 'core_emoticons', array( 'emo_set=?', $set['set'] ) )->first();
			$info['emo_position'] = $newPosition;
		}
		
		$file = \IPS\File::create( 'core_Emoticons', $info['filename'], $filedata, 'emoticons' );
		unset( $info['filename'] );
		$info['image'] = (string) $file;
		$dims = $file->getImageDimensions();
		
		if ( !isset( $info['width'] ) )
		{
			$info['width'] = $dims[0];
		}
		
		if ( !isset( $info['height'] ) )
		{
			$info['height'] = $dims[1];
		}
		
		if ( isset( $info['filenamex2'] ) AND ( !is_null( $filedatax2 ) OR !is_null( $filepathx2 ) ) )
		{
			try
			{
				if ( is_null( $filedatax2 ) AND !is_null( $filepathx2 ) )
				{
					$filedatax2 = file_get_contents( rtrim( $filepathx2, '/' ) . '/' . $info['filenamex2'] );
					$filepathx2 = NULL;
				}
				$filex2 = \IPS\File::create( 'core_Emoticons', $info['filenamex2'], $filedatax2, 'emoticons' );
				unset( $info['filenamex2'] );
				$info['image_2x'] = (string) $filex2;
			}
			catch( \Exception $e )
			{
				$info['image_2x'] = NULL;
			}
			catch( \ErrorException $e )
			{
				$info['image_2x'] = NULL;
			}
		}
		
		if ( $haveID )
		{
			$id = $info['id'];
			unset( $info['id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_emoticons', $info );
		
		if ( $haveID )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_emoticons' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert Follow Data
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean			Unlike other methods, we do not need to return an ID for the converted follow - so simply return TRUE on success, or FALSE on failure.
	 * @note This method should not have an individual step, but rather be called during others (ex. when converting topics, insert any follows at that point)
	 */
	public function convert_follow( $info=array() )
	{
		if ( !isset( $info['follow_app'] ) )
		{
			$this->software->app->log( 'follow_missing_app', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !\IPS\Application::appIsEnabled( $info['follow_app'] ) )
		{
			$this->software->app->log( 'follow_app_disabed', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['follow_area'] ) )
		{
			$this->software->app->log( 'follow_missing_area', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		/* Like attachments, we need a bit more information at runtime about where we need to lookup our link reference */
		if ( !isset( $info['follow_rel_id'] ) OR !isset( $info['follow_rel_id_type'] ) )
		{
			$this->software->app->log( 'follow_missing_rel_info', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		try
		{
			$info['follow_rel_id'] = $this->software->app->getLink( $info['follow_rel_id'], $info['follow_rel_id_type'] );
			unset( $info['follow_rel_id_type'] );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'follow_missing_rel_info_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['follow_member_id'] ) )
		{
			try
			{
				$info['follow_member_id'] = $this->software->app->getLink( $info['follow_member_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'follow_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'follow_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		/* Generic Stuff */
		$info['follow_is_anon']		= ( isset( $info['follow_is_anon'] ) ) ? $info['follow_is_anon'] : 0;
		$info['follow_notify_do']	= ( isset( $info['follow_notify_do'] ) ) ? $info['follow_notify_do'] : 0;
		$info['follow_notify_meta']	= ( isset( $info['follow_notify_meta'] ) ) ? ( is_array( $info['follow_notify_meta'] ) ? json_encode( $info['follow_notify_meta'] ) : $info['follow_notify_meta'] ) : '';
		$info['follow_notify_freq']	= ( isset( $info['follow_notify_freq'] ) AND in_array( $info['follow_notify_freq'], array( 'none', 'immediate', 'daily', 'weekly' ) ) ) ? $info['follow_notify_freq'] : 'none';
		$info['follow_visible']		= ( isset( $info['follow_visible'] ) ) ? $info['follow_visible'] : 1;
		$info['follow_index_id']	= ( isset( $info['follow_index_id'] ) ) ? $info['follow_index_id'] : NULL; // @todo Figure out what this does
		
		/* DateTime Stuff */
		if ( isset( $info['follow_added'] ) )
		{
			if ( $info['follow_added'] instanceof \IPS\DateTime )
			{
				$info['follow_added'] = $info['follow_added']->getTimestamp();
			}
		}
		else
		{
			$info['follow_added'] = time();
		}
		
		if ( isset( $info['follow_notify_sent'] ) )
		{
			if ( $info['follow_notify_sent'] instanceof \IPS\DateTime )
			{
				$info['follow_notify_sent'] = $info['follow_notify_sent']->getTimestamp();
			}
		}
		else
		{
			/* We set time() here as we may not know when the last notification was sent - we don't want to send another */
			$info['follow_notify_sent'] = time();
		}
		
		/* Generate follow ID */
		$info['follow_id'] = md5( $info['follow_app'] . ';' . $info['follow_area'] . ';' . $info['follow_rel_id'] );
		
		/* Dupicate? */
		try
		{
			$dupe = \IPS\Db::i()->select( '*', 'core_follow', array( "follow_id=?", $info['follow_id'] ) )->first();
			
			if ( $dupe['follow_id'] )
			{
				return TRUE;
			}
		}
		catch( \UnderflowException $e ) {}
		
		try
		{
			\IPS\Db::i()->insert( 'core_follow', $info );
			return TRUE;
		}
		catch( \IPS\Db\Exception $e )
		{
			$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'follow_failed_insert' ), $e->getMessage() ), __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
	}
	
	/**
	 * @brief	Bitwise are protected in \IPS\Member\Group, so copying here.
	 */
	protected static $groupBitOptions =	array(
		'gbw_mod_post_unit_type'	=> 1, 			// Lift moderation after x. 1 is days, 0 is posts. Corresponds to g_mod_post_unit
		'gbw_ppd_unit_type'			=> 2, 			// Lift post-per-day limit after x. 1 is days, 0 is posts. Corresponds to g_ppd_unit
		'gbw_displayname_unit_type'	=> 4, 			// Username change restrictions. 1 is days, 0 is posts. Corresponds to g_displayname_unit
		'gbw_sig_unit_type'			=> 8, 			// Signature edit restrictions. 1 is days, 0 is posts. Corresponds to g_sig_unit
		'gbw_promote_unit_type'		=> 16, 			// Type of group promotion to use. 1 is days since joining, 0 is content count. Corresponds to g_promotion
		'gbw_no_status_update'		=> 32, 			// Can NOT post status updates
		// 64 is deprecated (previously gbw_soft_delete)
		'gbw_soft_delete_own'		=> 128, 		// Allow users of this group to hide their own submitted content
		// 256 is deprecated (previously gbw_soft_delete_own_topic)
		// 512 is deprecated (previously gbw_un_soft_delete)
		// 1024 is deprecated (previously gbw_soft_delete_see)
		// 2048 is deprecated (previously gbw_soft_delete_topic)
		// 4096 is deprecated (previously gbw_un_soft_delete_topic)
		// 8192 is deprecated (previously gbw_soft_delete_topic_see)
		// 16384 is deprecated (previously gbw_soft_delete_reason)
		// 32768 is deprecated (previously gbw_soft_delete_see_post)
		// 65536 is deprecated (previously gbw_allow_customization)
		// 131072 is deprecated (previously gbw_allow_url_bgimage)
		'gbw_allow_upload_bgimage'	=> 262144, 		// Can upload a cover photo?
		'gbw_view_reps'				=> 524288, 		// Can view who gave reputation?
		'gbw_no_status_import'		=> 1048576, 	// Can NOT import status updates from Facebook/Twitter
		'gbw_disable_tagging'		=> 2097152, 	// Can NOT use tags
		'gbw_disable_prefixes'		=> 4194304, 	// Can NOT use prefixes
		// 8388608 is deprecated (previously gbw_view_last_info)
		// 16777216 is deprecated (previously gbw_view_online_lists)
		// 33554432 is deprecated (previously gbw_hide_leaders_page)
		'gbw_pm_unblockable'		=> 67108864,	// Deprecated in favour of global unblockable setting
		'gbw_pm_override_inbox_full'=> 134217728,	// 1 means this group can send other members PMs even when that member's inbox is full
		'gbw_no_report'				=> 268435456,	// 1 means they CAN'T report content. 0 means they can.
		'gbw_cannot_be_ignored'		=> 536870912,	// 1 means they cannot be ignored. 0 means they can
		'gbw_delete_attachments'	=> 1073741824,	// 1 means they can delete attachments from the "My Attachments" screen
	);
	
	/**
	 * Convert a Member Group
	 *
	 * @param	array			$info		Data to insert
	 * @param	integer|NULL	$mergeWith	THe ID of the group to merge this one into, or NULL to create new.
	 * @param	string|NULL		$iconpath	Path to Icon file for the group, or NULL.
	 * @param	string|NULL		$icondata	Binary Icon Data, or NULL.
	 * @return	boolean|integer	The ID of the newly inserted group, or FALSE on failure.
	 * @note	Other libraries will handle their own settings
	 */
	public function convert_group( $info=array(), $mergeWith=NULL, $iconpath=NULL, $icondata=NULL )
	{
		if ( !isset( $info['g_id'] ) )
		{
			$this->software->app->log( 'group_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		/* Are we merging this group with an existing one? Saves a lot of headache later */
		if ( !is_null( $mergeWith ) )
		{
			$this->software->app->addLink( $mergeWith, $info['g_id'], 'core_groups', TRUE );
			return $mergeWith;
		}
				
		/* Do specialty stuff first, then we can just go over generic stuff later */
		if ( !isset( $info['g_name'] ) )
		{
			$name = "Untitled Group {$info['g_id']}";
			$this->software->app->log( 'group_missing_name', __METHOD__, \IPS\convert\App::LOG_NOTICE );
		}
		else
		{
			$name = $info['g_name'];
			unset( $info['g_name'] );
		}
		
		if ( isset( $info['g_icon'] ) AND ( !is_null( $iconpath ) OR !is_null( $icondata ) ) )
		{
			try
			{
				if ( is_null( $icondata ) AND !is_null( $iconpath ) )
				{
					$icondata = file_get_contents( $iconpath );
					$iconpath = NULL;
				}
				$file = \IPS\File::create( 'core_Theme', $info['g_icon'], $icondata );
				$info['g_icon'] = (string) $file;
			}
			catch( \Exception $e )
			{
				$info['g_icon'] = NULL;
			}
			catch( \ErrorException $e )
			{
				$info['g_icon'] = NULL;
			}
		}
		else
		{
			$info['g_icon'] = NULL;
		}
		
		if ( isset( $info['g_promotion'] ) )
		{
			if ( is_array( $info['g_promotion'] ) )
			{
				try
				{
					$group					= $this->software->app->getLink( $info['g_promotion'][0], 'core_groups' );
					$promotion				= $group . '&' . $info['g_promotion'][1];
					$info['g_promotion']	= $promotion;
				}
				catch( \OutOfRangeException $e )
				{
					/* Not much we can do here... */
					$info['g_promotion'] = '-1&-1';
				}
			}
			else
			{
				try
				{
					$promotion					= explode( '&', $info['g_promotion'] );
					$group						= $this->software->app->getLink( $promotion[0], 'core_groups' );
					$info['g_promotion']		= $group . '&' . $promotion[1];
				}
				catch( \OutOfRangeException $e )
				{
					$info['g_promotion'] = '-1&-1';
				}
			}
		}
		else
		{
			$info['g_promotion'] = '-1&-1';
		}
		
		if ( isset( $info['g_photo_max_vars'] ) )
		{
			if ( is_array( $info['g_photo_max_vars'] ) )
			{
				$info['g_photo_max_vars'] = implode( ':', $info['g_photo_max_vars'] );
			}
		}
		else
		{
			$info['g_photo_max_vars'] = '500:170:170';
		}
		
		if ( isset( $info['g_signature_limits'] ) )
		{
			if ( is_array( $info['g_signature_limits'] ) )
			{
				$info['g_signature_limits'] = implode( ':', $info['g_signature_limits'] );
			}
		}
		else
		{
			$info['g_signature_limits'] = '0:::::';
		}
		
		$bitoptions = 0;
		if ( isset( $info['g_bitoptions'] ) AND is_array( $info['g_bitoptions'] ) )
		{
			foreach( static::$groupBitOptions AS $key => $value )
			{
				if ( isset( $info['g_bitoptions'][$key] ) AND $info['g_bitoptions'][$key] == TRUE )
				{
					$bitoptions += $value;
				}
			}
		}
		$info['g_bitoptions'] = $bitoptions;
		
		/* Now let's do generic stuff - start with things that should be 1 as default for our default members group */
		foreach( array( 'g_view_board', 'g_mem_info', 'g_use_search', 'g_edit_profile', 'g_edit_posts', 'g_use_pm', 'g_can_msg_attach', 'g_dname_date', 'g_pm_flood_mins', 'g_post_polls', 'g_vote_polls' ) AS $oneIsDefault )
		{
			if ( !isset( $info[$oneIsDefault] ) )
			{
				$info[$oneIsDefault] = 1;
			}
		}
		
		/* Now let's do 0 defaults */
		foreach( array( 'g_delete_own_posts', 'g_is_supmod', 'g_access_cp', 'g_append_edit', 'g_access_offline', 'g_avoid_q', 'g_avoid_flood', 'g_max_messages', 'g_dohtml', 'g_bypass_badwords', 'g_attach_per_post', 'g_dname_changes', 'g_mod_preview', 'g_hide_online_list', 'g_mod_post_unit', 'g_ppd_limit', 'g_ppd_unit', 'g_displayname_unit', 'g_sig_unit', 'g_topic_rate_setting' ) AS $zeroIsDefault )
		{
			if ( !isset( $info[$zeroIsDefault] ) )
			{
				$info[$zeroIsDefault] = 0;
			}
		}
		
		/* -1 Defaults */
		foreach( array( 'g_attach_max', 'g_max_bgimg_upload' ) AS $negOneDefault )
		{
			if ( !isset( $info[$negOneDefault] ) )
			{
				$info[$negOneDefault] = -1;
			}
		}
		
		/* Other defaults */
		foreach( array( 'prefix', 'suffix', 'g_max_mass_pm', 'g_search_flood', 'g_edit_cutoff', 'g_rep_max_positive', 'g_rep_max_negative', 'g_pm_perday' ) AS $otherDefault )
		{
			if ( !isset( $info[$otherDefault] ) )
			{
				switch( $otherDefault )
				{
					case 'prefix':
					case 'suffix':
						$info[$otherDefault] = '';
					break;
					
					case 'g_max_mass_pm':
						$info[$otherDefault] = 10;
					break;
					
					case 'g_search_flood':
						$info[$otherDefault] = 3;
					break;
					
					case 'g_edit_cutoff':
						$info[$otherDefault] = 5;
					break;
					
					case 'g_rep_max_positive':
					case 'g_rep_max_negative':
						$info[$otherDefault] = 10;
					break;
				}
			}
		}
		
		/* Unlike the other apps, we do Chat here as we can't really convert anything else from it */
		if ( \IPS\Application::appisEnabled( 'chat' ) )
		{
			if ( !isset( $info['chat_access'] ) )
			{
				$info['chat_access'] = 1;
			}
			
			if ( !isset( $info['chat_moderate'] ) )
			{
				$info['chat_moderate'] = 0;
			}
			
			if ( !isset( $info['chat_private'] ) )
			{
				$info['chat_private'] = 0;
			}
		}
		else
		{
			/* Unset them to avoid DB errors */
			unset( $info['chat_access'], $info['chat_moderate'], $info['chat_private'] );
		}
		
		/* Save it! */
		$id = $info['g_id'];
		unset( $info['g_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_groups', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_groups' );
		\IPS\Lang::saveCustom( 'core', "core_group_{$inserted_id}", $name );
		return $inserted_id;
	}
	
	/**
	 * Convert an Ignored User
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Ignore, or FALSE on error.
	 */
	public function convert_ignored_user( $info=array() )
	{
		if ( !isset( $info['ignore_id'] ) )
		{
			$this->software->app->log( 'ignored_user_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['ignore_owner_id'] ) )
		{
			try
			{
				$owner = $this->software->app->getLink( $info['ignore_owner_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'ignored_user_missing_owner', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'ignored_user_missing_owner', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['ignore_ignore_id'] ) )
		{
			try
			{
				$ignore = $this->software->app->getLink( $info['ignore_ignore_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'ignored_user_missing_ignore', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'ignored_user_missing_ignore', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		$obj = new \IPS\core\Ignore;
		$obj->owner_id = $owner;
		$obj->ignore_id = $ignore;
		
		foreach( \IPS\core\Ignore::types() AS $type )
		{
			/* If the type is set and evaluates to true, then set to 1, otherwise 0. */
			$obj->$type = ( isset( $info['ignore_' . $type] ) AND $info['ignore_' . $type] ) ? 1 : 0;
		}
		
		$obj->save();
		$this->software->app->addLink( $obj->id, $info['ignore_id'], 'core_ignored_users' );
		return $obj->id;
	}
	
	/**
	 * Convert a Staff Directory Entry
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Leader, or FALSE on failure.
	 */
	public function convert_leader( $info=array() )
	{
		if ( !isset( $info['leader_id'] ) )
		{
			$this->software->app->log( 'leader_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['leader_type'] ) )
		{
			$this->software->app->log( 'leader_missing_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['leader_type_id'] ) )
		{
			$this->software->app->log( 'leader_missing_type_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		switch( $info['leader_type'] )
		{
			case 'g':
			case 'group':
				try
				{
					$group = $this->software->app->getLink( $info['leader_type_id'], 'core_groups' );
				}
				catch( \OutOfRangeException $e )
				{
					$this->software->app->log( 'leader_missing_group_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
					return FALSE;
				}
				
				$info['leader_type']	= 'g';
				$info['leader_type_id']	= $group;
			break;
			
			case 'm':
			case 'members':
				try
				{
					$member = $this->software->app->getLink( $info['leader_type_id'], 'core_members' );
				}
				catch( \OutOfRangeException $e )
				{
					$this->software->app->log( 'leader_missing_member_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
					return FALSE;
				}
				
				$info['leader_type']	= 'm';
				$info['leader_type_id']	= $member;
			break;
		}
		
		/* This is technically missing required data, however we can create a new parent object in this instance, and avoid data loss */
		try
		{
			if ( !isset( $info['leader_group_id'] ) )
			{
				/* If it's not set throw an exception to try and trigger orphan parent detection */
				throw new \DomainException;
			}
			
			$info['leader_group_id'] = $this->software->app->getLink( $info['leader_group_id'], 'core_leader_groups' );
		}
		catch( \LogicException $e ) /* LogicException here so as to accommodate for catching both OutOfRangeException and DomainException - we don't care which was thrown */
		{
			try
			{
				/* Have we already created an orphaned row storage container? */
				$info['leader_group_id'] = $this->software->app->getLink( '__orphan__', 'core_leader_groups' );
			}
			catch( \OutOfRangeException $e )
			{
				/* If we are creating, we should do it in the sense that we are converting so we can use it later on */
				$info['leader_group_id'] = $this->convert_leader_group( array(
					'group_id'		=> '__orphan__',
					'group_name'	=> 'Staff',
				) );
			}
		}
		
		if ( !isset( $info['leader_position'] ) )
		{
			$info['leader_position'] = 1;
		}
		
		$obj			= new \IPS\core\StaffDirectory\User;
		$obj->type		= $info['leader_type'];
		$obj->type_id	= $info['leader_type_id'];
		$obj->group_id	= $info['leader_group_id'];
		$obj->position	= $info['leader_position'];
		$obj->save();
		
		$this->software->app->addLink( $obj->id, $info['leader_id'], 'core_leaders' );
		return $obj->id;
	}
	
	/**
	 * Convert a Staff Directory Group
	 *
	 * @pararm	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Staff Directory Group, or FALSE on failure.
	 */
	public function convert_leader_group( $info=array() )
	{
		if ( !isset( $info['group_id'] ) )
		{
			$this->software->app->log( 'leader_group_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['group_name'] ) )
		{
			$this->software->app->log( 'leader_group_missing_name', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$info['group_name'] = 'Untitled Group';
		}
		
		if ( !isset( $info['group_template'] ) )
		{
			$info['group_template'] = 'layout_full';
		}
		
		if ( !isset( $info['group_position'] ) )
		{
			$info['group_position'] = 1;
		}
		
		$obj = new \IPS\core\StaffDirectory\Group;
		$obj->name		= $info['group_name'];
		$obj->template	= $info['group_template'];
		$obj->position	= $info['group_position'];
		$obj->save();
		
		$this->software->app->addLink( $obj->id, $info['group_id'], 'core_leader_groups' );
		return $obj->id;
	}
	
	/**
	 * Convert a Member Rank
	 *
	 * @param	array			$info		Data to insert
	 * @param	string|NULL		$iconpath	Icon File Path, or NULL
	 * @param	string|NULL		$icondata	Icon File Data, or NULL
	 * @return	boolean|integer	The ID of the newly inserted Rank, or FALSE on failure.
	 */
	public function convert_rank( $info=array(), $iconpath=NULL, $icondata=NULL )
	{
		/* We don't really need an ID for these, so if one isn't specified, then that's okay. */
		$hasId = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$hasId = FALSE;
			$this->software->app->log( 'rank_missing_ids', __METHOD__, \IPS\convert\App::LOG_NOTICE );
		}
		
		/* We do need this, though. */
		if ( !isset( $info['title'] ) )
		{
			$this->software->app->log( 'rank_missing_title', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			return FALSE;
		}
		
		if ( !isset( $info['posts'] ) )
		{
			$info['posts'] = 0;
		}
		
		if ( !isset( $info['pips'] ) )
		{
			$info['pips'] = 0;
		}
		
		if ( isset( $info['icon'] ) AND ( !is_null( $iconpath ) OR !is_null( $icondata ) ) )
		{
			try
			{
				if ( is_null( $icondata ) AND !is_null( $iconpath ) )
				{
					$icondata = file_get_contents( $iconpath );
				}
				$file				= \IPS\File::create( 'core_Theme', $info['icon'], $icondata );
				$info['icon']		= (string) $file;
				$info['use_icon']	= 1;
			}
			catch( \Exception $e )
			{
				$info['icon']		= '';
				$info['use_icon']	= 0;
			}
			catch( \ErrorException $e )
			{
				$info['icon']		= '';
				$info['use_icon']	= 0;
			}
		}
		else
		{
			$info['icon']		= '';
			$info['use_icon']	= 0;
		}
		
		if ( $hasId )
		{
			$id = $info['id'];
			unset( $info['id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_member_ranks', $info );
		\IPS\Lang::saveCustom( 'core', "core_member_rank_{$inserted_id}", $info['title'] );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_member_ranks' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Status Reply
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Status Reply, or FALSE on failure.
	 */
	public function convert_status_reply( $info=array() )
	{
		if ( !isset( $info['reply_id'] ) )
		{
			$this->software->app->log( 'status_reply_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['reply_status_id'] ) )
		{
			try
			{
				$info['reply_status_id'] = $this->software->app->getLink( $info['reply_status_id'], 'core_member_status_updates' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'status_reply_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['reply_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'status_reply_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['reply_id'] );
			return FALSE;
		}
		
		if ( isset( $info['reply_member_id'] ) )
		{
			try
			{
				$info['reply_member_id'] = $this->software->app->getLink( $info['reply_member_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'status_reply_no_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['reply_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'status_reply_no_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['reply_id'] );
			return FALSE;
		}
		
		if ( isset( $info['reply_date'] ) )
		{
			if ( $info['reply_date'] instanceof \IPS\DateTime )
			{
				$info['reply_date'] = $info['reply_date']->getTimestamp();
			}
		}
		else
		{
			$info['reply_date'] = time();
		}
		
		if ( empty( $info['reply_content'] ) )
		{
			$this->software->app->log( 'status_reply_no_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['reply_id'] );
			return FALSe;
		}
		
		if ( !isset( $info['reply_approved'] ) )
		{
			$info['reply_approved'] = 1;
		}
		
		if ( !isset( $info['reply_ip_address'] ) OR filter_var( $info['reply_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['reply_ip_address'] = '127.0.0.1';
		}
		
		$id = $info['reply_id'];
		unset( $info['reply_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_member_status_replies', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_member_status_replies' );
		return $inserted_id;
	}
	
	/**
	 * Convert a Status Update
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted Status Update, or FALSE on failure.
	 */
	public function convert_status( $info=array() )
	{
		if ( !isset( $info['status_id'] ) )
		{
			$this->software->app->log( 'status_update_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['status_member_id'] ) )
		{
			try
			{
				$info['status_member_id'] = $this->software->app->getLink( $info['status_member_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'status_update_no_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['status_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'status_update_no_member', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['status_id'] );
			return FALSE;
		}
		
		if ( isset( $info['status_date'] ) )
		{
			if ( $info['status_date'] instanceof \IPS\DateTime )
			{
				$info['status_date'] = $info['status_date']->getTimestamp();
			}
		}
		else
		{
			$info['status_date'] = time();
		}
		
		if ( empty( $info['status_content'] ) )
		{
			$this->software->app->log( 'status_update_no_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['status_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['status_replies'] ) )
		{
			/* Converter will indicate that these need recounted */
			$info['status_replies'] = 0;
		}
		
		/* No longer used */
		$info['status_last_ids']	= NULL;
		$info['status_is_latest']	= 0;
		$info['status_hash']		= '';
		
		if ( !isset( $info['status_is_locked'] ) )
		{
			$info['status_is_locked'] = 0;
		}
		
		if ( !isset( $info['status_imported'] ) )
		{
			$info['status_imported'] = 0;
		}
		
		if ( isset( $info['status_author_id'] ) )
		{
			try
			{
				$info['status_author_id'] = $this->software->app->getLink( $info['status_author_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'status_update_no_author', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['status_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'status_update_no_author', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['status_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['status_author_ip'] ) OR filter_var( $info['status_author_ip'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['status_author_ip'] = '127.0.0.1';
		}
		
		if ( !isset( $info['status_approved'] ) )
		{
			$info['status_approved'] = 1;
		}
		
		$id = $info['status_id'];
		unset( $info['status_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_member_status_updates', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_member_status_updates' );
		return $inserted_id;
	}
	
	/**
	 * Convert a Member
	 *
	 * @param	array			$info				Data to insert
	 * @param	NULL|string		$profilePhotoName	Filename for the users Profile Photo or NULL if none set.
	 * @param	NULL|string		$profilePhotoPath	Path to Profile Photos, or NULL to load from the database.
	 * @param	NULL|string		$profileFileData	If loading from the database, the filedata for the profile photo from the Binary column.
	 * @param	NULL|string		$coverPhotoName		Filename for the users Cover Photo or NULL if none set.
	 * @param	NULL|string		$coverPhotoPath		Path to Cover Photos, or NULL to load from the database.
	 * @param	NULL|string		$coverFileData		If loading from the database, the filedatafor the cover photo from the Binary column.
	 * @return	boolean|integer	The ID of the newly inserted member, or FALSE on failure.
	 * @todo	Work out if we can convert Social Login Stuff.
	 * @todo	Password Stuff
	 */
	public function convert_member( $info=array(), $profileFields=array(), $profilePhotoName=NULL, $profilePhotoPath=NULL, $profileFileData=NULL, $coverPhotoName=NULL, $coverPhotoPath=NULL, $coverFileData=NULL )
	{
		if ( !isset( $info['member_id'] ) )
		{
			$this->software->app->log( 'member_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['email'] ) OR filter_var( $info['email'], FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			/* No email, or it is invalid */
			$newEmail = time() . '@example.com';
			$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_email_invalid' ), $info['email'], $newEmail ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
			$info['email'] = $newEmail;
		}
		else
		{
			/* If this email is already in use, we need to merge the two members. Queue Tasks later will handle synchronize stuffs */
			$memberToTest = \IPS\Member::load( $info['email'], 'email' );
			
			if ( $memberToTest->member_id )
			{
				$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_exists' ), $info['name'], $memberToTest->name ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
				$this->software->app->addLink( $memberToTest->member_id, $info['member_id'], 'core_members', TRUE );
				return $memberToTest->member_id;
			}
		}
		
		/* This is required, but we can generate a default value from the Member ID */
		if ( !isset( $info['name'] ) )
		{
			$info['name'] = "User_{$info['member_id']}";
			$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_missing_name' ), $info['name'] ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
		}
		else
		{
			/* If the username is longer than our defined length, then cut it down to fit rather than not converting */
			if ( mb_strlen( $info['name'] ) > \IPS\Settings::i()->max_user_name_length )
			{
				$newName = mb_substr( $info['name'], 0, \IPS\Settings::i()->max_user_name_length );
				$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_name_too_long' ), $info['name'], $newName ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
				$info['name'] = $newName;
			}
			
			/* Is it using any blocked characters? */
			if ( \IPS\Settings::i()->username_characters AND !preg_match( '/^[' . str_replace( '\-', '-', preg_quote( \IPS\Settings::i()->username_characters, '/' ) ) . ']*$/i', $info['name'] ) )
			{
				$newName = preg_replace( '/^[' . str_replace( '\-', '-', preg_quote( \IPS\Settings::i()->username_characters, '/' ) ), '', $info['name'] );
				$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_name_invalid_chars' ), $info['name'], $newName ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
				$info['name'] = $newName;
			}
			
			/* Finally, check it's not in use */
			$memberToTest = \IPS\Member::load( $info['name'], 'name' );
			
			if ( $memberToTest->member_id )
			{
				/* It is... add a tiemstamp on the end */
				$newName = $info['name'] . time();
				$this->software->app->log( sprintf( \IPS\Member::loggedIn()->language()->get( 'member_name_in_use' ), $info['name'], $newName ), __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
				$info['name'] = $newName;
			}
			
			unset( $memberToTest );
		}
		
		/* Work out passwords */
		if ( isset( $info['password'] ) )
		{
			/* This is a password that is still hashed per the source */
			$info['conv_password'] = $info['password'];
			unset( $info['password'] );
			
			if ( isset( $info['password_extra'] ) ) # salts and such
			{
				$info['conv_password_extra'] = $info['password_extra'];
				unset( $info['password_extra'] );
			}
		}
		else if ( isset( $info['md5_password'] ) )
		{
			/* This is a simple md5 hashed password - we can convert this to the 3.x format which will be picked up by the internal login handler later and converted */
			$salt = '';

			for ( $i = 0; $i < 5; $i++ )
			{
				$num   = mt_rand(33, 126);

				if ( $num == '92' )
				{
					$num = 93;
				}

				$salt .= chr( $num );
			}
			$info['members_pass_hash'] = md5( md5( $salt ) . $info['md5_password'] );
			$info['members_pass_salt'] = $salt;
			unset( $info['md5_password'] );
		}
		else if ( isset( $info['plain_password'] ) )
		{
			/* This is just a plaintext password, so we can just store it normally... I haven't decided if I really want to do this here or later as its intentionally slow */
			$salt = ''; # generateSalt() is not a static method in \IPS\Member
			for ( $i=0; $i<22; $i++ )
			{
				do
				{
					$chr = rand( 48, 122 );
				}
				while ( in_array( $chr, range( 58,  64 ) ) or in_array( $chr, range( 91,  96 ) ) );
				
				$salt .= chr( $chr );
			}
			$info['members_pass_hash'] = crypt( $info['plain_password'], '$2a$13$' . $salt );
			$info['members_pass_salt'] = $salt;
			unset( $info['plain_password'] );
		}
		else
		{
			/* No need to do anything if we're coming from IPS4 */
			if ( !isset( $info['members_pass_hash'] ) OR !isset( $info['members_pass_salt'] ) )
			{
				$info['members_pass_hash'] = NULL;
				$info['members_pass_salt'] = NULL;
			}
		}
		
		$group = \IPS\Settings::i()->member_group;

		if ( isset( $info['ips_group_id'] ) )
		{
			$group = $info['ips_group_id'];
			unset( $info['ips_group_id'] );
		}
		elseif ( isset( $info['member_group_id'] ) )
		{
			try
			{
				$group = $this->software->app->getLink( $info['member_group_id'], 'core_groups' );
			}
			catch( \OutOfRangeException $e ) {}
		}
		
		$info['member_group_id'] = $group;
		
		/* If no join date specified, just use current time */
		if ( isset( $info['joined'] ) )
		{
			if ( $info['joined'] instanceof \IPS\DateTime )
			{
				$info['joined'] = $info['joined']->getTimestamp();
			}
		}
		else
		{
			$info['joined'] = time();
		}
		
		/* If no IP Address specified, just use a generic value */
		if ( !isset( $info['ip_address'] ) OR filter_var( $info['ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['ip_address'] = '127.0.0.1';
		}
		
		/* We cannot convert themes - make sure this value is NULL */
		$info['skin'] = NULL;
		
		if ( !isset( $info['warn_level'] ) )
		{
			$info['warn_level'] = NULL;
		}
		
		if ( isset( $info['warn_lastwarn'] ) )
		{
			if ( $info['warn_lastwarn'] instanceof \IPS\DateTime )
			{
				$info['warn_lastwarn'] = $info['warn_lastwarn']->getTimestamp();
			}
		}
		else
		{
			$info['warn_lastwarn'] = 0;
		}
		
		/* Cannot convert languages - set to default */
		$info['language'] = \IPS\Lang::defaultLanguage();
		
		if ( isset( $info['restrict_post'] ) )
		{
			if ( $info['restrict_post'] instanceof \IPS\DateTime )
			{
				$info['restrict_post'] = $info['restrict_post']->getTimestamp();
			}
		}
		else
		{
			$info['restrict_post'] = 0;
		}
		
		if ( !isset( $info['bday_day'] ) )
		{
			$info['bday_day'] = NULL;
		}
		
		if ( !isset( $info['bday_month'] ) )
		{
			$info['bday_month'] = NULL;
		}
		
		if ( !isset( $info['bday_year'] ) )
		{
			$info['bday_year'] = NULL;
		}
		
		if ( !isset( $info['msg_count_new'] ) )
		{
			$info['msg_count_new'] = 0;
		}
		
		if ( !isset( $info['msg_count_total'] ) )
		{
			$info['msg_count_total'] = 0;
		}
		
		if ( !isset( $info['msg_count_reset'] ) )
		{
			$info['msg_count_reset'] = 0;
		}
		
		if ( !isset( $info['msg_show_notification'] ) )
		{
			$info['msg_show_notification'] = 0;
		}
		
		if ( isset( $info['last_visit'] ) )
		{
			if ( $info['last_visit'] instanceof \IPS\DateTime )
			{
				$info['last_visit'] = $info['last_visit']->getTimestamp();
			}
		}
		else
		{
			$info['last_visit'] = time();
		}
		
		if ( isset( $info['last_activity'] ) )
		{
			if ( $info['last_activity'] instanceof \IPS\DateTime )
			{
				$info['last_activity'] = $info['last_activity']->getTimestamp();
			}
		}
		else
		{
			$info['last_activity'] = time();
		}
		
		if ( isset( $info['mod_posts'] ) )
		{
			if ( $info['mod_posts'] instanceof \IPS\DateTime )
			{
				$info['mod_posts'] = $info['mod_posts']->getTimestamp();
			}
		}
		else
		{
			$info['mod_posts'] = 0;
		}
		
		if ( isset( $info['auto_track'] ) )
		{
			$autoTrack = array( 'content' => 0, 'comments' => 0, 'method' => 'none' );
			
			/* Is it JSON or an array? */
			if ( !is_array( $info['auto_track'] ) AND $autoTrackJson = @json_decode( $info['auto_track'], TRUE ) )
			{
				$info['auto_track'] = $autoTrackJson;
			}
			
			if ( is_array( $info['auto_track'] ) )
			{
				/* Make sure everything is valid */
				if ( isset( $info['auto_track']['content'] ) )
				{
					$autoTrack['content'] = intval( $info['auto_track']['content'] );
				}
				
				if ( isset( $info['auto_track']['comments'] ) )
				{
					$autoTrack['comments'] = intval( $info['auto_track']['comments'] );
				}
				
				if ( isset( $info['auto_track']['method'] ) AND in_array( $info['auto_track']['method'], array( 'immediate', 'daily', 'weekly' ) ) )
				{
					$autoTrack['method'] = $info['auto_track']['method'];
				}
			}
			
			/* Encode and store */
			$info['auto_track'] = json_encode( $autoTrack );
		}
		else
		{
			/* Interestingly, this is 0 by default */
			$info['auto_track'] = 0;
		}
		
		if ( isset( $info['temp_ban'] ) )
		{
			if ( $info['temp_ban'] instanceof \IPS\DateTime )
			{
				$info['temp_ban'] = $info['temp_ban']->getTimestamp();
			}
		}
		else
		{
			$info['temp_ban'] = 0;
		}
		
		if ( isset( $info['mgroup_others'] ) AND !empty( $info['mgroup_others'] ) )
		{
			$newGroups = array();
			/* Just one? */
			if ( is_numeric( $info['mgroup_others'] ) )
			{
				try
				{
					$newGroups[] = $this->software->app->getLink( $info['mgroup_others'], 'core_groups' );
				}
				catch( \OutOfRangeException $e ) {}
			}
			/* An array? */
			else if ( is_array( $info['mgroup_others'] ) )
			{
				if ( count( $info['mgroup_others'] ) )
				{
					foreach( $info['mgroup_others'] AS $group )
					{
						try
						{
							$newGroups[] = $this->software->app->getLink( $group, 'core_groups' );
						}
						catch( \OutOfRangeException $e ) {}
					}
				}
			}
			/* Comma delimited list? */
			else if ( mb_strstr( $info['mgroup_others'], ',' ) )
			{
				$groups = explode( ',', $info['mgroup_others'] );
				if ( count( $groups ) )
				{
					foreach( $groups AS $group )
					{
						try
						{
							$newGroups[] = $this->software->app->getLink( $group, 'core_groups' );
						}
						catch( \OutOfRangeException $e ) {}
					}
				}
			}
			
			if ( count( $newGroups ) )
			{
				$info['mgroup_others'] = implode( ',', $newGroups );
			}
			else
			{
				$info['mgroup_others'] = '';
			}
		}
		else
		{
			$info['mgroup_others'] = '';
		}
		
		/* Some generic stuff for uniformity */
		$info['member_login_key']			= md5( uniqid() );
		$info['member_login_key_expire']	= \IPS\DateTime::create()->add( new \DateInterval( 'P7D' ) )->getTimestamp();
		$info['members_seo_name']			= \IPS\Http\Url::seoTitle( $info['name'] );
		$info['members_cache']				= NULL;
		$info['failed_logins']				= json_encode( array() );
		$info['failed_login_count']			= 0;
		
		if ( !isset( $info['members_profile_views'] ) )
		{
			$info['members_profile_views'] = 0;
		}

		$bitoptions = 0;
		if ( isset( $info['members_bitoptions'] ) AND is_array( $info['members_bitoptions'] ) )
		{
			foreach( \IPS\Member::$bitOptions['members_bitoptions']['members_bitoptions'] AS $key => $value )
			{
				if ( isset( $info['members_bitoptions'][$key] ) AND $info['members_bitoptions'][$key] == TRUE )
				{
					$bitoptions += $value;
				}
			}
		}
		$info['members_bitoptions'] = $bitoptions;
		
		$bitoptions2 = 0;
		if ( isset( $info['members_bitoptions2'] ) AND is_array( $info['members_bitoptions2'] ) )
		{
			foreach( \IPS\Member::$bitOptions['members_bitoptions']['members_bitoptions2'] AS $key => $value )
			{
				if ( isset( $info['members_bitoptions2'][$key] ) AND $info['members_bitoptions2'] == TRUE )
				{
					$bitoptions2 += $value;
				}
			}
		}
		$info['members_bitoptions2'] = $bitoptions2;
		
		/* These are no longer used */
		$info['fb_bwoptions'] = 0;
		$info['tc_bwoptions'] = 0;
		
		/* We won't know these */
		$info['members_day_posts']	= '0,0';
		$info['notification_cnt']	= 0;
		$info['ipsconnect_id']		= 0;
		
		if ( isset( $info['pp_last_visitors'] ) )
		{
			if ( !is_array( $info['pp_last_visitors'] ) )
			{
				$info['pp_last_visitors'] = @json_decode( $info['pp_last_visitors'], TRUE );
			}
			
			if ( count( $info['pp_last_visitors'] ) )
			{
				$newVisitors = array();
				$count = 0;
				foreach( $info['pp_last_visitors'] AS $memberId => $date )
				{
					try
					{
						if ( $count > 5 )
						{
							throw new \OutOfRangeException;
						}
						$newVisitorId = $this->software->app->getLink( $memberId, 'core_members' )->first();
						
						if ( $date instanceof \IPS\DateTime )
						{
							$date = $date->getTimestamp();
						}
						
						$newVisitors[$newVisitorId] = $date;
						
						$count++;
					}
					catch( \OutOfRangeException $e )
					{
						continue;
					}
				}
				
				if ( count( $newVisitors ) )
				{
					$info['pp_last_visitors'] = json_encode( $newVisitors );
				}
			}
			else
			{
				$info['pp_last_visitors'] = NULL;
			}
		}
		else
		{
			$info['pp_last_visitors'] = NULL;
		}
		
		/* We need to make sure this is not set as we need it to be something specific later */
		unset( $info['pp_photo_type'], $info['pp_main_photo'], $info['pp_thumb_photo'] );
		
		/* Profile Photos! */
		if ( !is_null( $profilePhotoName ) AND ( !is_null( $profilePhotoPath ) OR !is_null( $profileFileData ) ) )
		{
			/* <3 IPS4 */
			try
			{
				if ( is_null( $profileFileData ) AND !is_null( $profilePhotoPath ) )
				{
					$profileFileData = file_get_contents( rtrim( $profilePhotoPath, '/' ) . '/' . $profilePhotoName );
				}
				
				$photo						= \IPS\File::create( 'core_Profile', $profilePhotoName, $profileFileData );
				$info['pp_photo_type']		= 'custom';
				$info['pp_main_photo']		= (string) $photo;
				$info['pp_thumb_photo']		= (string) $photo->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE );
			}
			catch( \Exception $e )
			{
				$info['pp_photo_type']	= '';
				$info['pp_main_photo']	= NULL;
				$info['pp_thumb_photo']	= NULL;
			}
			catch( \ErrorException $e )
			{
				$info['pp_photo_type']	= '';
				$info['pp_main_photo']	= NULL;
				$info['pp_thumb_photo']	= NULL;
			}
		}
		else
		{
			$info['pp_photo_type']		= '';
			$info['pp_main_photo']		= NULL;
			$info['pp_thumb_photo']		= NULL;
		}
		
		/* Cover Photos! */
		if ( !is_null( $coverPhotoName ) AND ( !is_null( $coverPhotoPath ) OR !is_null( $coverFileData ) ) )
		{
			try
			{
				if ( is_null( $coverFileData ) AND !is_null( $coverPhotoPath ) )
				{
					$coverFileData = file_get_contents( rtrim( $coverPhotoPath, '/' ) .'/' . $coverPhotoName );
				}
				$cover						= \IPS\File::create( 'core_Profile', $coverPhotoName, $coverFileData );
				$info['pp_cover_photo']		= (string) $cover;
				$info['pp_cover_offset']	= 0; /* Designs are different so this will never likely be correct */
			}
			catch( \Exception $e )
			{
				$info['pp_cover_photo']		= '';
				$info['pp_cover_offset']	= 0;
			}
			catch( \ErrorException $e )
			{
				$info['pp_cover_photo']		= '';
				$info['pp_cover_offset']	= 0;
			}
		}
		
		if ( empty( $info['pp_photo_type'] ) AND isset( $info['pp_gravatar'] ) )
		{
			/* Make sure email is valid */
			if ( filter_var( $info['pp_gravatar'], FILTER_VALIDATE_EMAIL ) !== FALSE )
			{
				$info['pp_photo_type'] = 'gravatar';
				if ( $info['pp_gravatar'] == $info['email'] )
				{
					$info['pp_gravatar'] = NULL;
				}
			}
			else
			{
				$info['pp_photo_type']	= '';
				$info['pp_gravatar']	= NULL;
			}
		}
		
		if ( !isset( $info['pp_setting_count_comments'] ) )
		{
			$info['pp_setting_count_comments'] = 0;
		}
		
		if ( !isset( $info['pp_reputation_points'] ) )
		{
			$info['pp_reputation_points'] = 0;
		}
		
		if ( !isset( $info['signature'] ) )
		{
			$info['signature'] = NULL;
		}
		
		if ( isset( $info['pconversation_filters'] ) )
		{
			/* We don't really need to do much here - just if it's an array, encode it */
			if ( is_array( $info['pconversation_filters'] ) )
			{
				$info['pconversation_filters'] = json_encode( $info['pconversation_filters'] );
			}
		}
		else
		{
			$info['pconversation_filters'] = NULL;
		}
		
		/* No Longer Used */
		$info['pp_customization'] = NULL;
		
		if ( isset( $info['timezone'] ) )
		{
			if ( $info['timezone'] instanceof \DateTimeZone )
			{
				$info['timezone'] = $info['timezone']->getName();
			}
			else
			{
				if ( ( $info['timezone'] = @timezone_name_from_abbr( NULL, $info['timezone'] ) ) === FALSE )
				{
					$info['timezone'] = 'UTC';
				}
			}
		}
		else
		{
			$info['timezone'] = 'UTC';
		}
		
		if ( isset( $info['allow_admin_mails'] ) )
		{
			$info['allow_admin_mails'] = (boolean) $info['allow_admin_mails'];
		}
		else
		{
			$info['allow_admin_mails'] = FALSE;
		}
		
		$info['ipsconnect_revalidate_url'] = NULL;
		
		if ( !isset( $info['members_disable_pm'] ) )
		{
			$info['members_disable_pm'] = 0;
		}
		
		$info['marked_site_read']	= time();
		$info['acp_skin']			= NULL;
		$info['acp_language']		= \IPS\Lang::defaultLanguage();
		
		if ( !isset( $info['member_title'] ) )
		{
			$info['member_title'] = NULL;
		}

		/* Check length of supplied title */
		if( $info['member_title'] AND ( mb_strlen( $info['member_title'] ) > 64 ) )
		{
			$this->software->app->log( 'members_title_truncated', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['member_id'] );
			$info['member_title'] = mb_substr( $info['member_title'], 0, 64 );
		}

		if ( !isset( $info['member_posts'] ) )
		{
			$info['member_posts'] = 0;
		}
		
		if ( isset( $info['member_last_post'] ) )
		{
			if ( $info['member_last_post'] instanceof \IPS\DateTime )
			{
				$info['member_last_post'] = $info['member_last_post']->getTimestamp();
			}
		}
		else
		{
			$info['member_last_post'] = NULL;
		}
		
		$info['member_streams'] = NULL;
		$info['create_menu']	= NULL;
		$id						= $info['member_id'];
		unset( $info['member_id'] );
		
		/* Whew, finally */
		$inserted_id = \IPS\Db::i()->insert( 'core_members', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_members' );
		\IPS\Db::i()->replace( 'core_pfields_content', $this->_formatMemberProfileFieldContent( $inserted_id, $profileFields ), TRUE );
		return $inserted_id;
	}
	
	/**
	 * Convert a Warning Action
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted warning action, or FALSE on failure.
	 */
	public function convert_warn_action( $info=array() )
	{
		/* We do not really need an ID here */
		$hasId = TRUE;
		if ( !isset( $info['wa_id'] ) )
		{
			$this->software->app->log( 'wa_missing_ids', __METHOD__, \IPS\convert\App::LOG_NOTICE );
			$hasId = FALSE;
		}
		
		if ( !isset( $info['wa_points'] ) )
		{
			$info['wa_points'] = 0;
		}
		
		if ( !isset( $info['wa_mq'] ) )
		{
			$info['wa_mq'] = 0;
		}
		
		if ( !isset( $info['wa_mq_unit'] ) OR !in_array( $info['wa_mq_unit'], array( 'd', 'h' ) ) )
		{
			$info['wa_mq_unit'] = 'h';
		}
		
		if ( !isset( $info['wa_rpa'] ) )
		{
			$info['wa_rpa'] = 0;
		}
		
		if ( !isset( $info['wa_rpa_unit'] ) OR !in_array( $info['wa_rpa_unit'], array( 'd', 'h' ) ) )
		{
			$info['wa_rpa_unit'] = 'h';
		}
		
		if ( !isset( $info['wa_suspend'] ) )
		{
			$info['wa_suspend'] = 0;
		}
		
		if ( !isset( $info['wa_suspend_unit'] ) OR !in_array( $info['wa_rpa_unit'], array( 'd', 'h' ) ) )
		{
			$info['wa_suspend_unit'] = 'h';
		}
		
		if ( !isset( $info['wa_override'] ) )
		{
			$info['wa_override'] = 0;
		}
		
		if ( $hasId )
		{
			$id = $info['wa_id'];
			unset( $info['wa_id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_members_warn_actions', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_members_warn-actions' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Warning Log
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted warning log, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_warn_log( $info=array() )
	{
		if ( !isset( $info['wl_id'] ) )
		{
			$this->software->app->log( 'wl_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['wl_member'] ) )
		{
			try
			{
				$info['wl_member'] = $this->software->app->getLink( $info['wl_member'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'wl_member_missing', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['wl_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'wl_member_missing', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['wl_id'] );
			return FALSE;
		}
		
		if ( isset( $info['wl_moderator'] ) )
		{
			try
			{
				$info['wl_moderator'] = $this->software->app->getLink( $info['wl_moderator'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['wl_moderator'] = 0;
			}
		}
		else
		{
			$info['wl_moderator'] = 0;
		}
		
		if ( isset( $info['wl_date'] ) )
		{
			if ( $info['wl_date'] instanceof \IPS\DateTime )
			{
				$info['wl_date'] = $info['wl_date']->getTimestamp();
			}
		}
		else
		{
			$info['wl_date'] = time();
		}
		
		if ( isset( $info['wl_reason'] ) )
		{
			try
			{
				$info['wl_reason'] = $this->software->app->getLink( $info['wl_reason'], 'core_members_warn_reasons' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['wl_reason'] = 0;
			}
		}
		else
		{
			$info['wl_reason'] = 0;
		}
		
		if ( !isset( $info['wl_points'] ) )
		{
			$info['wl_points'] = 0;
		}
		
		if ( !isset( $info['wl_note_member'] ) )
		{
			$info['wl_note_member'] = '';
		}
		
		if ( !isset( $info['wl_note_mods'] ) )
		{
			$info['wl_note_mods'] = '';
		}
		
		foreach( array( 'wl_mq', 'wl_rpa', 'wl_suspend' ) AS $restriction )
		{
			if ( isset( $info[$restriction] ) )
			{
				if ( $info[$restriction] instanceof \IPS\DateTime )
				{
					/* Naughty copy/paste */
					$difference = \IPS\DateTime::create()->diff( $info[$restriction] );
					$period = 'P';
					foreach ( array( 'y' => 'Y', 'm' => 'M', 'd' => 'D' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$period .= $difference->$k . $v;
						}
					}
					$time = '';
					foreach ( array( 'h' => 'H', 'i' => 'M', 's' => 'S' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$time .= $difference->$k . $v;
						}
					}
					if ( $time )
					{
						$period .= 'T' . $time;
					}
					
					$info[$restriction] = $period;
				}
			}
			else
			{
				$info[$restriction] = NULL;
			}
		}
		
		if ( !isset( $info['wl_acknowledged'] ) )
		{
			/* Normally we would use the default, but in this instance let's assume the warning was acknowledged */
			$info['wl_acknowledged'] = 1;
		}
		
		/* As far as where the content is stored... we can't figure that out automatically - the converter will need to do so, and pass the appropriate values and ID's for app and content */
		if ( !isset( $info['wl_content_app'] ) )
		{
			$info['wl_content_app'] = NULL;
		}
		
		if ( !isset( $info['wl_content_id1'] ) )
		{
			$info['wl_content_id1'] = NULL;
		}
		
		if ( !isset( $info['wl_content_id2'] ) )
		{
			$info['wl_content_id2'] = NULL;
		}
		
		if ( !isset( $info['wl_content_module'] ) )
		{
			$info['wl_content_module'] = NULL;
		}
		
		if ( isset( $info['wl_expire_date'] ) )
		{
			if ( $info['wl_expire_date'] instanceof \IPS\DateTime )
			{
				$info['wl_expire_date'] = $info['wl_expire_date']->getTimestamp();
			}
		}
		else
		{
			$info['wl_expire_date'] = -1;
		}
		
		$id = $info['wl_id'];
		unset( $info['wl_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_members_warn_logs', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_members_warn_logs' );
		return $inserted_id;
	}
	
	/**
	 * Convert a Warning Reason
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted warning reason, or FALSE on failure.
	 */
	public function convert_warn_reason( $info=array() )
	{
		if ( !isset( $info['wr_id'] ) )
		{
			$this->software->app->log( 'wr_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['wr_name'] ) )
		{
			$info['wr_name'] = "Untitled Reason {$info['wr_id']}";
			$this->software->app->log( 'wr_missing_name', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['wr_id'] );
		}
		
		if ( !isset( $info['wr_points'] ) )
		{
			$info['wr_points'] = 0;
		}
		
		if ( !isset( $info['wr_points_override'] ) )
		{
			$info['wr_points_override'] = 0;
		}
		
		if ( !isset( $info['wr_remove'] ) )
		{
			$info['wr_remove'] = 0;
		}
		
		if ( !isset( $info['wr_remove_unit'] ) OR !in_array( $info['wr_remove_unit'], array( 'h', 'd' ) ) )
		{
			$info['wr_remove_unit'] = 'h';
		}
		
		if ( !isset( $info['we_remove_override'] ) )
		{
			$info['wr_remove_override'] = 0;
		}
		
		if ( !isset( $info['wr_order'] ) )
		{
			$highest = \IPS\Db::i()->select( 'MAX(wr_order)', 'core_members_warn_reasons' )->first();
			$info['wr_order'] = $highest + 1;
		}
		
		$id = $info['wr_id'];
		unset( $info['wr_id'] );
		
		$name = $info['wr_name'];
		unset( $info['wr_name'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_members_warn_reasons', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_members_warn_reasons' );
		
		\IPS\Lang::saveCustom( 'core', "core_warn_reason_{$inserted_id}", $name );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Private Message
	 *
	 * @param	array	$topic	The Message Topic Data to insert
	 * @param	array	$posts	The Message Post Data to insert. Example: array( postId => array ( data ) )
	 * @param	array	$maps	The User Map Data to insert. Example: array( memberId => array( data ) )
	 * @return	boolean|integer	The ID of the newly inserted Message Topic, or FALSE on failure.
	 */
	public function convert_private_message( $topic=array(), $posts=array(), $maps=array() )
	{
		if ( !isset( $topic['mt_id'] ) )
		{
			$this->software->app->log( 'private_message_topic_ids_missing', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( count( $posts ) == 0 )
		{
			$this->software->app->log( 'private_message_topic_no_posts', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( count( $maps ) == 0 )
		{
			$this->software->app->log( 'private_message_topic_no_users', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $topic['mt_date'] ) )
		{
			if ( $topic['mt_date'] instanceof \IPS\DateTime )
			{
				$topic['mt_date'] = $topic['mt_date']->getTimestamp();
			}
		}
		else
		{
			/* Converter will indicate PM's need rebuilding if any information is missing */
			$topic['mt_date'] = 0;
		}
		
		if ( !isset( $topic['mt_title'] ) )
		{
			$topic['mt_title'] = "Untitled Conversation {$topic['mt_id']}";
			$this->software->app->log( 'private_message_topic_title_missing', __METHOD__, \IPS\convert\App::LOG_NOTICE, $topic['mt_id'] );
		}
		
		/* No Longer Used */
		$topic['mt_hasattach']		= 0;
		$topic['mt_to_member_id']	= 0;
		$topic['mt_is_draft']		= 0;
		$topic['mt_is_system']		= 0;
		
		if ( isset( $topic['mt_starter_id'] ) )
		{
			try
			{
				$topic['mt_starter_id'] = $this->software->app->getLink( $topic['mt_starter_id'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'private_message_topic_starter_missing', __METHOD__, \IPS\convert\App::LOG_WARNING, $topic['mt_id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'private_message_topic_starter_missing', __METHOD__, \IPS\convert\App::LOG_WARNING, $topic['mt_id'] );
			return FALSE;
		}
		
		if ( isset( $topic['mt_start_time'] ) )
		{
			if ( $topic['mt_start_time'] instanceof \IPS\DateTime )
			{
				$topic['mt_start_time'] = $topic['mt_start_time']->getTimestamp();
			}
		}
		else
		{
			/* Assign the value of mt_date - if it's 0 we'll rebuild anyway */
			$topic['mt_start_time'] = $topic['mt_date'];
		}
		
		if ( isset( $topic['mt_last_post_time'] ) )
		{
			if ( $topic['mt_last_post_time'] instanceof \IPS\DateTime )
			{
				$topic['mt_last_post_time'] = $topic['mt_last_post_time']->getTimestamp();
			}
		}
		else
		{
			$topic['mt_last_post_time'] = 0;
		}
		
		if ( !isset( $topic['mt_to_count'] ) )
		{
			$topic['mt_to_count'] = count( $maps );
		}
		
		if ( !isset( $topic['mt_replies'] ) )
		{
			$topic['mt_replies'] = count( $posts );
		}
		
		$topicId = $topic['mt_id'];
		unset( $topic['mt_id'] );
		
		$topicInsertedId = \IPS\Db::i()->insert( 'core_message_topics', $topic );
		$this->software->app->addLink( $topicInsertedId, $topicId, 'core_message_topics' );
		
		/* Now handle Posts */
		$postsInserted = array();
		foreach( $posts AS $postId => $post )
		{
			if ( !isset( $post['msg_id'] ) )
			{
				$this->software->app->log( 'private_message_post_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING, $topicId );
				continue;
			}
			
			/* No need to perform link lookups here */
			$post['msg_topic_id'] = $topicInsertedId;
			
			if ( isset( $post['msg_date'] ) )
			{
				if ( $post['msg_date'] instanceof \IPS\DateTime )
				{
					$post['msg_date'] = $post['msg_date']->getTimestamp();
				}
			}
			else
			{
				$post['msg_date'] = time();
			}
			
			if ( empty( $post['msg_post'] ) )
			{
				$this->software->app->log( 'private_message_post_no_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $post['msg_id'] );
				continue;
			}
			
			$post['msg_post_key'] = 0;
			
			if ( isset( $post['msg_author_id'] ) )
			{
				try
				{
					$post['msg_author_id'] = $this->software->app->getLink( $post['msg_author_id'], 'core_members' );
				}
				catch( \OutOfRangeException $e )
				{
					$post['msg_author_id'] = 0;
				}
			}
			else
			{
				$post['msg_author_id'] = 0;
			}
			
			if ( !isset( $post['msg_ip_address'] ) OR filter_var( $post['msg_ip_address'], FILTER_VALIDATE_IP ) === FALSE )
			{
				$post['msg_ip_address'] = '127.0.0.1';
			}
			
			/* We'll set this later */
			$post['msg_is_first_post'] = 0;
			unset( $post['msg_id'] );
			
			$postInsertedId = \IPS\Db::i()->insert( 'core_message_posts', $post );
			$this->software->app->addLink( $postInsertedId, $postId, 'core_message_posts' );
			$postsInserted[] = $postInsertedId;
		}
		
		/* Did we actually insert any posts? */
		if ( count( $postsInserted ) == 0 )
		{
			/* We did not... remove the topic and link, log it, and stop */
			\IPS\Db::i()->delete( 'core_message_topics', array( "mt_id=?", $topicInsertedId ) );
			\IPS\Db::i()->delete( 'convert_link_pms', array( "ipb_id=? AND type=? AND app=?", $topicInsertedId, 'core_message_topics', $this->software->app->app_id ) );
			$this->software->app->log( 'private_message_topic_no_posts', __METHOD__, \IPS\convert\App::LOG_WARNING, $topicId );
			return FALSE;
		}
		
		/* Whew... let's do our maps now */
		$mapsInserted = array();
		foreach( $maps AS $memberId => $map )
		{
			/* We don't necessarily need an existing ID for the maps */
			$hasMapId = TRUE;
			if ( !isset( $map['map_id'] ) )
			{
				/* We don't really need to log it either - most software won't have the equivalent of this column */
				$hasMapId = FALSE;
			}
			
			if ( isset( $map['map_user_id'] ) )
			{
				try
				{
					$map['map_user_id'] = $this->software->app->getLink( $map['map_user_id'], 'core_members' );
				}
				catch( \OutOfRangeException $e )
				{
					$this->software->app->log( 'private_message_map_missing_user', __METHOD__, \IPS\convert\App::LOG_WARNING, $topicId );
					continue;
				}
			}
			else
			{
				$this->software->app->log( 'private_message_map_missing_user', __METHOD__, \IPS\convert\App::LOG_WARNING, $topicId );
				continue;
			}
			
			/* We already know this */
			$map['map_topic_id'] = $topicInsertedId;
			
			if ( !isset( $map['map_folder_id'] ) )
			{
				$map['map_folder_id'] = 'myconvo';
			}
			
			if ( isset( $map['map_read_time'] ) )
			{
				if ( $map['map_read_time'] instanceof \IPS\DateTime )
				{
					$map['map_read_time'] = $map['map_read_time']->getTimestamp();
				}
			}
			else
			{
				$map['map_read_time'] = time();
			}
			
			if ( !isset( $map['map_user_active'] ) )
			{
				$map['map_user_active'] = 1;
			}
			
			if ( !isset( $map['map_user_banned'] ) )
			{
				$map['map_user_banned'] = 0;
			}
			
			if ( !isset( $map['map_has_unread'] ) )
			{
				/* We can dynamically set this if it's unknown by checking last read time */
				if ( $map['map_read_time'] > time() )
				{
					$map['map_has_unread'] = 1;
				}
				else
				{
					$map['map_has_unread'] = 0;
				}
			}
			
			/* Unused */
			$map['map_is_system'] = 0;
			
			if ( !isset( $map['map_is_starter'] ) )
			{
				if ( $map['map_user_id'] == $topic['mt_starter_id'] )
				{
					$map['map_is_starter'] = 1;
				}
				else
				{
					$map['map_is_starter'] = 0;
				}
			}
			
			if ( isset( $map['map_left_time'] ) )
			{
				if ( $map['map_left_time'] instanceof \IPS\DateTime )
				{
					$map['map_left_time'] = $map['map_left_time']->getTimestamp();
				}
			}
			else
			{
				$map['map_left_time'] = 0;
			}
			
			if ( !isset( $map['map_ignore_notification'] ) )
			{
				$map['map_ignore_notification'] = 0;
			}
			
			if ( isset( $map['map_last_topic_reply'] ) )
			{
				if ( $map['map_last_topic_reply'] instanceof \IPS\DateTime )
				{
					$map['map_last_topic_reply'] = $map['map_last_topic_reply']->getTimestamp();
				}
			}
			else
			{
				$map['map_last_topic_reply'] = $topic['mt_last_post_time'];
			}
			
			if ( $hasMapId )
			{
				$mapId = $map['map_id'];
				unset( $map['map_id'] );
			}
			
			try
			{
				/* Does a map for this user already exist? */
				$existing		= \IPS\Db::i()->select( '*', 'core_message_topic_user_map', array( "map_topic_id=? AND map_user_id=?", $map['map_topic_id'], $map['map_user_id'] ) )->first();
				$mapInsertedId	= $existing['map_id'];
				$mapsInserted[]	= $existing['map_id'];
			}
			catch( \UnderflowException $e )
			{
				$mapInsertedId	= \IPS\Db::i()->insert( 'core_message_topic_user_map', $map );
				$mapsInserted[]	= $mapInsertedId;
			}
			
			if ( $hasMapId )
			{
				$this->software->app->addLink( $mapInsertedId, $mapId, 'core_message_topic_user_map' );
			}
		}
		
		/* Did we actually add any maps? */
		if ( count( $mapsInserted ) == 0 )
		{
			/* Nope... clean up, log, and return */
			\IPS\Db::i()->delete( 'core_message_topics', array( "mt_id=?", $topicInsertedId ) );
			\IPS\Db::i()->delete( 'core_message_posts', array( "msg_topic_id=?", $topicInsertedId ) );
			\IPS\Db::i()->delete( 'convert_link_pms', array( "ipb_id=? AND type=? AND app=?", $topicInsertedId, 'core_message_topics', $this->software->app->app_id ) );
			\IPS\Db::i()->delete( 'convert_link_pms', array( "ipb_id IN(?) AND type=? AND app=?", implode( ',', $postsInserted ), 'core_message_posts', $this->software->app->app_id ) );
			$this->software->app->log( 'private_message_topic_missing_maps', __METHOD__, \IPS\convert\App::LOG_WARNING, $topicId );
			return FALSE;
		}
		
		/* Still here? All good - just return the new topic ID */
		return $topicInsertedId;
	}
	
	/**
	 * Convert a Moderator
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean			TRUE on success, or FALSE on failure.
	 */
	public function convert_moderator( $info=array() )
	{
		if ( !isset( $info['type'] ) OR !in_array( $info['type'], array( 'g', 'm' ) ) )
		{
			$this->software->app->log( 'moderator_type_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'moderator_type_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['perms'] ) )
		{
			$info['perms'] = '*';
		}
		else if ( $info['perms'] !== '*' AND is_array( $info['perms'] ) )
		{
			$info['perms'] = json_encode( $info['perms'] );
		}
		
		switch( $info['type'] )
		{
			case 'm':
				$info['id'] = $this->software->app->getLink( $info['id'], 'core_members' );
			break;
			
			case 'g':
				$info['id'] = $this->software->app->getLink( $info['id'], 'core_groups' );
			break;
		}
		
		\IPS\Db::i()->insert( 'core_moderators', $info );
		
		return TRUE;
	}
	
	/**
	 * Convert Permissions
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted permission row, or FALSE on failure.
	 * @note	These should be converted when the relevant node is converted.
	 */
	public function convert_permission( $info=array() )
	{
		/* Valid app? */
		if ( !isset( $info['app'] ) )
		{
			$this->software->app->log( 'permission_index_app_missing', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		try
		{
			$application = \IPS\Application::load( $info['app'] );
			
			if ( \IPS\Application::appIsEanbled( $info['app'] ) === FALSE )
			{
				throw new \UnexpectedValueException;
			}
		}
		catch( \UnexpectedValueException $e )
		{
			$this->software->application->log( 'permission_index_app_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['perm_type'] ) )
		{
			$this->software->app->log( 'permission_index_missing_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		$nodeClass = NULL;
		foreach( $application->extensions( 'core', 'Permissions' ) AS $extension )
		{
			foreach( array_keys( $extension->getNodeClasses() ) AS $class )
			{
				if ( $class::$permType == $info['perm_type'] )
				{
					$nodeClass = $class;
					break 2;
				}
			}
		}
		
		if ( is_null( $nodeClass ) )
		{
			$this->software->app->log( 'permission_index_invalid_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['perm_type_id'] ) )
		{
			try
			{
				$info['perm_type_id'] = $this->software->app->getLink( $info['perm_type_id'], $nodeClass::$databaseTable );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'permission_index_missing_type_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'permission_index_missing_type_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		/* Does this permission index already exist? */
		try
		{
			$perm = \IPS\Db::i()->select( '*', 'core_permission_index', array( "app=? AND perm_type=? AND perm_type_id=?", $info['app'], $info['perm_type'], $info['perm_type_id'] ) )->first();
		}
		catch( \UnderflowException $e )
		{
			/* No, create a new one. This will almost always be the case. */
			$perm = array(
				'app'			=> $info['app'],
				'perm_type'		=> $info['perm_type'],
				'perm_type_id'	=> $info['perm_type_id'],
				'perm_view'		=> NULL,
				'perm_2'		=> NULL,
				'perm_3'		=> NULL,
				'perm_4'		=> NULL,
				'perm_5'		=> NULL,
				'perm_6'		=> NULL,
				'perm_7'		=> NULL,
				'owner_only'	=> 0,
				'friend_only'	=> NULL,
			);
			
			$permId = \IPS\Db::i()->insert( 'core_permission_index', $perm );
			$perm['perm_id'] = $permId;
		}
		
		foreach( $nodeClass::$permissionMap AS $key => $permission )
		{
			/* If it's the same value just use that. */
			if ( $info["perm_{$key}"] == $perm["perm_{$permission}"] )
			{
				$perm["perm_{$permission}"] = $info["perm_{$key}"];
				break;
			}
			
			/* If our current permission is NULL, but we are explicitly granting all, do that. */
			if ( is_null( $perm["perm_{$permission}"] ) AND $info["perm_{$key}"] == '*' )
			{
				$perm["perm_{$permission}"] = '*';
				break;
			}
			
			/* If our current permission is NULL, but we are assigning groups, do that. */
			if ( is_null( $perm["perm_{$permission}"] ) AND ( is_array( $info["perm_{$key}"] ) OR mb_strstr( $info["perm_{$key}"], ',' ) OR is_numeric( $info["perm_{$key}"] ) ) )
			{
				if ( !is_array( $info["perm_{$key}"] ) )
				{
					$info["perm_{$key}"] = explode( ',', $info["perm_{$key}"] );
				}
				
				if ( count( $info["perm_{$key}"] ) )
				{
					$groupsToAdd = array();
					foreach( $info["perm_{$key}"] AS $group )
					{
						try
						{
							$group = $this->software->app->getLink( $group, 'core_groups' );
						}
						catch( \OutOfRangeException $e )
						{
							continue;
						}
						
						$groupsToAdd[] = $group;
					}
					
					$perm["perm_{$permission}"] = implode( ',', $groupsToAdd );
					break;
				}
			}
			
			/* If our current permission has specific groups, and we are passing more groups, merge them */
			if ( mb_strstr( $perm["perm_{$permission}"], ',' ) OR is_numeric( $perm["perm_{$permission}"] ) )
			{
				$currentGroups = explode( ',', $perm["perm_{$permission}"] );
				if ( count( $currentGroups ) )
				{
					$groupsToAdd = array();
					if ( !is_array( $info["perm_{$key}"] ) )
					{
						$info["perm_{$key}"] = explode( ',', $info["perm_{$key}"] );
					}
					
					if ( count( $info["perm_{$key}"] ) )
					{
						foreach( $info["perm_{$key}"] AS $group )
						{
							try
							{
								$group = $this->software->app->getLink( $group, 'core_groups' );
							}
							catch( \OutOfRangeException $e )
							{
								continue;
							}
							
							if ( !in_array( $currentGroups ) )
							{
								$groupsToAdd[] = $group;
							}
						}
					}
					
					$perm["perm_{$permission}"] = array_merge( $currentGroups, $groupsToAdd );
					break;
				}
			}
			
			/* Still here? Something went wrong... log a notice and assign NULL */
			$perm["perm_{$permission}"] = NULL;
			$this->software->app->log( 'permission_index_null', __METHOD__, \IPS\convert\App::LOG_NOTICE, $info['perm_type_id'] );
		}
		
		/* Update the database */
		\IPS\Db::i()->update( 'core_permission_index', $perm, array( "perm_id=?", $perm['perm_id'] ) );
		return $perm['perm_id'];
	}
	
	/**
	 * Convert a Profile Field Group
	 *
	 * @param	array			$info		Data to insert
	 * @param	integer|NULL	$mergeWith	The group we are merging with, or NULL to not merge.
	 * @return	boolean|integer	The ID of the newly inserted profile field group, or FALSE on failure
	 */
	public function convert_profile_field_group( $info=array(), $mergeWith=NULL )
	{
		if ( !isset( $info['pf_group_id'] ) )
		{
			$this->software->app->log( 'profile_field_group_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !is_null( $mergeWith ) )
		{
			$this->software->app->addLink( $mergeWith, $info['pf_group_id'], 'core_pfieldgroups', TRUE );
			return $mergeWith;
		}
		
		if ( !isset( $info['pf_group_name'] ) )
		{
			$name = "Converted {$info['pf_group_id']}";
			$this->software->app->log( 'profile_field_group_missing_name', __METHOD__, \IPS\convert\App::LOG_WARNING );
		}
		else
		{
			$name = $info['pf_group_name'];
			unset( $info['pf_group_name'] );
		}
		
		if ( !isset( $info['pf_group_order'] ) )
		{
			$position = \IPS\Db::i()->select( 'MAX(pf_group_order)', 'core_pfields_groups' )->first();
			$info['pf_group_order'] = $position + 1;
		}
		
		$id = $info['pf_group_id'];
		unset( $info['pf_group_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_pfields_groups', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_pfields_groups' );
		
		\IPS\Lang::saveCustom( 'core', "core_pfieldgroups_{$inserted_id}", $name );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Profile Field
	 *
	 * @param	array			$info		Data to insert
	 * @param	integer|NULL	$mergeWith	THe ID of an existing profile field to merge this one with, or NULL to create new.
	 * @return	boolean|integer	The ID of the newly inserted profile field, or FALSE on failure.
	 * @note Profile Field Content for individual members needs to be done during the members step, not here.
	 */
	public function convert_profile_field( $info=array(), $mergeWith=NULL )
	{
		/* Get valid fields while taking hooks into account */
		$validFields = array_merge( static::$fieldTypes, \IPS\core\ProfileFields\Field::$additionalFieldTypes );
		
		if ( !isset( $info['pf_id'] ) )
		{
			$this->software->app->log( 'profile_field_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !is_null( $mergeWith ) )
		{
			$this->software->app->addLink( $mergeWith, $info['pf_id'], 'core_pfields_data', TRUE );
			return $mergeWith;
		}
		
		if ( !isset( $info['pf_type'] ) OR !in_array( $info['pf_type'], $validFields ) )
		{
			$this->software->app->log( 'profile_field_invalid_type', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['pf_id'] );
			return FALSE;
		}
		
		if ( !isset( $info['pf_name'] ) )
		{
			$name = "{$info['pf_type']} Field {$info['pf_id']}";
		}
		else
		{
			$name = $info['pf_name'];
			unset( $info['pf_name'] );
		}
		
		if ( !isset( $info['pf_desc'] ) )
		{
			$desc = '';
		}
		else
		{
			$desc = $info['pf_desc'];
			unset( $info['pf_desc'] );
		}
		
		if ( isset( $info['pf_content'] ) )
		{
			if ( is_array( $info['pf_content'] ) )
			{
				$info['pf_content'] = json_encode( $info['pf_content'] );
			}
		}
		else
		{
			$info['pf_content'] = NULL;
		}
		
		if ( !isset( $info['pf_not_null'] ) )
		{
			$info['pf_not_null'] = 0;
		}
		
		if ( !isset( $info['pf_member_hide'] ) )
		{
			$info['pf_member_hide'] = 1;
		}
		
		if ( !isset( $info['pf_max_input'] ) )
		{
			$info['pf_max_input'] = 0;
		}
		
		if ( !isset( $info['pf_member_edit'] ) )
		{
			$info['pf_member_edit'] = 0;
		}
		
		if ( !isset( $info['pf_position'] ) )
		{
			$position = \IPS\Db::i()->select( 'MAX(pf_position)', 'core_pfields_data' )->first();
			$info['pf_position'] = $position + 1;
		}
		
		if ( !isset( $info['pf_show_on_reg'] ) )
		{
			$info['pf_show_on_reg'] = 0;
		}
		
		if ( !isset( $info['pf_input_format'] ) )
		{
			$info['pf_input_format'] = NULL;
		}
		
		if ( !isset( $info['pf_admin_only'] ) )
		{
			$info['pf_admin_only'] = 0;
		}
		
		if ( !isset( $info['pf_format'] ) )
		{
			$info['pf_format'] = NULL;
		}
		
		if ( isset( $info['pf_group_id'] ) )
		{
			try
			{
				$info['pf_group_id'] = $this->software->app->getLink( $info['pf_group_id'], 'core_pfields_groups' );
			}
			catch( \OutOfrangeException $e )
			{
				/* Create an orphan group */
				$info['pf_group_id'] = $this->convert_profile_field_group( array(
					'pf_group_id'	=> '__orphaned__',
					'pf_group_name'	=> 'Converted',
				) );
			}
		}
		else
		{
			try
			{
				$info['pf_group_id'] = $this->software->app->getLink( '__orphaned__', 'core_pfields_groups' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['pf_group_id'] = $this->convert_profile_field_group( array(
					'pf_group_id'	=> '__orphaned__',
					'pf_group_name'	=> 'Converted',
				) );
			}
		}
		
		if ( !isset( $info['pf_search_type'] ) )
		{
			$info['pf_search_type'] = 'loose';
		}
		
		if ( !isset( $info['pf_filtering'] ) )
		{
			$info['pf_filtering'] = 0;
		}
		
		if ( !isset( $info['pf_multiple'] ) )
		{
			$info['pf_multiple'] = 0;
		}
		
		$id = $info['pf_id'];
		unset( $info['pf_id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_pfields_data', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_pfields_data' );
		
		\IPS\Lang::saveCustom( 'core', "core_pfield_{$inserted_id}", $name );
		\IPS\Lang::saveCustom( 'core', "core_pfield_{$inserted_id}_desc", $desc );
		
		/* Now... create our column */
		$columnDefinition = array( 'name' => "field_{$inserted_id}", 'type' => 'TEXT' );

		/* !IPS4 currently creates all profile fields as TEXT.
		switch( $info['pf_type'] )
		{
				case 'CheckboxSet':
				case 'Member':
					if ( $info['pf_multiple'] )
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
		
		if ( isset( $info['pf_max_input'] ) AND $info['pf_max_input'] )
		{
			$columnDefinition['length'] = $info['pf_max_input'];
		}
		 */
		
		\IPS\Db::i()->addColumn( 'core_pfields_content', $columnDefinition );
		
		if ( $info['pf_type'] != 'Upload' )
		{
			if ( $columnDefinition['type'] == 'TEXT' )
			{
				\IPS\Db::i()->addIndex( 'core_pfields_content', array( 'type' => 'fulltext', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
			}
			else
			{
				\IPS\Db::i()->addIndex( 'core_pfields_content', array( 'type' => 'key', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
			}
		}
		
		return $inserted_id;
	}
	
	/**
	 * Format Member Profile Field Content
	 *
	 * @param	int		$member_id		The ID of the member ID to insert data for.
	 * @param	array	$fieldInfo		The Profile Field Information to format. This SHOULD be in $foreign_id => $content format, however field_$foreign_id => $content is also accepted.
	 * @return	array					An array of data formatted for core_pfields_content
	 */
	protected function _formatMemberProfileFieldContent( $member_id, $fieldInfo )
	{
		$return = array( 'member_id' => $member_id );
		
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
					$link = $this->software->app->getLink( $id, 'core_pfields_data' );
					
					/* Make sure the field itself was not removed. */
					$field = \IPS\Db::i()->select( '*', 'core_pfields_data', array( "pf_id=?", $link ) )->first();
				}
				catch( \OutOfRangeException $e )
				{
					/* Link does not exist so we cannot map */
					continue;
				}
				catch( \UnderflowException $e )
				{
					/* Field does not exist */
					continue;
				}
				
				/* Too long? */
				if ( $field['pf_max_input'] )
				{
					if ( mb_strlen( $value ) > $field['pf_max_input'] )
					{
						$value = mb_substr( $value, 0, $field['pf_max_input'] );
					}
				}
				
				/* If this is a number field, we need to intval() */
				if ( in_array( $field['pf_type'], array( 'CheckboxSet', 'Member', 'Date', 'Poll', 'YesNo', 'Checkbox', 'Rating', 'Number' ) ) )
				{
					if ( in_array( $field['pf_type'], array( 'CheckboxSet', 'Member' ) ) AND $field['pf_multiple'] )
					{
						$return[ 'field_' . $link ] = $value;
					}
					else
					{
						$return[ 'field_' . $link ] = intval( $value );
					}
				}
				else
				{
					$return[ 'field_' . $link ] = $value;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Convert a Poll
	 *
	 * @param	array	$info	Data to insert
	 * @param	array	$votes	Vote Data
	 * @return	boolean|integer	The ID of the newly inserted Poll, or false on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_poll( $info=array(), $votes=array() )
	{
		/* Another instance where we really don't need this, but will store if we have it */
		$hasId = TRUE;
		if ( !isset( $info['pid'] ) )
		{
			$hasId = FALSE;
		}
		
		if ( !isset( $info['choices'] ) OR !is_array( $info['choices'] ) OR count( $info['choices'] ) == 0 )
		{
			$this->software->app->log( 'poll_no_choices', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['pid'] : NULL );
			return FALSE;
		}
		
		/* No longer used */
		$info['poll_only']	= 0;
		
		if ( !isset( $info['poll_question'] ) )
		{
			$info['poll_question'] = 'Untitled Poll';
			$this->software->app->log( 'poll_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['pid'] : NULL );
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
		
		if ( isset( $info['starter_id'] ) )
		{
			try
			{
				$info['starter_id'] = $this->software->app->getLink( $info['starter_id'], 'core_members' );
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
		
		if ( !isset( $info['votes'] ) )
		{
			$voteCount = 0;
			foreach( $votes AS $vote )
			{
				if ( isset( $vote['member_choices'] ) AND is_array( $vote['member_choices'] ) )
				{
					foreach( $vote['member_choices'] AS $question_id => $choices )
					{
						if ( is_array( $vote ) )
						{
							$voteCount += count( $vote );
						}
						else
						{
							$voteCount += 1;
						}
					}
				}
			}
			$info['votes'] = $voteCount;
		}
		
		if ( !isset( $info['poll_view_voters'] ) )
		{
			$info['poll_view_voters'] = 0;
		}
		
		/* Encode our choices... there isn't really a whole lot of checking we can do here */
		$info['choices'] = json_encode( $info['choices'] );
		
		if ( $hasId )
		{
			$id = $info['pid'];
			unset( $info['pid'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_polls', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_polls' );
		}
		
		/* Now do Votes */
		if ( count( $votes ) )
		{
			foreach( $votes AS $member_id => $vote )
			{
				$voteHasId = TRUE;
				if ( !isset( $vote['vid'] ) )
				{
					$voteHasId = FALSE;
				}
				
				/* Not used */
				$vote['tid']		= 0;
				$vote['forum_id']	= 0;
				
				if ( isset( $vote['vote_date'] ) )
				{
					if ( $vote['vote_date'] instanceof \IPS\DateTime )
					{
						$vote['vote_date'] = $vote['vote_date']->getTimestamp();
					}
				}
				else
				{
					$vote['vote_date'] = time();
				}
				
				try
				{
					$vote['member_id'] = $this->software->app->getLink( $vote['member_id'], 'core_members' );
				}
				catch( \OutOfRangeException $e )
				{
					/* Votes need a member account */
					$this->software->app->log( 'voter_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $id : NULL );
					continue;
				}
				
				if ( !isset( $vote['ip_address'] ) OR filter_var( $info['ip_address'], FILTER_VALIDATE_IP ) === FALSE )
				{
					$vote['ip_address'] = '127.0.0.1';
				}
				
				if ( isset( $vote['member_choices'] ) AND is_array( $vote['member_choices'] ) AND count( $vote['member_choices'] ) )
				{
					$vote['member_choices'] = json_encode( $vote['member_choices'] );
				}
				else
				{
					/* We need votes */
					continue;
				}
				
				$vote['poll'] = $inserted_id;
				
				if ( $voteHasId )
				{
					$voteId = $vote['vid'];
					unset( $vote['vid'] );
				}
				
				$voteInsertedId = \IPS\Db::i()->insert( 'core_voters', $vote );
				
				if ( $voteHasId )
				{
					$this->software->app->addLink( $voteInsertedId, $voteId, 'core_voters' );
				}
			}
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Profanity Filter
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted profanity filter, or FALSE on failure.
	 */
	public function convert_profanity_filter( $info=array() )
	{
		if ( !isset( $info['wid'] ) )
		{
			$this->software->app->log( 'profanity_filter_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['type'] ) )
		{
			$this->software->app->log( 'profanity_filter_no_type', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['swop'] ) )
		{
			$this->software->app->log( 'profanity_filter_no_swop', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['m_exact'] ) )
		{
			$info['m_exact'] = 0;
		}
		
		$id = $info['wid'];
		unset( $info['wid'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_profanity_filters', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_profanity_filters' );
		return $inserted_id;
	}
	
	/**
	 * Convert Question and Answer Spam Prevention
	 *
	 * @param	string	$info		Data to insert
	 * @param	array	$answers	The answers
	 * @return	boolean|integer	The ID of the newly insert Q&A, or FALSE on failure.
	 */
	public function convert_question_and_answer( $info, $answers=array() )
	{
		$haveId = TRUE;
		if ( !isset( $info['qa_id'] ) )
		{
			$haveId = FALSE;
			$this->software->app->log( 'convert_question_and_answer_missing_ids', __METHOD__, \IPS\convert\App::LOG_NOTICE );
		}
		
		if ( !count( $answers ) )
		{
			$this->software->app->log( 'convert_question_and_answer_no_answers', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['qa_question'] ) )
		{
			$this->software->app->log( 'convert_question_and_answer_no_question', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( $haveId )
		{
			$id = $info['qa_id'];
			unset( $info['qa_id'] );
		}
		
		$question = $info['qa_question'];
		unset( $info['qa_question'] );
		
		$info['qa_answers'] = json_encode( $answers );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_question_and_answer', $info );
		\IPS\Lang::saveCustom( 'core', "core_question_and_answer_{$inserted_id}", $question );
		
		if ( $haveId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_question_and_answer' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Rating
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted rating, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_rating( $info=array() )
	{
		$haveId = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$haveId = FALSE;
			$this->software->app->log( 'rating_missing_ids', __METHOD__, \IPS\convert\App::LOG_NOTICE );
		}
		
		if ( !isset( $info['class'] ) )
		{
			$this->software->app->log( 'rating_missing_class', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['item_link'] ) )
		{
			$this->software->app->log( 'rating_missing_item_link', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['item_id'] ) )
		{
			$this->software->app->log( 'rating_missing_item_id', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['rating'] ) )
		{
			$this->software->app->log( 'rating_missing_rating', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( !isset( $info['ip'] ) OR filter_var( $info['ip'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['ip'] = '127.0.0.1';
		}
		
		if ( $haveId )
		{
			$id					= $info['id'];
		}
		
		try
		{
			$info['member']		= $this->software->app->getLink( $info['member'], 'core_members' );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'rating_no_member', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		try
		{
			$info['item_id']	= $this->software->app->getLink( $info['item_id'], $info['item_link'] );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'rating_no_item', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		unset( $info['item_link'], $info['id'] );
		
		try
		{
			$inserted_id = \IPS\Db::i()->select( 'id', 'core_ratings', array( "class=? AND item_id=? AND member=?", $info['class'], $info['item_id'], $info['member'] ) )->first();
			
			$this->software->app->log( 'core_rating_duplicate', __METHOD__, \IPS\convert\App::LOG_NOTICE, ( $haveId ) ? $id : NULL );
		}
		catch( \UnderflowException $e )
		{
			$inserted_id = \IPS\Db::i()->insert( 'core_ratings', $info );
		}
		
		if ( $haveId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_ratings' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Report Index
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted report index, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_report_index( $info=array() )
	{
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'report_index_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		$contentClasses = \IPS\Content::routedClasses();
		if ( !isset( $info['class'] ) OR !in_array( $info['class'], $contentClasses ) )
		{
			$this->software->app->log( 'report_index_invalid_class', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		/* The converter will need to pass in the converted content ID */
		if ( !isset( $info['content_id'] ) )
		{
			$this->software->app->log( 'report_index_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		/* Same with this - we need to figure it out in the converter */
		if ( !isset( $info['perm_id'] ) )
		{
			$this->software->app->log( 'report_index_missing_perm', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( !isset( $info['status'] ) OR !in_array( $info['status'], array( 1, 2, 3 ) ) )
		{
			$info['status'] = 1;
		}
		
		if ( !isset( $info['num_reports'] ) )
		{
			$info['num_reports'] = 0;
		}
		
		if ( !isset( $info['num_comments'] ) )
		{
			$info['num_comments'] = 0;
		}
		
		if ( isset( $info['first_report_by'] ) )
		{
			try
			{
				$info['first_report_by'] = $this->software->app->getLink( $info['first_report_by'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['first_report_by'] = 0;
			}
		}
		else
		{
			$info['first_report_by'] = 0;
		}
		
		if ( isset( $info['first_report_date'] ) )
		{
			if ( $info['first_report_date'] instanceof \IPS\DateTime )
			{
				$info['first_report_date'] = $info['first_report_date']->getTimestamp();
			}
		}
		else
		{
			$info['first_report_date'] = time();
		}
		
		if ( isset( $info['last_updated'] ) )
		{
			if ( $info['last_updated'] instanceof \IPS\DateTime )
			{
				$info['last_updated'] = $info['last_updated']->getTimestamp();
			}
		}
		else
		{
			$info['last_updated'] = NULL;
		}
		
		if ( isset( $info['author'] ) )
		{
			try
			{
				$info['author'] = $this->software->app->getLink( $info['author'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['author'] = 0;
			}
		}
		else
		{
			$info['author'] = 0;
		}
		
		$id = $info['id'];
		unset( $info['id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_rc_index', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_rc_index' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Report
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted report, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_report( $info=array() )
	{
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'report_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['rid'] ) )
		{
			try
			{
				$info['rid'] = $this->software->app->getLink( $info['rid'], 'core_rc_index' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'report_missing_index', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'report_missing_index', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( empty( $info['report'] ) )
		{
			$this->software->app->log( 'report_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( isset( $info['report_by'] ) )
		{
			try
			{
				$info['report_by'] = $this->software->app->getLink( $info['report_by'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['report_by'] = 0;
			}
		}
		else
		{
			$info['report_by'] = 0;
		}
		
		if ( isset( $info['date_reported'] ) )
		{
			if ( $info['date_reported'] instanceof \IPS\DateTime )
			{
				$info['date_reported'] = $info['date_reported']->getTimestamp();
			}
		}
		else
		{
			$info['date_reported'] = time();
		}
		
		if ( !isset( $info['ip_address'] ) OR filter_var( $info['ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['ip_address'] = '127.0.0.1';
		}
		
		$id = $info['id'];
		unset( $info['id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_rc_reports', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_rc_reports' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Report Comment
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted comment, or FALSE on failure.
	 * @note UNLIKE report indexes, this can be done separately.
	 */
	public function convert_report_comment( $info=array() )
	{
		if ( !isset( $info['id'] ) )
		{
			$this->software->app->log( 'report_comment_missing_ids', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		if ( isset( $info['rid'] ) )
		{
			try
			{
				$info['rid'] = $this->software->app->getLink( $info['rid'], 'core_rc_index' );
			}
			catch( \OutOfRangeException $e )
			{
				$this->software->app->log( 'report_comment_missing_index', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
				return FALSE;
			}
		}
		else
		{
			$this->software->app->log( 'report_comment_missing_index', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( empty( $info['comment'] ) )
		{
			$this->software->app->log( 'report_comment_missing_content', __METHOD__, \IPS\convert\App::LOG_WARNING, $info['id'] );
			return FALSE;
		}
		
		if ( isset( $info['comment_by'] ) )
		{
			try
			{
				$info['comment_by'] = $this->software->app->getLink( $info['comment_by'], 'core_members' );
			}
			catch( \OutOfRangeException $e )
			{
				$info['comment_by'] = 0;
			}
		}
		else
		{
			$info['comment_by'] = 0;
		}
		
		if ( isset( $info['comment_date'] ) )
		{
			if( $info['comment_date'] instanceof \IPS\DateTime )
			{
				$info['comment_date'] = $info['comment_date']->getTimestamp();
			}
		}
		else
		{
			$info['comment_date'] = time();
		}
		
		if ( !isset( $info['approved'] ) )
		{
			$info['approved'] = 1;
		}
		
		if ( isset( $info['edit_date'] ) )
		{
			if ( $info['edit_date'] instanceof \IPS\DateTime )
			{
				$info['edit_date'] = $info['edit_date']->getTimestamp();
			}
		}
		else
		{
			$info['edit_date'] = 0;
		}
		
		if ( !isset( $info['ip_address'] ) OR filter_var( $info['ip_address'], FILTER_VALIDATE_IP ) === FALSE )
		{
			$info['ip_address'] = '127.0.0.1';
		}
		
		$id = $info['id'];
		unset( $info['id'] );
		
		$inserted_id = \IPS\Db::i()->insert( 'core_rc_comments', $info );
		$this->software->app->addLink( $inserted_id, $id, 'core_rc_comments' );
		
		return $inserted_id;
	}
	
	/**
	 * Convert Reputation
	 *
	 * @param	array	$info	Data to insert
	 * @return	boolean|integer	The ID of the newly inserted reputation, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 */
	public function convert_reputation( $info=array() )
	{
		/* Another instance where we really don't need this, but will store if we have it */
		$hasId = TRUE;
		if ( !isset( $info['id'] ) )
		{
			$hasId = FALSE;
		}
		
		if ( !isset( $info['app'] ) )
		{
			$this->software->app->log( 'reputation_no_app', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['type'] ) )
		{
			$this->software->app->log( 'reputation_missing_type', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['type_id'] ) )
		{
			$this->software->app->log( 'reputation_missing_type_id', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['member_id'] ) )
		{
			$this->software->app->log( 'reputation_missing_member', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasid ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( !isset( $info['member_received'] ) )
		{
			$this->software->app->log( 'reputation_missing_member_received', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$application = \IPS\Application::load( $info['app'] );
			
			if ( \IPS\Application::appIsEnabled( $info['app'] ) === FALSE )
			{
				throw new \UnexpectedValueException;
			}
		}
		catch( \UnexpectedValueException $e )
		{
			$this->software->app->log( 'reputation_app_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( isset( $info['rep_date'] ) )
		{
			if ( $info['rep_date'] instanceof \IPS\DateTime )
			{
				$info['rep_date'] = $info['rep_date']->getTimestamp();
			}
		}
		else
		{
			$info['rep_date'] = time();
		}
		
		if ( !isset( $info['rep_rating'] ) OR $info['rep_rating'] > 1 )
		{
			$info['rep_rating'] = 1;
		}
		else
		{
			if ( $info['rep_rating'] < -1 )
			{
				$info['rep_rating'] = -1;
			}
		}
		
		/* Get our content class */
		$contentClass = NULL;
		foreach( $application->extensions( 'core', 'ContentRouter' ) AS $extension )
		{
			foreach( $extension->classes AS $contentClass )
			{
				if ( isset( $contentClass::$reputationType ) )
				{
					if ( $contentClass::$reputationType == $info['type'] )
					{
						break 2;
					}
				}
				
				if ( isset( $contentClass::$commentClass ) )
				{
					$commentClass = $contentClass::$commentClass;
					
					if ( isset( $commentClass::$reputationType ) )
					{
						if ( $commentClass::$reputationType == $info['type'] )
						{
							$contentClass = $commentClass;
							break 2;
						}
					}
				}
				
				if ( isset( $contentClass::$reviewClass ) )
				{
					$reviewClass = $contentClass::$reviewClass;
					
					if ( isset( $reviewClass::$reputationType ) )
					{
						if ( $reviewClass::$reputationType == $info['type'] )
						{
							$contentClass = $reviewClass;
							break 2;
						}
					}
				}
			}
		}
		
		if ( is_null( $contentClass ) )
		{
			$this->software->app->log( 'reputation_type_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$info['member_id'] = $this->software->app->getLink( $info['member_id'], 'core_members' );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'reputation_member_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$info['type_id'] = $this->software->app->getLink( $info['type_id'], $contentClass::$databaseTable );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'reputation_type_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$info['member_received'] = $this->software->app->getLink( $info['member_received'], 'core_members' );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'reputation_member_received_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['id'] : NULL );
			return FALSE;
		}
		
		if ( $hasId )
		{
			$id = $info['id'];
			unset( $info['id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_reputation_index', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_reputation_index' );
		}
		
		return $inserted_id;
	}
	
	/**
	 * Convert Reputation Level
	 *
	 * @param	array			$info			Data to insert
	 * @param	string|NULL		$badgepath		Path to Reputation Badge file.
	 * @param	string|NULL		$badgedata		Binary data for Reputation Badge file.
	 * @return	boolean|integer			The ID of the newly inserted reputation level, or FALSE on failure.
	 */
	public function convert_reputation_level( $info=array(), $badgepath=NULL, $badgedata=NULL )
	{
		/* Another instance where we really don't need this, but will store if we have it */
		$hasId = TRUE;
		if ( !isset( $info['level_id'] ) )
		{
			$hasId = FALSE;
		}
		
		if ( !isset( $info['level_points'] ) )
		{
			$info['level_points'] = 0;
		}
		
		if ( !isset( $info['level_title'] ) )
		{
			$name = "Reputation {$info['level_points']}";
			$this->software->app->log( 'reputation_level_missing_title', __METHOD__, \IPS\convert\App::LOG_WARNING );
		}
		else
		{
			$name = $info['level_title'];
			unset( $info['level_title'] );
		}
		
		if ( isset( $info['level_image'] ) AND ( !is_null( $badgepath ) OR !is_null( $badgedata ) ) )
		{
			try
			{
				if ( is_null( $badgedata ) AND !is_null( $badgepath ) )
				{
					$badgedata = file_get_contents( $badgepath );
				}
				$file = \IPS\File::create( 'core_Theme', $info['level_image'], $badgedata );
				$info['level_image'] = (string) $file;
			}
			catch( \Exception $e )
			{
				$info['level_image'] = '';
			}
			catch( \ErrorException $e )
			{
				$info['level_image'] = '';
			}
		}
		else
		{
			$info['level_image'] = '';
		}
		
		if ( $hasId )
		{
			$id = $info['level_id'];
			unset( $info['level_id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_reputation_levels', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_reputation_levels' );
		}
		
		\IPS\Lang::saveCustom( 'core', "core_reputation_level_{$inserted_id}", $name );
		
		return $inserted_id;
	}
	
	/**
	 * Convert a Setting
	 *
	 * @param	array	$settings	Settings to convert
	 * @return	boolean|array		An array of settings changed, or FALSE on failure.
	 */
	public function convert_settings( $settings=array() )
	{
		if ( !count( $settings ) )
		{
			$this->software->app->log( 'no_settings_to_convert', __METHOD__, \IPS\convert\App::LOG_WARNING );
			return FALSE;
		}
		
		foreach( $settings AS $setting )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $setting['value'] ), array( "conf_key=?", $setting['key'] ) );
		}
		
		unset( \IPS\Data\Store::i()->settings );
		
		return $settings;
	}
	
	/**
	 * Convert a Tag
	 *
	 * @param	array		$info	Data to insert
	 * @return	boolean|integer		The ID of the newly inserted tag, or FALSE on failure.
	 * @note Like Follows, this should be done when the actual content it's attached too is being converted.
	 * @note core_tags_cache and core_tags_perms need to be populated by the converter.
	 */
	public function convert_tag( $info=array() )
	{
		/* Another instance where we really don't need this, but will store if we have it */
		$hasId = TRUE;
		if ( !isset( $info['tag_id'] ) )
		{
			$hasId = FALSE;
		}
		
		/* Basic checks to make sure we have what we need. */
		foreach( array( 'tag_meta_app', 'tag_meta_area', 'tag_meta_id', 'tag_meta_parent_id', 'tag_text' ) AS $column )
		{
			if ( !isset( $info[$column] ) OR empty( $info[$column] ) )
			{
				$this->software->app->log( "tag_missing_{$column}", __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['tag_id'] : NULL );
				return FALSE; # return here - all of these are required so don't bother proceeding
			}
		}
		
		/* let's do some set up */
		try
		{
			$application = \IPS\Application::load( $info['tag_meta_app'] );
			
			if ( \IPS\Application::appIsEnabled( $info['tag_meta_app'] ) === FALSE )
			{
				throw new \UnexpectedValueException;
			}
		}
		catch( \UnexpectedValueException $e )
		{
			$this->software->app->log( 'tag_app_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['tag_id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$info['tag_member_id'] = $this->software->app->getLink( $info['tag_member_id'], 'core_members' );
		}
		catch( \OutOfRangeException $e )
		{
			/* Tags can be added by guests */
			$info['tag_member_id'] = 0;
		}
		
		if ( isset( $info['tag_added'] ) )
		{
			if ( $info['tag_added'] instanceof \IPS\DateTime )
			{
				$info['tag_added'] = $info['tag_added']->getTimestamp();
			}
		}
		else
		{
			$info['tag_added'] = time();
		}
		
		if ( !isset( $info['tag_prefix'] ) )
		{
			$info['tag_prefix'] = 0;
		}
		
		$isVisible = 1;
		if ( isset( $info['tag_visible'] ) )
		{
			$isVisible = $info['tag_visible'];
			unset( $info['tag_visible'] );
		}
		
		/* Figure out our content class */
		$contentClass = NULL;
		foreach( $application->extensions( 'core', 'ContentRouter' ) AS $extension )
		{
			foreach( $extension->classes AS $contentClass )
			{
				if ( $contentClass::$module == $info['tag_meta_area'] )
				{
					break 2;
				}
			}
		}
		
		if ( is_null( $contentClass ) )
		{
			$this->software->app->log( 'tag_area_invalid', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['tag_id'] : NULL );
			return FALSE;
		}
		
		$nodeClass = $contentClass::$containerNodeClass;
		
		try
		{
			$table = $contentClass::$databaseTable;
			if ( isset( $info['tag_meta_link'] ) )
			{
				$table = $info['tag_meta_link'];
				unset( $info['tag_meta_link'] );
			}
			
			$info['tag_meta_id'] = $this->software->app->getLink( $info['tag_meta_id'], $table );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'tag_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['tag_id'] : NULL );
			return FALSE;
		}
		
		try
		{
			$parentTable = $nodeClass::$databaseTable;
			
			if ( isset( $info['tag_meta_parent_link'] ) )
			{
				$table = $info['tag_meta_parent_link'];
				unset( $info['tag_meta_parent_link'] );
			}
			$info['tag_meta_parent_id'] = $this->software->app->getLink( $info['tag_meta_parent_id'], $nodeClass::$databaseTable );
		}
		catch( \OutOfRangeException $e )
		{
			$this->software->app->log( 'tag_parent_orphaned', __METHOD__, \IPS\convert\App::LOG_WARNING, ( $hasId ) ? $info['tag_id'] : NULL );
			return FALSE;
		}
		
		/* Set up our lookup md5's */
		$info['tag_aai_lookup'] = md5( $info['tag_meta_app'] . ';' . $info['tag_meta_area'] . ';' . $info['tag_meta_id'] );
		$info['tag_aap_lookup'] = md5( $nodeClass::$permApp . ';' . $nodeClass::$permType . ';' . $info['tag_meta_parent_id'] );
		
		if ( $hasId )
		{
			$id = $info['tag_id'];
			unset( $info['tag_id'] );
		}
		
		$inserted_id = \IPS\Db::i()->insert( 'core_tags', $info );
		
		if ( $hasId )
		{
			$this->software->app->addLink( $inserted_id, $id, 'core_tags' );
		}
		
		return $inserted_id;
	}
}