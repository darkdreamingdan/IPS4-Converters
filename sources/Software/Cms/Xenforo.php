<?php

/**
 * @brief		Converter XenForo Page Nodes Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Cms;

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
		/* Child classes must override this method */
		return "XenForo Page Nodes";
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
		return "xenforo";
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
			'convert_cms_pages' => array(
				'table'				=> 'xf_page',
				'where'				=> NULL,
			)
		);
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
		return array( 'core' => array( 'xenforo' ) );
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
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 *
	 * @return	string		Message to display
	 */
	public function finish()
	{
		
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
	
	public function convert_cms_pages()
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'xf_page.node_id' );
		
		foreach( $this->fetch( 'xf_page', 'xf_page.node_id' )->join( 'xf_node', 'xf_node.node_id = xf_page.node_id' ) AS $row )
		{
			try
			{
				$template = $this->db->select( 'template', 'xf_template', array( "title=?", "_page_node.{$row['node_id']}" ) )->first();
			}
			catch( \UnderflowException $e )
			{
				$template = '';
			}
			
			$libraryClass->convert_cms_page( array(
				'page_id'		=> $row['node_id'],
				'page_name'		=> $row['title'],
				'page_seo_name'	=> $row['node_name'],
				'page_content'	=> $template,
			) );
			
			$libraryClass->setLastKeyValue( $row['node_id'] );
		}
	}
}