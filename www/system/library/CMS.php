<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *
 *	\brief		Classe do Mini CMS
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1.1
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

class CMS extends Kernel {
	private static $category_data = NULL;
	private static $article_data = NULL;

	/**
	 *	\brief Informa se um artigo foi encontrado e carregado
	 *
	 *	\return \c true se um artigo foi carregado pra memória e \c false em caso contrário
	 */
	public static function article_is_loaded() {
		return (self::$article_data != NULL);
	}

	/**
	 *	\brief Informa se uma categoria foi encontrada e carregada
	 *
	 *	\return \c true se uma categoria foi carregada pra memória e \c false em caso contrário
	 */
	public static function category_is_loaded() {
		return (self::$category_data != NULL);
	}

	/**
	 *	\brief Retorna um determinado dado da categoria carregada
	 *
	 *	@param[in] $col Coluna a ser retornada. Default = 'category_id'
	 *
	 *	\return o valor da coluna selecionada
	 */
	public static function get_article_data($col='category_id') {
		if (self::category_is_loaded() && isset(self::$category_data[$col])) {
			return self::$category_data[$col];
		}

		return NULL;
	}

	/**
	 *	\brief Verifica se o artigo/categoria existe e carrega seus dados para a memória
	 *
	 *	@param[in] $slug Slug do artigo/categoria a ser buscado
	 *
	 *	\return \c true se carregou o artigo/categoria pra memória e \c false em caso contrário
	 */
	public static function check_article_or_category($slug='') {
		$slug = (empty($slug) ? URI::current_page() : $slug);

		DB::connect();

		// Busca um artigo a partir do slug
		if ($article = self::get_article_by_slug($slug, true)) {
			self::$article_data = $article;
			return true;
		}

		// Busca por uma categoria, já que não há um artigo com o slug da página
		if (DB::execute('SELECT `category_id`, `title`, `slug`, `dt_update` FROM `cms_categories` WHERE `slug` = '.DB::escape_str($slug)) && DB::num_rows() > 0) {
			if (self::$category_data = DB::fetch_next()) {
				if ($slug = URI::get_segment(0, true)) {
					if (DB::execute('SELECT a.`article_id`, a.`title`, a.`slug`, a.`subtitle`, a.`text`, a.`author`, a.`dt_creation`, a.`dt_update`, c.`category_id`, c.`title` AS category_title, c.`slug` AS category_slug FROM `cms_articles` a LEFT OUTER JOIN `cms_categories` c ON c.`category_id` = a.`category_id` AND c.`category_id` = '.self::$category_data['category_id'].' WHERE a.`published` = 1 AND a.`slug` = '.DB::escape_str($slug).' ORDER BY a.`dt_creation` DESC') && DB::num_rows() > 0) {
						if (self::$article_data = DB::fetch_next()) {
							return true;
						} else {
							return false;
						}
					}
				}

				return true;
			}
		}

		return false;
	}

	/*	================================================================================ === == =
		[pt-br] Funções para tratamento de artigos
		================================================================================ === == = */
	/**
	 *	\brief Retorna os dados de um determinado artigo
	 *
	 *	@param[in] $article_key Chave do artigo a ser buscado
	 *	@param[in] $key Coluna a ser usada como chave. Default 'article_id'
	 *	@param[in] $published Flag (true/false) para filtrar apenas artigos com status de publicado
	 *		Default = true
	 *
	 *	\return um \c array com os dados do artigo caso o encontre ou \c false se não encontrar um
	 *		artigo correspondete a chave informada
	 */
	public static function get_article($article_key, $key='article_id', $published=true) {
		DB::connect();

		switch ($key) {
			case 'article_id':
				if (!is_int($article_key)) {
					return false;
				}
				$where = 'a.`'.$key.'` = '.$article_key;
				break;
			case 'slug':
				$where = 'a.`'.$key.'` = '.DB::escape_str($article_key);
				break;
			default:
				return false;
		}

		$query = 'SELECT a.`article_id`, a.`title`, a.`slug`, a.`subtitle`, a.`text`, a.`author`, a.`published`, a.`dt_creation`, a.`dt_update`, c.`category_id`, c.`title` AS category_title, c.`slug` AS category_slug';
		$query .= ' FROM `cms_articles` a';
		$query .= ' LEFT OUTER JOIN `cms_categories` c ON c.`category_id` = a.`category_id`';
		$query .= ' WHERE '.$where.($published?' AND a.`published` = 1':'');

		if (DB::execute($query) && DB::num_rows() > 0) {
			return DB::fetch_next();
		}

		return false;
	}

