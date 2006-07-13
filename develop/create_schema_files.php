<?php
/** 
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2006 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
* This file creates new schema files for every database.
* The filenames will be prefixed with an underscore to not overwrite the current schema files.
*
* If you overwrite the original schema files please make sure you save the file with UNIX linefeeds.
*/

die("Please read the first lines of this script for instructions on how to enable it");

set_time_limit(0);

$schema_path = './../install/schemas/';

if (!is_writeable($schema_path))
{
	die('Schema path not writeable');
}

$schema_data = get_schema_struct();
$dbms_type_map = array(
	'mysql'		=> array(
		'INT:'		=> 'int(%d)',
		'BINT'		=> 'bigint(20)',
		'UINT'		=> 'mediumint(8) UNSIGNED',
		'UINT:'		=> 'int(%d) UNSIGNED',
		'TINT:'		=> 'tinyint(%d)',
		'USINT'		=> 'smallint(4) UNSIGNED',
		'BOOL'		=> 'tinyint(1) UNSIGNED',
		'VCHAR'		=> 'varchar(255)',
		'VCHAR:'	=> 'varchar(%d)',
		'CHAR:'		=> 'char(%d)',
		'XSTEXT'	=> 'text',
		'STEXT'		=> 'text',
		'TEXT'		=> 'text',
		'MTEXT'		=> 'mediumtext',
		'TIMESTAMP'	=> 'int(11) UNSIGNED',
		'DECIMAL'	=> 'decimal(5,2)',
		'VCHAR_BIN'	=> 'varchar(252) BINARY',
		'VCHAR_CI'	=> 'varchar(255)',
	),

	'firebird'	=> array(
		'INT:'		=> 'INTEGER',
		'BINT'		=> 'DOUBLE PRECISION',
		'UINT'		=> 'INTEGER',
		'UINT:'		=> 'INTEGER',
		'TINT:'		=> 'INTEGER',
		'USINT'		=> 'INTEGER',
		'BOOL'		=> 'INTEGER',
		'VCHAR'		=> 'VARCHAR(255)',
		'VCHAR:'	=> 'VARCHAR(%d)',
		'CHAR:'		=> 'CHAR(%d)',
		'XSTEXT'	=> 'BLOB SUB_TYPE TEXT',
		'STEXT'		=> 'BLOB SUB_TYPE TEXT',
		'TEXT'		=> 'BLOB SUB_TYPE TEXT',
		'MTEXT'		=> 'BLOB SUB_TYPE TEXT',
		'TIMESTAMP'	=> 'INTEGER',
		'DECIMAL'	=> 'DOUBLE PRECISION',
		'VCHAR_BIN'	=> 'VARCHAR(252)',
		'VCHAR_CI'	=> 'VARCHAR(255)',
	),

	'mssql'		=> array(
		'INT:'		=> '[int]',
		'BINT'		=> '[float]',
		'UINT'		=> '[int]',
		'UINT:'		=> '[int]',
		'TINT:'		=> '[int]',
		'USINT'		=> '[int]',
		'BOOL'		=> '[int]',
		'VCHAR'		=> '[varchar] (255)',
		'VCHAR:'	=> '[varchar] (%d)',
		'CHAR:'		=> '[char] (%d)',
		'XSTEXT'	=> '[varchar] (1000)',
		'STEXT'		=> '[varchar] (3000)',
		'TEXT'		=> '[varchar] (8000)',
		'MTEXT'		=> '[text]',
		'TIMESTAMP'	=> '[int]',
		'DECIMAL'	=> '[float]',
		'VCHAR_BIN'	=> '[nvarchar] (252)',
		'VCHAR_CI'	=> '[varchar] (255)',
	),

	'oracle'	=> array(
		'INT:'		=> 'number(%d)',
		'BINT'		=> 'number(20)',
		'UINT'		=> 'number(8)',
		'UINT:'		=> 'number(%d)',
		'TINT:'		=> 'number(%d)',
		'USINT'		=> 'number(4)',
		'BOOL'		=> 'number(1)',
		'VCHAR'		=> 'varchar2(255)',
		'VCHAR:'	=> 'varchar2(%d)',
		'CHAR:'		=> 'char(%d)',
		'XSTEXT'	=> 'varchar2(1000)',
		'STEXT'		=> 'varchar2(3000)',
		'TEXT'		=> 'clob',
		'MTEXT'		=> 'clob',
		'TIMESTAMP'	=> 'number(11)',
		'DECIMAL'	=> 'number(5, 2)',
		'VCHAR_BIN'	=> 'varchar2(252)',
		'VCHAR_CI'	=> 'varchar2(255)',
	),

	'sqlite'	=> array(
		'INT:'		=> 'int(%d)',
		'BINT'		=> 'bigint(20)',
		'UINT'		=> 'mediumint(8) UNSIGNED',
		'UINT:'		=> 'int(%d) UNSIGNED',
		'TINT:'		=> 'tinyint(%d)',
		'USINT'		=> 'mediumint(4) UNSIGNED',
		'BOOL'		=> 'tinyint(1) UNSIGNED',
		'VCHAR'		=> 'varchar(255)',
		'VCHAR:'	=> 'varchar(%d)',
		'CHAR:'		=> 'char(%d)',
		'XSTEXT'	=> 'text(65535)',
		'STEXT'		=> 'text(65535)',
		'TEXT'		=> 'text(65535)',
		'MTEXT'		=> 'mediumtext(16777215)',
		'TIMESTAMP'	=> 'int(11) UNSIGNED',
		'DECIMAL'	=> 'decimal(5,2)',
		'VCHAR_BIN'	=> 'varchar(252)',
		'VCHAR_CI'	=> 'varchar(255)',
	),

	'postgres'	=> array(
		'INT:'		=> 'INT4',
		'BINT'		=> 'INT8',
		'UINT'		=> 'INT4', // unsigned
		'UINT:'		=> 'INT4', // unsigned
		'USINT'		=> 'INT2', // unsigned
		'BOOL'		=> 'INT2', // unsigned
		'TINT:'		=> 'INT2',
		'VCHAR'		=> 'varchar(255)',
		'VCHAR:'	=> 'varchar(%d)',
		'CHAR:'		=> 'char(%d)',
		'XSTEXT'	=> 'varchar(1000)',
		'STEXT'		=> 'varchar(3000)',
		'TEXT'		=> 'varchar(8000)',
		'MTEXT'		=> 'TEXT',
		'TIMESTAMP'	=> 'INT4', // unsigned
		'DECIMAL'	=> 'decimal(5,2)',
		'VCHAR_BIN'	=> 'varchar(252)',
		'VCHAR_CI'	=> 'varchar_ci',
	),
);

// A list of types being unsigned for better reference in some db's
$unsigned_types = array('UINT', 'UINT:', 'USINT', 'BOOL', 'TIMESTAMP');

