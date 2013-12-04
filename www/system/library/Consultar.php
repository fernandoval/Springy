<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe para crição de table grids de consulta
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.10.2
 *  \author		Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup	framework
 */

class Grid extends Pagination {
	private $class = "";

	private $width = "";
	private $results = array();
	/// Colunas da tabela
	private $tableColumns = array();
	private $iptCheck = true;

	private $uri_param = array();
	private $uri_get = array();

	private $filterTpl = NULL;
	private $extraFormTpl = NULL;

	private $filterByUser  = array();
	private $filterDefault = array();

	private $cookieName = NULL;
	private $filterUseCookies = false;
	private $order = array();
	private $methodGrid = 'grid';
	private $methodCount = 'count';
	private $methodSum = 'soma';

	private $extraToolbar = array();
	private $extraActions = array();

	/// Título da tabela
	private $tableTitle = '(UNTITLED)';
	private $lista = "";
	private $insert = false;
	private $edit = false;
	private $columnsToTitle = array();
	private $delete = false;
	private $csv = false;
	private $excel = false;

	private $tplVariables = array();

	private $ajax = false;

	/// Mensagem a ser exibida de tabela não tiver linhas
	private $noRowsMessage = '(NO ROWS)';

	private $gridCSS = "";

	private $onlyInAjax = false;
	
	/// Nome da variável usada no POST para determinar um filtro
	private $postFilterVarName = 'filter';
	/// Nome da variável usada no POST para determinar ordenação
	private $postOrderVarName = 'order';

	/**
	 *  \brief Método construtor
	 *  
	 *  \param $classe - nome da classe
	 */
	public function __construct($classe) {
		if (!class_exists($classe)) {
			throw new Exception('Class given "'.$classe.'" does not exists.');
		}
		$this->class = $classe;

		if (URI::_GET('cAjax')) {
			$this->ajax = true;
		}

		parent::__construct();

		$this->uri_param = URI::getAllSegments();
		$this->uri_get   = URI::getParams();
		unset($this->uri_get['cAjax']);

		if (isset($_POST[$this->postFilterVarName])) {
			foreach($_POST[$this->postFilterVarName] as $k => $v) {
				if (empty($_POST[$this->postFilterVarName][$k])) {
					unset($_POST[$this->postFilterVarName][$k]);
				}
			}

			$this->filterByUser = $_POST[$this->postFilterVarName];
		}

		if (isset($_POST[$this->postOrderVarName]) && is_array($_POST[$this->postOrderVarName])) {
			$this->order = $_POST[$this->postOrderVarName];
		}

		$this->cookieName = md5(implode('', URI::getAllSegments()));
	}

	/**
	 *  \brief Tamanho da tabela
	 *  
	 *  \param String $width ([0-9]+\%|[0-9]+px) - Se for passado altera o tamanho da tabela
	 *  \return Retorna o tamanho da tabela
	 */
	public function width($width=null) {
		if (!is_null($width)) {
			$this->width = $width;
		}
		return $this->width;
	}
	/**
	 *  \bried DEPRECATED - Use width
	 *  \deprecated
	 *  \see width
	 */
	public function setWidth($width=null) {
		return $this->width($width);
	}

	/**
	 *  \brief Seta os valores a serem printados
	 *  
	 *  @param Array $res
	 *  @return void()
	 */
	public function setResult($res) {
		$this->results = $res;
	}

