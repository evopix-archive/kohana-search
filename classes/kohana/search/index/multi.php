<?php defined('SYSPATH') or die('no direct scrip access');

/**
 * Represents a Multiple Index Search
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
class Kohana_Search_Index_Multi {

	protected $_index;

	/**
	 * @param  array   $names    names of the index's
	 * @param  boolean  $create  whether or not to create new index's
	 * @return  Zend_Search_Lucene_Interface
	 */
	public function __construct($names)
	{
		Search::load_search_libs();
		require_once Kohana::find_file('vendor', 'Zend/Search/Lucene/MultiSearcher');
		$index = new Zend_Search_Lucene_Interface_MultiSearcher();

		foreach ($names as $name)
		{
			$index->addIndex(Search_Index::open($name));
		}

		$this->_index = $index;
	}

	/**
	 * Opens multiple index's
	 *
	 * @param  string  $names  names of the index's to open
	 * @return  Zend_Search_Lucene_Interface
	 */
	public static function open($names)
	{
		$index = new Search_Index_Multi($names);
		return $index->index;
	}

	public function __get($var)
	{
		if ($var == 'index')
			return $this->_index;
	}

}