foreach (array('firebird', 'mssql', 'mysql', 'oracle', 'postgres', 'sqlite') as $dbms)
{
	$fp = fopen($schema_path . '_' . $dbms . '_schema.sql', 'wt');

	$line = '';

	// Write Header
	switch ($dbms)
	{
		case 'mysql':
			$line = "#\n# MySQL Schema for phpBB 3.x - (c) phpBB Group, 2005\n#\n# \$I" . "d: $\n#\n\n";
		break;

		case 'firebird':
			$line = "#\n# Firebird Schema for phpBB 3.x - (c) phpBB Group, 2005\n#\n# \$I" . "d: $\n#\n\n";
		break;

		case 'sqlite':
			$line = "#\n# SQLite Schema for phpBB 3.x - (c) phpBB Group, 2005\n#\n# \$I" . "d: $\n#\n\n";
			$line .= "BEGIN TRANSACTION;\n\n";
		break;

		case 'mssql':
			$line = "/*\n MSSQL Schema for phpBB 3.x - (c) phpBB Group, 2005\n\n \$I" . "d: $\n\n*/\n\n";
			$line .= "BEGIN TRANSACTION\nGO\n\n";
		break;

		case 'oracle':
			$line = "/*\n Oracle Schema for phpBB 3.x - (c) phpBB Group, 2005\n\n \$I" . "d: $\n\n*/\n\n";
			$line .= oracle_custom_data() . "\n";
		break;

		case 'postgres':
			$line = "/*\n PostgreSQL Schema for phpBB 3.x - (c) phpBB Group, 2005\n\n \$I" . "d: $\n\n*/\n\n";
			$line .= "BEGIN;\n\n";
			$line .= postgres_custom_data() . "\n";
		break;
	}

	fwrite($fp, $line);

	foreach ($schema_data as $table_name => $table_data)
	{
		// Write comment about table
		switch ($dbms)
		{
			case 'mysql':
			case 'firebird':
			case 'sqlite':
				fwrite($fp, "# Table: '{$table_name}'\n");
			break;

			case 'mssql':
			case 'oracle':
			case 'postgres':
				fwrite($fp, "/* Table: '{$table_name}' */\n");
			break;
		}

		// Create Table statement
		$generator = $textimage = false;
		$line = '';

		switch ($dbms)
		{
			case 'mysql':
			case 'firebird':
			case 'oracle':
			case 'sqlite':
			case 'postgres':
				$line = "CREATE TABLE {$table_name} (\n";
			break;

			case 'mssql':
				$line = "CREATE TABLE [{$table_name}] (\n";
			break;
		}

		// Write columns one by one...
		foreach ($table_data['COLUMNS'] as $column_name => $column_data)
		{
			// Get type
			if (strpos($column_data[0], ':') !== false)
			{
				list($orig_column_type, $column_length) = explode(':', $column_data[0]);

				$column_type = sprintf($dbms_type_map[$dbms][$orig_column_type . ':'], $column_length);
				$orig_column_type .= ':';
			}
			else
			{
				$orig_column_type = $column_data[0];
				$column_type = $dbms_type_map[$dbms][$column_data[0]];
			}

			switch ($dbms)
			{
				case 'mysql':
					$line .= "\t{$column_name} {$column_type} ";
					$line .= (!is_null($column_data[1])) ? "DEFAULT '{$column_data[1]}' " : '';
					$line .= 'NOT NULL';

					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$line .= ' auto_increment';
					}

					$line .= ",\n";
				break;

				case 'sqlite':
					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$line .= "\t{$column_name} INTEGER ";
					}
					else
					{
						$line .= "\t{$column_name} {$column_type} ";
					}

					if (isset($table_data['PRIMARY_KEY']))
					{
						$table_data['PRIMARY_KEY'] = (!is_array($table_data['PRIMARY_KEY'])) ? array($table_data['PRIMARY_KEY']) : $table_data['PRIMARY_KEY'];

						if (in_array($column_name, $table_data['PRIMARY_KEY']))
						{
							$line .= 'PRIMARY KEY ';
						}
					}

					$line .= 'NOT NULL ';
					$line .= (!is_null($column_data[1])) ? "DEFAULT '{$column_data[1]}'" : '';
					$line .= ",\n";
				break;

				case 'firebird':
					$line .= "\t{$column_name} {$column_type} ";

					if (!is_null($column_data[1]))
					{
						$line .= 'DEFAULT ' . ((is_numeric($column_data[1])) ? $column_data[1] : "'{$column_data[1]}'") . ' ';
					}

					$line .= "NOT NULL,\n";

					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$generator = $column_name;
					}
				break;

				case 'mssql':
					if ($column_type == '[text]')
					{
						$textimage = true;
					}

					$line .= "\t[{$column_name}] {$column_type} ";
					if (!is_null($column_data[1]))
					{
						$line .= 'DEFAULT (' . ((is_numeric($column_data[1])) ? $column_data[1] : "'{$column_data[1]}'") . ') ';
					}

					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$line .= 'IDENTITY (1, 1) ';
					}

					$line .= 'NOT NULL';
					$line .= " ,\n";
				break;

				case 'oracle':
					$line .= "\t{$column_name} {$column_type} ";
					$line .= (!is_null($column_data[1])) ? "DEFAULT '{$column_data[1]}' " : '';
					$line .= "NOT NULL,\n";

					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$generator = $column_name;
					}
				break;

				case 'postgres':
					$line .= "\t{$column_name} {$column_type} ";

					if (isset($column_data[2]) && $column_data[2] == 'auto_increment')
					{
						$line .= "DEFAULT nextval('{$table_name}_seq'),\n";

						// Make sure the sequence will be created before creating the table
						$line = "CREATE SEQUENCE {$table_name}_seq;\n\n" . $line;
					}
					else
					{
						$line .= (!is_null($column_data[1])) ? "DEFAULT '{$column_data[1]}' " : '';
						$line .= "NOT NULL";

						// Unsigned? Then add a CHECK contraint
						if (in_array($orig_column_type, $unsigned_types))
						{
							$line .= " CHECK ({$column_name} >= 0)";
						}

						$line .= ",\n";
					}
				break;
			}
		}

		switch ($dbms)
		{
			case 'firebird':
				// Remove last line delimiter...
				$line = substr($line, 0, -2);
				$line .= "\n);;\n\n";
			break;

			case 'sqlite':
				// Remove last line delimiter...
				$line = substr($line, 0, -2);
				$line .= "\n);\n\n";
			break;

			case 'mssql':
				$line = substr($line, 0, -2);
				$line .= "\n) ON [PRIMARY]" . (($textimage) ? ' TEXTIMAGE_ON [PRIMARY]' : '') . "\n";
				$line .= "GO\n\n";
			break;
		}

		// Write primary key
		if (isset($table_data['PRIMARY_KEY']))
		{
			if (!is_array($table_data['PRIMARY_KEY']))
			{
				$table_data['PRIMARY_KEY'] = array($table_data['PRIMARY_KEY']);
			}

			switch ($dbms)
			{
				case 'mysql':
				case 'postgres':
					$line .= "\tPRIMARY KEY (" . implode(', ', $table_data['PRIMARY_KEY']) . "),\n";
				break;

				case 'firebird':
					$line .= "ALTER TABLE {$table_name} ADD PRIMARY KEY (" . implode(', ', $table_data['PRIMARY_KEY']) . ");;\n\n";
				break;

				case 'mssql':
					$line .= "ALTER TABLE [{$table_name}] WITH NOCHECK ADD \n";
					$line .= "\tCONSTRAINT [PK_{$table_name}] PRIMARY KEY  CLUSTERED \n";
					$line .= "\t(\n";
					$line .= "\t\t[" . implode("],\n\t\t[", $table_data['PRIMARY_KEY']) . "]\n";
					$line .= "\t)  ON [PRIMARY] \n";
					$line .= "GO\n\n";
				break;

				case 'oracle':
					$line .= "\tCONSTRAINT pk_{$table_name} PRIMARY KEY (" . implode(', ', $table_data['PRIMARY_KEY']) . "),\n";
				break;
			}
		}

		switch ($dbms)
		{
			case 'oracle':
				// UNIQUE contrains to be added?
				if (isset($table_data['KEYS']))
				{
					foreach ($table_data['KEYS'] as $key_name => $key_data)
					{
						if (!is_array($key_data[1]))
						{
							$key_data[1] = array($key_data[1]);
						}

						if ($key_data[0] == 'UNIQUE')
						{
							$line .= "\tCONSTRAINT u_phpbb_{$key_name} UNIQUE (" . implode(', ', $key_data[1]) . "),\n";
						}
					}
				}

				// Remove last line delimiter...
				$line = substr($line, 0, -2);
				$line .= "\n)\n/\n\n";
			break;

			case 'postgres':
				// Remove last line delimiter...
				$line = substr($line, 0, -2);
				$line .= "\n);\n\n";
			break;
		}

		// Write Keys
		if (isset($table_data['KEYS']))
		{
			foreach ($table_data['KEYS'] as $key_name => $key_data)
			{
				if (!is_array($key_data[1]))
				{
					$key_data[1] = array($key_data[1]);
				}

				switch ($dbms)
				{
					case 'mysql':
						$line .= ($key_data[0] == 'INDEX') ? "\tKEY" : '';
						$line .= ($key_data[0] == 'UNIQUE') ? "\tUNIQUE" : '';
						$line .= ' ' . $key_name . ' (' . implode(', ', $key_data[1]) . "),\n";
					break;

					case 'firebird':
						$line .= ($key_data[0] == 'INDEX') ? 'CREATE INDEX' : '';
						$line .= ($key_data[0] == 'UNIQUE') ? 'CREATE UNIQUE INDEX' : '';

						$line .= ' ' . $table_name . '_' . $key_name . ' ON ' . $table_name . '(' . implode(', ', $key_data[1]) . ");;\n";
					break;

					case 'mssql':
						$line .= ($key_data[0] == 'INDEX') ? 'CREATE  INDEX' : '';
						$line .= ($key_data[0] == 'UNIQUE') ? 'CREATE  UNIQUE  INDEX' : '';
						$line .= " [{$key_name}] ON [{$table_name}]([" . implode('], [', $key_data[1]) . "]) ON [PRIMARY]\n";
						$line .= "GO\n\n";
					break;

					case 'oracle':
						if ($key_data[0] == 'UNIQUE')
						{
							continue;
						}

						$line .= ($key_data[0] == 'INDEX') ? 'CREATE INDEX' : '';
						
						$line .= " {$table_name}_{$key_name} ON {$table_name} (" . implode(', ', $key_data[1]) . ")\n";
						$line .= "/\n";
					break;

					case 'sqlite':
					case 'postgres':
						$line .= ($key_data[0] == 'INDEX') ? 'CREATE INDEX' : '';
						$line .= ($key_data[0] == 'UNIQUE') ? 'CREATE UNIQUE INDEX' : '';
						
						$line .= " {$table_name}_{$key_name} ON {$table_name} (" . implode(', ', $key_data[1]) . ");\n";
					break;
				}
			}
		}

		switch ($dbms)
		{
			case 'mysql':
				// Remove last line delimiter...
				$line = substr($line, 0, -2);
				$line .= "\n);\n\n";
			break;

			// Create Generator
			case 'firebird':
				if ($generator !== false)
				{
					$line .= "\nCREATE GENERATOR {$table_name}_gen;;\n";
					$line .= 'SET GENERATOR ' . $table_name . "_gen TO 0;;\n\n";

					$line .= 'CREATE TRIGGER t_' . $table_name . '_gen FOR ' . $table_name . "\n";
					$line .= "BEFORE INSERT\nAS\nBEGIN\n";
					$line .= "\tNEW.{$generator} = GEN_ID({$table_name}_gen, 1);\nEND;;\n\n";
				}
			break;

			case 'oracle':
				if ($generator !== false)
				{
					$line .= "\nCREATE SEQUENCE {$table_name}_seq\n/\n\n";

					$line .= "CREATE OR REPLACE TRIGGER ai_{$table_name}_seq\n";
					$line .= "BEFORE INSERT ON {$table_name}\n";
					$line .= "FOR EACH ROW WHEN (\n";
					$line .= "\tnew.{$generator} IS NULL OR new.{$generator} = 0\n";
					$line .= ")\nBEGIN\n";
					$line .= "\tSELECT {$table_name}_seq.nextval\n";
					$line .= "\tINTO :new.{$generator}\n";
					$line .= "\tFROM dual;\nEND;\n/\n\n";
				}
			break;
		}

		fwrite($fp, $line . "\n");
	}

	$line = '';

	// Write custom function at the end for some db's
	switch ($dbms)
	{
		case 'firebird':
			$line = "\n\nDECLARE EXTERNAL FUNCTION STRLEN\n";
			$line .= "\tCSTRING(32767)\n";
			$line .= "RETURNS INTEGER BY VALUE\n";
			$line .= "ENTRY_POINT 'IB_UDF_strlen' MODULE_NAME 'ib_udf';;\n\n";

			$line .= "DECLARE EXTERNAL FUNCTION LOWER CSTRING(80)\n";
			$line .= "RETURNS CSTRING(80) FREE_IT \n";
			$line .= "ENTRY_POINT 'IB_UDF_lower' MODULE_NAME 'ib_udf';;\n\n";
		break;

		case 'mssql':
			$line = "\nCOMMIT\nGO\n\n";
		break;

		case 'sqlite':
		case 'postgres':
			$line = "\nCOMMIT;";
		break;
	}

	fwrite($fp, $line);
	fclose($fp);
}


