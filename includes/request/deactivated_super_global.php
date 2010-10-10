<?php
/**
*
* @package phpbb_request
* @copyright (c) 2010 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Replacement for a superglobal (like $_GET or $_POST) which calls
* trigger_error on all operations but isset, overloads the [] operator with SPL.
*
* @package phpbb_request
*/
class phpbb_request_deactivated_super_global implements ArrayAccess, Countable, IteratorAggregate
{
	/**
	* @var	string	Holds the name of the superglobal this is replacing.
	*/
	private $name;

	/**
	* @var	phpbb_request_interface::POST|GET|REQUEST|COOKIE	Super global constant.
	*/
	private $super_global;

	/**
	* @var	phpbb_request_interface	The request class instance holding the actual request data.
	*/
	private $request;

	/**
	* Constructor generates an error message fitting the super global to be used within the other functions.
	*
	* @param	phpbb_request_interface	$request	A request class instance holding the real super global data.
	* @param	string					$name		Name of the super global this is a replacement for - e.g. '_GET'.
	* @param	phpbb_request_interface::POST|GET|REQUEST|COOKIE	$super_global	The variable's super global constant.
	*/
	public function __construct(phpbb_request_interface $request, $name, $super_global)
	{
		$this->request = $request;
		$this->name = $name;
		$this->super_global = $super_global;
	}

	/**
	* Calls trigger_error with the file and line number the super global was used in.
	*/
	private function error()
	{
		$file = '';
		$line = 0;

		$message = 'Illegal use of $' . $this->name . '. You must use the request class or request_var() to access input data. Found in %s on line %d. This error message was generated';

		$backtrace = debug_backtrace();
		if (isset($backtrace[1]))
		{
			$file = $backtrace[1]['file'];
			$line = $backtrace[1]['line'];
		}
		trigger_error(sprintf($message, $file, $line), E_USER_ERROR);
	}

	/**
	* Redirects isset to the correct request class call.
	*
	* @param	string	$offset	The key of the super global being accessed.
	*
	* @return	bool	Whether the key on the super global exists.
	*/
	public function offsetExists($offset)
	{
		return $this->request->is_set($offset, $this->super_global);
	}

	/**#@+
	* Part of the ArrayAccess implementation, will always result in a FATAL error.
	*/
	public function offsetGet($offset)
	{
		$this->error();
	}

	public function offsetSet($offset, $value)
	{
		$this->error();
	}

	public function offsetUnset($offset)
	{
		$this->error();
	}
	/**#@-*/

	/**
	* Part of the Countable implementation, will always result in a FATAL error
	*/
	public function count()
	{
		$this->error();
	}

	/**
	* Part of the Traversable/IteratorAggregate implementation, will always result in a FATAL error
	*/
	public function getIterator()
	{
		$this->error();
	}
}

