<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Search service providing access to underlying index
 *
 * @package		Kohana Search
 * @author		Brandon Summers (brandon@evolutionpixels.com)
 * @author		Howie Weiner (howie@microbubble.net)
 * @copyright	(c) 2010, Brandon Summers
 * @copyright	(c) 2009, Mirobubble Web Design
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 */
class Kohana_Search {

	
	// Configuration array
	protected $_config;

	protected $_index;
	protected $_index_path;

	/**
	 * Create a new instance of the Search class.
	 *
	 * @return  Search
	 **/
	public static function factory()
	{
		return new Search;
	}

	/**
	 * Protected constructor for singleton pattern
	 */
	protected function __construct()
	{
		$this->_config = Kohana::config('search');
		$this->_index_path = $this->_config['index_path'];

		if ( ! file_exists($this->_get_index_path()))
		{
			throw new Kohana_Exception('Could not find index path :path',
				array('path' => $this->_get_index_path()));
		}
		elseif ( ! is_dir($this->_get_index_path()))
		{
			throw new Kohana_Exception('Index path :path is not a directory',
				array('path' => $this->_get_index_path()));
		}
		elseif ( ! is_writable($this->_get_index_path()))
		{
			throw new Kohana_Exception('Index path :path is not writeable',
				array('path' => $this->_get_index_path()));
		}

		Search::load_search_libs();

		if ($this->_config['use_english_stemming_analyser'])
		{
			// use stemming analyser - http://codefury.net/2008/06/a-stemming-analyzer-for-zends-php-lucene/
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new StandardAnalyzer_Analyzer_Standard_English());
		}
		else
		{
			// set default analyzer to UTF8 with numbers, and case insensitive. Number are useful when searching on e.g. product codes
			Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
		}
	}

	/**
	 * Query the index, returns the number of hits.
	 * 
	 * @param   string                             the query
	 * @return  Zend_Search_Lucene_Search_QueryHit
	 */
	public function find()
	{
		$this->_open_index();
		$args = func_get_args();
		return call_user_func_array(array($this->_index, 'find'), $args);;
	}

	/**
	 * Add an entry to the index
	 *
	 * @param   Searchable  Implememting Searchable interface
	 * @param   Boolean     Whether or not to create new index when adding item, only used when index is rebuilt.
	 * @return  Search
	 */
	public function add($item, $create_new = FALSE)
	{
		if ( ! $item instanceof Searchable)
		{
			throw new Kohana_User_Exception('Invalid Object', 'Object must implement Searchable Interface');
		}

		if ( ! $create_new)
		{
			$this->_open_index();
		}

		$doc = new Zend_Search_Lucene_Document();

		$fields = $item->get_indexable_fields();

		// index the object type - this allows search results to be grouped/searched by type
		$doc->addField(Zend_Search_Lucene_Field::Keyword('type', $item->get_type()));

		// index the object's id - to avoid any confusion, we call it 'identifier' as Lucene uses 'id' attribute internally.
		$doc->addField(Zend_Search_Lucene_Field::UnIndexed('identifier', $item->get_identifier()));

		// index the object type plus identifier - this gives us a unique identifier for later retrieval - e.g. to delete
		$doc->addField(Zend_Search_Lucene_Field::Keyword('uid', $item->get_unique_identifier()));

		// index all fields that have been identified by Interface
		foreach ($fields as $field)
		{
			// get attribute value from model
			$value = $item->{$field->name};

			// html decode value if required
			$value = $field->html_decode ? htmlspecialchars_decode($value) : $value;

			// add field value based on type
			switch ($field->type)
			{
				case Searchable::KEYWORD :
					$doc->addField(Zend_Search_Lucene_Field::Keyword($field->name, $value));
					break;

				case Searchable::UNINDEXED :
					$doc->addField(Zend_Search_Lucene_Field::UnIndexed($field->name, $value));
					break;

				case Searchable::BINARY :
					$doc->addField(Zend_Search_Lucene_Field::Binary($field->name, $value));
					break;

				case Searchable::TEXT :
					$doc->addField(Zend_Search_Lucene_Field::Text($field->name, $value));
					break;

				case Searchable::UNSTORED :
					$doc->addField(Zend_Search_Lucene_Field::UnStored($field->name, $value));
					break;
			}
		}

		$this->_index->addDocument($doc);

		return $this;
	}

	/**
	 * Update an entry. We must first remove the entry from the index, then 
	 * re-add it. To remove, we must find it by unique identifier.
	 *
	 * @param   Searchable  Model to update
	 * @return  Search
	 */
	public function update($item)
	{
		$this->remove($item)->add($item);

		return $this;
	}

	/**
	 * Remove an entry from the index
	 *
	 * @param   Searchable  Model to remove
	 * @return  Search
	 */
	public function remove($item)
	{
		$hits = $this->find('uid:'.$item->get_unique_identifier());

		if (sizeof($hits) == 0)
		{
			Kohana_Log::instance()->add('error', 'No index entry found for id '.$item->get_unique_identifier())->write();
		}
		elseif (sizeof($hits) > 1)
		{
			Kohana_Log::instance()->add('error', 'Non-unique Identifier - More than one record was returned')->write();
		}
		elseif (sizeof($hits) == 1)
		{
			$this->_open_index()->delete($hits[0]->id);
		}

		return $this;
	}

	/**
	 * Build new site index
	 * 
	 * @param   Array   Array of models to add
	 * @return  Search
	 */
	public function build_search_index($items)
	{
        // rebuild new index - create, not open
		$this->_create_index();

		foreach ($items as $item)
		{
			$this->add($item, self::CREATE_NEW);
		}

		$this->_index->optimize();

		return $this;
	}

	/**
	 * Return underlying Search index to allow use of Zend API.
	 * 
	 * @return  Zend_Search_Lucene
	 */
	public function get_index()
	{
		$this->_create_index();

		return $this->_index;
	}

	/**
	 * Load Zend classes. Requires calling externally if get_index() is used,
	 * or if Zend classes need instatiating
	 */
	public static function load_search_libs()
	{
		if ($path = Kohana::find_file('vendor', 'Zend/Loader'))
		{
			ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(dirname($path)));
		}

		require_once Kohana::find_file('vendor', 'Zend/Loader/Autoloader');
		require_once Kohana::find_file('vendor', 'StandardAnalyzer/Analyzer/Standard/English');

		Zend_Loader_Autoloader::getInstance();
	}

	/**
	 * Add a Zend document - utility call to underlying Zend method
	 *
	 * @param   Zend_Search_Lucene_Document  Document to add
	 * @return  Search	
	 */
	public function add_document(Zend_Search_Lucene_Document $doc)
	{
		
		$this->_open_index()->addDocument($doc);

		return $this;
	}
	
	/**
	 * Gets the path to the search index
	 * 
	 * @return  string
	 */
	protected function _get_index_path()
	{
		return realpath($this->_index_path);
	}

	/**
	 * Opens an existing search index. If an index isn't found a new one is created.
	 * 
	 * @return  Zend_Search_Lucene
	 */
	protected function _open_index()
	{
		if (empty($this->_index))
		{
			try
			{
				$this->_index = $index = Zend_Search_Lucene::open($this->_get_index_path());
			}
			catch(Zend_Search_Lucene_Exception $e)
			{
				$this->_index = Zend_Search_Lucene::create($this->_get_index_path());
			}
		}

		return $this->_index;
	}

	/**
	 * Creates a new index.
	 * 
	 * @return  void
	 */
	protected function _create_index()
	{
		if (empty($this->_index))
		{
			$this->_index = Zend_Search_Lucene::create($this->_get_index_path());
		}
	}

}