	/**
	 *  \brief Define as colunas da tabela
	 *  
	 *  \param (array)$columns - se informado, define os nomes das colunas da tabela.
	 *    O array de colunas deve ser um array multidimensional com os seguintes índices para cada coluna:
	 *    \li 'label' - o nome da coluna
	 *    \li 'order' - define um "alias" para, caso o usuário mande ORDENAR pela coluna,
	 *      o script identifique a coluna clicada e faça as devidas ordenações
	 *      (não conselhavel usar o nome da coluna do banco)
	 *    \li 'sum' - define um "alias" para, caso o usuário mande SOMAR a coluna,
	 *      o script identifique a coluna clicada e faça as devidas somas
	 *      (não conselhavel usar o nome da coluna do banco)
	 *    \li 'width' - define o tamanho em pixels da coluna na tabela/grid
	 *    \li 'image' - um array que define que dentro da coluna será apresentada uma imagem. Os parametros
	 *      usados neste array serão considerados como atributos da tag IMG
	 *    \li 'editable' - coloca um input text dentro da coluna.
	 *  \return Retorna um array contendo os nomes das colunas da tabela
	 */
	public function columns($columns=null) {
		if (!is_null($columns)) {
			$this->tableColumns = $columns;
		}
		return $this->tableColumns;
	}
	public function setColumnsNames($columns=null) {
		return $this->columns($columns);
	}

	/**
	 *  \brief Ativa a ordenação de resultados da página
	 *  
	 *  @return void()
	 */
	public function desactiveInputCheck() {
		$this->iptCheck = false;
	}

	/**
	 *  Expecifica os dados para serem utilizados na classe/metodo URI::buildURL()
	 *  para construção de URL a ser colocada em links
	 *  
	 *  @param Array $params
	 *  @param Array $get
	 *  @return void()
	 */
	public function setURI($param, $get) {
		$this->uri_param = $param;
		$this->uri_get   = $get;
	}

	/**
	 *  \brief Seta um texto para exibir caso a classe não tenha reultados a serem mostrados
	 *  
	 *  \param (string)$message - se fornecido altera a mensagem
	 *  \return Retorna a mensagem que será exibida caso a tabela não tenha linhas
	 */
	public function empty_message($message=null) {
		if (!is_null($message)) {
			$this->noRowsMessage = $message;
		}
		return $this->noRowsMessage;
	}
	/**
	 *  \brief DEPRECATED - Use empty_message
	 *  \deprecated
	 *  \see empty_message
	 */
	public function setTxtNoRows($message=null) {
		return $this->empty_message($message);
	}

	/**
	 *  \brief Seta o título do sistema a ser administrado
	 *  
	 *  \param (string)$title - Se passado altera o título da tabela
	 *  \return Retirna uma string contendo o título da tabela
	 */
	public function title($title=null) {
		if (!is_null($title)) {
			$this->tableTitle = $title;
		}
		return $this->tableTitle;
	}
	/**
	 *  \brief DEPRECATED - Use title
	 *  \deprecated
	 *  \see title
	 */
	public function setTitle($title=null) {
		return $this->title($title);
	}

	/**
	 *  \brief Seta o parametro a ser adicionado em $this->uri_param no qual se refere a parte de listagem do sistema em questão
	 *  
	 *  @param String $param
	 *  @return void()
	 */
	public function setListUrlParam($param) {
		$this->lista = $param;
	}

	/**
	 *  \brief Seta configurações para o botão Novo
	 *  
	 *  @param Array $param
	 *  @return void()
	 */
	public function setInsert(array $param=array()) {
		$this->insert = array_merge(array(
			'label' => 'Novo',
			'href' => 'cadastro',
			'hrefCompleto' => NULL
		), $param);

	}

	/**
	 *  \brief Seta configurações para o botão edit
	 *  
	 *  @param Array $param
	 *  @return void()
	 */
	public function setEdit($param) {
		$this->edit = array_merge(array(
			'title' => '',

			'minWidth' => '',
			'minHeight' => '',

			'width' => '',
			'height' => '',

			'maxWidth' => '',
			'maxHeight' => '',

			'allowWidthReduction' => '',

			'label' => 'Editar',
			'class' => 'editar',
			'url' => 'alterar',
			'hrefCompleto' => NULL,
			'cond' => NULL,
		), $param);
	}

	public function setColumnsToTitle($columns) {
		$this->columnsToTitle = $columns;
	}

