<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Pagination Class

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val

	What's:			[pt-br]
					"Pagination" é uma classe em PHP para fazer os links de navegação de página:
					"Anterior <primeira> ... <n-3> <n-2> <n-1> N <n+1> <n+2> <n+3> ... <ultima> Próxima".

					A classe é totalmente personalizável, permitindo que o programador defina os seguintes
					parâmetros:

					- Link do site;
					- Variável GET de definição da página;
					- Número de páginas navegáveis laterais a página atual;
					- Texto usado para o separador da primeira e últiam páginas dos navegadores de páginas;
					- Texto da link "Anterior";
					- Texto do link "Próxima";
					- Classes CSS dos links, página atual e separadores.

					[en-US]
					Pagination is a PHP class to build pagination links in some formats.

					It is totaly customizable. The programmer can defines the following parameters:

					- Site link;
					- GET variable to define page number;
					- Number of pages beside current;
					- Separator text;
					- Previous text;
					- Next text;
					- CSS class of the links, current page and separators.

	Observations:	[pt-br]
					Esta classe foi totalmente escrita no idioma Português do Brasil, inclusive seu nome,
					propriedades e funções. Não há interesse do autor em portar nada para outros idiomas.
					Interessados em traduzir a documentação e instruções para outros idiomas são bem vindos.
					Caso alguém escreva alguma documentação em outro idioma, favor avisar o autor para que
					o documento e seus devidos créditos sejam anexados ao projeto;

					O autor agradece quaisquer comentários e sugestões de melhorias.

	License:		[pt-BR] : Esta classe é Open Source e distribuida sob a licença GPL.
					[en-US] : This class is Open Source and distributed under GPL license.

	Version:		2.0

	Developed by:	Fernando Val <fernando.val@gmail.com>

	How to use:		[pt-br]
					Exemplo de código PHP de como usar esta classe

					include('PaginacaoClass.php');
					$Pagination = new Paginacao;
					$curr_page = empty($_GET['pag']) ? 1 : (int)$_GET['pag'];
					$sql = 'SELECT COUNT(1) FROM mytable';
					$res = mysql_query($sql);
					$reg = mysql_fetch_row($res);
					$rowsperpage = 10;
					$ultima_pag = $Pagination->CalculateNumPages((int)$reg[0], $rowsperpage);
					print $Pagination->Parse($curr_page, $ultima_pag);
	------------------------------------------------------------------------------------ --- -- - */

class Pagination {
    /*
		[pt-br]  Indices dos array da classe
	*/
    private $IndFirst = 'first';
    private $IndLast  = 'last';
    private $IndPrev  = 'previous';
    private $IndNext  = 'next';
    private $IndPages = 'pages';
    private $IndTotal = 'lastpage';
    private $IndCurr  = 'currpage';
    private $IndTotF  = 'lastpage_fmt';
    private $IndCurrF = 'currpage_fmt';

    /*
		[pt-br]  Array contendo os links das página
	*/
    private $PagesLink = array();

    /*
		[pt-br]  Define a página atual
	*/
    private $CurrentPage = false;
    private $PaginaAtual = 1;

    /*
		[pt-br]  Define a quantidade de páginas
	*/
    private $LastPage = false;
    private $UltimaPagina = 1;

    /*
		[pt-br]  Define quantas páginas serão navegáveis para os lados a partir da página atual
	*/
    private $BesidePages = false;
    private $NumPgLaterais = 5;

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
		[pt-br]  Define a variável do GET que receberá o número da página para navegar
	*/
    public $PageGET = 'pag';

    /*
		[pt-br]  Código HTML com a navegação
	*/
    private $HTML = '';
    private $HTMLPaginacao = '';

    /*
		[pt-br]  Texto a ser mostrado como separador para primeira e última páginas
	*/
    private $SeparatorText = false;
    private $TextoSeparador = '...';

    /*
		[pt-br]  Classe CSS usada para a LABEL de página atual
	*/
    private $CurrentPageClass = false;
    private $ClassePaginaAtual = 'paginacao_atual';
    /*
		[pt-br]  Classe CSS usada para os LINKs de navegação
	*/
    private $NavigatorClass = false;
    private $ClasseNavegadores = 'paginacao_navegar';
    /*
		[pt-br]  Classe CSS usada para os LABELS dos separadores de primeira e última páginas
	*/
    private $SeparatorClass = false;
    private $ClasseSeparadores = 'paginacao_atual';

    /*
		[pt-br]  Texto a ser mostrado no link para a página anterior
	*/
    private $PreviousText = false;
    private $TextoAnterior = 'Anterior';
    /*
		[pt-br]  Texto a ser mostrado no link para a príxima página
	*/
    private $NextText = false;
    private $TextoProxima = 'Pr&oacute;xima';

    public function CalculateNumPages($numrows, $rowsperpage) {
        return $this->LastPage = ceil($numrows / $rowsperpage);
    }

	public function setTagLink($tag) {
		$this->tagLink = $tag;
	}

	public function getTagLink() {
		return $this->tagLink;
	}

	public function setSiteLink($link) {
		$this->siteLink = $link;
	}

