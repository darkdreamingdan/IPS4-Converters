<?php

/**
 * @brief		Converter XenForo 1.x Master Class
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

class _Xenforo extends \IPS\convert\Software
{
	/**
	 * Software Name
	 *
	 * @return	string
	 * @throws	\BadMethodCallException
	 */
	public static function softwareName()
	{
		return "XenForo (1.x)";
	}

	/**
	 * Software Key
	 *
	 * @return	string
	 * #throws	\BadMethodCallException
	 */
	public static function softwareKey()
	{
		return 'xenforo';
	}

	public static function getPreConversionInformation()
	{
		return <<<INFORMATION
You can typically obtain these details from the file located at /path/to/xenforo/library/config.php
INFORMATION;
	}
	
	/**
	 * Uses Prefix
	 *
	 * @return	bool
	 */
	public static function usesPrefix()
	{
		return FALSE;
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return	array
	 * @throws	\BadMethodCallException
	 */
	public static function canConvert()
	{
		return array(
			'convert_emoticons'				=> array(
				'table'		=> 'xf_smilie',
				'where'		=> NULL
			),
			'convert_profile_fields'		=> array(
				'table'		=> 'xf_user_field',
				'where'		=> NULL
			),
			'convert_groups'				=> array(
				'table'		=> 'xf_user_group',
				'where'		=> NULL
			),
			'convert_warn_reason'			=> array(
				'table'		=> 'xf_warning_definition',
				'where'		=> NULL
			),
			'convert_members'				=> array(
				'table'		=> 'xf_user',
				'where'		=> NULL
			),
			'convert_statuses'				=> array(
				'table'		=> 'xf_profile_post',
				'where'		=> NULL
			),
			'convert_status_replies'		=> array(
				'table'		=> 'xf_profile_post_comment',
				'where'		=> NULL
			),
			'convert_ignored_users'			=> array(
				'table'		=> 'xf_user_ignored',
				'where'		=> NULL
			),
			'convert_private_messages'		=> array(
				'table'		=> 'xf_conversation_master',
				'where'		=> NULL
			),
			'convert_ranks'					=> array(
				'table'		=> 'xf_user_title_ladder',
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
		return TRUE;
	}

	/**
	 * Can we convert settings?
	 *
	 * @return	boolean
	 */
	public static function canConvertSettings()
	{
		return TRUE;
	}


	public static function checkConf()
	{
		return array(
			'convert_emoticons',
			'convert_profile_fields',
			'convert_groups',
			'convert_members',
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

		switch ( $method )
		{
			case 'convert_emoticons':
				/* XenForo stores emoticons either as a remotely linked image, or relative to the installation path, so we need to change the verbiage here a bit. */
				\IPS\Member::loggedIn()->language()->words['emoticon_path'] = \IPS\Member::loggedIn()->language()->addToStack( 'source_path', FALSE, array( 'sprintf' => array( 'XenForo' ) ) );
				$return['convert_emoticons'] = array(
					'emoticon_path'				=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> NULL,
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
					),
					'keep_existing_emoticons'	=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Checkbox',
						'field_default'		=> TRUE,
						'field_required'	=> FALSE,
						'field_extra'		=> array(),
						'field_hint'		=> NULL,
					)
				);
				break;
			
			case 'convert_profile_fields':
				$return['convert_profile_fields'] = array();
				
				$options = array();
				$options['none'] = \IPS\Member::loggedIn()->language()->addToStack( 'none' );
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_pfields_data' ), 'IPS\core\ProfileFields\Field' ) AS $field )
				{
					$options[$field->_id] = $field->_title;
				}
				
				foreach( $this->db->select( '*', 'xf_user_field' ) AS $field )
				{
					\IPS\Member::loggedIn()->language()->words["map_pfield_{$field['field_id']}"]			= $this->getPhrase( "user_field_{$field['field_id']}" );
					\IPS\Member::loggedIn()->language()->words["map_pfield_{$field['field_id']}_desc"]	= \IPS\Member::loggedIn()->language()->addToStack( 'map_pfield_desc' );
					
					$return['convert_profile_fields']["map_pfield_{$field['field_id']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;
			
			case 'convert_groups':
				$return['convert_groups'] = array();

				$options = array();
				$options['none'] = 'None';
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_groups' ), 'IPS\Member\Group' ) AS $group )
				{
					$options[$group->g_id] = $group->name;
				}

				foreach( $this->db->select( '*', 'xf_user_group' ) AS $group )
				{
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['user_group_id']}"]			= $group['title'];
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['user_group_id']}_desc"]	= \IPS\Member::loggedIn()->language()->addToStack( 'map_group_desc' );

					$return['convert_groups']["map_group_{$group['user_group_id']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL,
					);
				}
				break;
			case 'convert_members':
				$return['convert_members'] = array();

				/* Find out where the photos live */
				\IPS\Member::loggedIn()->language()->words['photo_location_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'photo_location_nodb_desc' );
				$return['convert_members']['photo_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> "This is typically: /path/to/xenforo/data/avatars",
					'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);

				foreach( array( 'homepage', 'location', 'occupation', 'about', 'gender' ) AS $field )
				{
					\IPS\Member::loggedIn()->language()->words["field_{$field}"]		= \IPS\Member::loggedIn()->language()->addToStack( 'pseudo_field', FALSE, array( 'sprintf' => $field ) );
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
	 * Settings Map
	 *
	 * @return	array
	 */
	public function settingsMap()
	{
		return array(
			'boardTitle'	=> 'board_name',
		);
	}

	/**
	 * Settings Map Listing
	 *
	 * @return	array
	 */
	public function settingsMapList()
	{
		$settings = array();
		foreach( $this->settingsMap() AS $theirs => $ours )
		{
			try
			{
				$setting = $this->db->select( 'option_value', 'option', array( "option_id=?", $theirs ) )->first();
			}
			catch( \UnderflowException $e )
			{
				continue;
			}


			$title = $this->getPhrase( 'option_' . $settings['varname']);

			if ( !$title )
			{
				$title = $setting['varname'];
			}

			$settings[$setting['varname']] = array( 'title' => $title, 'value' => $setting['value'], 'our_key' => $ours, 'our_title' => \IPS\Member::loggedIn()->language()->addToStack( $ours ) );
		}

		return $settings;
	}

	/**
	 * Helper to fetch a xenforo phrase
	 *
	 * @param $title
	 *
	 * @return string|null	Phrase
	 */
	protected function getPhrase( $title )
	{
		try
		{
			return $this->db->select( 'phrase_text', 'xf_phrase', array( "title=?", $title ) )->first();
		}
		catch( \UnderflowException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Finish
	 *
	 * @return	array
	 */
	public function finish()
	{
		/* Search Index Rebuild */
		\IPS\Content\Search\Index::i()->rebuild();
		
		/* Clear Cache and Store */
		\IPS\Data\Store::i()->clearAll();
		\IPS\Data\Cache::i()->clearAll();
		
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
		// run everything through htmlspecialchars to prevent XSS ( @see http://community.invisionpower.com/resources/bugs.html/_/ips-extras/converters/possible-xf-converter-xss-vector-r38108 )
		$post = htmlspecialchars( $post, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE );
		
		// find YouTube ID's and replace.
		$post = preg_replace( '#\[media=youtube\](.+?)\[/media\]#i', '[media]http://www.youtube.com/watch?v=$1[/media]', $post );
		
		/* Mentions */
		preg_match_all( '#\[user=(\d+)\](.+?)\[\/user\]#i', $post, $matches );
		
		if ( count( $matches ) )
		{
			if ( isset( $matches[1] ) )
			{
				$mentions = array();
				foreach( $matches[1] AS $k => $v )
				{
					if ( isset( $matches[2][$k] ) )
					{
						$name = trim( $matches[2][$k], '@' );
						$mentions[$name] = $v;
					}
				}
				
				$maps		= array();
				$urls		= array();
				$cardUrls	= array();
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_members', array( \IPS\Db::i()->in( 'name', array_keys( $mentions ) ) ) ), 'IPS\Member' ) AS $member )
				{
					$maps[$member->name]		= $member->member_id;
					$urls[$member->name]		= $member->url();
					$cardUrls[$member->name]	= $member->url()->setQueryString( 'do', 'hovercard' );
				}
				
				foreach( $mentions AS $member_name => $member_id )
				{
					$urls[$member_name]		= preg_quote( $urls[$member_name], '#' );
					$maps[$member_name]		= preg_quote( $maps[$member_name], '#' );
					$cardUrls[$member_name]	= preg_quote( $cardUrls[$member_name], '#' );
					$member_name			= preg_quote( $member_name, '#' );
					
					$post = preg_replace( "#\[user={$member_id}\]\@{$member_name}\[\/user\]#i", "<a contenteditable=\"false\" rel=\"\" href=\"{$urls[$member_name]}\" data-mentionid=\"{$maps[$member_name]}\" data-ipshover-target=\"{$cardUrls[$member_name]}\" data-ipshover=\"\">@{$member_name}</a>", $post );
				}
			}
		}
		
		// finally, give us the post back.
		return $post;
	}
	
	public function convert_emoticons()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'smilie_id' );
		
		foreach( $this->fetch( 'xf_smilie', 'smilie_id' ) AS $row )
		{
			/* We need to figure out where our file lives - if it's remote, then we need to use file_get_contents() and pass the raw data. */
			$filepath = NULL;
			$filedata = NULL;
			
			if ( mb_substr( $row['image_url'], 0, 4 ) == 'http' )
			{
				$filedata	= file_get_contents( $row['image_url'] );
				$fileurl	= explode( '/', $row['image_url'] );
				$filename	= array_pop( $fileurl );
			}
			else
			{
				$fileurl	= explode( '/', $row['image_url'] );
				$filename	= array_pop( $fileurl );
				$filepath	= rtrim( $this->app->_session['more_info']['convert_emoticons']['emoticon_path'], '/' ) . '/' . implode( '/', $fileurl );
			}
			
			/* XenForo allows multiple codes - we don't. */
			$code = explode( "\n", $row['smilie_text'] );
			$code = array_shift( $code );
			
			/* And our set */
			try
			{
				$category	= $this->db->select( '*', 'xf_smilie_category', array( "smilie_category_id=?", $row['smilie_category_id'] ) )->first();
				$title		= $this->getPhrase( "smilie_category_{$category['smilie_category_id']}_title" );
				
				if ( is_null( $title ) )
				{
					/* Bubble Up */
					throw new \UnderflowException;
				}
			}
			catch( \UnderflowException $e )
			{
				$category = array(
					'display_order'	=> 1,
				);
				
				$title = "Converted";
			}
			
			$set = array(
				'set'		=> md5( $title ),
				'title'		=> $title,
				'position'	=> $category['display_order']
			);
			
			$info = array(
				'id'			=> $row['smilie_id'],
				'typed'			=> $code,
				'filename'		=> $filename,
				'emo_position'	=> $row['display_order'],
			);
			
			$libraryClass->convert_emoticon( $info, $set, $this->app->_session['more_info']['convert_emoticons']['keep_existing_emoticons'], $filepath, $filedata );
			
			$libraryClass->setLastKeyValue( $row['smilie_id'] );
		}
	}
	
	public function convert_profile_fields()
	{
		$libraryClass = $this->getLibrary();
		
		foreach( $this->fetch( 'xf_user_field', 'field_id' ) AS $row )
		{
			$info						= array();
			$info['pf_id']				= $row['field_id'];
			$merge						= $this->app->_session['more_info']['convert_profile_fields']["map_pfield_{$row['field_id']}"] != 'none' ? $this->app->_session['more_info']['convert_profile_fields']["map_pfield_{$row['field_id']}"] : NULL;
			$info['pf_type']			= $this->_fieldMap( $row['field_type'] );
			$info['pf_name']			= $this->getPhrase( "user_field_{$row['field_id']}" );
			$info['pf_desc']			= $this->getPhrase( "user_field_{$row['field_id']}_desc" );
			$info['pf_content'] 		= ( !in_array( $row['field_type'], array( 'textbox', 'textarea' ) ) ) ? \unserialize( $row['field_choices'] ) : NULL;
			$info['pf_not_null']		= $row['required'];
			$info['pf_member_hide']		= ( $row['viewable_profile'] > 0 ) ? 0 : 1;
			$info['pf_max_input']		= $row['max_length'];
			$info['pf_member_edit'] 	= ( in_array( $row['user_editable'], array( 'yes', 'once' ) ) ) ? 0 : 1;
			$info['pf_position']		= $row['display_order'];
			$info['pf_show_on_reg']		= ( $row['show_registration'] > 0 ) ? 1 : 0;
			$info['pf_input_format']	= ( $row['match_type'] == 'regex' AND $row['match_regex'] ) ? '/' . $row['match_regex'] . '/i' : NULL;
			$info['pf_multiple']		= ( $row['field_type'] == 'multiselect' ) ? 1 : 0;
			
			$libraryClass->convert_profile_field( $info, $merge );
		}
	}
	
	/**
	 * Maps the XenForo field ttype to IPS field type
	 *
	 * @param	string	XF Field Type
	 * @return	string	IPS Field Type
	 */
	protected function _fieldMap( $type )
	{
		switch( $type )
		{
			case 'select':
			case 'radio':
			case 'checkbox':
				return ucwords( $type );
				break;
			
			case 'textbox':
				return 'Text';
				break;
			
			case 'textarea':
				return 'TextArea';
				break;
			
			case 'multiselect':
				return 'Select';
				break;
			
			default:
				return 'Text';
				break;
		}
	}
	
	public function convert_groups()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'user_group_id' );
		
		foreach( $this->fetch( 'xf_user_group', 'user_group_id' ) AS $row )
		{
			/* Basic info */
			$info = array(
				'g_id'		=> $row['user_group_id'],
				'g_name'	=> $row['title'],
			);
			
			/* XenForo stores raw CSS to style usernames - we can convert this into a <span> tag with an inline style attribute */
			$style = str_replace( array( '<br>', '<br />' ), '', nl2br( $row['username_css'] ) );
			$info['prefix'] = "<span style='{$style}'>";
			$info['suffix']	= "</span>";
			
			/* General Permissions */
			foreach( $this->db->select( '*', 'xf_permission_entry', array( "user_group_id=?", $row['user_group_id'] ) ) AS $perm )
			{
				switch( $perm['permission_id'] )
				{
					case 'view':
						$info['g_view_board'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;
		
					case 'deleteOwnPost' :
						$info['g_delete_own_posts'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;
		
					case 'editOwnPost':
						$info['g_edit_posts'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;
							
					case 'postThread':
						$info['g_post_polls'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;

					case 'votePoll':
						$info['g_vote_polls'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;

					case 'deleteOwnThread':
						$info['g_delete_own_posts'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;

					case 'maxRecipients':
						$info['g_max_mass_pm'] = $perm['permission_value_int'];
						break;

					case 'bypassFloodCheck':
						$info['g_avoid_flood'] = ($perm['permission_value'] == 'allow') ? 1 : 0;
						break;
				}
			}
			
			$merge = ( $this->app->_session['more_info']['convert_groups']["map_group_{$row['user_group_id']}"] != 'none' ) ? $this->app->_session['more_info']['convert_groups']["map_group_{$row['user_group_id']}"] : NULL;
			
			$libraryClass->convert_group( $info, $merge );
			
			$libraryClass->setLastKeyValue( $row['user_group_id'] );
		}
	}
	
	public function convert_warn_reason()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'warning_definition_id' );
		
		foreach( $this->fetch( 'xf_warning_definition', 'warning_definition_id' ) AS $row )
		{
			$title	= $this->getPhrase( "warning_definition_{$row['warning_definition_id']}_title" );
			$remove	= ( $row['expiry_type'] == 'never' ) ? 0 : $row['expiry_default'];

			switch( $row['expiry_type'] )
			{
				case 'weeks':
					$remove = $remove * 7;
					break;
				
				case 'months':
					$remove = $remove * 30;
					break;
				
				case 'years':
					$remove = $remove * 365;
					break;
			}
			
			
			$libraryClass->convert_warn_reason( array(
				'wr_id'					=> $row['warning_definition_id'],
				'wr_name'				=> $title,
				'wr_points_override'	=> $row['is_editable'],
				'wr_remove'				=> $remove,
				'wr_remove_unit'		=> 'd',
				'wr_remove_override'	=> $row['is_editable'],
			) );
			
			$libraryClass->setLastKeyValue( $row['warning_definition_id'] );
		}
	}
	
	public function convert_members()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'xf_user.user_id' );
		
		$it = $this->fetch( 'xf_user', 'xf_user.user_id' )
			->join( 'xf_user_profile', 'xf_user.user_id = xf_user_profile.user_id' )
			->join( 'xf_user_option', 'xf_user.user_id = xf_user_option.user_id' );
		
		foreach( $it AS $row )
		{
			/* Fetch our password. XenForo supports multiple authentication types (XF, vB, SMF, etc.) so let's try and retain these as much as possible. */
			try
			{
				$auth = $this->db->select( 'data', 'xf_user_authenticate', array( "user_id=?", $row['user_id'] ) )->first();
				$data = \unserialize( $auth );
				$hash = $data['hash'];
				
				/* vB, IPB, etc. */
				$salt = NULL;
				if ( isset( $data['salt'] ) )
				{
					$salt = $data['salt'];
				}
				
				/* SMF */
				if ( isset( $data['username'] ) )
				{
					$salt = $data['username'];
				}
			}
			catch( \UnderflowException $e )
			{
				/* Ut oh... do something random. */
				$hash = md5( uniqid() );
				$salt = md5( uniqid() );
			}
			
			/* IP Address */
			try
			{
				$ip = $this->db->select( 'ip', 'xf_ip', array( "user_id=? AND action=?", $row['user_id'], 'register' ) )->first();
			}
			catch( \UnderflowException $e )
			{
				try
				{
					$ip = $this->db->select( 'ip', 'xf_ip', array( "user_id=? AND action=?", $row['user_id'], 'login' ) )->first();
				}
				catch( \UnderflowException $e )
				{
					$ip = '127.0.0.1';
				}
			}
			
			/* Last warn */
			try
			{
				$last_warn = $this->db->select( 'warning_date', 'xf_warning', array( "user_id=?", $row['user_id'] ), "warning_id DESC" )->first();
			}
			catch( \UnderflowException $e )
			{
				$last_warn = 0;
			}
			
			/* PM Count */
			try
			{
				$pm_count = $this->db->select( 'COUNT(*)', 'xf_conversation_master', array( "user_id=?", $row['user_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$pm_count = 0;
			}
			
			/* Auto Track */
			$auto_track = 0;
			if ( $row['default_watch_state'] == 'watch_email' )
			{
				$auto_track = array(
					'content'	=> 1,
					'comments'	=> 1,
					'method'	=> 'immediate',
				);
			}
			
			/* Ban */
			try
			{
				$ban = $this->db->select( 'end_date', 'xf_user_ban', array( "user_id=?", $row['user_id'] ) )->first();
				
				if ( $ban == 0 )
				{
					$ban = -1;
				}
			}
			catch( \UnderflowException $e )
			{
				$ban = 0;
			}
			
			/* Timezone Verification */
			try
			{
				$timezone = new \DateTimeZone( $row['timezone'] ); # if invalid, this will throw an exception
			}
			catch( \Exception $e )
			{
				$timezone = 'UTC';
			}
			
			/* Main Member Information */
			$info = array(
				'member_id'					=> $row['user_id'],
				'name'						=> $row['username'],
				'email'						=> $row['email'],
				'conv_password'					=> $hash,
				'conv_password_extra'			=> $salt,
				'member_group_id'			=> $row['user_group_id'],
				'joined'					=> $row['register_date'],
				'ip_address'				=> $ip,
				'warn_level'				=> $row['warning_points'],
				'warn_lastwarn'				=> $last_warn,
				'bday_day'					=> $row['dob_day'],
				'bday_month'				=> $row['dob_month'],
				'bday_year'					=> $row['dob_year'],
				'msg_count_new'				=> $row['conversations_unread'],
				'msg_count_total'			=> $pm_count,
				'last_visit'				=> $row['last_activity'],
				'last_activity'				=> $row['last_activity'],
				'auto_track'				=> $auto_track,
				'temp_ban'					=> $ban,
				'mgroup_others'				=> $row['secondary_group_ids'],
				'members_bitoptions'		=> array(
					'view_sigs'					=> $row['content_show_signature']
				),
				'pp_setting_count_comments'	=> 1, # always on for XF
				'pp_reputation_points'		=> $row['like_count'],
				'timezone'					=> $timezone,
				'allow_admin_mails'			=> $row['receive_admin_email'],
				'member_title'				=> $row['custom_title'],
				'member_posts'				=> $row['message_count'],
			);
			
			/* Profile Photo */
			$group		= floor( $row['user_id'] / 1000 );
			$path		= rtrim( $this->app->_session['more_info']['convert_members']['photo_location'], '/' );
			$filename	= NULL;
			$filepath	= NULL;
			if ( file_exists( $path . '/l/' . $group . '/' . $row['user_id'] . '.jpg' ) )
			{
				$filename = $row['user_id'] . '.jpg';
				$filepath = $path . '/l/' . $group;
			}
			else
			{
				/* Got a gravatar? */
				if ( $row['gravatar'] )
				{
					$info['pp_gravatar'] = $row['gravatar'];
				}
			}
			
			/* Profile Fields */
			$pfields = array();
			foreach( $this->db->select( '*', 'xf_user_field_value', array( "user_id=?", $row['user_id'] ) ) AS $field )
			{
				$pfields[ $field['field_id'] ] = $field['field_value'];
			}
			
			/* Pseudo Fields */
			foreach( array( 'homepage', 'location', 'occupation', 'about', 'gender' ) AS $pseudo )
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
						'pf_type'			=> ($pseudo == 'gender') ? 'Select' : 'Text',
						'pf_content'		=> ($pseudo == 'gender') ? json_encode( array( 'male', 'female' ) ) : '[]',
						'pf_member_hide'	=> 0,
						'pf_max_input'		=> ($pseudo == 'gender') ? 0 : 255,
						'pf_member_edit'	=> 1,
						'pf_show_on_reg'	=> 0,
						'pf_admin_only'		=> 0,
					) );
				}
				
				$pfields[$pseudo] = $row[$pseudo];
			}
			
			$libraryClass->convert_member( $info, $pfields, $filename, $filepath );
			
			/* Followers */
			foreach( $this->db->select( '*', 'xf_user_follow', array( "user_id=?", $row['user_id'] ) ) AS $follower )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'core',
					'follow_area'			=> 'members',
					'follow_rel_id'			=> $follower['follow_user_id'],
					'follow_rel_id_type'	=> 'core_members',
					'follow_member_id'		=> $follower['user_id'],
				) );
			}
			
			/* Warnings */
			foreach( $this->db->select( '*', 'xf_warning', array( "content_type=? AND user_id=?", 'user', $row['user_id'] ) ) AS $warning )
			{
				$libraryClass->convert_warn_log( array(
					'wl_id'				=> $warning['warning_id'],
					'wl_member'			=> $warning['user_id'],
					'wl_moderator'		=> $warning['warning_user_id'],
					'wl_date'			=> $warning['warning_date'],
					'wl_reason'			=> $warning['warning_definition_id'],
					'wl_points'			=> $warning['points'],
					'wl_note_mods'		=> $warning['notes'],
					'wl_expire_date'	=> $warning['expiry_date'],
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['user_id'] );
		}
	}
	
	public function convert_statuses()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'profile_post_id' );
		
		foreach( $this->fetch( 'xf_profile_post', 'profile_post_id' ) AS $row )
		{
			/* We have to query for the IP Address */
			try
			{
				$ip = $this->db->select( 'ip', 'xf_ip', array( "ip_id=?", $row['ip_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$ip = '127.0.0.1';
			}
			
			/* Approval State */
			switch( $row['message_state'] )
			{
				case 'visible':
					$approved = 1;
					break;
				
				case 'moderated':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
			}
			
			$info = array(
				'status_id'			=> $row['profile_post_id'],
				'status_member_id'	=> $row['profile_user_id'],
				'status_date'		=> $row['post_date'],
				'status_content'	=> $row['message'],
				'status_replies'	=> $row['comment_count'],
				'status_author_id'	=> $row['user_id'],
				'status_author_ip'	=> $ip,
				'status_approved'	=> $approved,
			);
			
			$libraryClass->convert_status( $info );
			
			$libraryClass->setLastKeyValue( $row['profile_post_id'] );
		}
	}
	
	public function convert_status_replies()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'profile_post_comment_id' );
		
		foreach( $this->fetch( 'xf_profile_post_comment', 'profile_post_comment_id' ) AS $row )
		{
			/* We need to query for the IP Address */
			try
			{
				$ip = $this->db->select( 'ip', 'xf_ip', array( "ip_id=?", $row['ip_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$ip = '127.0.0.1';
			}
			
			/* Approval State */
			switch( $row['message_state'] )
			{
				case 'visible':
					$approved = 1;
					break;
				
				case 'moderated':
					$approved = 0;
					break;
				
				case 'deleted':
					$approved = -1;
					break;
			}
			
			$info = array(
				'reply_id'			=> $row['profile_post_comment_id'],
				'reply_status_id'	=> $row['profile_post_id'],
				'reply_member_id'	=> $row['user_id'],
				'reply_date'		=> $row['comment_date'],
				'reply_content'		=> $row['message'],
				'reply_approved'	=> $approved,
				'reply_ip_address'	=> $ip,
			);
			
			$libraryClass->convert_status_reply( $info );
			
			$libraryClass->setLastKeyValue( $row['profile_post_comment_id'] );
		}
	}
	
	public function convert_ignored_users()
	{
		$libraryClass = $this->getLibrary();
		
		foreach( $this->fetch( 'xf_user_ignored', 'user_id' ) AS $row )
		{
			$libraryClass->convert_ignored_user( array(
				'ignore_id'			=> $row['user_id'] . '-' . $row['ignore_user_id'],
				'ignore_owner_id'	=> $row['user_id'],
				'ignore_ignore_id'	=> $row['ignore_user_id']
			) );
		}
	}
	
	public function convert_ranks()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'minimum_level' );
		
		foreach( $this->fetch( 'xf_user_title_ladder', 'minimum_level' ) AS $row )
		{
			$libraryClass->convert_rank( array(
				'id'		=> $row['minimum_level'],
				'title'		=> $row['title'],
				'posts'		=> $row['minimum_level'],
			) );
			
			$libraryClass->setLastKeyValue( $row['minimum_level'] );
		}
	}
	
	public function convert_private_messages()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'conversation_id' );
		
		foreach( $this->fetch( 'xf_conversation_master', 'conversation_id' ) AS $row )
		{
			$topic = array(
				'mt_id'				=> $row['conversation_id'],
				'mt_date'			=> $row['start_date'],
				'mt_title'			=> $row['title'],
				'mt_starter_id'		=> $row['user_id'],
				'mt_start_time'		=> $row['start_date'],
				'mt_last_post_time'	=> $row['last_message_date'],
				'mt_to_count'		=> $row['recipient_count'],
				'mt_replies'		=> $row['reply_count'],
			);
			
			$posts = array();
			foreach( $this->db->select( '*', 'xf_conversation_message', array( "conversation_id=?", $row['conversation_id'] ) ) AS $post )
			{
				try
				{
					$ip = $this->db->select( 'ip', 'xf_ip', array( "ip_id=?", $post['ip_id'] ) )->first();
				}
				catch( \UnderflowException $e )
				{
					$ip = '127.0.0.1';
				}
				
				$posts[$post['message_id']] = array(
					'msg_id'			=> $post['message_id'],
					'msg_date'			=> $post['message_date'],
					'msg_post'			=> $post['message'],
					'msg_author_id'		=> $post['user_id'],
					'msg_ip_address'	=> $ip,
				);
			}
			
			$maps = array();
			foreach( $this->db->select( '*', 'xf_conversation_user', array( 'conversation_id=?', $row['conversation_id'] ) ) AS $map )
			{
				try
				{
					$recip = $this->db->select( '*', 'xf_conversation_recipient', array( "conversation_id=? AND user_id=?", $row['conversation_id'], $map['owner_user_id'] ) )->first();
				}
				catch( \UnderflowException $e )
				{
					$recip = array(
						'conversation_id'	=> $row['conversation_id'],
						'user_id'			=> $map['owner_user_id'],
						'recipient_state'	=> 'deleted',
						'last_read_date'	=> time(),
					);
				}
				$maps[$map['owner_user_id']] = array(
					'map_user_id'			=> $map['owner_user_id'],
					'map_read_time'			=> $recip['last_read_date'],
					'map_user_active'		=> ( $recip['recipient_state'] == 'active' ) ? 1 : 0,
					'map_user_banned'		=> ( $recip['recipient_state'] == 'deleted_ignored' ) ? 1 : 0,
					'map_has_unread'		=> $map['is_unread'],
					'map_is_starter'		=> ( $map['owner_user_id'] == $row['user_id'] ) ? 1 : 0,
					'map_last_topic_reply'	=> $map['last_message_date']
				);
			}
			
			$libraryClass->convert_private_message( $topic, $posts, $maps );
			
			$libraryClass->setLastKeyValue( $row['conversation_id'] );
		}
	}
}