	/**
	 *  \brief Seta configurações para o botão excluir
	 *  
	 *  @param Array $param
	 *  @return void()
	 */
	public function setDelete(array $param=array()) {
		$this->delete = array_merge(array(
			'label' => 'Excluir',
			'href' => 'excluir',
			'hrefCompleto' => NULL,
			'altertTitle' => 'Confirmar exclusão de registros',
			'alertMsg' => 'Deseja realmente deletar estes registros?'
		), $param);

	}

	/**
	 *	\brief Adiciona um botão na toolbar com a funcionalidade de baixar a grid consultada no formato CSV
	 *
	 *	@param Array $param (
	 *  	'metodo' => Define o nome do método de $this->class no qual o SQL será executado para retornar o CSV,
	 *  	'file_name' => Nome sugerido do arquivo,
	 *  		'colunas' => array(     //As colunas que o usuário poderá utilizar para gerar o excel,
	 *    		'nome_da_coluna_a_ser_inserida_no_SQL' => 'Nome amigável da coluna a ser apresentada para o usuário'
	 *  	)
	 *  	'titulo' => Um titulo
	 *	)
	 */
	public function setCSV($dados) {
		$this->csv = $dados;
	}

	/**
	 *	\brief Adiciona um botão na toolbar com a funcionalidade de baixar a grid consultada no formato excel
	 *
	 *	@param Array $param (
	 *  	'metodo' => Define o nome do método de $this->class no qual o SQL será executado para retornar o excel,
	 *  	'file_name' => Nome sugerido do arquivo,
	 *  	'colunas' => As colunas que o usuário poderá utilizar para gerar o excel,
	 *  	'titulo' => Um titulo
	 *	)
	 */
	public function setExcel($dados) {
		$this->excel = $dados;
	}

	/**
	 *	\brief Adiciona um botão na toolbar
	 *
	 *	@param Array $param (
	 *  	'label' o "nome" do botão
	 *  	'hs' define que a url será aberta com o HS,
	 *  	['href' define a url de ação],
	 *  	['onclick' informa uma ação onclick para uma função JS definida,
	 *  	'script' função em JS],
	 *  	['postScript' faz com q o usuário selecione resultados. Após uma tela de confirmação, a grid faz um POST para uma URL determinada.
	 *  		Este parametro deverá ter um array contendo :
	 *     		'title' titulo da caixa de mensagem para confirmar a ação do usuário
	 *     		'message' mensgem da caixa de mensagem para confirmar a ação do usuário
	 *     		'url' url do POST
	 * 		]
	 *	)
	 *	@return void()
	 */
	public function addExtraToolbar($param) {
		$this->extraToolbar[] = $param;
	}

	/**
	 *	\brief Adiciona um botão a cada registro para ações singulares
	 *
	 *	@param Array $param (
	 *  	'label' o "nome" do botão
	 *  	['href' define a url de ação],
	 *  	['hs' define que o link chamará a HS
	 *     	'title' titulo da HS
	 *     	'width' largura da HS
	 *  	['onclick' informa uma ação onclick para uma função JS definida,
	 *  	'script' função em JS],
	 *	)
	 *	@return void()
	 */
	public function addExtraActions(array $param) {
		$this->extraActions[] = $param;
	}

	/**
	 *  \brief Informa qual tpl será utilizado para utilizar ocmo filtro
	 *  
	 *  @param String $tpl
	 *  @return void()
	 */
	public function setFilterTpl($tpl) {
		$this->filterTpl = $tpl;
	}

	/**
	 *  \brief Informa qual tpl será utilizado para utilizar ocmo filtro
	 *  
	 *  @param String $tpl
	 *  @return void()
	 */
	public function setExtraFormTpl($tpl) {
		$this->extraFormTpl = $tpl;
	}

	/**
	 *  \brief Carrega o filtro setado no cookie
	 */
	public function loadCookedFilter() {
		if (!isset($_POST[$this->postFilterVarName]) && Cookie::exists('LibsConsultarFiltro_' . $this->cookieName )) {
			$this->filterByUser = array_merge(unserialize(base64_decode(Cookie::get('LibsConsultarFiltro_' . $this->cookieName ))), $this->filterByUser);
		}
		$this->filterUseCookies = true;
	}