    public function parse($pgatual = 0, $pgfim = 0) {
        /*
			[pt-br] Converte os parâmetros antigos para os novos
		*/
        if ($this->CurrentPage === false) {
            $this->CurrentPage = $this->PaginaAtual;
		}

        if ($this->LastPage === false) {
            $this->LastPage = $this->UltimaPagina;
		}

        if ($this->BesidePages === false) {
            $this->BesidePages = $this->NumPgLaterais;
		}

        if ($this->SeparatorText === false) {
            $this->SeparatorText = $this->TextoSeparador;
		}

        if ($this->CurrentPageClass === false) {
            $this->CurrentPageClass = $this->ClassePaginaAtual;
		}

        if ($this->NavigatorClass === false) {
            $this->NavigatorClass = $this->ClasseNavegadores;
		}

        if ($this->SeparatorClass === false) {
            $this->SeparatorClass = $this->ClasseSeparadores;
		}

        if ($this->PreviousText === false) {
            $this->PreviousText = $this->TextoAnterior;
		}

        if ($this->NextText === false) {
            $this->NextText = $this->TextoProxima;
		}

        $this->PagesLink = array();

        /*
			[pt-br]  Verifica se a página atual e/ou a última foram passadas por parâmetro na chamada
		*/
        if ($pgatual) {
            $this->CurrentPage = $pgatual;
		}

        if ($pgfim) {
            $this->LastPage = $pgfim;
		}

        $this->PagesLink[$this->IndCurr] = $this->CurrentPage;
        $this->PagesLink[$this->IndTotal] = $this->LastPage;
        $this->PagesLink[$this->IndCurrF] = number_format($this->CurrentPage, 0);
        $this->PagesLink[$this->IndTotF] = number_format($this->LastPage, 0);

        /*
			[pt-br]  Verifica se tem navagação pra página anterior
		*/
        $this->PagesLink[$this->IndPrev] = ($this->CurrentPage > 1 ? str_replace($this->tagLink, ($this->CurrentPage - 1), $this->siteLink) : '');

        /*
			[pt-br]  Verifica se mostra navegador para primeira página
		*/
        if (($this->CurrentPage - ($this->BesidePages + 1) > 1) && ($this->LastPage > ($this->BesidePages * 2 + 2))) {
            $this->PagesLink[$this->IndFirst] = str_replace($this->tagLink, 1, $this->siteLink);
            $dec = $this->BesidePages;
        } else {
            $this->PagesLink[$this->IndFirst] = '';
            $dec = $this->CurrentPage;
            while ($this->CurrentPage - $dec < 1) {
                $dec--;
            }
        }

        /*
			[pt-br]  Verifica se mostra navegador para última página
		*/
        if (($this->CurrentPage + ($this->BesidePages + 1) < $this->LastPage) && ($this->LastPage > ($this->BesidePages * 2 + 2))) {
            $this->PagesLink[$this->IndLast] = str_replace($this->tagLink, $this->LastPage, $this->siteLink);
            $inc = $this->BesidePages;
        } else {
            $this->PagesLink[$this->IndLast] = '';
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
        $Pages = array();
        for ($x = $this->CurrentPage - $dec; $x <= $this->CurrentPage + $inc; $x++) {
            $Pages[$x] = ($x == $this->CurrentPage) ? '' : str_replace($this->tagLink, $x, $this->siteLink);
        }
        $this->PagesLink[$this->IndPages] = $Pages;

        /*
			[pt-br]  Verifica se mostra navegador para próxima página
		*/
        if ($this->CurrentPage < $this->LastPage) {
            $this->PagesLink[$this->IndNext] = str_replace($this->tagLink, ($this->CurrentPage + 1), $this->siteLink);
        } else {
            $this->PagesLink[$this->IndNext] = '';
        }
        return $this->PagesLink;
    }

    public function makeHtml($pgatual = 0, $pgfim = 0) {
        $this->Parse($pgatual, $pgfim);

        $separator = '<label class="'.$this->SeparatorClass.'">'.$this->SeparatorText.'</label>';
        $previous  = empty($this->PagesLink[$this->IndPrev])  ? '' : '<a href="'.$this->PagesLink[$this->IndPrev].'" class="'.$this->NavigatorClass.'">'.$this->PreviousText.'</a> ';
        $next      = empty($this->PagesLink[$this->IndNext])  ? '' : '<a href="'.$this->PagesLink[$this->IndNext].'" class="'.$this->NavigatorClass.'">'.$this->NextText.'</a>';
        $first     = empty($this->PagesLink[$this->IndFirst]) ? '' : '<a href="'.$this->PagesLink[$this->IndFirst].'" class="'.$this->NavigatorClass.'">1</a>';
        $last      = empty($this->PagesLink[$this->IndLast])  ? '' : '<a href="'.$this->PagesLink[$this->IndLast].'" class="'.$this->NavigatorClass.'">'.$this->LastPage.'</a>';
        $pages     = array();
        foreach($this->PagesLink[$this->IndPages] as $Page => $Link) {
            $pages[] = empty($Link) ? '<label class="'.$this->CurrentPageClass.'">'.$Page.'</label>' : '<a href="'.$Link.'" class="'.$this->NavigatorClass.'">'.$Page.'</a> ';
        }

        $middle = '';
        foreach($pages as $Page) {
            $middle .= ' '.$Page;
        }

        $this->HTML = str_replace('  ', ' ', trim($previous.' '.$first.(!empty($first)?' '.$separator:'').' '.trim($middle).' '.(!empty($last)?' '.$separator:'').$last.' '.$next));
        return $this->HTML;
    }

    public function show($pgatual = 0, $pgfim = 0) {
        return $this->MakeHTML($pgatual, $pgfim);
    }
}
?>