	/**
	 *	\brief Exclui um artigo
	 *
	 *	@param[in] $article_id ID do artigo a ser excluído
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return a quantidade de registros afetados se tiver êxito na execução do método ou
	 *		\c false se não tiver sucesso
	 */
	public static function delete_article($article_id, &$error='') {
		if (!is_numeric($article_id)) {
			$error = 'Invalid method call CMS::delete_article(): article_id is not numeric';
			Errors::display_error(500, $error);
			return false;
		}

		$sql = 'DELETE FROM `cms_articles` WHERE `article_id` = '.(int)$article_id;
		DB::connect();
		if (DB::execute($sql)) {
			return DB::affected_rows();
		}

		return false;
	}

	/**
	 *	\brief Inclui um artigo
	 *
	 *	@param[in] $data Array com os dados do artigo a ser incluído
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return 
	 */
	public static function insert_article($data, &$error='') {
		$data['title']       = empty($data['title']) ? 'Untitled' : trim($data['title']);
		$data['slug']        = empty($data['slug']) ? $data['title'] : trim($data['slug']);
		$data['subtitle']    = empty($data['subtitle']) ? '' : trim($data['subtitle']);
		$data['text']        = empty($data['text']) ? 'Empty article' : trim($data['text']);
		$data['author']      = empty($data['author']) ? '' : trim($data['author']);
		$data['category_id'] = empty($data['category_id']) ? 0 : (int)$data['category_id'];
		$data['published']   = empty($data['published']) ? 0 : (int)$data['published'];

		return self::update_article(0, $data, $error);
	}