/**
* Define the basic structure
* The format:
*		array('{TABLE_NAME}' => {TABLE_DATA})
*		{TABLE_DATA}:
*			COLUMNS = array({column_name} = array({column_type}, {default}, {auto_increment}))
*			PRIMARY_KEY = {column_name(s)}
*			KEYS = array({key_name} = array({key_type}, {column_name(s)})),
*
*	Column Types:
*	INT:x		=> SIGNED int(x)
*	BINT		=> BIGINT
*	UINT		=> mediumint(8) UNSIGNED
*	UINT:x		=> int(x) UNSIGNED
*	TINT:x		=> tinyint(x)
*	USINT		=> smallint(4) UNSIGNED (for _order columns)
*	BOOL		=> tinyint(1) UNSIGNED
*	VCHAR		=> varchar(255)
*	CHAR:x		=> char(x)
*	XSTEXT		=> text for storing 1000 characters (topic_title for example)
*	STEXT		=> text for storing 3000 characters (normal input field with a max of 255 single-byte chars)
*	TEXT		=> text for storing 8000 characters (short text, descriptions, comments, etc.)
*	MTEXT		=> mediumtext (post text, large text)
*	VCHAR:x		=> varchar(x)
*	TIMESTAMP	=> int(11) UNSIGNED
*	DECIMAL		=> decimal number (5,2)
*	VCHAR_BIN	=> varchar(252) BINARY
*	VCHAR_CI	=> varchar_ci for postgresql, others VCHAR
*/
function get_schema_struct()
{
	$schema_data = array();

	$schema_data['phpbb_attachments'] = array(
		'COLUMNS'		=> array(
			'attach_id'			=> array('UINT', NULL, 'auto_increment'),
			'post_msg_id'		=> array('UINT', 0),
			'topic_id'			=> array('UINT', 0),
			'in_message'		=> array('BOOL', 0),
			'poster_id'			=> array('UINT', 0),
			'pysical_filename'	=> array('VCHAR', ''),
			'real_filename'		=> array('VCHAR', ''),
			'download_count'	=> array('UINT', 0),
			'attach_comment'	=> array('TEXT', ''),
			'extension'			=> array('VCHAR:100', ''),
			'mimetype'			=> array('VCHAR:100', ''),
			'filesize'			=> array('UINT:20', 0),
			'filetime'			=> array('TIMESTAMP', 0),
			'thumbnail'			=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'attach_id',
		'KEYS'			=> array(
			'filetime'			=> array('INDEX', 'filetime'),
			'post_msg_id'		=> array('INDEX', 'post_msg_id'),
			'topic_id'			=> array('INDEX', 'topic_id'),
			'poster_id'			=> array('INDEX', 'poster_id'),
			'filesize'			=> array('INDEX', 'filesize'),
		),
	);

	$schema_data['phpbb_acl_groups'] = array(
		'COLUMNS'		=> array(
			'group_id'			=> array('UINT', 0),
			'forum_id'			=> array('UINT', 0),
			'auth_option_id'	=> array('UINT', 0),
			'auth_role_id'		=> array('UINT', 0),
			'auth_setting'		=> array('TINT:2', 0),
		),
		'KEYS'			=> array(
			'group_id'			=> array('INDEX', 'group_id'),
			'auth_option_id'	=> array('INDEX', 'auth_option_id'),
		),
	);

	$schema_data['phpbb_acl_options'] = array(
		'COLUMNS'		=> array(
			'auth_option_id'	=> array('UINT', NULL, 'auto_increment'),
			'auth_option'		=> array('VCHAR:50', ''),
			'is_global'			=> array('BOOL', 0),
			'is_local'			=> array('BOOL', 0),
			'founder_only'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'auth_option_id',
		'KEYS'			=> array(
			'auth_option'		=> array('INDEX', 'auth_option'),
		),
	);

	$schema_data['phpbb_acl_roles'] = array(
		'COLUMNS'		=> array(
			'role_id'			=> array('UINT', NULL, 'auto_increment'),
			'role_name'			=> array('VCHAR', ''),
			'role_description'	=> array('TEXT', ''),
			'role_type'			=> array('VCHAR:10', ''),
			'role_order'		=> array('USINT', 0),
		),
		'PRIMARY_KEY'	=> 'role_id',
		'KEYS'			=> array(
			'role_type'			=> array('INDEX', 'role_type'),
			'role_order'		=> array('INDEX', 'role_order'),
		),
	);

	$schema_data['phpbb_acl_roles_data'] = array(
		'COLUMNS'		=> array(
			'role_id'			=> array('UINT', 0),
			'auth_option_id'	=> array('UINT', 0),
			'auth_setting'		=> array('TINT:2', 0),
		),
		'PRIMARY_KEY'	=> array('role_id', 'auth_option_id'),
	);

	$schema_data['phpbb_acl_users'] = array(
		'COLUMNS'		=> array(
			'user_id'			=> array('UINT', 0),
			'forum_id'			=> array('UINT', 0),
			'auth_option_id'	=> array('UINT', 0),
			'auth_role_id'		=> array('UINT', 0),
			'auth_setting'		=> array('TINT:2', 0),
		),
		'KEYS'			=> array(
			'user_id'			=> array('INDEX', 'user_id'),
			'auth_option_id'	=> array('INDEX', 'auth_option_id'),
		),
	);

	$schema_data['phpbb_banlist'] = array(
		'COLUMNS'		=> array(
			'ban_id'			=> array('UINT', NULL, 'auto_increment'),
			'ban_userid'		=> array('UINT', 0),
			'ban_ip'			=> array('VCHAR:40', ''),
			'ban_email'			=> array('VCHAR:100', ''),
			'ban_start'			=> array('TIMESTAMP', 0),
			'ban_end'			=> array('TIMESTAMP', 0),
			'ban_exclude'		=> array('BOOL', 0),
			'ban_reason'		=> array('STEXT', ''),
			'ban_give_reason'	=> array('STEXT', ''),
		),
		'PRIMARY_KEY'			=> 'ban_id',
	);

	$schema_data['phpbb_bbcodes'] = array(
		'COLUMNS'		=> array(
			'bbcode_id'				=> array('TINT:3', 0),
			'bbcode_tag'			=> array('VCHAR:16', ''),
			'display_on_posting'	=> array('BOOL', 0),
			'bbcode_match'			=> array('VCHAR', ''),
			'bbcode_tpl'			=> array('MTEXT', ''),
			'first_pass_match'		=> array('VCHAR', ''),
			'first_pass_replace'	=> array('VCHAR', ''),
			'second_pass_match'		=> array('VCHAR', ''),
			'second_pass_replace'	=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'bbcode_id',
		'KEYS'			=> array(
			'display_in_posting'	=> array('INDEX', 'display_on_posting'),
		),
	);

	$schema_data['phpbb_bookmarks'] = array(
		'COLUMNS'		=> array(
			'topic_id'			=> array('UINT', 0),
			'user_id'			=> array('UINT', 0),
			'order_id'			=> array('UINT', 0),
		),
		'KEYS'			=> array(
			'order_id'			=> array('INDEX', 'order_id'),
			'topic_user_id'		=> array('INDEX', array('topic_id', 'user_id')),
		),
	);

	$schema_data['phpbb_bots'] = array(
		'COLUMNS'		=> array(
			'bot_id'			=> array('UINT', NULL, 'auto_increment'),
			'bot_active'		=> array('BOOL', 1),
			'bot_name'			=> array('STEXT', ''),
			'user_id'			=> array('UINT', 0),
			'bot_agent'			=> array('VCHAR', ''),
			'bot_ip'			=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> 'bot_id',
		'KEYS'			=> array(
			'bot_active'		=> array('INDEX', 'bot_active'),
		),
	);

	$schema_data['phpbb_config'] = array(
		'COLUMNS'		=> array(
			'config_name'		=> array('VCHAR', ''),
			'config_value'		=> array('VCHAR', ''),
			'is_dynamic'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'config_name',
		'KEYS'			=> array(
			'is_dynamic'		=> array('INDEX', 'is_dynamic'),
		),
	);

	$schema_data['phpbb_confirm'] = array(
		'COLUMNS'		=> array(
			'confirm_id'		=> array('CHAR:32', ''),
			'session_id'		=> array('CHAR:32', ''),
			'confirm_type'		=> array('TINT:3', 0),
			'code'				=> array('VCHAR:8', ''),
		),
		'PRIMARY_KEY'	=> array('session_id', 'confirm_id'),
	);

	$schema_data['phpbb_disallow'] = array(
		'COLUMNS'		=> array(
			'disallow_id'		=> array('UINT', NULL, 'auto_increment'),
			'disallow_username'	=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> 'disallow_id',
	);

	$schema_data['phpbb_drafts'] = array(
		'COLUMNS'		=> array(
			'draft_id'			=> array('UINT', NULL, 'auto_increment'),
			'user_id'			=> array('UINT', 0),
			'topic_id'			=> array('UINT', 0),
			'forum_id'			=> array('UINT', 0),
			'save_time'			=> array('TIMESTAMP', 0),
			'draft_subject'		=> array('XSTEXT', ''),
			'draft_message'		=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'draft_id',
		'KEYS'			=> array(
			'save_time'			=> array('INDEX', 'save_time'),
		),
	);

	$schema_data['phpbb_extensions'] = array(
		'COLUMNS'		=> array(
			'extension_id'		=> array('UINT', NULL, 'auto_increment'),
			'group_id'			=> array('UINT', 0),
			'extension'			=> array('VCHAR:100', ''),
		),
		'PRIMARY_KEY'	=> 'extension_id',
	);

	$schema_data['phpbb_extension_groups'] = array(
		'COLUMNS'		=> array(
			'group_id'			=> array('UINT', NULL, 'auto_increment'),
			'group_name'		=> array('VCHAR', ''),
			'cat_id'			=> array('TINT:2', 0),
			'allow_group'		=> array('BOOL', 0),
			'download_mode'		=> array('BOOL', 1),
			'upload_icon'		=> array('VCHAR', ''),
			'max_filesize'		=> array('UINT:20', 0),
			'allowed_forums'	=> array('TEXT', ''),
			'allow_in_pm'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'group_id',
	);

	$schema_data['phpbb_forums'] = array(
		'COLUMNS'		=> array(
			'forum_id'				=> array('UINT', NULL, 'auto_increment'),
			'parent_id'				=> array('UINT', 0),
			'left_id'				=> array('UINT', 0),
			'right_id'				=> array('UINT', 0),
			'forum_parents'			=> array('MTEXT', ''),
			'forum_name'			=> array('STEXT', ''),
			'forum_desc'			=> array('TEXT', ''),
			'forum_desc_bitfield'	=> array('UINT:11', 0),
			'forum_desc_uid'		=> array('VCHAR:5', ''),
			'forum_link'			=> array('VCHAR', ''),
			'forum_password'		=> array('VCHAR:40', ''),
			'forum_style'			=> array('TINT:4', 0),
			'forum_image'			=> array('VCHAR', ''),
			'forum_rules'			=> array('TEXT', ''),
			'forum_rules_link'		=> array('VCHAR', ''),
			'forum_rules_bitfield'	=> array('UINT:11', 0),
			'forum_rules_uid'		=> array('VCHAR:5', ''),
			'forum_topics_per_page'	=> array('TINT:4', 0),
			'forum_type'			=> array('TINT:4', 0),
			'forum_status'			=> array('TINT:4', 0),
			'forum_posts'			=> array('UINT', 0),
			'forum_topics'			=> array('UINT', 0),
			'forum_topics_real'		=> array('UINT', 0),
			'forum_last_post_id'	=> array('UINT', 0),
			'forum_last_poster_id'	=> array('UINT', 0),
			'forum_last_post_time'	=> array('TIMESTAMP', 0),
			'forum_last_poster_name'=> array('VCHAR', ''),
			'forum_flags'			=> array('TINT:4', 32),
			'display_on_index'		=> array('BOOL', 1),
			'enable_indexing'		=> array('BOOL', 1),
			'enable_icons'			=> array('BOOL', 1),
			'enable_prune'			=> array('BOOL', 0),
			'prune_next'			=> array('TIMESTAMP', 0),
			'prune_days'			=> array('TINT:4', 0),
			'prune_viewed'			=> array('TINT:4', 0),
			'prune_freq'			=> array('TINT:4', 0),
		),
		'PRIMARY_KEY'	=> 'forum_id',
		'KEYS'			=> array(
			'left_right_id'			=> array('INDEX', array('left_id', 'right_id')),
			'forum_last_post_id'	=> array('INDEX', 'forum_last_post_id'),
		),
	);

	$schema_data['phpbb_forums_access'] = array(
		'COLUMNS'		=> array(
			'forum_id'				=> array('UINT', 0),
			'user_id'				=> array('UINT', 0),
			'session_id'			=> array('CHAR:32', ''),
		),
		'PRIMARY_KEY'	=> array('forum_id', 'user_id', 'session_id'),
	);

	$schema_data['phpbb_forums_track'] = array(
		'COLUMNS'		=> array(
			'user_id'				=> array('UINT', 0),
			'forum_id'				=> array('UINT', 0),
			'mark_time'				=> array('TIMESTAMP', 0),
		),
		'PRIMARY_KEY'	=> array('user_id', 'forum_id'),
	);

	$schema_data['phpbb_forums_watch'] = array(
		'COLUMNS'		=> array(
			'forum_id'				=> array('UINT', 0),
			'user_id'				=> array('UINT', 0),
			'notify_status'			=> array('BOOL', 0),
		),
		'KEYS'			=> array(
			'forum_id'				=> array('INDEX', 'forum_id'),
			'user_id'				=> array('INDEX', 'user_id'),
			'notify_status'			=> array('INDEX', 'notify_status'),
		),
	);

	$schema_data['phpbb_groups'] = array(
		'COLUMNS'		=> array(
			'group_id'				=> array('UINT', NULL, 'auto_increment'),
			'group_type'			=> array('TINT:4', 1),
			'group_name'			=> array('VCHAR_CI', ''),
			'group_desc'			=> array('TEXT', ''),
			'group_desc_bitfield'	=> array('UINT:11', 0),
			'group_desc_uid'		=> array('VCHAR:5', ''),
			'group_display'			=> array('BOOL', 0),
			'group_avatar'			=> array('VCHAR', ''),
			'group_avatar_type'		=> array('TINT:4', 0),
			'group_avatar_width'	=> array('TINT:4', 0),
			'group_avatar_height'	=> array('TINT:4', 0),
			'group_rank'			=> array('UINT', 0),
			'group_colour'			=> array('VCHAR:6', ''),
			'group_sig_chars'		=> array('UINT', 0),
			'group_receive_pm'		=> array('BOOL', 0),
			'group_message_limit'	=> array('UINT', 0),
			'group_legend'			=> array('BOOL', 1),
		),
		'PRIMARY_KEY'	=> 'group_id',
		'KEYS'			=> array(
			'group_legend'			=> array('INDEX', 'group_legend'),
		),
	);

	$schema_data['phpbb_icons'] = array(
		'COLUMNS'		=> array(
			'icons_id'				=> array('UINT', NULL, 'auto_increment'),
			'icons_url'				=> array('VCHAR', ''),
			'icons_width'			=> array('TINT:4', 0),
			'icons_height'			=> array('TINT:4', 0),
			'icons_order'			=> array('UINT', 0),
			'display_on_posting'	=> array('BOOL', 1),
		),
		'PRIMARY_KEY'	=> 'icons_id',
	);

	$schema_data['phpbb_lang'] = array(
		'COLUMNS'		=> array(
			'lang_id'				=> array('TINT:4', NULL, 'auto_increment'),
			'lang_iso'				=> array('VCHAR:5', ''),
			'lang_dir'				=> array('VCHAR:30', ''),
			'lang_english_name'		=> array('VCHAR:100', ''),
			'lang_local_name'		=> array('VCHAR:255', ''),
			'lang_author'			=> array('VCHAR:255', ''),
		),
		'PRIMARY_KEY'	=> 'lang_id',
		'KEYS'			=> array(
			'lang_iso'				=> array('INDEX', 'lang_iso'),
		),
	);

	$schema_data['phpbb_log'] = array(
		'COLUMNS'		=> array(
			'log_id'				=> array('UINT', NULL, 'auto_increment'),
			'log_type'				=> array('TINT:4', 0),
			'user_id'				=> array('UINT', 0),
			'forum_id'				=> array('UINT', 0),
			'topic_id'				=> array('UINT', 0),
			'reportee_id'			=> array('UINT', 0),
			'log_ip'				=> array('VCHAR:40', ''),
			'log_time'				=> array('TIMESTAMP', 0),
			'log_operation'			=> array('TEXT', ''),
			'log_data'				=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'log_id',
		'KEYS'			=> array(
			'log_type'				=> array('INDEX', 'log_type'),
			'forum_id'				=> array('INDEX', 'forum_id'),
			'topic_id'				=> array('INDEX', 'topic_id'),
			'reportee_id'			=> array('INDEX', 'reportee_id'),
			'user_id'				=> array('INDEX', 'user_id'),
		),
	);

	$schema_data['phpbb_moderator_cache'] = array(
		'COLUMNS'		=> array(
			'forum_id'				=> array('UINT', 0),
			'user_id'				=> array('UINT', 0),
			'username'				=> array('VCHAR', ''),
			'group_id'				=> array('UINT', 0),
			'group_name'			=> array('VCHAR', ''),
			'display_on_index'		=> array('BOOL', 1),
		),
		'KEYS'			=> array(
			'display_on_index'		=> array('INDEX', 'display_on_index'),
			'forum_id'				=> array('INDEX', 'forum_id'),
		),
	);

	$schema_data['phpbb_modules'] = array(
		'COLUMNS'		=> array(
			'module_id'				=> array('UINT', NULL, 'auto_increment'),
			'module_enabled'		=> array('BOOL', 1),
			'module_display'		=> array('BOOL', 1),
			'module_basename'		=> array('VCHAR', ''),
			'module_class'			=> array('VCHAR:10', ''),
			'parent_id'				=> array('UINT', 0),
			'left_id'				=> array('UINT', 0),
			'right_id'				=> array('UINT', 0),
			'module_langname'		=> array('VCHAR', ''),
			'module_mode'			=> array('VCHAR', ''),
			'module_auth'			=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> 'module_id',
		'KEYS'			=> array(
			'left_right_id'			=> array('INDEX', array('left_id', 'right_id')),
			'module_enabled'		=> array('INDEX', 'module_enabled'),
			'class_left_id'			=> array('INDEX', array('module_class', 'left_id')),
		),
	);

	$schema_data['phpbb_poll_options'] = array(
		'COLUMNS'		=> array(
			'poll_option_id'		=> array('TINT:4', 0),
			'topic_id'				=> array('UINT', 0),
			'poll_option_text'		=> array('TEXT', ''),
			'poll_option_total'		=> array('UINT', 0),
		),
		'KEYS'			=> array(
			'poll_option_id'		=> array('INDEX', 'poll_option_id'),
			'topic_id'				=> array('INDEX', 'topic_id'),
		),
	);

	$schema_data['phpbb_poll_votes'] = array(
		'COLUMNS'		=> array(
			'topic_id'				=> array('UINT', 0),
			'poll_option_id'		=> array('TINT:4', 0),
			'vote_user_id'			=> array('UINT', 0),
			'vote_user_ip'			=> array('VCHAR:40', ''),
		),
		'KEYS'			=> array(
			'topic_id'				=> array('INDEX', 'topic_id'),
			'vote_user_id'			=> array('INDEX', 'vote_user_id'),
			'vote_user_ip'			=> array('INDEX', 'vote_user_ip'),
		),
	);

	$schema_data['phpbb_posts'] = array(
		'COLUMNS'		=> array(
			'post_id'				=> array('UINT', NULL, 'auto_increment'),
			'topic_id'				=> array('UINT', 0),
			'forum_id'				=> array('UINT', 0),
			'poster_id'				=> array('UINT', 0),
			'icon_id'				=> array('UINT', 0),
			'poster_ip'				=> array('VCHAR:40', ''),
			'post_time'				=> array('TIMESTAMP', 0),
			'post_approved'			=> array('BOOL', 1),
			'post_reported'			=> array('BOOL', 0),
			'enable_bbcode'			=> array('BOOL', 1),
			'enable_smilies'		=> array('BOOL', 1),
			'enable_magic_url'		=> array('BOOL', 1),
			'enable_sig'			=> array('BOOL', 1),
			'post_username'			=> array('VCHAR', ''),
			'post_subject'			=> array('XSTEXT', ''),
			'post_text'				=> array('MTEXT', ''),
			'post_checksum'			=> array('VCHAR:32', ''),
			'post_encoding'			=> array('VCHAR:20', 'iso-8859-1'),
			'post_attachment'		=> array('BOOL', 0),
			'bbcode_bitfield'		=> array('UINT:11', 0),
			'bbcode_uid'			=> array('VCHAR:5', ''),
			'post_edit_time'		=> array('TIMESTAMP', 0),
			'post_edit_reason'		=> array('STEXT', ''),
			'post_edit_user'		=> array('UINT', 0),
			'post_edit_count'		=> array('USINT', 0),
			'post_edit_locked'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'post_id',
		'KEYS'			=> array(
			'forum_id'				=> array('INDEX', 'forum_id'),
			'topic_id'				=> array('INDEX', 'topic_id'),
			'poster_ip'				=> array('INDEX', 'poster_ip'),
			'poster_id'				=> array('INDEX', 'poster_id'),
			'post_approved'			=> array('INDEX', 'post_approved'),
			'post_time'				=> array('INDEX', 'post_time'),
		),
	);

	$schema_data['phpbb_privmsgs'] = array(
		'COLUMNS'		=> array(
			'msg_id'				=> array('UINT', NULL, 'auto_increment'),
			'root_level'			=> array('UINT', 0),
			'author_id'				=> array('UINT', 0),
			'icon_id'				=> array('UINT', 0),
			'author_ip'				=> array('VCHAR:40', ''),
			'message_time'			=> array('TIMESTAMP', 0),
			'enable_bbcode'			=> array('BOOL', 1),
			'enable_smilies'		=> array('BOOL', 1),
			'enable_magic_url'		=> array('BOOL', 1),
			'enable_sig'			=> array('BOOL', 1),
			'message_subject'		=> array('XSTEXT', ''),
			'message_text'			=> array('MTEXT', ''),
			'message_edit_reason'	=> array('STEXT', ''),
			'message_edit_user'		=> array('UINT', 0),
			'message_encoding'		=> array('VCHAR:20', 'iso-8859-1'),
			'message_attachment'	=> array('BOOL', 0),
			'bbcode_bitfield'		=> array('UINT:11', 0),
			'bbcode_uid'			=> array('VCHAR:5', ''),
			'message_edit_time'		=> array('TIMESTAMP', 0),
			'message_edit_count'	=> array('USINT', 0),
			'to_address'			=> array('TEXT', ''),
			'bcc_address'			=> array('TEXT', ''),
		),
		'PRIMARY_KEY'	=> 'msg_id',
		'KEYS'			=> array(
			'author_ip'				=> array('INDEX', 'author_ip'),
			'message_time'			=> array('INDEX', 'message_time'),
			'author_id'				=> array('INDEX', 'author_id'),
			'root_level'			=> array('INDEX', 'root_level'),
		),
	);

	$schema_data['phpbb_privmsgs_folder'] = array(
		'COLUMNS'		=> array(
			'folder_id'				=> array('UINT', NULL, 'auto_increment'),
			'user_id'				=> array('UINT', 0),
			'folder_name'			=> array('VCHAR', ''),
			'pm_count'				=> array('UINT', 0),
		),
		'PRIMARY_KEY'	=> 'folder_id',
		'KEYS'			=> array(
			'user_id'				=> array('INDEX', 'user_id'),
		),
	);

	$schema_data['phpbb_privmsgs_rules'] = array(
		'COLUMNS'		=> array(
			'rule_id'				=> array('UINT', NULL, 'auto_increment'),
			'user_id'				=> array('UINT', 0),
			'rule_check'			=> array('UINT', 0),
			'rule_connection'		=> array('UINT', 0),
			'rule_string'			=> array('VCHAR', ''),
			'rule_user_id'			=> array('UINT', 0),
			'rule_group_id'			=> array('UINT', 0),
			'rule_action'			=> array('UINT', 0),
			'rule_folder_id'		=> array('UINT', 0),
		),
		'PRIMARY_KEY'	=> 'rule_id',
	);

	$schema_data['phpbb_privmsgs_to'] = array(
		'COLUMNS'		=> array(
			'msg_id'				=> array('UINT', 0),
			'user_id'				=> array('UINT', 0),
			'author_id'				=> array('UINT', 0),
			'pm_deleted'			=> array('BOOL', 0),
			'pm_new'				=> array('BOOL', 1),
			'pm_unread'				=> array('BOOL', 1),
			'pm_replied'			=> array('BOOL', 0),
			'pm_marked'				=> array('BOOL', 0),
			'pm_forwarded'			=> array('BOOL', 0),
			'folder_id'				=> array('UINT', 0),
		),
		'KEYS'			=> array(
			'msg_id'				=> array('INDEX', 'msg_id'),
			'user_folder_id'		=> array('INDEX', array('user_id', 'folder_id')),
		),
	);

	$schema_data['phpbb_profile_fields'] = array(
		'COLUMNS'		=> array(
			'field_id'				=> array('UINT', NULL, 'auto_increment'),
			'field_name'			=> array('VCHAR', ''),
			'field_type'			=> array('TINT:4', 0),
			'field_ident'			=> array('VCHAR:20', ''),
			'field_length'			=> array('VCHAR:20', ''),
			'field_minlen'			=> array('VCHAR', ''),
			'field_maxlen'			=> array('VCHAR', ''),
			'field_novalue'			=> array('VCHAR', ''),
			'field_default_value'	=> array('VCHAR', ''),
			'field_validation'		=> array('VCHAR:20', ''),
			'field_required'		=> array('BOOL', 0),
			'field_show_on_reg'		=> array('BOOL', 0),
			'field_hide'			=> array('BOOL', 0),
			'field_no_view'			=> array('BOOL', 0),
			'field_active'			=> array('BOOL', 0),
			'field_order'			=> array('UINT', 0),
		),
		'PRIMARY_KEY'	=> 'field_id',
		'KEYS'			=> array(
			'field_type'			=> array('INDEX', 'field_type'),
			'field_order'			=> array('INDEX', 'field_order'),
		),
	);

	$schema_data['phpbb_profile_fields_data'] = array(
		'COLUMNS'		=> array(
			'user_id'				=> array('UINT', 0),
		),
		'PRIMARY_KEY'	=> 'user_id',
	);

	$schema_data['phpbb_profile_fields_lang'] = array(
		'COLUMNS'		=> array(
			'field_id'				=> array('UINT', 0),
			'lang_id'				=> array('UINT', 0),
			'option_id'				=> array('UINT', 0),
			'field_type'			=> array('TINT:4', 0),
			'lang_value'			=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> array('field_id', 'lang_id', 'option_id'),
	);

	$schema_data['phpbb_profile_lang'] = array(
		'COLUMNS'		=> array(
			'field_id'				=> array('UINT', 0),
			'lang_id'				=> array('UINT', 0),
			'lang_name'				=> array('VCHAR', ''),
			'lang_explain'			=> array('TEXT', ''),
			'lang_default_value'	=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> array('field_id', 'lang_id'),
	);

	$schema_data['phpbb_ranks'] = array(
		'COLUMNS'		=> array(
			'rank_id'				=> array('UINT', NULL, 'auto_increment'),
			'rank_title'			=> array('VCHAR', ''),
			'rank_min'				=> array('UINT', 0),
			'rank_special'			=> array('BOOL', 0),
			'rank_image'			=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> 'rank_id',
	);

	$schema_data['phpbb_reports'] = array(
		'COLUMNS'		=> array(
			'report_id'				=> array('UINT', NULL, 'auto_increment'),
			'reason_id'				=> array('USINT', 0),
			'post_id'				=> array('UINT', 0),
			'user_id'				=> array('UINT', 0),
			'user_notify'			=> array('BOOL', 0),
			'report_closed'			=> array('BOOL', 0),
			'report_time'			=> array('TIMESTAMP', 0),
			'report_text'			=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'report_id',
	);

	$schema_data['phpbb_reports_reasons'] = array(
		'COLUMNS'		=> array(
			'reason_id'				=> array('USINT', NULL, 'auto_increment'),
			'reason_title'			=> array('VCHAR', ''),
			'reason_description'	=> array('MTEXT', ''),
			'reason_order'			=> array('USINT', 0),
		),
		'PRIMARY_KEY'	=> 'reason_id',
	);

	$schema_data['phpbb_search_results'] = array(
		'COLUMNS'		=> array(
			'search_key'			=> array('VCHAR:32', ''),
			'search_time'			=> array('TIMESTAMP', 0),
			'search_keywords'		=> array('MTEXT', ''),
			'search_authors'		=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'search_key',
	);

	$schema_data['phpbb_search_wordlist'] = array(
		'COLUMNS'		=> array(
			'word_text'			=> array('VCHAR_BIN', ''),
			'word_id'			=> array('UINT', NULL, 'auto_increment'),
			'word_common'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'word_text',
		'KEYS'			=> array(
			'word_id'			=> array('INDEX', 'word_id'),
		),
	);

	$schema_data['phpbb_search_wordmatch'] = array(
		'COLUMNS'		=> array(
			'post_id'			=> array('UINT', 0),
			'word_id'			=> array('UINT', 0),
			'title_match'		=> array('BOOL', 0),
		),
		'KEYS'			=> array(
			'word_id'			=> array('INDEX', 'word_id'),
		),
	);

	$schema_data['phpbb_sessions'] = array(
		'COLUMNS'		=> array(
			'session_id'			=> array('CHAR:32', ''),
			'session_user_id'		=> array('UINT', 0),
			'session_last_visit'	=> array('TIMESTAMP', 0),
			'session_start'			=> array('TIMESTAMP', 0),
			'session_time'			=> array('TIMESTAMP', 0),
			'session_ip'			=> array('VCHAR:40', ''),
			'session_browser'		=> array('VCHAR:150', ''),
			'session_page'			=> array('VCHAR', ''),
			'session_viewonline'	=> array('BOOL', 1),
			'session_autologin'		=> array('BOOL', 0),
			'session_admin'			=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'session_id',
		'KEYS'			=> array(
			'session_time'		=> array('INDEX', 'session_time'),
			'session_user_id'	=> array('INDEX', 'session_user_id'),
		),
	);

	$schema_data['phpbb_sessions_keys'] = array(
		'COLUMNS'		=> array(
			'key_id'			=> array('CHAR:32', ''),
			'user_id'			=> array('UINT', 0),
			'last_ip'			=> array('VCHAR:40', ''),
			'last_login'		=> array('TIMESTAMP', 0),
		),
		'PRIMARY_KEY'	=> array('key_id', 'user_id'),
		'KEYS'			=> array(
			'last_login'		=> array('INDEX', 'last_login'),
		),
	);

	$schema_data['phpbb_sitelist'] = array(
		'COLUMNS'		=> array(
			'site_id'		=> array('UINT', NULL, 'auto_increment'),
			'site_ip'		=> array('VCHAR:40', ''),
			'site_hostname'	=> array('VCHAR', ''),
			'ip_exclude'	=> array('BOOL', 0),
		),
		'PRIMARY_KEY'		=> 'site_id',
	);

	$schema_data['phpbb_smilies'] = array(
		'COLUMNS'		=> array(
			'smiley_id'			=> array('UINT', NULL, 'auto_increment'),
			'code'				=> array('VCHAR:50', ''),
			'emotion'			=> array('VCHAR:50', ''),
			'smiley_url'		=> array('VCHAR:50', ''),
			'smiley_width'		=> array('TINT:4', 0),
			'smiley_height'		=> array('TINT:4', 0),
			'smiley_order'		=> array('UINT', 0),
			'display_on_posting'=> array('BOOL', 1),
		),
		'PRIMARY_KEY'	=> 'smiley_id',
		'KEYS'			=> array(
			'display_on_posting'	=> array('INDEX', 'display_on_posting'),
		),
	);

	$schema_data['phpbb_styles'] = array(
		'COLUMNS'		=> array(
			'style_id'				=> array('TINT:4', NULL, 'auto_increment'),
			'style_name'			=> array('VCHAR', ''),
			'style_copyright'		=> array('VCHAR', ''),
			'style_active'			=> array('BOOL', 1),
			'template_id'			=> array('TINT:4', 0),
			'theme_id'				=> array('TINT:4', 0),
			'imageset_id'			=> array('TINT:4', 0),
		),
		'PRIMARY_KEY'	=> 'style_id',
		'KEYS'			=> array(
			'style_name'		=> array('UNIQUE', 'style_name'),
			'template_id'		=> array('INDEX', 'template_id'),
			'theme_id'			=> array('INDEX', 'theme_id'),
			'imageset_id'		=> array('INDEX', 'imageset_id'),
		),
	);

	$schema_data['phpbb_styles_template'] = array(
		'COLUMNS'		=> array(
			'template_id'			=> array('TINT:4', NULL, 'auto_increment'),
			'template_name'			=> array('VCHAR', ''),
			'template_copyright'	=> array('VCHAR', ''),
			'template_path'			=> array('VCHAR:100', ''),
			'bbcode_bitfield'		=> array('UINT:11', 6921),
			'template_storedb'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'template_id',
		'KEYS'			=> array(
			'template_name'			=> array('UNIQUE', 'template_name'),
		),
	);

	$schema_data['phpbb_styles_template_data'] = array(
		'COLUMNS'		=> array(
			'template_id'			=> array('TINT:4', NULL, 'auto_increment'),
			'template_filename'		=> array('VCHAR:100', ''),
			'template_included'		=> array('TEXT', ''),
			'template_mtime'		=> array('TIMESTAMP', 0),
			'template_data'			=> array('MTEXT', ''),
		),
		'KEYS'			=> array(
			'template_id'			=> array('INDEX', 'template_id'),
			'template_filename'		=> array('INDEX', 'template_filename'),
		),
	);

	$schema_data['phpbb_styles_theme'] = array(
		'COLUMNS'		=> array(
			'theme_id'				=> array('TINT:4', NULL, 'auto_increment'),
			'theme_name'			=> array('VCHAR', ''),
			'theme_copyright'		=> array('VCHAR', ''),
			'theme_path'			=> array('VCHAR:100', ''),
			'theme_storedb'			=> array('BOOL', 0),
			'theme_mtime'			=> array('TIMESTAMP', 0),
			'theme_data'			=> array('MTEXT', ''),
		),
		'PRIMARY_KEY'	=> 'theme_id',
		'KEYS'			=> array(
			'theme_name'		=> array('UNIQUE', 'theme_name'),
		),
	);

	$schema_data['phpbb_styles_imageset'] = array(
		'COLUMNS'		=> array(
			'imageset_id'				=> array('TINT:4', NULL, 'auto_increment'),
			'imageset_name'				=> array('VCHAR', ''),
			'imageset_copyright'		=> array('VCHAR', ''),
			'imageset_path'				=> array('VCHAR:100', ''),
			'site_logo'					=> array('VCHAR:200', ''),
			'btn_post'					=> array('VCHAR:200', ''),
			'btn_post_pm'				=> array('VCHAR:200', ''),
			'btn_reply'					=> array('VCHAR:200', ''),
			'btn_reply_pm'				=> array('VCHAR:200', ''),
			'btn_locked'				=> array('VCHAR:200', ''),
			'btn_profile'				=> array('VCHAR:200', ''),
			'btn_pm'					=> array('VCHAR:200', ''),
			'btn_delete'				=> array('VCHAR:200', ''),
			'btn_info'					=> array('VCHAR:200', ''),
			'btn_quote'					=> array('VCHAR:200', ''),
			'btn_search'				=> array('VCHAR:200', ''),
			'btn_edit'					=> array('VCHAR:200', ''),
			'btn_report'				=> array('VCHAR:200', ''),
			'btn_email'					=> array('VCHAR:200', ''),
			'btn_www'					=> array('VCHAR:200', ''),
			'btn_icq'					=> array('VCHAR:200', ''),
			'btn_aim'					=> array('VCHAR:200', ''),
			'btn_yim'					=> array('VCHAR:200', ''),
			'btn_msnm'					=> array('VCHAR:200', ''),
			'btn_jabber'				=> array('VCHAR:200', ''),
			'btn_online'				=> array('VCHAR:200', ''),
			'btn_offline'				=> array('VCHAR:200', ''),
			'btn_friend'				=> array('VCHAR:200', ''),
			'btn_foe'					=> array('VCHAR:200', ''),
			'icon_unapproved'			=> array('VCHAR:200', ''),
			'icon_reported'				=> array('VCHAR:200', ''),
			'icon_attach'				=> array('VCHAR:200', ''),
			'icon_post'					=> array('VCHAR:200', ''),
			'icon_post_new'				=> array('VCHAR:200', ''),
			'icon_post_latest'			=> array('VCHAR:200', ''),
			'icon_post_newest'			=> array('VCHAR:200', ''),
			'forum'						=> array('VCHAR:200', ''),
			'forum_new'					=> array('VCHAR:200', ''),
			'forum_locked'				=> array('VCHAR:200', ''),
			'forum_link'				=> array('VCHAR:200', ''),
			'sub_forum'					=> array('VCHAR:200', ''),
			'sub_forum_new'				=> array('VCHAR:200', ''),
			'folder'					=> array('VCHAR:200', ''),
			'folder_moved'				=> array('VCHAR:200', ''),
			'folder_posted'				=> array('VCHAR:200', ''),
			'folder_new'				=> array('VCHAR:200', ''),
			'folder_new_posted'			=> array('VCHAR:200', ''),
			'folder_hot'				=> array('VCHAR:200', ''),
			'folder_hot_posted'			=> array('VCHAR:200', ''),
			'folder_hot_new'			=> array('VCHAR:200', ''),
			'folder_hot_new_posted'		=> array('VCHAR:200', ''),
			'folder_locked'				=> array('VCHAR:200', ''),
			'folder_locked_posted'		=> array('VCHAR:200', ''),
			'folder_locked_new'			=> array('VCHAR:200', ''),
			'folder_locked_new_posted'	=> array('VCHAR:200', ''),
			'folder_locked_announce'	=> array('VCHAR:200', ''),
			'folder_locked_announce_new'		=> array('VCHAR:200', ''),
			'folder_locked_announce_posted'		=> array('VCHAR:200', ''),
			'folder_locked_announce_new_posted'	=> array('VCHAR:200', ''),
			'folder_locked_global'				=> array('VCHAR:200', ''),
			'folder_locked_global_new'			=> array('VCHAR:200', ''),
			'folder_locked_global_posted'		=> array('VCHAR:200', ''),
			'folder_locked_global_new_posted'	=> array('VCHAR:200', ''),
			'folder_locked_sticky'				=> array('VCHAR:200', ''),
			'folder_locked_sticky_new'			=> array('VCHAR:200', ''),
			'folder_locked_sticky_posted'		=> array('VCHAR:200', ''),
			'folder_locked_sticky_new_posted'	=> array('VCHAR:200', ''),
			'folder_sticky'				=> array('VCHAR:200', ''),
			'folder_sticky_posted'		=> array('VCHAR:200', ''),
			'folder_sticky_new'			=> array('VCHAR:200', ''),
			'folder_sticky_new_posted'	=> array('VCHAR:200', ''),
			'folder_announce'			=> array('VCHAR:200', ''),
			'folder_announce_posted'	=> array('VCHAR:200', ''),
			'folder_announce_new'		=> array('VCHAR:200', ''),
			'folder_announce_new_posted'=> array('VCHAR:200', ''),
			'folder_global'				=> array('VCHAR:200', ''),
			'folder_global_posted'		=> array('VCHAR:200', ''),
			'folder_global_new'			=> array('VCHAR:200', ''),
			'folder_global_new_posted'	=> array('VCHAR:200', ''),
			'poll_left'					=> array('VCHAR:200', ''),
			'poll_center'				=> array('VCHAR:200', ''),
			'poll_right'				=> array('VCHAR:200', ''),
			'attach_progress_bar'		=> array('VCHAR:200', ''),
			'user_icon1'				=> array('VCHAR:200', ''),
			'user_icon2'				=> array('VCHAR:200', ''),
			'user_icon3'				=> array('VCHAR:200', ''),
			'user_icon4'				=> array('VCHAR:200', ''),
			'user_icon5'				=> array('VCHAR:200', ''),
			'user_icon6'				=> array('VCHAR:200', ''),
			'user_icon7'				=> array('VCHAR:200', ''),
			'user_icon8'				=> array('VCHAR:200', ''),
			'user_icon9'				=> array('VCHAR:200', ''),
			'user_icon10'				=> array('VCHAR:200', ''),
		),
		'PRIMARY_KEY'		=> 'imageset_id',
		'KEYS'				=> array(
			'imageset_name'			=> array('UNIQUE', 'imageset_name'),
		),
	);

	$schema_data['phpbb_topics'] = array(
		'COLUMNS'		=> array(
			'topic_id'					=> array('UINT', NULL, 'auto_increment'),
			'forum_id'					=> array('UINT', 0),
			'icon_id'					=> array('UINT', 0),
			'topic_attachment'			=> array('BOOL', 0),
			'topic_approved'			=> array('BOOL', 1),
			'topic_reported'			=> array('BOOL', 0),
			'topic_title'				=> array('XSTEXT', ''),
			'topic_poster'				=> array('UINT', 0),
			'topic_time'				=> array('TIMESTAMP', 0),
			'topic_time_limit'			=> array('TIMESTAMP', 0),
			'topic_views'				=> array('UINT', 0),
			'topic_replies'				=> array('UINT', 0),
			'topic_replies_real'		=> array('UINT', 0),
			'topic_status'				=> array('TINT:3', 0),
			'topic_type'				=> array('TINT:3', 0),
			'topic_first_post_id'		=> array('UINT', 0),
			'topic_first_poster_name'	=> array('VCHAR', ''),
			'topic_last_post_id'		=> array('UINT', 0),
			'topic_last_poster_id'		=> array('UINT', 0),
			'topic_last_poster_name'	=> array('VCHAR', ''),
			'topic_last_post_time'		=> array('TIMESTAMP', 0),
			'topic_last_view_time'		=> array('TIMESTAMP', 0),
			'topic_moved_id'			=> array('UINT', 0),
			'topic_bumped'				=> array('BOOL', 0),
			'topic_bumper'				=> array('UINT', 0),
			'poll_title'				=> array('XSTEXT', ''),
			'poll_start'				=> array('TIMESTAMP', 0),
			'poll_length'				=> array('TIMESTAMP', 0),
			'poll_max_options'			=> array('TINT:4', 1),
			'poll_last_vote'			=> array('TIMESTAMP', 0),
			'poll_vote_change'			=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> 'topic_id',
		'KEYS'			=> array(
			'forum_id'				=> array('INDEX', 'forum_id'),
			'forum_id_type'			=> array('INDEX', array('forum_id', 'topic_type')),
			'topic_last_post_time'	=> array('INDEX', 'topic_last_post_time'),
		),
	);

	$schema_data['phpbb_topics_track'] = array(
		'COLUMNS'		=> array(
			'user_id'			=> array('UINT', 0),
			'topic_id'			=> array('UINT', 0),
			'forum_id'			=> array('UINT', 0),
			'mark_time'			=> array('TIMESTAMP', 0),
		),
		'PRIMARY_KEY'	=> array('user_id', 'topic_id'),
		'KEYS'			=> array(
			'forum_id'			=> array('INDEX', 'forum_id'),
		),
	);

	$schema_data['phpbb_topics_posted'] = array(
		'COLUMNS'		=> array(
			'user_id'			=> array('UINT', 0),
			'topic_id'			=> array('UINT', 0),
			'topic_posted'		=> array('BOOL', 0),
		),
		'PRIMARY_KEY'	=> array('user_id', 'topic_id'),
	);

	$schema_data['phpbb_topics_watch'] = array(
		'COLUMNS'		=> array(
			'topic_id'			=> array('UINT', 0),
			'user_id'			=> array('UINT', 0),
			'notify_status'		=> array('BOOL', 0),
		),
		'KEYS'			=> array(
			'topic_id'			=> array('INDEX', 'topic_id'),
			'user_id'			=> array('INDEX', 'user_id'),
			'notify_status'		=> array('INDEX', 'notify_status'),
		),
	);

	$schema_data['phpbb_user_group'] = array(
		'COLUMNS'		=> array(
			'group_id'			=> array('UINT', 0),
			'user_id'			=> array('UINT', 0),
			'group_leader'		=> array('BOOL', 0),
			'user_pending'		=> array('BOOL', 1),
		),
		'KEYS'			=> array(
			'group_id'			=> array('INDEX', 'group_id'),
			'user_id'			=> array('INDEX', 'user_id'),
			'group_leader'		=> array('INDEX', 'group_leader'),
		),
	);

	$schema_data['phpbb_users'] = array(
		'COLUMNS'		=> array(
			'user_id'					=> array('UINT', NULL, 'auto_increment'),
			'user_type'					=> array('TINT:2', 0),
			'group_id'					=> array('UINT', 3),
			'user_permissions'			=> array('MTEXT', ''),
			'user_perm_from'			=> array('UINT', 0),
			'user_ip'					=> array('VCHAR:40', ''),
			'user_regdate'				=> array('TIMESTAMP', 0),
			'username'					=> array('VCHAR_CI', ''),
			'user_password'				=> array('VCHAR:40', ''),
			'user_passchg'				=> array('TIMESTAMP', 0),
			'user_email'				=> array('VCHAR:100', ''),
			'user_email_hash'			=> array('BINT', 0),
			'user_birthday'				=> array('VCHAR:10', ''),
			'user_lastvisit'			=> array('TIMESTAMP', 0),
			'user_lastmark'				=> array('TIMESTAMP', 0),
			'user_lastpost_time'		=> array('TIMESTAMP', 0),
			'user_lastpage'				=> array('VCHAR:200', ''),
			'user_last_confirm_key'		=> array('VCHAR:10', ''),
			'user_last_search'			=> array('TIMESTAMP', 0),
			'user_warnings'				=> array('TINT:4', 0),
			'user_last_warning'			=> array('TIMESTAMP', 0),
			'user_login_attempts'		=> array('TINT:4', 0),
			'user_posts'				=> array('UINT', 0),
			'user_lang'					=> array('VCHAR:30', ''),
			'user_timezone'				=> array('DECIMAL', 0),
			'user_dst'					=> array('BOOL', 0),
			'user_dateformat'			=> array('VCHAR:30', 'd M Y H:i'),
			'user_style'				=> array('TINT:4', 0),
			'user_rank'					=> array('UINT', 0),
			'user_colour'				=> array('VCHAR:6', ''),
			'user_new_privmsg'			=> array('TINT:4', 0),
			'user_unread_privmsg'		=> array('TINT:4', 0),
			'user_last_privmsg'			=> array('TIMESTAMP', 0),
			'user_message_rules'		=> array('BOOL', 0),
			'user_full_folder'			=> array('INT:11', -3),
			'user_emailtime'			=> array('TIMESTAMP', 0),
			'user_topic_show_days'		=> array('USINT', 0),
			'user_topic_sortby_type'	=> array('VCHAR:1', 't'),
			'user_topic_sortby_dir'		=> array('VCHAR:1', 'd'),
			'user_post_show_days'		=> array('USINT', 0),
			'user_post_sortby_type'		=> array('VCHAR:1', 't'),
			'user_post_sortby_dir'		=> array('VCHAR:1', 'a'),
			'user_notify'				=> array('BOOL', 0),
			'user_notify_pm'			=> array('BOOL', 1),
			'user_notify_type'			=> array('TINT:4', 0),
			'user_allow_pm'				=> array('BOOL', 1),
			'user_allow_email'			=> array('BOOL', 1),
			'user_allow_viewonline'		=> array('BOOL', 1),
			'user_allow_viewemail'		=> array('BOOL', 1),
			'user_allow_massemail'		=> array('BOOL', 1),
			'user_options'				=> array('UINT:11', 893),
			'user_avatar'				=> array('VCHAR', ''),
			'user_avatar_type'			=> array('TINT:2', 0),
			'user_avatar_width'			=> array('TINT:4', 0),
			'user_avatar_height'		=> array('TINT:4', 0),
			'user_sig'					=> array('MTEXT', ''),
			'user_sig_bbcode_uid'		=> array('VCHAR:5', ''),
			'user_sig_bbcode_bitfield'	=> array('UINT:11', 0),
			'user_from'					=> array('VCHAR:100', ''),
			'user_icq'					=> array('VCHAR:15', ''),
			'user_aim'					=> array('VCHAR', ''),
			'user_yim'					=> array('VCHAR', ''),
			'user_msnm'					=> array('VCHAR', ''),
			'user_jabber'				=> array('VCHAR', ''),
			'user_website'				=> array('VCHAR:200', ''),
			'user_occ'					=> array('VCHAR', ''),
			'user_interests'			=> array('TEXT', ''),
			'user_actkey'				=> array('VCHAR:32', ''),
			'user_newpasswd'			=> array('VCHAR:32', ''),
		),
		'PRIMARY_KEY'	=> 'user_id',
		'KEYS'			=> array(
			'user_birthday'				=> array('INDEX', 'user_birthday'),
			'user_email_hash'			=> array('INDEX', 'user_email_hash'),
			'user_type'					=> array('INDEX', 'user_type'),
			'username'					=> array('INDEX', 'username'),
		),
	);

	$schema_data['phpbb_warnings'] = array(
		'COLUMNS'		=> array(
			'warning_id'			=> array('UINT', NULL, 'auto_increment'),
			'user_id'				=> array('UINT', 0),
			'post_id'				=> array('UINT', 0),
			'log_id'				=> array('UINT', 0),
			'warning_time'			=> array('TIMESTAMP', 0),
		),
		'PRIMARY_KEY'	=> 'warning_id',
	);

	$schema_data['phpbb_words'] = array(
		'COLUMNS'		=> array(
			'word_id'				=> array('UINT', NULL, 'auto_increment'),
			'word'					=> array('VCHAR', ''),
			'replacement'			=> array('VCHAR', ''),
		),
		'PRIMARY_KEY'	=> 'word_id',
	);

	$schema_data['phpbb_zebra'] = array(
		'COLUMNS'		=> array(
			'user_id'				=> array('UINT', 0),
			'zebra_id'				=> array('UINT', 0),
			'friend'				=> array('BOOL', 0),
			'foe'					=> array('BOOL', 0),
		),
		'KEYS'			=> array(
			'user_id'				=> array('INDEX', 'user_id'),
			'zebra_id'				=> array('INDEX', 'zebra_id'),
		),
	);

	return $schema_data;
}


/**
* Data put into the header for oracle
*/
function oracle_custom_data()
{
	return <<<EOF
/*
  This first section is optional, however its probably the best method
  of running phpBB on Oracle. If you already have a tablespace and user created
  for phpBB you can leave this section commented out!

  The first set of statements create a phpBB tablespace and a phpBB user,
  make sure you change the password of the phpBB user before you run this script!!
*/

/*
CREATE TABLESPACE "PHPBB"
	LOGGING 
	DATAFILE \'E:\ORACLE\ORADATA\LOCAL\PHPBB.ora\' 
	SIZE 10M
	AUTOEXTEND ON NEXT 10M
	MAXSIZE 100M;

CREATE USER "PHPBB" 
	PROFILE "DEFAULT" 
	IDENTIFIED BY "phpbb_password" 
	DEFAULT TABLESPACE "PHPBB" 
	QUOTA UNLIMITED ON "PHPBB" 
	ACCOUNT UNLOCK;

GRANT ANALYZE ANY TO "PHPBB";
GRANT CREATE SEQUENCE TO "PHPBB";
GRANT CREATE SESSION TO "PHPBB";
GRANT CREATE TABLE TO "PHPBB";
GRANT CREATE TRIGGER TO "PHPBB";
GRANT CREATE VIEW TO "PHPBB";
GRANT "CONNECT" TO "PHPBB";

COMMIT;
DISCONNECT;

CONNECT phpbb/phpbb_password;
*/
EOF;
}

/**
* Data put into the header for postgreSQL
*/
function postgres_custom_data()
{
	return <<<EOF
/* Domain definition */
CREATE DOMAIN varchar_ci AS varchar(255) NOT NULL DEFAULT ''::character varying;

/* Operation Functions */
CREATE FUNCTION _varchar_ci_equal(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) = LOWER($2)' LANGUAGE SQL STRICT;
CREATE FUNCTION _varchar_ci_not_equal(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) != LOWER($2)' LANGUAGE SQL STRICT;
CREATE FUNCTION _varchar_ci_less_than(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) < LOWER($2)' LANGUAGE SQL STRICT;
CREATE FUNCTION _varchar_ci_less_equal(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) <= LOWER($2)' LANGUAGE SQL STRICT;
CREATE FUNCTION _varchar_ci_greater_than(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) > LOWER($2)' LANGUAGE SQL STRICT;
CREATE FUNCTION _varchar_ci_greater_equals(varchar_ci, varchar_ci) RETURNS boolean AS 'SELECT LOWER($1) >= LOWER($2)' LANGUAGE SQL STRICT;

/* Operators */
CREATE OPERATOR <(
  PROCEDURE = _varchar_ci_less_than,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = >,
  NEGATOR = >=,
  RESTRICT = scalarltsel,
  JOIN = scalarltjoinsel);

CREATE OPERATOR <=(
  PROCEDURE = _varchar_ci_less_equal,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = >=,
  NEGATOR = >,
  RESTRICT = scalarltsel,
  JOIN = scalarltjoinsel);

CREATE OPERATOR >(
  PROCEDURE = _varchar_ci_greater_than,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = <,
  NEGATOR = <=,
  RESTRICT = scalargtsel,
  JOIN = scalargtjoinsel);

CREATE OPERATOR >=(
  PROCEDURE = _varchar_ci_greater_equals,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = <=,
  NEGATOR = <,
  RESTRICT = scalargtsel,
  JOIN = scalargtjoinsel);

CREATE OPERATOR <>(
  PROCEDURE = _varchar_ci_not_equal,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = <>,
  NEGATOR = =,
  RESTRICT = neqsel,
  JOIN = neqjoinsel);

CREATE OPERATOR =(
  PROCEDURE = _varchar_ci_equal,
  LEFTARG = varchar_ci,
  RIGHTARG = varchar_ci,
  COMMUTATOR = =,
  NEGATOR = <>,
  RESTRICT = eqsel,
  JOIN = eqjoinsel,
  HASHES,
  MERGES,
  SORT1= <);

EOF;
}

?>