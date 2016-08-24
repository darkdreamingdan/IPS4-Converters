<?php
/**
 * @brief		Converter UBBthreads Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2015 Invision Power Services, Inc.
 * @package		IPS Social Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 * @version		
 */

namespace IPS\convert\Software\Core;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _UBBthreads extends \IPS\convert\Software
{
	/**
	 * @brief   Emoticons WHERE statement
	 * @see     convert_emoticons()
	 */
	protected static $emoticonsWhere = 'GRAEMLIN_IS_ACTIVE=1';

	/**
	 * @brief   Groups WHERE statement
	 * @see     convert_groups()
	 */
	protected static $groupsWhere = 'GROUP_IS_DISABLED=0';

	/**
	 * @brief   Ignored users WHERE statement
	 * @see     convert_ignored_users()
	 */
	protected static $ignoredUsersWhere = "USER_IGNORE_LIST IS NOT NULL AND USER_IGNORE_LIST NOT IN ( '', '-' )";

	/**
	 * @brief   Members WHERE statement
	 * @see     convert_members()
	 */
	protected static $membersWhere = array( 'u.USER_LOGIN_NAME<>?', '**DONOTDELETE**' );

	/**
	 * This is.. kind of hacky, but it's used so we can try and support non-exact profanity matches without converting
	 * other regexes we don't support
	 *
	 * @brief   Profanity filters WHERE statement
	 * @see     convert_profanity_filters()
	 */
	protected static $profanityFiltersWhere = '(
		CENSOR_WORD NOT LIKE "%(.*)%" AND (
			CENSOR_WORD NOT LIKE "%(.*?)%" OR (
			    CENSOR_WORD LIKE "%(.*?)" AND CENSOR_WORD NOT LIKE "%(.*?)%(.*?)"
			)
		)
	)';

	/**
	 * Software Name
	 *
	 * @return	string
	 * @throws	\BadMethodCallException
	 */
	public static function softwareName()
	{
		/* Child classes must override this method */
		return "UBBthreads";
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
		return "UBBthreads";
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
			'convert_banfilters'			=> array(
				'table'							=> 'pseudo_banfilters',  /** @see countRows() */
				'where'							=> NULL
			),
			'convert_emoticons'				=> array(
				'table'							=> 'GRAEMLINS',
				'where'							=> static::$emoticonsWhere,
			),
			'convert_groups'				=> array(
				'table'							=> 'GROUPS',
				'where'							=> static::$groupsWhere
			),
			'convert_ignored_users'         => array(
				'table'                         => 'USER_PROFILE',
				'where'                         => static::$ignoredUsersWhere
			),
			'convert_members'				=> array(
				'table'							=> array( 'USERS', 'u' ),
				'where'							=> static::$membersWhere,
				'extra_steps'                   => array( 'convert_members_followers' ),
			),
			'convert_members_followers'		=> array(
				'table'							=> 'WATCH_LISTS',
				'where'							=> array( 'WATCH_TYPE=?', 'u' )
			),
			'convert_ranks'					=> array(
				'table'							=> 'USER_TITLES',
				'where'							=> NULL
			),
			'convert_private_messages'		=> array(
				'table'							=> 'PRIVATE_MESSAGE_TOPICS',
				'where'							=> NULL
			),
			'convert_profanity_filters'		=> array(
				'table'							=> 'CENSOR_LIST',
				'where'							=> static::$profanityFiltersWhere
			),
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
	 * Count Source Rows for a specific step
	 *
	 * @param    string     $table The table containing the rows to count.
	 * @param    array|NULL $where WHERE clause to only count specific rows, or NULL to count all.
	 * @return int
	 * @throws \IPS\convert\Exception
	 */
	public function countRows( $table, $where=NULL )
	{
		switch ( $table )
		{
			case 'pseudo_banfilters':
				return parent::countRows( 'BANNED_EMAILS' )
				     + parent::countRows( 'BANNED_HOSTS' )
				     + parent::countRows( 'RESERVED_NAMES' );
		}

		return parent::countRows( $table, $where );
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
			'convert_emoticons',
			'convert_groups',
			'convert_members'
		);
	}

	/**
	 * Attempt to convert a textual date(time) representation to a DateTime instance
	 *
	 * @param   string  $date
	 * @return  \IPS\DateTime|null
	 */
	protected function stringToDateTime( $date )
	{
		try
		{
			return new \IPS\DateTime( $date );
		}
		catch( \Exception $e )
		{
			return NULL;
		}
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
			case 'convert_emoticons':
				$return['convert_emoticons'] = array();

				\IPS\Member::loggedIn()->language()->words['emoticon_path'] = \IPS\Member::loggedIn()->language()->addToStack( 'source_path', FALSE, array( 'sprintf' => array( 'UBBthreads' ) ) );
				$return['convert_emoticons']['emoticon_path'] = array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Text',
					'field_default'		=> NULL,
					'field_required'	=> TRUE,
					'field_extra'		=> array(),
					'field_hint'		=> NULL
				);
				$return['convert_emoticons']['keep_existing_emoticons']	= array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Checkbox',
					'field_default'		=> TRUE,
					'field_required'	=> FALSE,
					'field_extra'		=> array(),
					'field_hint'		=> NULL,
				);
				break;

			case 'convert_groups':
				$return['convert_groups'] = array();

				$options = array();
				$options['none'] = \IPS\Member::loggedIn()->language()->addToStack( 'none' );
				foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_groups' ), 'IPS\Member\Group' ) AS $group )
				{
					$options[ $group->g_id ] = $group->name;
				}

				foreach( $this->db->select( '*', 'GROUPS' ) AS $group )
				{
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['GROUP_ID']}"]        = $group['GROUP_NAME'];
					\IPS\Member::loggedIn()->language()->words["map_group_{$group['GROUP_ID']}_desc"]   = \IPS\Member::loggedIn()->language()->addToStack( 'map_group_desc' );

					$return['convert_groups']["map_group_{$group['GROUP_ID']}"] = array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Select',
						'field_default'		=> NULL,
						'field_required'	=> FALSE,
						'field_extra'		=> array( 'options' => $options ),
						'field_hint'		=> NULL
					);
				}
				break;

			case 'convert_members':
				$return['convert_members'] = array();

				/* Should we use the username or display name property when converting? */
				$return['convert_members']['username'] = array(
					'field_class'			=> 'IPS\\Helpers\\Form\\Radio',
					'field_default'			=> 'display_name',
					'field_required'		=> TRUE,
					'field_extra'			=> array( 'options' => array( 'username' => \IPS\Member::loggedIn()->language()->addToStack( 'user_name' ), 'display_name' => \IPS\Member::loggedIn()->language()->addToStack( 'display_name' ) ) ),
					'field_hint'			=> \IPS\Member::loggedIn()->language()->addToStack( 'username_hint' ),
				);

				foreach( array( 'homepage', 'occupation', 'hobbies', 'location', 'icq', 'yahoo', 'aim', 'msn' ) AS $field )
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

		return $return[ $method ];
	}
	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
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
		\IPS\Task::queue( 'core', 'RecountMemberContent', array( 'dummy' => 'placeholder' ), 4, array( 'dummy' ) );

		/* Attachments */
		\IPS\Task::queue( 'core', 'RebuildAttachmentThumbnails', array( 'dummy' => 'placeholder' ), 4, array( 'dummy' ) );

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
		$post = preg_replace( "#\[quote=(.+?)\]#i", "[quote name=\"$1\"]", $post );
		$post = str_ireplace( [ "[image]", '[/image]', '[size:', '[color:' ], [ '[img]', '[/img]', '[size=', '[color=' ], $post );

		return $post;
	}

	// Insert convert_ methods here

	public function convert_banfilters()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();

		/* Banned e-mails */
		foreach ( $this->db->select( 'BANNED_EMAIL', 'BANNED_EMAILS' ) as $row )
		{
			$libraryClass->convert_banfilter( array(
				'ban_id'        => $row,  // We don't actually have an ID column
				'ban_type'      => 'email',
				'ban_content'   => str_replace( '%', '*', $row )  // Replace UBB's wildcard character
			) );
		}

		/* Banned IP's */
		foreach( $this->db->select( 'BANNED_HOST', 'BANNED_HOSTS' ) as $row )
		{
			$libraryClass->convert_banfilter( array(
				'ban_id'        => $row,  // We don't actually have an ID column
				'ban_type'      => 'ip',
				'ban_content'   => str_replace( '%', '*', $row )  // Replace UBB's wildcard character
			) );
		}

		/* Banned / "reserved" names */
		foreach ( $this->db->select( 'RESERVED_USERNAME', 'RESERVED_NAMES' ) as $row )
		{
			$libraryClass->convert_banfilter( array(
				'ban_id'        => $row,  // We don't actually have an ID column
				'ban_type'      => 'name',
				'ban_content'   => str_replace( '%', '*', $row )  // Replace UBB's wildcard character; TODO: Verify UBB actually supports wildcards here
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_emoticons()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach( $this->fetch( 'GRAEMLINS', 'GRAEMLIN_ID', static::$emoticonsWhere ) as $row )
		{
			$info = array(
				'id'            => $row['GRAEMLIN_ID'],
				'typed'         => $row['GRAEMLIN_SMILEY_CODE'] ?: ':'.$row['GRAEMLIN_MARKUP_CODE'].':',
				'width'         => $row['GRAEMLIN_WIDTH'],
				'height'        => $row['GRAEMLIN_HEIGHT'],
				'filename'      => $row['GRAEMLIN_IMAGE'],
				'emo_position'  => $row['GRAEMLIN_ID'],
			);

			$set = array(
				'set'		=> md5( 'Converted' ),
				'title'		=> 'Converted',
				'position'	=> 1,
			);

			$libraryClass->convert_emoticon(
				$info, $set, $this->app->_session['more_info']['convert_emoticons']['keep_existing_emoticons'],
				rtrim( $this->app->_session['more_info']['convert_emoticons']['emoticon_path'], '/' ) . "/images/graemlins/default"
			);
			$libraryClass->setLastKeyValue( $row['GRAEMLIN_ID'] );
		}
	}

	public function convert_groups()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'GROUP_ID' );

		foreach ( $this->fetch( 'GROUPS', 'GROUP_ID', static::$groupsWhere ) as $row )
		{
			/* TODO: Can we enable custom titles per group? */
			$info = array(
				'g_id'      => $row['GROUP_ID'],
				'g_name'    => $row['GROUP_NAME'],
			);

			$merge = $this->app->_session['more_info']['convert_groups']["map_group_{$row['GROUP_ID']}"] != 'none' ? $this->app->_session['more_info']['convert_groups']["map_group_{$row['GROUP_ID']}"] : NULL;

			$libraryClass->convert_group( $info, $merge );
			$libraryClass->setLastKeyValue( $row['GROUP_ID'] );
		}
	}

	public function convert_ignored_users()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'USER_ID' );

		foreach ( $this->fetch( 'USER_PROFILE', 'USER_ID', static::$ignoredUsersWhere ) as $row )
		{
			/* Proper database modeling? CSV's? JSON? What are those things? */
			foreach ( explode( '-', $row['USER_IGNORE_LIST'] ) as $ignoredMemberMaybe )
			{
				if ( ! $ignoredMemberMaybe )
				{
					/* We split an empty string. Fabulous. */
					continue;
				}

				$info = array(
					'ignore_id'         => $row['USER_ID'] . '-' . $ignoredMemberMaybe,
					'ignore_owner_id'   => $row['USER_ID'],
					'ignore_ignore_id'  => $ignoredMemberMaybe
				);

				/* Assume we want to ignore everything by this member */
				foreach ( \IPS\core\Ignore::types() as $type )
				{
					$info[ 'ignore_' . $type ] = 1;
				}

				$libraryClass->convert_ignored_user( $info );
				$libraryClass->setLastKeyValue( $row['USER_ID'] );
			}
		}
	}

	public function convert_members()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'u.USER_ID' );

		$select = $this->fetch( array( 'USERS', 'u' ), 'u.USER_ID', static::$membersWhere )
		               ->join( array( 'BANNED_USERS', 'b' ), 'u.USER_ID=b.USER_ID', 'LEFT' )
		               ->join( array( 'USER_PROFILE', 'p' ), 'u.USER_ID=p.USER_ID' )
			           ->join( array( 'USER_DATA', 'd' ), 'u.USER_ID=d.USER_ID' );

		foreach ( $select as $row )
		{
			/* Birthday */
			$birthday = array( 'day' => NULL, 'month' => NULL, 'year' => NULL );

			if ( $birthdayDt = $this->stringToDateTime( $row['USER_BIRTHDAY'] ) )
			{
				$birthday['day']    = $birthdayDt->format( 'j' );
				$birthday['month']  = $birthdayDt->format( 'n' );
				$birthday['year']   = $birthdayDt->format( 'Y' );
			}

			/* Member groups */
			$secondaryGroups = iterator_to_array( $this->db->select( 'GROUP_ID', 'USER_GROUPS', array( 'USER_ID=?', $row['USER_ID'] ) ) );
			$primaryGroup    = array_shift( $secondaryGroups );

			$info = array(
				'member_id'                 => $row['USER_ID'],
				'name'                      => ( $this->app->_session['more_info']['convert_members']['username'] == 'user_name' )
					? $row['USER_LOGIN_NAME']
					: $row['USER_DISPLAY_NAME'],
				'email'                     => $row['USER_REAL_EMAIL'],
				'md5_password'              => $row['USER_PASSWORD'],
				'member_group_id'           => $primaryGroup,
				'mgroup_others'             => $secondaryGroups,
				'joined'                    => \IPS\DateTime::create()->setTimestamp( $row['USER_REGISTERED_ON'] ),
				'ip_address'                => $row['USER_REGISTRATION_IP'],
				'bday_day'                  => $birthday['day'],
				'bday_month'                => $birthday['month'],
				'bday_year'                 => $birthday['year'],
				'msg_count_total'           => $row['USER_TOTAL_PM'],
				'last_visit'                => \IPS\DateTime::create()->setTimestamp( $row['USER_LAST_VISIT_TIME'] ),
				'last_activity'             => \IPS\DateTime::create()->setTimestamp(
					max( (int) $row['USER_LAST_POST_TIME'], (int) $row['USER_LAST_SEARCH_TIME'] )
				),
				'allow_admin_mails'         => ( $row['USER_ACCEPT_ADMIN_EMAILS'] != 'Off' ),
				'member_title'              => $row['USER_CUSTOM_TITLE'],
				'member_posts'              => $row['USER_TOTAL_POSTS'],
				'signature'					=> $this->fixPostData( $row['USER_DEFAULT_SIGNATURE'] ),
				'member_last_post'          => \IPS\DateTime::create()->setTimestamp( $row['USER_LAST_POST_TIME'] ),
				'temp_ban'                  => isset( $row['BAN_EXPIRATION'] )
					? ( ( (string) $row['BAN_EXPIRATION'] === '0' )
						? -1
						: \IPS\DateTime::create()->setTimestamp( $row['BAN_EXPIRATION'] ) )
					: NULL,
			);

			/* Profile fields */
			$pfields = array();
			foreach( array( 'homepage', 'occupation', 'hobbies', 'location', 'icq', 'yahoo', 'aim', 'msn' ) AS $pseudo )
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

				$fieldColumn = 'USER_' . \strtoupper( $pseudo );
				$pfields[ $pseudo ] = isset( $row[ $fieldColumn ] ) ? $row[ $fieldColumn ] : NULL;
			}

			/* Profile photo */
			$profilePhotoName = NULL;
			$profilePhotoPath = NULL;

			if ( !empty( $row['USER_AVATAR'] ) AND ( $row['USER_AVATAR'] != 'http://' ) )
			{
				try
				{
					$profilePhotoName = pathinfo( parse_url( $row['USER_AVATAR'], PHP_URL_PATH ), PATHINFO_FILENAME );
					$profilePhotoData = \IPS\Http\Url::external( $row['USER_AVATAR'] )->request()->get();
				}
				catch( \IPS\Http\Request\Exception $e ) { }
			}

			$libraryClass->convert_member( $info, $pfields, $profilePhotoName, NULL, $profilePhotoData );
			$libraryClass->setLastKeyValue( $row['USER_ID'] );
		}
	}

	public function convert_members_followers()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach ( $this->fetch( 'WATCH_LISTS', 'WATCH_ID', array( 'WATCH_TYPE=?', 'u' ) ) as $row )
		{
			$libraryClass->convert_follow( array(
				'follow_app'            => 'core',
				'follow_area'           => 'member',
				'follow_rel_id'         => $row['WATCH_ID'],
				'follow_rel_id_type'    => 'core_members',
				'follow_member_id'      => $row['USER_ID'],
				'follow_notify_freq'    => $row['WATCH_NOTIFY_IMMEDIATE'] ? 'immediate' : 'none',
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}

	public function convert_ranks()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'USER_TITLE_ID' );

		foreach ( $this->fetch( 'USER_TITLES', 'USER_TITLE_ID' ) as $row )
		{
			/**
			 * TODO: We should probably prompt to keep or overwrite existing titles, since this can currently result...
			 * ...in duplicate entries for the same post counts if the local rows are not dropped
			 */
			$libraryClass->convert_rank( array(
				'id'    => $row['USER_TITLE_ID'],
				'title' => $row['USER_TITLE_NAME'],
				'posts' => $row['USER_TITLE_POST_COUNT']
			) );

			$libraryClass->setLastKeyValue( $row['USER_TITLE_ID'] );
		}
	}

	public function convert_private_messages()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'TOPIC_ID' );

		foreach ( $this->fetch( 'PRIVATE_MESSAGE_TOPICS', 'TOPIC_ID' ) as $topicRow )
		{
			$topic = array(
				'mt_id'             => $topicRow['TOPIC_ID'],
				'mt_date'           => \IPS\DateTime::create()->setTimestamp( $topicRow['TOPIC_TIME'] ),
				'mt_title'          => $topicRow['TOPIC_SUBJECT'],
				'mt_starter_id'     => $topicRow['USER_ID'],
				'mt_start_time'     => \IPS\DateTime::create()->setTimestamp( $topicRow['TOPIC_TIME'] ),
				'mt_last_post_time' => \IPS\DateTime::create()->setTimestamp( $topicRow['TOPIC_LAST_REPLY_TIME'] ),
				'mt_replies'        => $topicRow['TOPIC_REPLIES'],
			);

			$posts = array();
			foreach ( $this->db->select( '*', 'PRIVATE_MESSAGE_POSTS', array( 'TOPIC_ID=?', $topicRow['TOPIC_ID'] ) ) as $postRow )
			{
				$posts[ $postRow['POST_ID'] ] = array(
					'msg_id'        => $postRow['POST_ID'],
					'msg_date'      => \IPS\DateTime::create()->setTimestamp( $postRow['POST_TIME'] ),
					'msg_post'      => $this->fixPostData( $postRow['POST_DEFAULT_BODY'] ),
					'msg_author_id' => $postRow['USER_ID']
				);
			}

			$maps = array();
			foreach ( $this->db->select( '*', 'PRIVATE_MESSAGE_USERS',  array( 'TOPIC_ID=?', $topicRow['TOPIC_ID'] ) ) as $userRow )
			{
				$maps[ $userRow['USER_ID'] ] = array(
					'map_user_id'   => $userRow['USER_ID'],
					'map_read_time' => $userRow['MESSAGE_LAST_READ']
				);
			}

			$libraryClass->convert_private_message( $topic, $posts, $maps );
			$libraryClass->setLastKeyValue( $topicRow['TOPIC_ID'] );
		}
	}

	public function convert_profanity_filters()
	{
		/** @var \IPS\convert\Library\Core $libraryClass */
		$libraryClass = $this->getLibrary();

		foreach ( $this->db->select( '*', 'CENSOR_LIST', static::$profanityFiltersWhere ) as $row )
		{
			/**
			 * UBB seems to support regex based profanity filters to some extent. We want to avoid converting those,
			 * but if there is a filter that ends with with a wildcard match, we can convert it to a non-exact profanity
			 * filter
			 */
			$parsedWord = str_replace( '(.*?)', '', $row['CENSOR_WORD'] );
			$exact      = ( $parsedWord == $row['CENSOR_WORD'] );

			$libraryClass->convert_profanity_filter( array(
				'wid'       => $row['CENSOR_WORD'],
				'type'      => $parsedWord,
				'swop'      => $row['CENSOR_REPLACE_WITH'],
				'm_exact'   => $exact
			) );
		}

		throw new \IPS\convert\Software\Exception;
	}
}