	/**
	 *	\brief Atualiza os dados de um determinado artigo
	 *
	 *	@param[in] $article_id ID do artigo a ser alterado
	 *	@param[in] $data Array com os dados do artigo a ser alterado
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return a quantidade de registros afetados ou \c false se houver erro
	 */
	public static function update_article($article_id, $data, &$error='') {
		if (!is_numeric($article_id)) {
			$error = 'Invalid method call CMS::update_article(): article_id is not numeric';
			Errors::display_error(500, $error);
			return false;
		}
		$article_id = (int)$article_id;

		DB::connect();

		// Valida o slug
		if (empty($data['slug']) && !empty($data['title']) && (int)$article_id == 0) {
			$data['slug'] = $data['title'];
		}
		if (isset($data['slug'])) {
			$data['slug'] = URI::slug_generator($data['slug']);

			if (empty($data['slug'])) {
				$error = 'Invalid method call CMS::update_article(): empty slug';
				Errors::display_error(500, $error);
				return false;
			}

			$slug = $data['slug'];
			$counter = ($article_id == 0) ? 1 : $article_id;
			while (($article = self::get_article_by_slug($data['slug'], false)) && ((int)$article['article_id'] != $article_id)) {
				$data['slug'] = $slug . '-' . $counter;
				$counter++;
			}
		}

		// Inicializa dados
		if ($article_id == 0) {
			$fields = array('title', 'slug', 'subtitle', 'text', 'author', 'category_id', 'published', 'dt_creation');
			$fieldlist = '';
			$valuelist = '';
			foreach($fields as $field) {
				$fieldlist .= (empty($fieldlist)?'':',').'`'.$field.'`';
				if (isset($data[$field])) {
					switch ($field) {
						case 'category_id':
						case 'published':
							Kernel::debug($field . ' = ' . $data[$field]);
							$data[$field] = (int)$data[$field];
							$valuelist .= (empty($valuelist)?'':',').$data[$field];
							break;
						default:
							$data[$field] = trim($data[$field]);
							$valuelist .= (empty($valuelist)?'':',').DB::escape_str(trim($data[$field]));
					}
				} else {
					switch ($field) {
						case 'title':
						case 'slug':
						case 'text':
							$error = 'Invalid method call CMS::update_article(): '.$field.' not defined';
							Errors::display_error(500, $error);
							return false;
						case 'dt_creation':
							$valuelist .= (empty($valuelist)?'':',').'NOW()';
							break;
						case 'category_id':
						case 'published':
							$data[$field] = 0;
							$valuelist .= (empty($valuelist)?'':',').$data[$field];
							break;
						default:
							$data[$field] = '';
							$valuelist .= (empty($valuelist)?'':',').DB::escape_str(trim($data[$field]));
					}
				}
			}
			$sql = sprintf('INSERT INTO `cms_articles` (%s) VALUES (%s)', $fieldlist, $valuelist);
		} else {
			$update = '';
			foreach($data as $field => $value) {
				switch ($field) {
					case 'title':
					case 'slug':
					case 'subtitle':
					case 'text':
					case 'author':
						$data[$field] = trim($value);
						$update .= (empty($update)?'':',').'`'.$field.'` = '.DB::escape_str(trim($data[$field]));
						break;
					case 'category_id':
					case 'published':
						$data[$field] = (int)$value;
						$update .= (empty($update)?'':',').'`'.$field.'` = '.$data[$field];
						break;
					default:
						unset($data[$field]);
				}
			}

			if (empty($update)) {
				$error = 'Invalid method call CMS::update_article(): no columns to be updated';
				Errors::display_error(500, $error);
				return false;
			}

			$sql = 'UPDATE `cms_articles` SET '.$update.' WHERE `article_id` = '.$article_id;
		}
		if (DB::execute($sql)) {
			return DB::affected_rows();
		}

		return false;
	}

	/**
	 *  \brief Retorna os dados de um artigo dado o seu id
	 *
	 *	Este método chama o método get_article internamente
	 *
	 *	@param[in] $article_id ID do artigo a ser procurado
	 *	@param[in] $published Flag (true/false) de filtro de artigos publicados
	 *		Default = true
	 *
	 *	\see get_article
	 */
	public static function get_article_by_id($article_key, $published=true) {
		return self::get_article((int)$article_key, 'article_id', $published);
	}

	/**
	 *  \brief Retorna os dados de um artigo dado o seu slug
	 *
	 *	Este método chama o método get_article internamente
	 *
	 *	@param[in] $slug Slug do artigo a ser procurado
	 *	@param[in] $published Flag (true/false) de filtro de artigos publicados
	 *		Default = true
	 *
	 *	\see get_article
	 */
	public static function get_article_by_slug($slug, $published=true) {
		return self::get_article($slug, 'slug', $published);
	}

