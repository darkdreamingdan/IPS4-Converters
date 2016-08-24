//<?php

/**
 * About This Hook
 * A common problem with the way IP.Board 3.x handled converters that supported passwords was that the converter login handler could often
 * become out of date with the converters package. In IPS4, we still ship with the converter login handler for sites that have already
 * converted, however now we can overload that for those converting into IPS4 or those who upgrade the converter application which changes
 * the database columns used to store password / salt information. Those who do not upgrade the app will continue to use the older one.
 */
class convert_hook_Login extends _HOOK_CLASS_
{
	/**
	 * Authenticate
	 *
	 * @param	array	$values	Values from form
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $values )
	{
		try
		{
			/* Get member(s) */
			$members = array();
			if ( $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME )
			{
				$_member = \IPS\Member::load( $values['auth'], 'name', NULL );
				if ( $_member->member_id )
				{
					$members[] = $_member;
				}
			}
			if ( $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL )
			{
				$_member = \IPS\Member::load( $values['auth'], 'email' );
				if ( $_member->member_id )
				{
					$members[] = $_member;
				}
			}
			
			/* If we didn't match any, throw an exception */
			if ( empty( $members ) )
			{
				throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $this->getLoginType( $this->authTypes ) ) ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
			}
			
			$apps = \IPS\Db::i()->select( 'app_key', 'convert_apps', array( 'login=?', 1 ) );
			
			foreach( $apps as $sw )
			{
				/* loop found members */
				foreach( $members as $member )
				{
					/* Check the app method exists */
					if ( !method_exists( $this, $sw ) )
					{
						continue;
					}
					
					/* We still want to use the parent methods (no sense in recreating them) so copy conv_password_extra to misc */
					$member->misc = $member->conv_password_extra;
					$success = $this->$sw( $member, $values['password'] );
					
					unset( $member->misc );
					unset( $member->changed['misc'] );
					if ( $success )
					{
						/*	Update password and return */
						$member->conv_password			= NULL;
						$member->conv_password_extra	= NULL;
						$member->members_pass_salt		= $member->generateSalt();
						$member->members_pass_hash		= $member->encryptedPassword( $values['password'] );
						$member->save();
						$member->memberSync( 'onPassChange', array( $values['password'] ) );
						
						return $member;
					}
				}
			}
			
			/* Still here? Throw a password incorrect exception */
			throw new \IPS\Login\Exception( 'login_err_bad_password', \IPS\Login\Exception::BAD_PASSWORD, NULL, $member );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}
	
	/* Add new methods here for newly supported converters or if we need to change any that are in the parent. Custom converters should use their own hooks that extend \IPS\Login\Convert */
	
	/**
	 * vBulletin 5 Wrapper Method
	 *
	 * @param	\IPS|Member		$member
	 * @param	string			$password
	 * @return	bool
	 */
	public function vbulletin5( $member, $password )
	{
		try
		{
			return $this->vb5connect( $member, $password );
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}
	
	/**
	 * PunBB
	 *
	 * @param	\IPS\Member		$member
	 * @param	string			$password
	 * @return	bool
	 */
	public function punbb( $member, $password )
	{
		try
		{
			$success = FALSE;
			
			if ( mb_strlen( $member->conv_password ) == 40 )
			{
				/* Password with salt */
				$success = \IPS\Login::compareHashes( $member->conv_password, sha1( $member->conv_password_extra . sha1( $password ) ) );
				
				if ( !$success )
				{
					/* No salt */
					$success = \IPS\Login::compareHashes( $member->conv_password, sha1( $password ) );
				}
			}
			else
			{
				/* MD5 */
				$success = \IPS\Login::compareHashes( $member->conv_password, md5( $password ) );
			}
			
			return $success;
		}
		catch ( \RuntimeException $e )
		{
			if ( method_exists( get_parent_class(), __FUNCTION__ ) )
			{
				return call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );
			}
			else
			{
				throw $e;
			}
		}
	}
}