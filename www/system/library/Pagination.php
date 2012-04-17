<?php
/**
 *	FVAL PHP Pagination Class 2
 *
 *	What's:			[pt-br]
 *					"Pagination" é uma classe em PHP para fazer os links de navegação de página:\n
 *					"<anterior> <primeira> ... <n-3> <n-2> <n-1> N <n+1> <n+2> <n+3> ... <última> <próxima>".
 *
 *					A classe é totalmente personalizável, permitindo que o programador defina os seguintes
 *					parâmetros:
 *
 *					- Link do site;
 *					- Variável GET de definição da página;
 *					- Número de páginas navegáveis laterais a página atual;
 *					- Texto usado para o separador da primeira e últiam páginas dos navegadores de páginas;
 *					- Texto da link "Anterior";
 *					- Texto do link "Próxima";
 *					- Classes CSS dos links, página atual e separadores.
 *
 *					[en-US]
 *					Pagination is a PHP class to build pagination links in some formats.
 *
 *					It is totaly customizable. The programmer can defines the following parameters:
 *
 *					- Site link;
 *					- GET variable to define page number;
 *					- Number of pages beside current;
 *					- Separator text;
 *					- Previous text;
 *					- Next text;
 *					- CSS class of the links, current page and separators.
 *
 *	\note			[pt-br]
 *					Esta classe foi totalmente escrita no idioma Português do Brasil, inclusive seu nome,
 *					propriedades e funções. Não há interesse do autor em portar nada para outros idiomas.
 *					Interessados em traduzir a documentação e instruções para outros idiomas são bem vindos.\n
 *					Caso alguém escreva alguma documentação em outro idioma, favor avisar o autor para que
 *					o documento e seus devidos créditos sejam anexados ao projeto;
 *
 *					O autor agradece quaisquer comentários e sugestões de melhorias.
 *
 *	\link http://www.fval.com.br/
 *
 *	\version 2.2.1
 *					copyright (c) 2007-2011 FVAL - Consultoria e Informática Ltda.\n
 *					copyright (c) 2007-2011 Fernando Val
 *
 *	\brief			A single pagination class
 *
 *	\author Fernando Val <fernando.val@gmail.com>
 *
 *	\b License:		[pt-BR] : Esta classe é Open Source e distribuida sob a licença GPL.\n
 *					[en-US] : This class is Open Source and distributed under GPL license.
 *
 *	How to use:	[pt-br] : Exemplo de código PHP de como usar esta classe
 *
 *					include('PaginacaoClass.php');
 *					$Pagination = new Paginacao;
 *					$curr_page = empty($_GET['pag']) ? 1 : (int)$_GET['pag'];
 *					$sql = 'SELECT COUNT(1) FROM mytable';
 *					$res = mysql_query($sql);
 *					$reg = mysql_fetch_row($res);
 *					$rowsperpage = 10;
 *					$ultima_pag = $Pagination->CalculateNumPages((int)$reg[0], $rowsperpage);
 *					print $Pagination->Parse($curr_page, $ultima_pag);
 *
 *	@package Pagination
 */

class Pagination {
    /*
		[pt-br]  Array contendo os links das página
	*/
    private $PagesLink = array();

    /*
		[pt-br]  Define a página atual
	*/
    private $CurrentPage = 1;

    /*
		[pt-br]  Define a quantidade de páginas
	*/
    private $LastPage = 1;

    /*
		[pt-br]  Define quantas páginas serão navegáveis para os lados a partir da página atual
	*/
    private $BesidePages = 5;
	
	/*
		[pt-br]  Define a quantidade total de registros
	*/
	private $nunRows = 0;
	
	/*
		[pt-br]  Define a quantidade de registros a serem exibidos por página
	*/
	private $nunRowsPerPage = 15;

    /*
		[pt-br]  Define o hyperlink dos navegadores
	*/
    private $siteLink = '';

	/*
		[pt-br] Usado para indicar o local da página na URL ex:

		$siteLink = 'http://www.site.com/pag/busca/[page]';
		$siteLink = 'http://www.site.com/?page=pag&search=busca&page=[page]';
	*/
	private $tagLink = '[page]';