	/**
	 *  \brief Retorna uma lista de artigos
	 *
	 *	@param[in] $category_id ID da categoria dos artigos a sererm retornados.
	 *		Se omitido retorna artigos de todas as categorias.
	 *	@param[in] $published Flag (true/false) de filtro de artigos publicados
	 *		Default = true
	 *	@param[in] $order_by Coluna pela qual os artigos devem ser ordenados.
	 *		Default = 'dt_creation'
	 *	@param[in] $order_sort Determina se a ordem é crescente ('ASC') ou decrescente ('DESC')
	 *		Default = 'DESC'
	 *	@param[in] $start Registro inicial (faz parceria com $limit)
	 *		Default = 0
	 *	@param[in] $limit quantidade de registros a ser retornado
	 *		Default = 10
	 *	@param[out] $count Variável de retorno da quantidade total de artigos
	 *
	 *	\return um array com a lista de artigos encontrados ou false caso encontre erro
	 */
	public static function get_articles($category_id=NULL, $published=true, $order_by='dt_creation', $order_sort='DESC', $start=0, $limit=10, &$count=0) {
		$query = 'SELECT SQL_CALC_FOUND_ROWS a.`article_id`, a.`title`, a.`slug`, a.`subtitle`, a.`text`, a.`author`, a.`published`, a.`dt_creation`, a.`dt_update`, c.`category_id`, c.`title` AS category_title, c.`slug` AS category_slug';
		$query .= ' FROM `cms_categories` c';
		$query .= ' RIGHT OUTER JOIN `cms_articles` a ON a.`category_id` = c.`category_id`';
		if ($category_id !== NULL) {
			$query .= ' WHERE c.`category_id` = '.$category_id;
		}
		if ($published) {
			$query .= (($category_id !== NULL)?' AND ':' WHERE ').'a.`published` = 1';
		}
		$query .= ' ORDER BY '.$order_by.' '.$order_sort;
		$query .= ' LIMIT '.$start.','.$limit;

		DB::connect();
		if (DB::execute($query)) {
			$articles = array();
			while ($article_data = DB::fetch_next()) {
				$articles[] = $article_data;
			}

			DB::execute('SELECT FOUND_ROWS() AS qtd');
			$data = DB::fetch_next();
			$count = (int)$data['qtd'];

			return $articles;
		}

		return false;
	}

	/**
	 *  \brief Retorna uma lista artigos ordenados pela categoria
	 *
	 *	Este método chama o método get_articles internamente 
	 *
	 *	@param[in] $category_id ID da categoria dos artigos a sererm retornados.
	 *		Se omitido retorna artigos de todas as categorias.
	 *	@param[in] $published Flag (true/false) de filtro de artigos publicados
	 *		Default = true
	 *	@param[in] $order_sort Determina se a ordem é crescente ('ASC') ou decrescente ('DESC')
	 *		Default = 'ASC'
	 *	@param[in] $start Registro inicial (faz parceria com $limit)
	 *		Default = 0
	 *	@param[in] $limit quantidade de registros a ser retornado
	 *		Default = 10
	 *	@param[out] $count Variável de retorno da quantidade total de artigos
	 *
	 *	\see get_articles
	 */
	public static function get_articles_by_category($category_id=NULL, $published=true, $order='ASC', $start=0, $limit=10, &$count=0) {
		return self::get_articles($category_id, $published, 'c.`category_id`', $order, $start, $limit, $count);
	}

	/**
	 *  \brief Retorna uma lista de artigos ordenados pela data de criação em órdem decrescente
	 *
	 *	Este método chama o método get_articles internamente 
	 *
	 *	@param[in] $category_id ID da categoria dos artigos a sererm retornados.
	 *		Se omitido retorna artigos de todas as categorias.
	 *	@param[in] $published Flag (true/false) de filtro de artigos publicados
	 *		Default = true
	 *	@param[in] $start Registro inicial (faz parceria com $limit)
	 *		Default = 0
	 *	@param[in] $limit quantidade de registros a ser retornado
	 *		Default = 10
	 *	@param[out] $count Variável de retorno da quantidade total de artigos
	 *
	 *	\see get_articles
	 */
	public static function get_last_articles($category_id=NULL, $published=true, $start=0, $limit=10, &$count=0) {
		return self::get_articles($category_id, $published, 'a.`dt_creation`', 'DESC', $start, $limit, $count);
	}

