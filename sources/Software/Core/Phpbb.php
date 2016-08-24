<?php

/**
 * @brief		Converter phpBB Class
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

class _Phpbb extends \IPS\convert\Software
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
		return "phpBB 3.1";
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
		return "phpbb";
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
			'convert_emoticons'			=> array(
				'table'		=> 'smilies',
				'where'		=> NULL
			),
			'convert_profile_fields'	=> array(
				'table'		=> 'profile_fields',
				'where'		=> NULL
			),
			'convert_groups'			=> array(
				'table'		=> 'groups',
				'where'		=> NULL
			),
			'convert_members'			=> array(
				'table'		=> 'users',
				'where'		=> array( "user_type<>?", 2 )
			),
			'convert_ignored_users'		=> array(
				'table'		=> 'zebra',
				'where'		=> array( "foe=?", 1 )
			),
			'convert_private_messages'	=> array(
				'table'		=> 'privmsgs',
				'where'		=> NULL
			),
			'convert_ranks'				=> array(
				'table'		=> 'ranks',
				'where'		=> array( "rank_special!=?", 1 ),
			),
			'convert_profanity_filters'	=> array(
				'table'		=> 'words',
				'where'		=> NULL
			),
			'convert_banfilters'		=> array(
				'table'			=> 'banfilters',
				'where'			=> NULL,
				'extra_steps'	=> array( 'convert_banfilters2' ),
			),
			'convert_banfilters2'		=> array(
				'table'			=> 'disallow',
				'where'			=> NULL,
			)
		);
	}
	
	/**
	 * Count Source Rows for a specific step
	 *
	 * @param	string		$table		The table containing the rows to count.
	 * @param	array|NULL	$where		WHERE clause to only count specific rows, or NULL to count all.
	 * @return	integer
	 */
	public function countRows( $table, $where=NULL )
	{
		switch( $table )
		{
			case 'banfilters':
			case 'disallow':
				$count = 0;
				$count += $this->db->select( 'COUNT(*)', 'banlist', array( "ban_userid=?", 0 ) )->first();
				$count += $this->db->select( 'COUNT(*)', 'disallow' )->first();
				return $count;
				break;
			
			default:
				return parent::countRows( $table, $where );
				break;
		}
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
		return <<<INFORMATION
You can typically obtain these details from the file located at /path/to/phpbb/config.php
INFORMATION;
	}
	
	/**
	 * List of conversion methods that require additional information
	 *
	 * @return	array
	 */
	public static function checkConf()
	{
		return array(
			'convert_emoticons',
			'convert_profile_fields',
			'convert_groups',
			'convert_members',
			'convert_ranks',
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
		switch( $method )
		{
			case 'convert_emoticons':
				$return['convert_emoticons'] = array(
					'emoticon_path'				=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> "This is typically: /path/to/phpbb/images/smilies",
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
				
				foreach( $this->db->select( '*', 'profile_fields' ) AS $field )
				{
					\IPS\Member::loggedIn()->language()->words["map_pfield_{$field['field_id']}"]		= $this->db->select( 'lang_name', 'profile_lang', array( "field_id=?", $field['field_id'] ) )->first();
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
				
				foreach( $this->db->select( '*', 'groups' ) AS $group )
				{
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['group_id']}"]			= $group['group_name'];
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['group_id']}_desc"]	= \IPS\Member::loggedIn()->language()->addToStack( 'map_group_desc' );
					
					$return['convert_groups']["map_group_{$group['group_id']}"] = array(
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
				
				/* We can only retain one type of photo */
				$return['convert_members']['photo_hash'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> "This is the string of characters that precedes the filename of all uploaded avatars - if you are not sure what this is, you can typically find it by referencing the files located at /path/to/phpbb/images/avatars/upload",
				);
				
				/* Find out where the photos live */
				\IPS\Member::loggedIn()->language()->words['photo_location_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'photo_location_nodb_desc' );
				$return['convert_members']['photo_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> "This is typically: /path/to/phpbb/images/avatars/upload",
					'field_validation'		=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);
				
				$return['convert_members']['gallery_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> FALSE,
					'field_extra'			=> array(),
					'field_hint'			=> "This is typically: /path/to/phpbb/images/avatars/gallery - enter nothing to skip converting avatars from the avatar gallery",
					'field_validation'		=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);
				
				break;
			
			case 'convert_ranks':
				$return['convert_ranks']['rank_location'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Text',
					'field_default'			=> NULL,
					'field_required'		=> TRUE,
					'field_extra'			=> array(),
					'field_hint'			=> "This is typically: /path/to/phpbb/images/ranks",
					'field_validation'		=> function( $value ) { if ( !@is_dir( $value ) ) { throw new \DomainException( 'path_invalid' ); } },
				);
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
		$post = nl2br($post);
		$post = html_entity_decode($post, ENT_COMPAT | ENT_HTML401, "UTF-8");
		// I have no idea what phpBB was thinking, but they like to hav e [code:randomstring] tags instead of proper BBCode...
		// Oh, and just to spice things up, 'randomstring' can have a : in it
		$post = preg_replace("#(\w+)://#", "\\1{~~}//", $post );
		//$post = preg_replace("#\[(\w+?)=([^\]:]*):([^\]]*)\]#", "[$1=$2]", $post);
		$post = preg_replace( "#\[([a-zA-Z-\*]+?)=([^\]:]*):([^\]]*)\]#", "[$1=$2]", $post );
		$post = str_replace( '{~~}//', '://', $post );
		//$post = preg_replace("#\[(\w+?):([^\]]*)\]#", "[$1]", $post); - \w is too restrictive - the code could have a dash in it, for example
		$post = preg_replace( "#\[([a-zA-Z0-9-\*]+?):([^\]]*)\]#", "[$1]", $post );
		$post = preg_replace( "#\[/([^\]:]*):([^\]]*)\]#"    , "[/$1]", $post );

		// We need to rework quotes a little (there's no standard on [quote]'s attributes)
		$post = str_replace('][quote', ']
[quote', $post);
		$post = preg_replace("#\[quote=(.+)\]#", "[quote name=$1]", $post);
		
		// We need to adjust the size of [size=] tags, as they take different units: IPB: 1-8; PHPBB: 1-200
		$post = preg_replace_callback(
			'(\[size=(\d+)\])',
			function($m) { 
				return '[size=' . round(($m[1]/100) * 4) . ']';
			},
			$post);

		// We also don't need [/*] - IP.Board can work out XHTML for itself!
		$post = preg_replace("/\[\/\*\]/", '', $post);

		// Oh, and we need to sort out emoticons
		$post = preg_replace("/<!-- s(\S+?) --><img(?:[^<]+?)<!-- (?:\S+?) -->/", '$1', $post);

		// And URLs
		$post = preg_replace("#<a class=\"postlink\" href=\"([^\]]+?)\"([^>]+?)?>([^\]]+?)</a>#i", "[url=$1]$3[/url]", $post);

		return $post;
	}
	
	public function convert_emoticons()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'smiley_id' );
		
		foreach( $this->fetch( 'smilies', 'smiley_id' ) AS $row )
		{
			$libraryClass->convert_emoticon( array(
				'id'		=> $row['smiley_id'],
				'typed'		=> $row['code'],
				'filename'	=> $row['smiley_url'],
				'emo_position'	=> $row['smiley_order'],
				'width'		=> $row['smiley_width'],
				'height'	=> $row['smiley_height'],
			), array(
				'set'		=> md5( 'Converted' ),
				'title'		=> 'Converted',
				'position'	=> 1,
			), $this->app->_session['more_info']['convert_emoticons']['keep_existing_emoticons'], $this->app->_session['more_info']['convert_emoticons']['emoticon_path'] );
			
			$libraryClass->setLastKeyValue( $row['smiley_id'] );
		}
	}
	
	public function convert_profile_fields()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'field_id' );
		
		foreach( $this->fetch( 'profile_fields', 'field_id' ) AS $row )
		{
			try
			{
				$name = $this->db->select( 'lang_name', 'profile_lang', array( "field_id=?", $row['field_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$name = $row['field_name'];
			}
			
			$field_type	= explode( '.', $row['field_type'] );
			$field_type	= array_pop( $field_type );
			$default	= $row['field_default_value'];
			
			switch( $field_type )
			{
				case 'bool':
					$type = 'YesNo';
					break;
				
				case 'date':
					$type = 'Date';
					break;
				
				case 'dropdown':
					$type = 'Select';
					
					$options = array();
					
					foreach( $this->db->select( 'option_id, lang_value', 'profile_fields_lang', array( "field_id=?", $row['field_id'] ) )->setKeyField( 'option_id' )->setValueField( 'lang_value' ) AS $key => $value )
					{
						$options[$key] = $value;
					}
					
					$default = json_encode( $options );
					break;
				
				case 'googleplus':
				case 'string':
					$type = 'Text';
					break;
				
				case 'int':
					$type = 'Number';
					break;
				
				case 'text':
					$type = 'TextArea';
					break;
				
				case 'url':
					$type = 'Url';
					break;
				
				default:
					$type = 'Text';
					break;
			}
			
			$info = array(
				'pf_id'				=> $row['field_id'],
				'pf_name'			=> $name,
				'pf_type'			=> $type,
				'pf_content'		=> $default,
				'pf_not_null'		=> $row['field_required'],
				'pf_member_hide'	=> $row['field_hide'],
				'pf_max_input'		=> ( $field_type == 'dropdown' ) ? NULL : $row['field_maxlen'], # ignore this for selects
				'pf_member_edit'	=> $row['field_active'],
				'pf_position'		=> $row['field_order'],
				'pf_show_on_reg'	=> $row['field_show_on_reg'],
				'pf_input_format'	=> $row['field_validation']  ? '/' . preg_quote( $row['field_validation'], '/' ) . '/i' : NULL,
			);
			
			$merge = ( $this->app->_session['more_info']['convert_profile_fields']["map_pfield_{$row['field_id']}"] != 'none' ) ? $this->app->_session['more_info']['convert_profile_fields']["map_pfield_{$row['field_id']}"] : NULL;
			
			$libraryClass->convert_profile_field( $info, $merge );
			
			$libraryClass->setLastKeyValue( $row['field_id'] );
		}
	}
	
	public function convert_groups()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'group_id' );
		
		foreach( $this->fetch( 'groups', 'group_id' ) AS $row )
		{
			$prefix = '';
			$suffix = '';
			
			if ( $row['group_colour'] )
			{
				$prefix = "<span style='color: {$row['group_colour']}'>";
				$suffix = "</span>";
			}
			
			$info = array(
				'g_id'				=> $row['group_id'],
				'g_name'			=> $row['group_name'],
				'g_use_pm'			=> $row['group_receive_pm'],
				'g_max_messages'	=> $row['group_message_limit'],
				'g_max_mass_pm'		=> $row['group_max_recipients'],
				'prefix'			=> $prefix,
				'suffix'			=> $suffix,
			);
			
			$merge = ( $this->app->_session['more_info']['convert_groups']["map_group_{$row['group_id']}"] != 'none' ) ? $this->app->_session['more_info']['convert_groups']["map_group_{$row['group_id']}"] : NULL;
			
			$libraryClass->convert_group( $info, $merge );
		}
	}
	
	public function convert_members()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'user_id' );
		
		foreach( $this->fetch( 'users', 'user_id', array( "user_type<>?", 2 ) ) AS $row )
		{
			/* Work out birthday */
			$bday_day	= NULL;
			$bday_month	= NULL;
			$bday_year	= NULL;
			
			if ( $row['user_birthday'] AND $row['user_birthday'] != " 0- 0-   0"  )
			{
				list( $bday_day, $bday_month, $bday_year ) = explode( '-', $row['user_birthday'] );
				
				if ( trim( $bday_day, ' -' ) == '0' )
				{
					$bday_day = NULL;
				}
				
				if ( trim( $bday_month, ' -' ) == '0' )
				{
					$bday_month = NULL;
				}
				
				if ( trim( $bday_year ) == '0' )
				{
					$bday_year = NULL;
				}
			}
			
			/* Work out secondary groups */
			$groups = array();
			foreach( $this->db->select( 'group_id', 'user_group', array( "user_id=? AND group_id!=? AND user_pending=?", $row['user_id'], $row['group_id'], 0 ) ) AS $group )
			{
				$groups[] = $group;
			}
			
			/* Work out timezone - we don't really need to create an instance of \DateTimeZone here, however it helps check for invalid ones */
			try
			{
				$timezone = new \DateTimeZone( $row['user_timezone'] );
			}
			catch( \Exception $e )
			{
				$timezone = 'UTC';
			}
			
			/* Work out banned stuff */
			$temp_ban = 0;
			try
			{
				$ban = $this->db->select( '*', 'banlist', array( "ban_userid=?", $row['user_id'] ) )->first();
				
				if ( $ban['ban_end'] == 0 )
				{
					$temp_ban = -1;
				}
				else
				{
					$temp_ban = $ban['ban_end'];
				}
			}
			catch( \UnderflowException $e ) {}
			
			/* Array of basic data */
			$info = array(
				'member_id'				=> $row['user_id'],
				'email'					=> $row['user_email'],
				'name'					=> $row['username'],
				'password'				=> $row['user_password'],
				'member_group_id'		=> $row['group_id'],
				'joined'				=> $row['user_regdate'],
				'ip_address'			=> $row['user_ip'],
				'warn_level'			=> $row['user_warnings'],
				'warn_lastwarn'			=> $row['user_last_warning'],
				'bday_day'				=> $bday_day,
				'bday_month'			=> $bday_month,
				'bday_year'				=> $bday_year,
				'msg_count_new'			=> $row['user_unread_privmsg'],
				'msg_count_total'		=> $this->db->select( 'COUNT(*)', 'privmsgs_to', array( "user_id=?", $row['user_id'] ) )->first(),
				'last_visit'			=> $row['user_lastvisit'],
				'last_activity'			=> $row['user_lastmark'],
				'mgroup_others'			=> $groups,
				'timezone'				=> $timezone,
				'allow_admin_mails'		=> ( $row['user_allow_massemail'] ) ? TRUE : FALSE,
				'members_disable_pm'	=> ( $row['user_allow_pm'] ) ? 0 : 1,
				'member_posts'			=> $row['user_posts'],
				'member_last_post'		=> $row['user_lastpost_time']
			);
			
			/* Profile Photos */
			$filepath = NULL;
			$filename = NULL;
			
			if ( $row['user_avatar_type'] )
			{
				/* Is it numeric? Apparently phpBB doesn't clean up after itself. */
				if ( is_numeric( $row['user_avatar_type'] ) )
				{
					switch( $row['user_avatar_type'] )
					{
						case 1:
							$row['user_avatar_type'] = 'avatar.driver.upload';
							break;
						
						case 2:
							$row['user_avatar_type'] = 'avatar.driver.remote';
							break;
						
						case 3:
							$row['user_avatar_type'] = 'avatar.driver.gallery';
							break;
					}
				}
				
				switch( $row['user_avatar_type'] )
				{
					case 'avatar.driver.upload':
						$fileext = explode( '.', $row['user_avatar'] );
						$fileext = array_pop( $fileext );
						$filepath = $this->app->_session['more_info']['convert_members']['photo_location'];
						$filename = $this->app->_session['more_info']['convert_members']['photo_hash'] . '_' . $row['user_id'] . '.' . $fileext;
						break;
					
					case 'avatar.driver.gravatar':
						$info['pp_photo_type'] = 'gravatar';
						$info['pp_gravatar']	= $row['user_avatar'];
						break;
					
					case 'avatar.driver.remote':
						/* The library uses file_get_contents() so we can just pop the file name off and pass the URL directly */
						$filebits = explode( "/", $row['user_avatar'] );
						$filename = array_pop( $filebits );
						$filepath = implode( '/', $filebits );
						break;
					
					case 'avatar.driver.gallery':
						/* I couldn't figure out how to set these up so they are probably wrong */
						$filepath = $this->app->_session['more_info']['convert_members']['gallery_location'];
						$filename = $row['user_avatar'];
						break;
				}
			}
			
			/* Profile Fields */
			$pfields = array();
			try
			{
				$userfields = $this->db->select( '*', 'profile_fields_data', array( "user_id=?", $row['user_id'] ) )->first();
			}
			catch( \UnderflowException $e )
			{}
			
			foreach( $this->db->select( '*', 'profile_fields' ) AS $field )
			{
				/* if this is a select field, we need to pull the value from profile_fields_lang */
				$field_type = explode( '.', $field['field_type'] );
				$field_type = array_pop( $field_type );
				
				if ( isset( $userfields['pf_' . $field['field_name'] ] ) )
				{
					if ( $field_type == 'dropdown' )
					{
						/* phpBB stores the option incremented from 1 - however the options are stored incremented from 0. So if you select the first option, 1 is stored but profile_fields_lang actually has it as 0. I don't get it either. */
						$pfields[ $field['field_id'] ] = $this->db->select( 'lang_value', 'profile_fields_lang', array( "field_id=? AND option_id=?", $field['field_id'], $userfields['pf_' . $field['field_name'] ] - 1 ) )->first();
					}
					else
					{
						$pfields[ $field['field_id'] ] = $userfields['pf_' . $field['field_name'] ];
					}
				}
			}
			
			/* Finally */
			$libraryClass->convert_member( $info, $pfields, $filename, $filepath );
			
			/* Friends */
			foreach( $this->db->select( '*', 'zebra', array( "user_id=? AND friend=?", $row['user_id'], 1 ) ) AS $follower )
			{
				$libraryClass->convert_follow( array(
					'follow_app'			=> 'core',
					'follow_area'			=> 'members',
					'follow_rel_id'			=> $follower['zebra_id'],
					'follow_rel_id_type'	=> 'core_members',
					'follow_member_id'		=> $follower['user_id'],
				) );
			}
			
			/* Warnings */
			foreach( $this->db->select( '*', 'warnings', array( "user_id=? AND post_id<>?", $row['user_id'], 0 ) ) AS $warn )
			{
				try
				{
					$log	= $this->db->select( '*', 'log', array( "log_id=?", $warn['log_id'] ) )->first();
					$data	= @\unserialize( $log['log_data'] );
				}
				catch( \UnderflowException $e )
				{
					$log	= array( 'user_id' => 0 );
					$data	= array( 0 => NULL );
				}
				
				$libraryClass->convert_warn_log( array(
					'wl_id'					=> $warn['warning_id'],
					'wl_member'				=> $warn['user_id'],
					'wl_moderator'			=> $log['user_id'],
					'wl_date'				=> $warn['warning_time'],
					'wl_points'				=> 1,
					'wl_note_member'		=> isset( $data[0] ) ? $data[0] : NULL,
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['user_id'] );
		}
	}
	
	public function convert_ignored_users()
	{
		$libraryClass = $this->getLibrary();
		
		foreach( $this->fetch( 'zebra', 'user_id', array( "foe=?", 1 ) ) AS $row )
		{
			$info = array(
				'ignore_id'			=> $row['user_id'] . '-' . $row['zebra_id'],
				'ignore_owner_id'	=> $row['user_id'],
				'ignore_ignore_id'	=> $row['zebra_id'],
			);
			
			foreach( \IPS\core\Ignore::types() AS $type )
			{
				$info['ignore_' . $type] = 1;
			}
			
			$libraryClass->convert_ignored_user( $info );
		}
	}
	
	public function convert_ranks()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'rank_id' );
		
		foreach( $this->fetch( 'ranks', 'rank_id', array( "rank_special=?", 0 ) ) AS $row )
		{
			$filepath = NULL;
			if ( $row['rank_image'] )
			{
				$filepath = rtrim( $this->app->_session['more_info']['convert_ranks']['rank_location'], '/' ) . '/' . $row['rank_image'];
			}
			
			$libraryClass->convert_rank( array(
				'id'			=> $row['rank_id'],
				'title'			=> $row['rank_title'],
				'posts'			=> $row['rank_min'],
			), $filepath );
			
			$libraryClass->setLastKeyValue( $row['rank_id'] );
		}
	}
	
	public function convert_profanity_filters()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'word_id' );
		
		foreach( $this->fetch( 'words', 'word_id' ) AS $row )
		{
			$libraryClass->convert_profanity_filter( array(
				'wid'		=> $row['word_id'],
				'type'		=> $row['word'],
				'swop'		=> $row['replacement']
			) );
			
			$libraryClass->setLastKeyValue( $row['word_id'] );
		}
	}
	
	public function convert_banfilters()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'ban_id' );
		
		foreach( $this->fetch( 'banlist', 'ban_id', array( "ban_userid=?", 0 ) ) AS $row )
		{
			$type = NULL;
			if ( $row['ban_ip'] )
			{
				$type = 'ip';
			}
			else if ( $row['ban_email'] )
			{
				$type = 'email';
			}
			
			/* If Type is null - skip */
			if ( is_null( $type ) )
			{
				$libraryClass->setLastKeyValue( $row['ban_id'] );
				continue;
			}
			
			$libraryClass->convert_banfilter( array(
				'ban_id'		=> 'b' . $row['ban_id'],
				'ban_type'		=> $type,
				'ban_content'	=> ( $type == 'ip' ) ? $row['ban_ip'] : $row['ban_email'],
				'ban_date'		=> $row['ban_start'],
				'ban_reason'	=> $row['ban_give_reason']
			) );
			
			$libraryClass->setLastKeyValue( $row['ban_id'] );
		}
	}
	
	public function convert_banfilters2()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'disallow_id' );
		
		foreach( $this->fetch( 'disallow', 'disallow_id' ) AS $row )
		{
			$libraryClass->convert_banfilter( array(
				'ban_id'		=> 'd' . $row['disallow_id'],
				'ban_content'	=> $row['disallow_username'],
				'ban_type'		=> 'name',
				'ban_date'		=> time(),
			) );
			
			$libraryClass->setLastKeyValue( $row['disallow_id'] );
		}
	}
	
	public function convert_private_messages()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'msg_id' );
		
		foreach( $this->fetch( 'privmsgs', 'msg_id' ) AS $row )
		{
			/* Set up the topic */
			$topic = array(
				'mt_id'				=> $row['msg_id'],
				'mt_date'			=> $row['message_time'],
				'mt_title'			=> $row['message_subject'],
				'mt_starter_id'		=> $row['author_id'],
				'mt_start_time'		=> $row['message_time'],
				'mt_last_post_time'	=> $row['message_time'],
			);
			
			/* We cannot convert phpBB PM's as full topics, so we need to do each individual one as it's own individual topic */
			$posts = array();
			$posts[$row['msg_id']] = array(
				'msg_id'			=> $row['msg_id'],
				'msg_date'			=> $row['message_time'],
				'msg_post'			=> $this->fixPostData($row['message_text']), 
				'msg_author_id'		=> $row['author_id'],
				'msg_ip_address'	=> $row['author_ip'],
			);
			
			/* Now the maps */
			$maps = array();
			
			/* First one first initial author */
			$maps[$row['author_id']] = array(
				'map_user_id'			=> $row['author_id'],
				'map_is_starter'		=> 1,
				'map_last_topic_reply'	=> $row['message_time'],
			);
			
			/* Parse the to_address field for missing recipients of PMs in stores folders*/
			foreach( explode ( ":", $row['to_address']) AS $toString )
			{
				// Sub out the u_ prefix
				$toString = substr ( $toString, 2 );
				// Make it an int
				$to = intval ( $toString );
				$maps[$to] = array(
					'map_user_id'			=> $to,
					'map_is_starter'		=> 0,
					'map_last_topic_reply'	=> $row['message_time']
				);
			}
			
			/* Now everyone else */
			foreach( $this->db->select( '*', 'privmsgs_to', array( "msg_id=?", $row['msg_id'] ) ) AS $to )
			{
				if ( !array_key_exists( $to['user_id'],$maps ) )
				{
					$maps[$to['user_id']] = array(
						'map_user_id'			=> $to['user_id'],
						'map_is_starter'		=> 0,
						'map_last_topic_reply'	=> $row['message_time']
					);
				}
			}
			
			$libraryClass->convert_private_message( $topic, $posts, $maps );
			
			$libraryClass->setLastKeyValue( $row['msg_id'] );
		}
	}
}