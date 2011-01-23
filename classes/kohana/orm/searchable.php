<?php defined('SYSPATH') or die('no direct scrip access');

/**
 * Implementation of ORM interface for ORM Models
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
abstract class Kohana_ORM_Searchable extends ORM {

	public function get_identifier()
	{
		return $this->primary_val();
	}

	public function get_type()
	{
		return $this->object_name();
	}

	public function get_unique_identifier()
	{
		return $this->object_name().'_'.$this->get_identifier();
	}

}