	/*  ================================================================================ === == =
		[pt-br] Funções para tratamento de categorias
	    ================================================================================ === == = */
	/**
	 *  \brief Retorna os dados de uma determinada categoria
	 *
	 *	@param[in] $category_key Chave da categoria a ser procurada
	 *	@param[in] $key Colula pela qual a categoria deve ser procurada
	 *		Default = 'category_id'
	 *	@param[in] $published Flag (true/false) de filtro de categorias publicadas
	 *		Default = false
	 *	\return um \c array com os dados da categoria, caso encontre ou \c false caso não encontre
	 *		uma categoria
	 */
	public static function get_category($category_key, $key='category_id', $published=false) {
		DB::connect();

		switch ($key) {
			case 'category_id':
				if (!is_int($category_key)) {
					return false;
				}
				$where = 'c.`'.$key.'` = '.$category_key;
				break;
			case 'slug':
				$where = 'c.`'.$key.'` = '.DB::escape_str($category_key);
				break;
			default:
				return false;
		}

		$query = 'SELECT c.`category_id`, c.`title`, c.`slug`, c.`dt_update`';
		$query .= ' FROM `cms_categories` c';
		$query .= ' WHERE '.$where/*.($published?' AND c.`published` = 1':'')*/;

		if (DB::execute($query) && DB::num_rows() > 0) {
			return DB::fetch_next();
		}

		return false;
	}

	/**
	 *	\brief Exclui uma categoria
	 *
	 *	@param[in] $category_id ID da categoria a ser excluída
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return a quantidade de registros afetados se tiver êxito na execução do método ou
	 *		\c false se não tiver sucesso
	 */
	public static function delete_category($category_id, &$error='') {
		if (!is_numeric($category_id)) {
			$error = 'Invalid method call CMS::delete_category(): category_id is not numeric';
			Errors::display_error(500, $error);
			return false;
		}

		$sql = 'DELETE FROM `cms_categories` WHERE `category_id` = '.(int)$category_id;
		DB::connect();
		if (DB::execute($sql)) {
			return DB::affected_rows();
		}

		return false;
	}

	/**
	 *	\brief Inclui uma categoria
	 *
	 *	@param[in] $data Array com os dados da categoria a ser incluída
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return 
	 */
	public static function insert_category($data, &$error='') {
		$data['title']       = empty($data['title']) ? 'Untitled' : trim($data['title']);
		$data['slug']        = empty($data['slug']) ? $data['title'] : trim($data['slug']);
		$data['published']   = empty($data['published']) ? 0 : (int)$data['published'];

		return self::update_category(0, $data, $error);
	}

	/**
	 *	\brief Atualiza os dados de uma determinada categoria
	 *
	 *	@param[in] $category_id ID da categoria a ser alterada
	 *	@param[in] $data Array com os dados da categoria a ser alterada
	 *	@param[out] $error Variável de retorno de erro
	 *
	 *	\return a quantidade de registros afetados ou \c false se houver erro
	 */
	public static function update_category($category_id, $data, &$error='') {
		if (!is_numeric($category_id)) {
			$error = 'Invalid method call CMS::update_category(): category_id is not numeric';
			Errors::display_error(500, $error);
			return false;
		}
		$category_id = (int)$category_id;

		DB::connect();

		// Valida o slug
		if (empty($data['slug']) && !empty($data['title']) && (int)$category_id == 0) {
			$data['slug'] = $data['title'];
		}
		if (isset($data['slug'])) {
			$data['slug'] = URI::slug_generator($data['slug']);

			if (empty($data['slug'])) {
				$error = 'Invalid method call CMS::update_category(): empty slug';
				Errors::display_error(500, $error);
				return false;
			}

			$slug = $data['slug'];
			$counter = ($category_id == 0) ? 1 : $category_id;
			while (($article = self::get_category_by_slug($data['slug'], false)) && ((int)$article['category_id'] != $category_id)) {
				$data['slug'] = $slug . '-' . $counter;
				$counter++;
			}
		}

		// Inicializa dados
		if ($category_id == 0) {
			$fields = array('title', 'slug');
			$fieldlist = '';
			$valuelist = '';
			foreach($fields as $field) {
				$fieldlist .= (empty($fieldlist)?'':',').'`'.$field.'`';
				if (isset($data[$field])) {
					switch ($field) {
						case 'published':
							Kernel::debug($field . ' = ' . $data[$field]);
							$data[$field] = (int)$data[$field];
							$valuelist .= (empty($valuelist)?'':',').$data[$field];
							break;
						default:
							$data[$field] = trim($data[$field]);
							$valuelist .= (empty($valuelist)?'':',').DB::escape_str(trim($data[$field]));
					}
				} else {
					switch ($field) {
						case 'title':
						case 'slug':
							$error = 'Invalid method call CMS::update_category(): '.$field.' not defined';
							Errors::display_error(500, $error);
							return false;
						case 'published':
							$data[$field] = 0;
							$valuelist .= (empty($valuelist)?'':',').$data[$field];
							break;
						default:
							$data[$field] = '';
							$valuelist .= (empty($valuelist)?'':',').DB::escape_str(trim($data[$field]));
					}
				}
			}
			$sql = sprintf('INSERT INTO `cms_categories` (%s) VALUES (%s)', $fieldlist, $valuelist);
		} else {
			$update = '';
			foreach($data as $field => $value) {
				switch ($field) {
					case 'title':
					case 'slug':
						$data[$field] = trim($value);
						$update .= (empty($update)?'':',').'`'.$field.'` = '.DB::escape_str(trim($data[$field]));
						break;
					/*case 'published':
						$data[$field] = (int)$value;
						$update .= (empty($update)?'':',').'`'.$field.'` = '.$data[$field];
						break;*/
					default:
						unset($data[$field]);
				}
			}

			if (empty($update)) {
				$error = 'Invalid method call CMS::update_article(): no columns to be updated';
				Errors::display_error(500, $error);
				return false;
			}

			$sql = 'UPDATE `cms_categories` SET '.$update.' WHERE `category_id` = '.$category_id;
		}
		if (DB::execute($sql)) {
			return DB::affected_rows();
		}

		return false;
	}