	/**
	 *  \brief Array de dados do FORM filterTpl para ser passado para o método grid
	 *  
	 *  @param Array $filter
	 *  @return void()
	 */
	public function setFilter(array $filter) {
		//$this->filter = array_merge($filter, $this->filter);
		$this->filterDefault = $filter;
	}

	/**
	 *  \brief Diz qual a ordenação da grid. Este método apenas é executado caso não seja passado, via POST, a ordem ou ela for invalida
	 *  
	 *  @param Array $filter
	 *  @return void()
	 */
	public function setOrder(array $order) {
		// seta somente se não houver post
		if (!count($this->order)) {
			$this->order = $order;
		}
	}

	/**
	 *  \brief Seta o nome do método grid para trazer os resultados do banco
	 *  
	 *  @param String $method
	 *  @return void()
	 */
	public function setMethodGrid($method) {
		$this->methodGrid = $method;
	}

	/**
	 *  \brief Seta o nome do método count que conta quantos registros o banco tem.
	 *  
	 *  @param String $method
	 *  @return void()
	 */
	public function setMethodCount($method) {
		$this->methodCount = $method;
	}

	/**
	 *  \brief Seta o nome do método sum que suma os valores de uma coluna
	 *  
	 *  @param String $method
	 *  @return void()
	 */
	public function setMethodSum($method) {
		$this->methodSum = $method;
	}

	/**
	 *  \brief Seta o método e as colunas a serem somadas
	 *  
	 *  @param array $method['columns']
	 *  @return void()
	 */
	public function setSumColumns(array $dados) {
		$this->sumColumns = $dados;
	}

	/**
	 *  \brief Seta variáveis para serem utilizadas no tpl da grid
	 *  
	 *  @param string $var
	 *  @param mixed $value
	 *  @return void()
	 */
	public function setTemplateVariable($var, $value) {
		$this->tplVariables[$var] = $value;
	}


	public function setGridCSS($css) {
		$this->gridCSS = $css;
	}

	/**
	 *	\brief Informa que a consultar somente será exibida via ajax.
	 *
	 *	Este tipo deverá ser usado para grids SEM forms de filtro OU quando o filtro não é importante.
	 */
	const SEARCH_TYPE_ONLY_AJAX = 1;
	
	/**
	 *	\brief Informa que a consultar somente será exibida via ajax com filtro.
	 *
	 *	Este tipo deverá ser usado para grids COM forms de filtro.
	 *	O usuário somente verá a grid "montada" caso ele preencha algo no form de filtro.
	 */
	const SEARCH_TYPE_ONLY_FILTER = 2;
	
	/**
	 * 	\brief Informa se a grid em si somente será exibida caso haja uma requisição via ajax
	 *
	 *  @param int $var
	 *  @return void()
	 */
	public function setSearchType($type=Consultar::SEARCH_TYPE_ONLY_AJAX) {
		$this->onlyInAjax = $type;
	}

	public function compile() {
		if ($this->filterUseCookies) {
			Cookie::set('LibsConsultarFiltro_' . $this->cookieName, base64_encode( serialize($this->filterByUser) ), 604800);
		}
		
		if (isset($_POST['excel'])) {
			return $this->excel();
		} else if (isset($_POST['csv'])) {
			return $this->csv();
		} else if (isset($_POST['sum'])) {
			return $this->soma();
		} else {
			return $this->html();
		}
	}

