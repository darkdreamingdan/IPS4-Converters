//<?php

class convert_hook_Db extends _HOOK_CLASS_
{
	/**
	 * Build SELECT statement
	 *
	 * @param	array|string		$columns	The columns (as an array) to select or an expression
	 * @param	array|string		$table		The table to select from. Either (string) table_name or (array) ( name, alias ) or \IPS\Db\Select object
	 * @param	array|string|NULL	$where		WHERE clause - see \IPS\Db::compileWhereClause() for details
	 * @param	string|NULL			$order		ORDER BY clause
	 * @param	array|int			$limit		Rows to fetch or array( offset, limit )
	 * @param	string|NULL|array	$group		Column(s) to GROUP BY
	 * @param	array|string|NULL	$having		HAVING clause (same format as WHERE clause)
	 * @param	int					$flags		Bitwise flags
	 *	@li	\IPS\Db::SELECT_DISTINCT				Will use SELECT DISTINCT
	 *	@li	\IPS\Db::SELECT_SQL_CALC_FOUND_ROWS		Will add SQL_CALC_FOUND_ROWS
	 *	@li	\IPS\Db::SELECT_MULTIDIMENSIONAL_JOINS	Will return the result as a multidimensional array, with each joined table separately
	 * @return	\IPS\Db\Select
	 *
	 */
	public function select( $columns=NULL, $table, $where=NULL, $order=NULL, $limit=NULL, $group=NULL, $having=NULL, $flags=0 )
	{
		try
		{
			switch( $table )
			{
				case 'custom_bbcode':
					$table = 'convert_custom_bbcode';
					break;
				
				case 'bbcode_mediatag':
					$table = 'convert_bbcode_mediatag';
					break;
			}
			
			return parent::select( $columns, $table, $where, $order, $limit, $group, $having, $flags );
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