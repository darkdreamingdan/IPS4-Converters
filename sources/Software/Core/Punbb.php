<?php

/**
 * @brief		Converter Punbb Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Core;

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
			'convert_groups'			=> array(
				'table'						=> 'groups',
				'where'						=> NULL,
			),
			'convert_members'			=> array(
				'table'						=> 'users',
				'where'						=> NULL,
			),
			'convert_ranks'				=> array(
				'table'						=> 'ranks',
				'where'						=> NULL
			),
			'convert_private_messages'	=> array(
				'table'						=> 'messages',
				'where'						=> NULL,
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
		return TRUE;
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
			'convert_groups',
			'convert_members'
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
			case 'convert_groups':
				$return['convert_groups'] = array();
				
				$options = array();
				$options['none'] = 'None';
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_groups' ), 'IPS\Member\Group' ) AS $group )
				{
					$options[$group->g_id] = $group->name;
				}
				
				foreach( $this->db->select( '*', 'groups' ) AS $group )
				{
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['g_id']}"]			= $group['g_title'];
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['g_id']}_desc"]	= \IPS\Member::loggedIn()->language()->addToStack( 'map_group_desc' );
					
					$return['convert_groups']["map_group_{$group['g_id']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;
			
			case 'convert_members':
				/* Pseudo Profile Fields */
				foreach( [ 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location' ] AS $field )
				{
					\IPS\Member::loggedIn()->language()->words["field_{$field}"]		= \IPS\Member::loggedIn()->language()->addToStack( 'pseudo_field', FALSE, array( 'sprintf' => ucwords( $field ) ) );
					\IPS\Member::loggedIn()->language()->words["field_{$field}_desc"]	= \IPS\Member::loggedIn()->language()->addToStack( 'pseudo_field_desc' );
					$return['convert_members']["field_{$field}"] = array(
						'field_class'			=> 'IPS\\Helpers\\Form\\Radio',
						'field_default'			=> 'no_convert',
						'field_required'		=> TRUE,
						'field_extra'			=> array(
							'options'				=> array(
								'no_convert'			=> \IPS\Member::loggedIn()->language()->addToStack( 'no_convert' ),
								'create_field'			=> \IPS\Member::loggedIn()->language()->addToStack( 'create_field' ),
							),
							'userSuppliedInput'		=> 'create_field'
						),
						'field_hint'			=> NULL
					);
				}
				break;
		}
		
		return $return[$method];
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 *
	 * @return	string		Message to display
	 */
	public function finish()
	{
		/* Search Index Rebuild */
		\IPS\Content\Search\Index::i()->rebuild();
		
		/* Clear Cache and Store */
		\IPS\Data\Store::i()->clearAll();
		\IPS\Data\Cache::i()->clearAll();
		
		/* Non-Content Rebuilds */
		\IPS\Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_message_posts', 'extension' => 'core_Messaging' ), 2, array( 'app', 'link', 'extension' ) );
		\IPS\Task::queue( 'convert', 'RebuildNonContent', array( 'app' => $this->app->app_id, 'link' => 'core_members', 'extension' => 'core_Signatures' ), 2, array( 'app', 'link', 'extension' ) );
		
		/* Content Counts */
		\IPS\Task::queue( 'core', 'RecountMemberContent', array( 'app' => $this->app->app_id ), 4, array( 'app' ) );

		/* First Post Data */
		\IPS\Task::queue( 'convert', 'RebuildConversationFirstIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		
		return array( "Search Index Rebuilding", "Caches Cleared", "Private Messages Rebuilding", "Signatures Rebuilding" );
	}
	
	/**
	 * Fix post data
	 *
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 **/
	public static function fixPostData( $post )
	{
		$post = nl2br( $post );
			
		$post = preg_replace( "#\[quote=(.+)\]#", "[quote name=$1]", $post );
		return $post;
	}
	
	public function convert_groups()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'g_id' );
		
		foreach( $this->fetch( 'groups', 'g_id' ) AS $row )
		{
			$info = array(
				'g_id'					=> $row['g_id'],
				'g_name'				=> $row['g_title'],
				'g_view_board'			=> $row['g_read_board'],
				'g_mem_info'			=> $row['g_view_users'],
				'g_is_supmod'			=> $row['g_moderator'],
				'g_use_search'			=> $row['g_search'],
				'g_edit_posts'			=> $row['g_edit_posts'],
				'g_use_pm'				=> $row['g_pm'],
				'g_pm_flood_mins'		=> ( $row['g_email_flood'] ) ? ceil( $row['g_email_flood'] / 60 ) : 0,
				'g_delete_own_posts'	=> $row['g_delete_posts'],
				'g_avoid_flood'			=> ( $row['g_post_flood'] == 0 ) ? 1 : 0,
				'g_max_messages'		=> $row['g_pm_limit'],
				'prefix'				=> ( $row['g_color'] ) ? "<span style='color:{$row['g_color']}'>" : NULL,
				'suffix'				=> ( $row['g_color'] ) ? "</span>" : NULL,
				'g_search_flood'		=> $row['g_search_flood'],
			);
			
			$merge = ( $this->app->_session['more_info']['convert_groups']["map_group_{$row['g_id']}"] != 'none' ) ? $this->app->_session['more_info']['convert_groups']["map_group_{$row['g_id']}"] : NULL;
			
			$libraryClass->convert_group( $info );
			
			$libraryClass->setLastKeyValue( $row['g_id'], $merge );
		}
	}
	
	public function convert_members()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'users', 'id' ) AS $row )
		{
			$info = array(
				'member_id'				=> $row['id'],
				'email'					=> $row['email'],
				'name'					=> $row['username'],
				'password'				=> $row['password'],
				'conv_password_extra'		=> $row['salt'],
				'member_group_id'		=> $row['group_id'],
				'joined'				=> $row['registered'],
				'ip_address'			=> $row['registration_ip'],
				'last_visit'			=> $row['last_visit'],
				'last_activity'			=> $row['last_visit'],
				'auto_track'			=> ( $row['auto_notify'] ) ? [ 'content' => 1, 'comments' => 1, 'method' => 'immediate' ] : 0,
				'members_bitoptions'	=> [ 'view_sigs' => $row['show_sig'] ],
				'signature'				=> $row['signature'],
				'timezone'				=> $row['timezone'],
				'member_title'			=> $row['title'],
				'member_posts'			=> $row['num_posts'],
			);
			
			/* Pseudo Fields */
			$profileFields = array();
			foreach( [ 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location' ] AS $pseudo )
			{
				/* Are we retaining? */
				if ( $this->app->_session['more_info']['convert_members']["field_{$pseudo}"] == 'no_convert' )
				{
					/* No, skip */
					continue;
				}
				
				try
				{
					/* We don't actually need this, but we need to make sure the field was created */
					$fieldId = $this->app->getLink( $pseudo, 'core_pfields_data' );
				}
				catch( \OutOfRangeException $e )
				{
					$libraryClass->convert_profile_field( array(
						'pf_id'				=> $pseudo,
						'pf_name'			=> $this->app->_session['more_info']['convert_members']["field_{$pseudo}"],
						'pf_desc'			=> '',
						'pf_type'			=> 'Text',
						'pf_content'		=> '[]',
						'pf_member_hide'	=> 0,
						'pf_max_input'		=> 255,
						'pf_member_edit'	=> 1,
						'pf_show_on_reg'	=> 0,
						'pf_admin_only'		=> 0,
					) );
				}
				
				$profileFields[$pseudo] = $row[$pseudo];
			}
			
			$libraryClass->convert_member( $info, $profileFields );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	public function convert_ranks()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'ranks', 'id' ) AS $row )
		{
			$info = array(
				'id'		=> $row['id'],
				'title'		=> $row['rank'],
				'posts'		=> $row['min_posts']
			);
			
			$libraryClass->convert_rank( $info );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	public function convert_private_messages()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'messages', 'id' ) AS $row )
		{
			/* PunBB does not have conversations, so convert each one into and individual PM topic */
			$topic = array(
				'mt_id'				=> $row['id'],
				'mt_date'			=> $row['posted'],
				'mt_title'			=> $row['subject'],
				'mt_starter_id'		=> $row['sender_id'],
				'mt_start_time'		=> $row['posted'],
				'mt_last_post_time'	=> $row['posted'],
				'mt_to_count'		=> ( $row['sender_id'] == $row['owner'] ) ? 1 : 2,
				'mt_replies'		=> 1,
			);
			
			$posts = array( $row['id'] => array(
				'msg_id'			=> $row['id'],
				'msg_date'			=> $row['posted'],
				'msg_post'			=> $row['message'],
				'msg_author_id'		=> $row['sender_id'],
				'msg_ip_address'	=> $row['sender_ip'],
				'msg_is_first_post'	=> 1,
			) );
			
			$maps = array();
			
			/* Sender */
			$maps[$row['sender_id']] = array(
				'map_user_id'			=> $row['sender_id'],
				'map_is_starter'		=> 1,
				'map_last_topic_reply'	=> $row['posted'],
			);
			
			/* Recipient... if it isn't the sender */
			if ( $row['sender_id'] != $row['owner'] )
			{
				$maps[$row['owner']] = array(
					'map_user_id'			=> $row['owner'],
					'map_is_starter'		=> 0,
					'map_last_topic_reply'	=> $row['posted']
				);
			}
			
			/* Simples! */
			$libraryClass->convert_private_message( $topic, $posts, $maps );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
}