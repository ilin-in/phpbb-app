<?php
/**
*
* @package migration
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License v2
*
*/

class phpbb_db_migration_v309rc4 extends phpbb_db_migration
{
	function depends_on()
	{
		return array('phpbb_db_migration_v309rc3');
	}

	function update_schema()
	{
		return array();
	}

	function update_data()
	{
	}
}