	/**
	 *  \brief Retorna os dados de uma categoria dado o seu id
	 *
	 *	Este método chama o método get_category internamente
	 *
	 *	@param[in] $category_key ID da categoria a ser procurada
	 *	@param[in] $published Flag (true/false) de filtro de categorias publicadas
	 *		Default = true
	 *
	 *	\see get_category
	 */
	public static function get_category_by_id($category_key, $published=true) {
		return self::get_category((int)$category_key, 'category_id', $published);
	}

	/**
	 *  \brief Retorna os dados de uma categoria dado o seu slug
	 *
	 *	Este método chama o método get_category internamente
	 *
	 *	@param[in] $slug slug da categoria a ser procurada
	 *	@param[in] $published Flag (true/false) de filtro de categorias publicadas
	 *		Default = true
	 *
	 *	\see get_category
	 */
	public static function get_category_by_slug($slug, $published=true) {
		return self::get_category($slug, 'slug', $published);
	}

	/**
	 *  \brief Retorna uma lista de categorias ordenados pela data de criação em órdem decrescente
	 *
	 *	@param[in] $published Flag (true/false) de filtro de categorias publicadas
	 *		Default = true
	 *	@param[in] $order_by Coluna pela qual as categorias devem ser ordenadas.
	 *		Default = 'title'
	 *	@param[in] $order_sort Determina se a ordem é crescente ('ASC') ou decrescente ('DESC')
	 *		Default = 'ASC'
	 *	@param[in] $start Registro inicial (faz parceria com $limit)
	 *		Default = 0
	 *	@param[in] $limit quantidade de registros a ser retornado
	 *		Default = 10
	 *	@param[out] $count Variável de retorno da quantidade total de artigos
	 *
	 *	\return um array com a lista de categorias encontradas ou false caso encontre erro
	 */
	public static function get_categories($published=true, $order_by='title', $order_sort='ASC', $start=0, $limit=10, &$count=0) {
		$query = 'SELECT SQL_CALC_FOUND_ROWS `category_id`, `title`, `slug`, `dt_update`';
		$query .= ' FROM `cms_categories`';
		/*if ($published) {
			$query .= (($category_id !== NULL)?' AND ':' WHERE ').'`published` = 1';
		}*/
		$query .= ' ORDER BY '.$order_by.' '.$order_sort;
		if ($limit > 0) {
			$query .= ' LIMIT '.$start.','.$limit;
		}

		DB::connect();
		if (DB::execute($query)) {
			$categories = array();
			while ($category_data = DB::fetch_next()) {
				$categories[] = $category_data;
			}

			DB::execute('SELECT FOUND_ROWS() AS qtd');
			$data = DB::fetch_next();
			$count = (int)$data['qtd'];

			return $categories;
		}

		return false;
	}