	public function excel() {
		ob_clean();

		set_time_limit(0);

		header('Content-type: application/vnd.ms-excel; charset=iso-8859-1');
		header('Content-Disposition: attachment; filename="'.utf8_decode($this->excel['file_name']) .'('.date('d-m-Y H-i-s').').xls"');

		$tmpfname = tempnam(sys_get_temp_dir(), $this->cookieName);
		$excel = new Excel;
		$excel->setNameFile($this->excel['file_name'] . '(' . date('d-m-Y H-i-s') . ').xls');
		$excel->open($tmpfname);

		$csv = 0;

		if (isset($this->excel['titulo'])) {
			$excel->write_header($this->excel['titulo']);
		}

		do {
			foreach (call_user_func(array($this->class, $this->excel['metodo']), array_merge($this->filterDefault, $this->filterByUser), $this->order, $this->excel['colunas'], $csv*500, 500) as $value) {
				$excel->write_line($value);
			}

			if ($csv == 0) {
				$this->setNumRows(call_user_func(array($this->class, $this->methodCount), array_merge($this->filterDefault, $this->filterByUser)));
			}

			$csv++;
		} while($csv <= ceil($this->getNumRows()/500));

		$excel->close();

		echo file_get_contents($tmpfname);

		unlink($tmpfname);
	}

	private function csv() {
		ob_clean();

		header('Content-type: text/csv; charset=iso-8859-1');
		header('Content-Disposition: attachment; filename="'.utf8_decode($this->csv['file_name']) .'('.date('d-m-Y H-i-s').').csv"');

		$this->setNumRows(call_user_func(array($this->class, $this->methodCount), array_merge($this->filterDefault, $this->filterByUser)));

		for ($csv=0; $csv<ceil($this->getNumRows()/500); $csv++) {
			foreach (call_user_func(array($this->class, $this->csv['metodo']), array_merge($this->filterDefault, $this->filterByUser), $this->order, (isset($_POST['csv']['col']) ? $_POST['csv']['col'] : array('*')), $csv*500, 500) as $value) {
				$key = 0;
				foreach($value as $data) {
					echo ($key > 0 ? (isset($_POST['csv']['separador']) ? $_POST['csv']['separador'] : ',') : '') . '"' . addslashes( utf8_decode($data) ). '"';
					$key++;
				}
				echo "\n";
			}
		}

		die;
	}

	private function soma() {
		$json = new JSON;
		if (!isset($_POST['sum'])) {
			$json->add(array('error' => array('itx' => 'Post mal formado.')));
		} else {
			$json->add(array('soma' => call_user_func(array($this->class, $this->methodSum), $_POST['sum'], array_merge($this->filterDefault, $this->filterByUser))));
		}
		$json->printJ();
	}

