<?php defined('SYSPATH') or die('no direct scrip access');

/**
 * Represents a Model's search field, describing the type of Index
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
class Kohana_Search_Field {

	protected $name;
	protected $type;
	protected $html_decode;

	/**
	 * @param  String                    attribute name e.g. db table column name
	 * @param  Zend_Search_Lucene_Field  $type
	 * @param  boolean                   whether or not field data should be decoded prior to indexing
	 */
	public function __construct($name, $type, $html_decode = FALSE)
	{
		$this->name = $name;
		$this->type = $type;
		$this->html_decode = $html_decode;
	}

	/**
	 * Accessor for private properties
	 * 
	 * @param  string  property to retrieve
	 */
	public function __get($var)
	{
		return $this->$var;
	}
}