	/*  ================================================================================ === == =
		[pt-br] Funções para alimentação de variáveis do template
	    ================================================================================ === == = */
	/**
	 *  \brief Coloca os dados da categoria em variáveis do template
	 *
	 *	\return \c true se tiver uma categoria em memória e \c false em caso contrário
	 */
	public static function load_category_to_template() {
		if (self::category_is_loaded()) {
			if (!$tpl = new Templateed()) {
				$tpl = new Template();
			}

			Template::assign('CMS_Category', self::$category_data);
			Template::assign('CMS_CategoryID', self::$category_data['category_id']);
			Template::assign('CMS_CategoryTitle', self::$category_data['title']);
			Template::assign('CMS_CategorySlug', self::$category_data['slug']);
			Template::assign('CMS_CategoryDtUpdate', self::$category_data['dt_update']);

			return true;
		}

		return false;
	}

	/**
	 *  \brief Coloca os dados do artigo em variáveis do template
	 *
	 *	\return \c true se tiver um artigo em memória e \c false em caso contrário
	 */
	public static function load_article_to_template() {
		if (self::article_is_loaded()) {
			if (!$tpl = new Templateed()) {
				$tpl = new Template();
			}

			Template::assign('CMS_Article', self::$article_data);
			Template::assign('CMS_ArticleID', self::$article_data['article_id']);
			Template::assign('CMS_ArticleTitle', self::$article_data['title']);
			Template::assign('CMS_ArticleSlug', self::$article_data['slug']);
			Template::assign('CMS_ArticleSubtitle', self::$article_data['subtitle']);
			Template::assign('CMS_ArticleText', self::$article_data['text']);
			Template::assign('CMS_ArticleAuthor', self::$article_data['author']);
			Template::assign('CMS_ArticleDtUpdate', self::$article_data['dt_update']);
			Template::assign('CMS_CategoryID', self::$article_data['category_id']);
			Template::assign('CMS_CategoryTitle', self::$article_data['category_title']);
			Template::assign('CMS_CategorySlug', self::$article_data['category_slug']);

			return true;
		}

		return false;
	}

	/**
	 *  \brief Coloca a lista os artigos da categoria carregada em variáveis do template
	 *
	 *	@param[in] $start Registro inicial (faz parceria com $limit)
	 *		Default = 0
	 *	@param[in] $limit quantidade de registros a ser retornado
	 *		Default = 10
	 *
	 *	\return \c true se tiver uma categoria em memória e houverem artigos para esta categoria
	 *		ou \c false em caso contrário
	 */
	public static function load_articles_to_template($start=0, $limit=10) {
		if (self::category_is_loaded()) {
			if ($articles = self::get_last_articles(self::$category_data['category_id'], true, $start, $limit, $count)) {
				if (!$tpl = new Templateed()) {
					$tpl = new Template();
				}
				Template::assign('CMS_ArticleList', $articles);

				$pagination = new Pagination;
				$pagination->setSiteLink('/'.self::$category_data['slug'].'/[page]');
				$pagination->CalculateNumPages($count, $limit);
				Template::assign('Pagination', $pagination->parse((int)($start / $limit)));
				return true;
			}
		}

		return false;
	}