	private function html() {
		$tpl = new Template();

		if ($this->ajax) {
			$tpl->setTemplate(array('consultar_body'));
		} else {
			$param = $this->uri_param;

			if ($this->lista) {
				$param[] = $this->lista;
			}

			$tpl->setTemplate(array('consultar'));

			$tpl->assign('urlAtual', Administrador_Static::build($param));
			$tpl->assign('urlAtualAjax', Administrador_Static::build($param, array_merge($this->uri_get, array('cAjax' => 1))));
			$tpl->assign('cookieName', $this->cookieName);
			unset($param);

			$tpl->assign('mensagem', URI::_GET('sucesso') ? URI::_GET('sucesso') : '');

			$tpl->assign('pag', $this->getCurrentPage());

			if ($this->gridCSS) {
				$tpl->assign('gridCSS', $this->gridCSS);
			}
		}

		$tpl->setTemplateDir(realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'Consultar');

		foreach($this->tplVariables as $var => $value) {
			$tpl->assign($var, $value);
			unset($this->tplVariables[$var]);
		}

		$tpl->assign('widthTable', ($this->width ? $this->width : '100%'));

		if (!$this->ajax) {
			if ($this->tableTitle) {
				$tpl->assign('adm_titulo', $this->tableTitle);
			}

			if ($this->delete) {
				if (!is_null($this->delete['hrefCompleto'])) {
					$this->delete['href'] = $this->delete['hrefCompleto'];
				} else {
					$param = $this->uri_param;
					$param[] = $this->delete['href'];
					$this->delete['href'] = Administrador_Static::build($param, array_merge($this->uri_get, array('cAjax' => 1)));
					unset($param);
				}

				array_push($this->extraToolbar, array(
					'hint' => '',
					'label' => $this->delete['label'],
					'icon' => 'bt_delete.png',
					'postScript' => array(
						'title' => $this->delete['altertTitle'],
						'message' => $this->delete['alertMsg'],
						'url' => $this->delete['href']
					)
				));

				$this->delete = NULL;
			}

			if ($this->insert) {
				if (!is_null($this->insert['hrefCompleto'])) {
					$this->insert['href'] = $this->insert['hrefCompleto'];
				} else {
					$param = $this->uri_param;
					$param[] = $this->insert['href'];
					$this->insert['href'] = Administrador_Static::build($param, array_merge($this->uri_get, array('cAjax' => 1)));
					unset($param);
				}

				array_unshift($this->extraToolbar, array(
					'hint' => '',
					'label' => $this->insert['label'],
					'icon' => 'bt_novo.png',
					'hs' => isset($this->insert['hs']) ? $this->insert['hs'] : true,
					'href' => $this->insert['href']
				));

				$this->insert = NULL;
			}

			if ($this->csv) {
				unset($this->csv['metodo']);
				$tpl->assign('bt_csv', json_encode($this->csv));
			}

			if (count($this->extraToolbar)) {
				$tpl->assign('extraToolbar', $this->extraToolbar);
			}

			if ($this->filterTpl) {
				$filtro = array();
				$filtro['tpl'] = $this->filterTpl;
				$filtro['dados'] = array_merge($this->filterDefault, $this->filterByUser);
				$tpl->assign($this->postFilterVarName, $filtro);
				unset($filtro);
			}

			if ($this->extraFormTpl) {
				$tpl->assign('extraFormTpl', $this->extraFormTpl);
			}
		}

		if (isset($this->filterByUser['n_paginas'])) {
			if (in_array((int)$this->filterByUser['n_paginas'], array(15, 30, 45))) {
				$this->setRowsPerPage((int)$this->filterByUser['n_paginas']);
			}
		}

		$tpl->assign('n_paginas', $this->getRowsPerPage());

		$offset = ($this->getCurrentPage()-1) * $this->getRowsPerPage();

		if (isset($this->filterByUser['n_paginas'])) {
			unset($this->filterByUser['n_paginas']);
		}

		// se a grid for somente via ajax, imprime uma mensagem informando ao usuário que ele precisa
		// a tela ja abre com a busca aberta
		if ($this->onlyInAjax) {
			$tpl->assign('filterOpened', true);
		}
		
		$fazConsulta = false;
		
		if ($this->onlyInAjax == Consultar::SEARCH_TYPE_ONLY_FILTER && $this->ajax && $this->filterByUser) {
			$fazConsulta = true;
		} else if ($this->onlyInAjax == Consultar::SEARCH_TYPE_ONLY_AJAX && $this->ajax) {
			$fazConsulta = true;
		} else if (!$this->onlyInAjax) {
			$fazConsulta = true;
		}
		
		if ($fazConsulta) {
			$this->results = call_user_func(array($this->class, $this->methodGrid), array_merge($this->filterDefault, $this->filterByUser), $this->order, $offset, $this->getRowsPerPage());
			if (!is_null($this->methodCount)) {
				$this->setNumRows(call_user_func(array($this->class, $this->methodCount), array_merge($this->filterDefault, $this->filterByUser)));
			}
		}
		unset($fazConsulta);
		
		//unset($filtro);

		if (empty($this->results)) {
			// se a grid for somente via ajax, imprime uma mensagem informando ao usuário que ele precisa
			// a tela ja abre com a busca aberta
			$tpl->assign('noRows', ($this->onlyInAjax && !$this->ajax ? 'Utilize a busca para visualizar dados desta grid.' : $this->noRowsMessage));
		} else {
			if ($this->iptCheck) {
				$tpl->assign('showCheckBox', true);
			}

			// prepara os nomes das colunas (<thead>)
			foreach ($this->tableColumns as $key => $column) {
				if (is_array($column)) {
					if (isset($column['order'])) {
						$slug = $column['order'];

						$column['orderBy'] = $slug;

						// se tiver ordenando por essa coluna, imprime uma setinha indicativa
						if (!empty($this->order[ $column['order'] ])) {
							$column['orderType'] = ((isset($this->order[ $column['order'] ]) && $this->order[ $column['order'] ] == 'asc') ? 'asc' : 'desc'); // se clicar denovo, ordena por
							if (count($this->order) > 1) {
								$column['showRmvOrder'] = true;
							}
						}

						unset($slug);
					}
				} else {
					$column = array(
						'label' => $column
					);
				}

				$colunas[ $key ] = $column;
				unset($column);
			}

			// se tiver mais de um campo sendo ordenado, mostra um botão para remover esta ordenação

			$tpl->assign('colunas', $colunas);

			if ($this->edit) {
				array_unshift($this->extraActions, array(
					'label' => $this->edit['label'],
					'class' => $this->edit['class'],
					'cond' => $this->edit['cond'],
					'href' => is_null($this->edit['hrefCompleto']) ? $this->edit['url'] : NULL,
					'hrefCompleto' => $this->edit['hrefCompleto'],
					'hs' => array(
						'title' => $this->edit['title'],
						'minWidth' => $this->edit['minWidth'],
						'minHeight' => $this->edit['minHeight'],
						'width' => $this->edit['width'],
						'height' => $this->edit['height'],
						'maxWidth' => $this->edit['maxWidth'],
						'maxHeight' => $this->edit['maxHeight'],
						'allowWidthReduction' => $this->edit['allowWidthReduction']
					)
				));
			}

			// prapara o corpo da grid (<tbody>)
			foreach($this->results as $id => $values) {
				foreach($this->tableColumns as $coumnName => $coluna) {
				//foreach($this->results[ $id ]['columns'] as $coumnName => $coluna) {
					if (is_array($coluna)) {
						// se a coluna tem alguma condição para colocar um style diferente
						// %s é o valor da coluna da linha atual

						if (isset($coluna['cond_style'])) {
							$this->results[ $id ]['style'][$coumnName] = eval( $this->replaceValues($coluna['cond_style'], $id, $values) );
							unset($countParms);
						}

						// se a coluna possue um link
						if (isset($coluna['href'])) {
							// tendo, joga as propriedades padrões para o valor da coluna da linha atual
							$this->results[ $id ]['href'][$coumnName] = $coluna['href'];

							// substitue as variaveis dinamicas
							$this->replaceValues($this->results[ $id ]['href'][$coumnName], $id, $values);
						}

						// se a coluna possue uma ação usando a HighSlide (highslide.com)
						if (isset($coluna['hs'])) {
							$this->results[ $id ]['hs'][$coumnName]['title'] = $this->replaceValues($coluna['hs']['title'], $id, $values);
							unset($countParms);
						}
					}
				}

				if ($this->extraActions) {
					foreach ($this->extraActions as $action) {

						$show = true;

						// monta uma condição: dependendo do valor de uma ou mais colunas ou variaveis, exibe ou não este botão extaAction
						if (isset($action['cond'])) {
							$this->replaceValues($action['cond'], $id, $values);

							if (!eval($action['cond'])) {
								$show = false;
							}

							unset($cond, $colsToCompare);
						}

						if ($show) {
							/* *** BEGIN monta o link do botão extraAction *** */
							if (isset($action['hrefCompleto'])) {
								$action['href'] = $action['hrefCompleto'];
							} else {
								$param = $this->uri_param;
								$param[] = $action['href'];

								$action['href'] = Administrador_Static::build($param, $this->uri_get + array('id' => '[id]') );
							}

							$this->replaceValues($action['href'], $id, $values);

							//* *** END  monta o link do botão extraAction *** */

							if (isset($action['hs']['title'])) {
								$action['hs']['title'] = $this->replaceValues($action['hs']['title'], $id, $values);
							}

							if (isset($action['ajax'])) {
								$action['ajax']['to'] = $action['href'];
								unset($action['href']);
							}

							if (isset($action['ajax']['msgConfirmacao']) && isset($action['ajax']['msgConfirmacaoColumns'])) {
								$ajax_colunas = array();

								foreach($action['ajax']['msgConfirmacaoColumns'] as $key) {
									$ajax_colunas[] = addslashes($values['columns'][$key]);
								}

								$action['ajax']['msgConfirmacao'] =  eval('return sprintf(\'' . addslashes($action['ajax']['msgConfirmacao']) . '\', \'' . implode('\',\'', $ajax_colunas) . '\');');
								unset($ajax_colunas);
							}

							$this->results[ $id ]['actions'][] = $action;
						}
					}
				}

				if (isset($this->results[ $id ]['actions']) && (!isset($countActions) || ($countActions < count($this->results[ $id ]['actions'])))) {
					$countActions = count($this->results[ $id ]['actions']);
				}
			}

			if (isset($countActions)) {
				$tpl->assign('countActions', $countActions);
				unset($countActions);
			}

			$tpl->assign('results', $this->results);
			$this->results = NULL;

			/* paginação */
			if ($this->getNumRows()) {
				$this->setSiteLink('javascript:Consultar.pagina_submit(' . $this->getTagLink() . ');');

				$tpl->assign('paginacao', $this->parse());

				if ($this->getNumRows() > 1) {
					$tpl->assign('registros', array(
						'inicio' => $offset + 1,
						'fim' => $this->getNumRows() < $this->getRowsPerPage() ? $this->getNumRows() : $offset + $this->getRowsPerPage(),
						'qtd' => $this->getNumRows()
					));
				}
			}

			if (isset($this->sumColumns['columns']) && count($this->sumColumns['columns'])) {
				$somas = array();
				foreach($this->sumColumns['columns'] as $column) {
					$somas[ $column['label'] ] = call_user_func(array($this->class, $this->methodSum), $column['dbColumn'], array_merge($this->filterDefault, $this->filterByUser));
				}
				$tpl->assign('showSum', $somas);
				unset($somas);
			}
		}

		if ($this->ajax) {
			$json = new JSON;
			$json->add(array(
				'html' => $tpl->fetch(),
				'orders' => $this->order
			));
			$json->printJ();
		} else {
			return $tpl->fetch();
		}
	}

