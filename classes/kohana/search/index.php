<?php defined('SYSPATH') or die('no direct scrip access');

/**
 * Represents a Search Index
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
class Kohana_Search_Index {

	protected $_index;

	/**
	 * @param  string   $name    name of the index
	 * @param  boolean  $create  whether or not to create new index
	 * @return  Zend_Search_Lucene_Interface
	 */
	public function __construct($name, $create = FALSE)
	{
		Search::load_search_libs();
		$index_path = $this->_get_index_path($name);

		if ($create)
		{
			$index = Zend_Search_Lucene::create($index_path);
		}
		else
		{
			try
			{
				$index = Zend_Search_Lucene::open($index_path);
			}
			catch(Zend_Search_Lucene_Exception $e)
			{
				$index = Zend_Search_Lucene::create($index_path);
			}
		}

		$this->_index = $index;
	}

	/**
	 * Create a new index
	 *
	 * @param  string  $name  name of the index to create
	 * @return  Zend_Search_Lucene_Interface
	 */
	public static function create($name)
	{
		$index = new Search_Index($name, TRUE);
		return $index->index;
	}

	/**
	 * Opens an index
	 *
	 * @param  string  $name  name of the index to open
	 * @return  Zend_Search_Lucene_Interface
	 */
	public static function open($name)
	{
		$index = new Search_Index($name);
		return $index->index;
	}

	protected function _get_index_path($name)
	{
		$index = Kohana::config('search.index_path');
		return $index.DIRECTORY_SEPARATOR.$name;
	}

	public function __get($var)
	{
		if ($var == 'index')
			return $this->_index;
	}

}