	/*  ================================================================================ === == =
		[pt-br] Funções para tratamento de usuários
	    ================================================================================ === == = */
	/**
	 *	\brief Retorna os dados de um determinado usuário
	 *
	 *	@param[in] $user_key Chave de busca do usuário
	 *	@param[in] $key Colula pela qual o usuário deve ser procurado
	 *		Default = 'user_id'
	 *	@param[in] $pass Senha do usuário (em texto puro)
	 *		usado quando a chave de busca é por login ou email
	 *
	 *	\return um \c array com os dados do usuário, caso encontre ou \c false em caso contrário
	 */
	private static function get_user($user_key, $key='user_id', $pass='') {
		DB::connect();

		switch ($key) {
			case 'user_id':
				if (!is_int($user_key)) {
					return false;
				}
				$where = '`'.$key.'` = '.$user_key;
				break;
			case 'login':
			case 'email':
				$where = '`'.$key.'` = '.DB::escape_str($user_key);
				if (!empty($pass)) {
					$where .= ' AND `password` = SHA1('.DB::escape_str($pass).')';
				}
				break;
			default:
				return false;
		}

		if (DB::execute('SELECT `user_id`, `login`, `name`, `email`, `dt_update` FROM `cms_users` WHERE '.$where) && DB::num_rows() > 0) {
			return DB::fetch_next();
		}

		return false;
	}

	/**
	 *	\brief Atualiza os dados de um determinado usuário
	 */
	private static function update_user($data, $user_key, $key='user_id') {
		DB::connect();

		switch ($key) {
			case 'user_id':
				if (!is_numeric($user_key)) {
					return false;
				}
				$where = '`'.$key.'` = '.(int)$user_key;
				break;
			case 'login':
			case 'email':
				$where = '`'.$key.'` = '.DB::escape_str($user_key);
				break;
			default:
				return false;
		}

		$update = '';
		foreach($data as $key => $value) {
			switch ($key) {
				case 'email':
				case 'login':
				case 'name':
					$update .= (empty($update)?'':',').'`'.$key.'` = '.DB::escape_str($value);
					break;
				case 'password':
					$update .= (empty($update)?'':',').'`'.$key.'` = SHA1('.DB::escape_str($value).')';
					break;
				default:
					unset($data[$key]);
			}
		}

		if (empty($update)) {
			return false;
		}

		if (DB::execute('UPDATE `cms_users` SET '.$update.' WHERE '.$where) && DB::affected_rows() > 0) {
			return true;
		}

		return false;
	}

	/**
	 *	\brief Retorna um array estrutura com os dados de um usuário
	 */
	private static function set_user_array($login='', $password='', $name='', $email='') {
		$data = array();
		if (!empty($login)) $data['login'] = $login;
		if (!empty($password)) $data['password'] = $password;
		if (!empty($name)) $data['name'] = $name;
		if (!empty($email)) $data['email'] = $email;
		return $data;
	}

	/**
	 *	\brief Retorna os dados de um usuário dado o seu id
	 */
	public static function get_user_by_id($user_id) {
		return self::get_user((int)$user_id, 'user_id');
	}

	/**
	 *	\brief Retorna os dados de um usuário dado o seu login
	 */
	public static function get_user_by_login($user_login, $pass='') {
		return self::get_user($user_login, 'login', $pass);
	}

	/**
	 *	\brief Atualiza os dados de um usuário dado o seu id
	 */
	public static function update_user_by_id($user_id, $login='', $password='', $name='', $email='') {
		return self::update_user(self::set_user_array($login, $password, $name, $email), $user_id, 'user_id');
	}

	/**
	 *	\brief Verifica se há um usuário logado
	 */
	public static function logged_in_user() {
		if (!Session::is_set('_cms_user')) {
			return false;
		}

		return Session::get('_cms_user');
	}

	/**
	 *	\brief Faz o logon de um usuário
	 */
	public static function login_user($login, $password) {
		if (Session::is_set('_cms_user')) {
			return false;
		}

		if ($user = self::get_user_by_login($login, $password)) {
			Session::set('_cms_user', $user);
		}

		return Session::get('_cms_user');
	}

	/**
	 *	\brief Faz o logoff do usuário
	 */
	public static function logout_user() {
		if (!Session::is_set('_cms_user')) {
			return false;
		}

		Session::unregister('_cms_user');

		return true;
	}

}
