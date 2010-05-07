# What is Kohana Search?

Kohana Search is a port of the kosearch Search module for Kohana 3.x. It is an implementation of [Zend (Lucene) Search](http://framework.zend.com/manual/en/zend.search.lucene.html), a file-based search/index solution, that provides a simple way to index and search Models.

# Getting things up and running

1. Add the Search module to the modules folder.
2. Enable the search module in bootstrap file.
3. Add [Zend Search](http://www.zend.com/community/downloads) to your vendor folder. Only the Search, Loader and Exception classes are required by this module. 
4. Add the [StandardAnalyzer](http://codefury.net/projects/StandardAnalyzer/ "StandardAnalyzer") library to your vendor folder, if you want [word stemming](http://en.wikipedia.org/wiki/Stemming "wikipedia article").
5. Create a "searchindex" folder in your application directory to hold the search indexes.

# Usage

## Example ORM model

	class Model_Product extends ORM_Search_Searchable {
	
		/**
		 * Define the fields to index
		 */
		public function get_indexable_fields()
		{
			$fields = array();
			
			// Store the product id but don't index it
			$fields[] = new Search_Field('id', Searchable::UNINDEXED);
			
			// Index the product name
			$fields[] = new Search_Field('name', Searchable::TEXT);
			
			// Index but don't store the product description
			$fields[] = new Search_Field('description', Searchable::UNSTORED, Searchable::DECODE_HTML);
			
			return $fields;
		}
	
	}

## Creating/Re-Building an index

	// Get a collection of indexable models
	$products = ORM::factory('product')->find_all();
	Search::instance()->build_search_index($products);

## Add a model to the index

	// Add a single model to the index
	$product = ORM::factory('product', 1);
	Search::instance()->add($product);

## Update a model in the index

	// Update a single model in the index
	$product = ORM::factory('product', 1);
	Search::instance()->update($product);

## Removing a model from the index

	// Remove a single model from the index
	$product = ORM::factory('product', 1);
	Search::instance()->remove($product);

## Searching the index

	$query = arr::get($_GET, 'q');
	$hits = Search::instance()->find($query);

## Adnvanced searching of the index

	Search::instance()->load_search_libs();
	
	$query = arr::get($_GET, 'q');
	$query = Zend_Search_Lucene_Search_QueryParser::parse($query);
					
	$hits = Search::instance()->find($query);