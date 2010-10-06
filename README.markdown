# What is Kohana Search?

Kohana Search is a port of the kosearch Search module for Kohana 3.x. It is an implementation of [Zend (Lucene) Search](http://framework.zend.com/manual/en/zend.search.lucene.html), a file-based search/index solution, that provides a simple way to index and search Models.

# Getting things up and running

1. Add the Search module to the modules folder.
2. Enable the search module in bootstrap file.
3. Add [Zend Search](http://www.zend.com/community/downloads) to your vendor folder. Only the Search, Loader and Exception classes are required by this module. 
4. Add the [StandardAnalyzer](http://codefury.net/projects/StandardAnalyzer/ "StandardAnalyzer") library to your vendor folder, if you want [word stemming](http://en.wikipedia.org/wiki/Stemming "wikipedia article").
5. Create a "searchindex" folder in your application directory to hold the search indexes.

Your folder structure should look like this:

	application / searchindex
	application / vendor / StandardAnalyzer
	application / vendor / Zend / Exception.php
	application / vendor / Zend / Loader
	application / vendor / Zend / Loader.php
	application / vendor / Zend / Search

# Usage

## Example ORM model

	class Model_Product extends ORM_Searchable {
	
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

## Example Controller

	class Controller_Product extends Controller {
	
		public function action_create()
		{
			$this->template->title = 'New Product';
			$this->template->content = View::factory('product/create')
				->bind('product', $product)
				->bind('errors', $errors);
			
			$product = ORM::factory('product');
			
			if ($_POST)
			{
				$product->values($_POST);
				
				if ($product->check())
				{
					$product->save();
					Search::instance()->add($product);
			
					$this->request->redirect(Route::get('admin/product')->uri());
				}
				else
				{
					$errors = $product->validate()->errors('product/create');
				}
			}
		}
	
		public function action_edit()
		{
			$this->template->title = 'Edit Product';
			$this->template->content = View::factory('product/edit')
				->bind('product', $product)
				->bind('errors', $errors);
			
			$id = (int) $this->request->param('id');
			$product = ORM::factory('product', $id);
			
			if ($_POST)
			{
				$product->values($_POST);
				
				if ($product->check())
				{
					$product->save();
					Search::instance()->update($product);
			
					$this->request->redirect(Route::get('admin/product')->uri());
				}
				else
				{
					$errors = $product->validate()->errors('product/create');
				}
			}
		}
	
		public function action_delete()
		{
			$this->template->title = 'Delete Product';
			$this->template->content = View::factory('product/delete');
			
			$id = (int) $this->request->param('id');
			$product = ORM::factory('product', $id);
			
			if ($_POST)
			{
				$product->delete();
				Search::instance()->remove($product);
			}
		}
	
		public function action_search()
		{
			$query = Arr::get($_GET, 'query');
			$this->template->title = 'Search results for: \''.urldecode($query).'\'';
			$this->template->content = View::factory('search/results')
				->set('query', $query)
				->bind('results', $results);

			$results = Search::instance()->find($query);
		}
	
		public function action_build_index()
		{
			$products = ORM::factory('product')->find_all();
			Search::instance()->build_search_index($products);
			
			$this->request->redirect('');
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