<?php defined('SYSPATH') or die('no direct scrip access');

/**
 * Searchable Interface for use by Search Class
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
interface Kohana_Searchable {

	const KEYWORD = 0;
	const UNINDEXED = 1;
	const BINARY = 2;
	const TEXT = 3;
	const UNSTORED = 4;

	const DECODE_HTML = TRUE;
	const DONT_DECODE_HTML = FALSE;

	/**
	 * Gets an array to the indexable fields for this item.
	 * 
	 * @return  array
	 */
	public function get_indexable_fields();

	/**
	 * Gets the identifier for this item, for ORM Models this would be the PK.
	 * 
	 * @return  mixed
	 */
	public function get_identifier();

	/**
	 * Gets the type of this item. For ORM Models this would likely be the object 
	 * name.
	 * 
	 * @return  string
	 */
	public function get_type();

	/**
	 * Gets the unique identifier of this item.
	 * 
	 * @return  mixed
	 */
	public function get_unique_identifier();

}