    /*
		[pt-br]  Código HTML com a navegação
	*/
    private $HTML = '';

    /*
		[pt-br]  Texto a ser mostrado como separador para primeira e última páginas
	*/
    private $SeparatorText = '...';

    /*
		[pt-br]  Classe CSS usada para a LABEL de página atual
	*/
    private $CurrentPageClass = '';
    /*
		[pt-br]  Classe CSS usada para os LINKs de navegação
	*/
    private $NavigatorClass = '';
    /*
		[pt-br]  Classe CSS usada para os LABELS dos separadores de primeira e última páginas
	*/
    private $SeparatorClass = 'separador';

    /*
		[pt-br]  Texto a ser mostrado no link para a página anterior
	*/
    private $PreviousText = '-';
    /*
		[pt-br]  Texto a ser mostrado no link para a príxima página
	*/
    private $NextText = '+';
	
	/* ********************** */
	public function __construct() {
		if (isset($_REQUEST['pag']) && is_numeric($_REQUEST['pag'])) {
			$this->setCurrentPage($_REQUEST['pag']);
		}
	}
	
	public function setTagLink($tag) {
		$this->tagLink = $tag;
	}

	public function getTagLink() {
		return $this->tagLink;
	}

	public function setSiteLink($link, $qs=array()) {
		if (is_array($link)) {
			$this->siteLink = str_replace(urlencode($this->tagLink), $this->tagLink, URI::build_url($link, array_merge($qs, array('pag' => $this->tagLink))));
		} else {
			$this->siteLink = $link;
		}
	}
	
	public function setCurrentPage($pgatual) {
		$this->CurrentPage = $pgatual;
	}
	
	public function getCurrentPage() {
		return ($this->CurrentPage == 0 ? 1 : $this->CurrentPage);
	}
	
	public function setBesidePages($BesidePages) {
		$this->BesidePages = $BesidePages;
	}
	
	public function getBesidePages() {
		return $this->BesidePages;
	}
	
	/*
		[pt-br] Seta o numero de registros
	*/
	public function setNumRows($rows) {
		$this->nunRows = $rows;
	}
	
	public function getNumRows() {
		return $this->nunRows;
	}
	
	/*
		[pt-br] Seta o numero de registros por página
	*/
	public function setRowsPerPage($qtd) {
		$this->nunRowsPerPage = $qtd;
	}
	
	public function getRowsPerPage() {
		return $this->nunRowsPerPage;
	}
	
	public function setPreviousText($txt) {
		$this->PreviousText = $txt;
	}
	
	public function setNextText($txt) {
		$this->NextText = $txt;
	}
	
	private function CalculateNumPages() {
        $this->LastPage = ceil($this->nunRows / $this->nunRowsPerPage);
    }