	/**
	 *	\brief Substitue variaveis dinamicas colocadas entre '[]' por valores
	 */
	private function replaceValues(&$cond, &$id, &$values) {
		$cond = str_replace('[id]', $id, $cond);
		$cond = str_replace('%5Bid%5D', $id, $cond);

		// columns values
		preg_match_all('/\%5Bcol_(.*?)\%5D/', $cond, $colsToCompare);
		foreach($colsToCompare[1] as $key) {
			$cond = str_replace('%5Bcol_' . $key . '%5D', $values['columns'][$key], $cond);
		}
		unset($colsToCompare);


		preg_match_all('/\[col_(.*?)\]/', $cond, $colsToCompare);
		foreach($colsToCompare[1] as $key) {
			$cond = str_replace('[col_' . $key . ']', $values['columns'][$key], $cond);
		}
		unset($colsToCompare);


		if (isset($values['vars'])) {
			// anothers variables
			preg_match_all('/\%5Bvar_(.*?)\%5D/', $cond, $colsToCompare);
			foreach($colsToCompare[1] as $key) {
				$cond = str_replace('%5Bvar_' . $key . '%5D', $values['vars'][$key], $cond);
			}
			unset($colsToCompare);

			// anothers variables
			preg_match_all('/\[var_(.*?)\]/', $cond, $colsToCompare);

			foreach($colsToCompare[1] as $key) {
				$cond = str_replace('[var_' . $key . ']', $values['vars'][$key], $cond);
			}
		}

		return $cond;
	}
}