    public function parse() {
		$this->CalculateNumPages();

		$this->PagesLink['pages'] = array();
        $this->PagesLink['currpage'] = $this->CurrentPage;
        $this->PagesLink['IndTotal'] = $this->LastPage;
        $this->PagesLink['currpageF'] = number_format($this->CurrentPage, 0);
        $this->PagesLink['IndTotF'] = number_format($this->LastPage, 0);

        /*
			[pt-br]  Verifica se tem navagação pra página anterior
		*/
		$this->PagesLink['previous'] = ($this->CurrentPage > 1 ? str_replace($this->tagLink, ($this->CurrentPage - 1), $this->siteLink) : '');
		
        /*
			[pt-br]  Verifica se mostra navegador para primeira página
		*/
        if (($this->CurrentPage - $this->BesidePages > 0) && ($this->LastPage > ($this->BesidePages * 2 + 2))) {
            $this->PagesLink['first'] = str_replace($this->tagLink, 1, $this->siteLink);
            $dec = $this->BesidePages;
        } else {
            $this->PagesLink['first'] = '';
            $dec = $this->CurrentPage - 1;
			// if ($dec <= 1) {
				// while ($this->CurrentPage - $dec < 1) {
					// $dec--;
				// }
			// }
        }

        /*
			[pt-br]  Verifica se mostra navegador para última página
		*/
        if (($this->CurrentPage + $this->BesidePages < $this->LastPage) && ($this->LastPage > ($this->BesidePages * 2 + 2))) {
            $this->PagesLink['last'] = str_replace($this->tagLink, $this->LastPage, $this->siteLink);
            $inc = $this->BesidePages;
        } else {
            $this->PagesLink['last'] = '';
            $inc = $this->LastPage - $this->CurrentPage;
        }

        /*
			[pt-br]  Se houverem menos páginas anteriores que o definido, tenta colocar mais páginas para a frente
		*/
        if ($dec < $this->BesidePages) {
            $x = $this->BesidePages - $dec;
            while ($this->CurrentPage + $inc < $this->LastPage && $x > 0) {
                $inc++;
                $x--;
            }
        }
        /*
			[pt-br]  Se houverem menos páginas seguintes que o definido, tenta colocar mais páginas para trás
		*/
        if ($inc < $this->BesidePages) {
            $x = $this->BesidePages - $inc;
            while ($this->CurrentPage - $dec > 1 && $x > 0) {
                $dec++;
                $x--;
            }
        }

        /*
			[pt-br]  Monta o conteúdo central do navegador
		*/
        for ($x = $this->CurrentPage - $dec; $x <= $this->CurrentPage + $inc; $x++) {
			if ($x == 0) {
				continue;
			}
			
            $this->PagesLink['pages'][$x] = ($x == $this->CurrentPage) ? '' : str_replace($this->tagLink, $x, $this->siteLink);
        }

        /*
			[pt-br]  Verifica se mostra navegador para próxima página
		*/
        if ($this->CurrentPage < $this->LastPage) {
            $this->PagesLink['next'] = str_replace($this->tagLink, ($this->CurrentPage + 1), $this->siteLink);
        } else {
            $this->PagesLink['next'] = '';
        }
		
        return $this->PagesLink;
    }

    public function makeHtml() {
        $this->Parse();
		
		if (count($this->PagesLink['pages']) == 1) {
			return;
		}
		
        $separator = '<span ' . ($this->SeparatorClass ? 'class="'.$this->SeparatorClass.'"' : '') . '>'.$this->SeparatorText.'</span>';
        $previous  = empty($this->PagesLink['previous'])  ? '' : '<a href="'.$this->PagesLink['previous'].'" class="'.$this->NavigatorClass.'">'.$this->PreviousText.'</a> ';
        $next      = empty($this->PagesLink['next'])  ? '' : '<a href="'.$this->PagesLink['next'].'" class="'.$this->NavigatorClass.'">'.$this->NextText.'</a>';
        $first     = empty($this->PagesLink['first']) ? '' : '<a href="'.$this->PagesLink['first'].'" class="'.$this->NavigatorClass.'">1</a>';
        $last      = empty($this->PagesLink['last'])  ? '' : '<a href="'.$this->PagesLink['last'].'" class="'.$this->NavigatorClass.'">'.$this->LastPage.'</a>';
        $pages     = array();
		
        foreach($this->PagesLink['pages'] as $Page => $Link) {
            $pages[] = empty($Link) ? '<span ' . ($this->CurrentPageClass ? 'class="'.$this->CurrentPageClass.'"' : '') . '>'.$Page.'</span>' : '<a href="'.$Link.'" class="'.$this->NavigatorClass.'">'.$Page.'</a> ';
        }

        $middle = '';
        foreach($pages as $Page) {
            $middle .= ' '.$Page;
        }

        $this->HTML = str_replace('  ', ' ', trim($previous.' '.$first.(!empty($first)?' '.$separator:'').' '.trim($middle).' '.(!empty($last)?' '.$separator:'').$last.' '.$next));
        return $this->HTML;
    }

    public function show($pgatual = 0, $pgfim = 0) {
        echo $this->MakeHTML($pgatual, $pgfim);
    }
}