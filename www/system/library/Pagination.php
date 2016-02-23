<?php
/**	\file
 *	Springy.
 *
 *	\brief		A single pagination class.
 *	\copyright	Copyright (c) 2007-2016 Fernando Val
 *	\author		Fernando Val <fernando.val@gmail.com>
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\link		http://www.fval.com.br/
 *	\version	2.3.7
 *	\ingroup	framework
 */
namespace Springy;

/**
 *  \brief		A single pagination class.
 *
 *	\b License:		[pt-BR] : Esta classe é Open Source e distribuida sob a licença GPL.\n
 *					[en-US] : This class is Open Source and distributed under GPL license.
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
 */
class Pagination
{
    /// Array contendo os links das página / page link array
    private $PagesLink = [];
    /// Define a página atual / current page number
    private $CurrentPage = 1;
    /// Define a quantidade de páginas / total page number
    private $LastPage = 1;
    /// Define quantas páginas serão navegáveis para os lados a partir da página atual / number of sided pages
    private $BesidePages = 5;
    /// Define a quantidade total de registros / total lines
    private $nunRows = 0;
    /// Define a quantidade de registros a serem exibidos por página / lines per page
    private $nunRowsPerPage = 15;
    /// Define o hyperlink dos navegadores / site link
    private $siteLink = '';
    /// Usado para indicar o local da página na URL ex: / page number macro
    /// $siteLink = 'http://www.site.com/pag/busca/[page]';
    /// $siteLink = 'http://www.site.com/?page=pag&search=busca&page=[page]';
    private $tagLink = '[page]';
    /// Código HTML com a navegação / HTML code
    private $HTML = '';
    /// Texto a ser mostrado como separador para primeira e última páginas / first/last page separator
    private $SeparatorText = '...';
    /// Classe CSS usada para a LABEL de página atual / current page class style
    private $CurrentPageClass = 'active';
    /// Classe CSS usada para os LINKs de navegação / page link class style
    private $NavigatorClass = '';
    /// Classe CSS usada para os LABELS dos separadores de primeira e última páginas / sepatator class style
    private $SeparatorClass = 'separador';
    /// Texto a ser mostrado no link para a página anterior / previous page text
    private $PreviousText = '&laquo;';
    /// Texto a ser mostrado no link para a príxima página / next page text
    private $NextText = '&raquo;';

    /**
     *  \brief Constructor.
     */
    public function __construct()
    {
        if (isset($_REQUEST['pag']) && is_numeric($_REQUEST['pag'])) {
            $this->setCurrentPage($_REQUEST['pag']);
        }
    }

    /**
     *  \brief Set tag link.
     */
    public function setTagLink($tag)
    {
        $this->tagLink = $tag;
    }

    /**
     *  \brief Get tag link.
     */
    public function getTagLink()
    {
        return $this->tagLink;
    }

    /**
     *  \brief Set site link.
     */
    public function setSiteLink($link, $qs = [])
    {
        if (is_array($link)) {
            if (isset($_SERVER['HTTPS'])) {
                $this->siteLink = str_replace(urlencode($this->tagLink), $this->tagLink, URI::buildURL($link, array_merge($qs, ['pag' => $this->tagLink]), true, 'secure'));
            } else {
                $this->siteLink = str_replace(urlencode($this->tagLink), $this->tagLink, URI::buildURL($link, array_merge($qs, ['pag' => $this->tagLink])));
            }
        } else {
            $this->siteLink = $link;
        }
    }

    /**
     *  \brief Set current page.
     */
    public function setCurrentPage($pgatual)
    {
        $this->CurrentPage = $pgatual;
    }

    /**
     *  \brief Get current page.
     */
    public function getCurrentPage()
    {
        return $this->CurrentPage == 0 ? 1 : $this->CurrentPage;
    }

    /**
     *  \brief Set number of pages beside current page.
     */
    public function setBesidePages($BesidePages)
    {
        $this->BesidePages = $BesidePages;
    }

    /**
     *  \brief Get number os beside pages.
     */
    public function getBesidePages()
    {
        return $this->BesidePages;
    }

    /**
     *  \brief Set total line number.
     */
    public function setNumRows($rows)
    {
        $this->nunRows = $rows;
    }

    /**
     *  \brief Get total line number.
     */
    public function getNumRows()
    {
        return $this->nunRows;
    }

    /**
     *  \brief Set number of lines per page.
     */
    public function setRowsPerPage($qtd)
    {
        $this->nunRowsPerPage = $qtd;
    }

    /**
     * \brief Get number of lines per page.
     */
    public function getRowsPerPage()
    {
        return $this->nunRowsPerPage;
    }

    /**
     *  \brief Set previous string text.
     */
    public function setPreviousText($txt)
    {
        $this->PreviousText = $txt;
    }

    /**
     *  \brief Set next string text.
     */
    public function setNextText($txt)
    {
        $this->NextText = $txt;
    }

    /**
     *  \brief Calculate total page number.
     */
    private function calculateNumPages()
    {
        $this->LastPage = ceil($this->nunRows / $this->nunRowsPerPage);
    }

    /**
     *  \brief Parses the pagination.
     */
    public function parse()
    {
        $this->calculateNumPages();

        $this->PagesLink['pages'] = [];
        $this->PagesLink['currpage'] = $this->CurrentPage;
        $this->PagesLink['IndTotal'] = $this->LastPage;
        $this->PagesLink['currpageF'] = number_format($this->CurrentPage, 0);
        $this->PagesLink['IndTotF'] = number_format($this->LastPage, 0);

        // Verifica se tem navagação pra página anterior
        $this->PagesLink['previous'] = ($this->CurrentPage > 1 ? str_replace($this->tagLink, ($this->CurrentPage - 1), $this->siteLink) : '');

        // Verifica se mostra navegador para primeira página
        if (($this->CurrentPage - $this->BesidePages > 0) && ($this->LastPage > ($this->BesidePages * 2 + 1))) {
            $this->PagesLink['first'] = str_replace($this->tagLink, 1, $this->siteLink);
            $dec = $this->BesidePages;
        } else {
            $this->PagesLink['first'] = '';
            $dec = $this->CurrentPage - 1;
        }

        // Verifica se mostra navegador para última página
        if (($this->CurrentPage + $this->BesidePages < $this->LastPage) && ($this->LastPage > ($this->BesidePages * 2 + 1))) {
            $this->PagesLink['last'] = str_replace($this->tagLink, $this->LastPage, $this->siteLink);
            $inc = $this->BesidePages;
        } else {
            $this->PagesLink['last'] = '';
            $inc = $this->LastPage - $this->CurrentPage;
        }

        // Se houverem menos páginas anteriores que o definido, tenta colocar mais páginas para a frente
        if ($dec < $this->BesidePages) {
            $x = $this->BesidePages - $dec;
            while ($this->CurrentPage + $inc < $this->LastPage && $x > 0) {
                $inc++;
                $x--;
            }
        }
        // Se houverem menos páginas seguintes que o definido, tenta colocar mais páginas para trás
        if ($inc < $this->BesidePages) {
            $x = $this->BesidePages - $inc;
            while ($this->CurrentPage - $dec > 1 && $x > 0) {
                $dec++;
                $x--;
            }
        }

        // Monta o conteúdo central do navegador
        for ($x = $this->CurrentPage - $dec; $x <= $this->CurrentPage + $inc; $x++) {
            if ($x == 0) {
                continue;
            }

            $this->PagesLink['pages'][$x] = ($x == $this->CurrentPage) ? '' : str_replace($this->tagLink, $x, $this->siteLink);
        }

        // Verifica se mostra navegador para próxima página
        if ($this->CurrentPage < $this->LastPage) {
            $this->PagesLink['next'] = str_replace($this->tagLink, ($this->CurrentPage + 1), $this->siteLink);
        } else {
            $this->PagesLink['next'] = '';
        }

        return $this->PagesLink;
    }

    /**
     *  \brief Make HTML format of pagination.
     */
    public function makeHtml()
    {
        $this->parse();

        if (count($this->PagesLink['pages']) == 1) {
            return;
        }

        $separator = '<li '.($this->SeparatorClass ? 'class="disabled '.$this->SeparatorClass.'"' : '').'><a href="#">'.$this->SeparatorText.'</a></li>';
        $previous = empty($this->PagesLink['previous'])  ? '' : '<li><a href="'.$this->PagesLink['previous'].'" class="'.$this->NavigatorClass.'">'.$this->PreviousText.'</a></li> ';
        $next = empty($this->PagesLink['next'])  ? '' : '<li><a href="'.$this->PagesLink['next'].'" class="'.$this->NavigatorClass.'">'.$this->NextText.'</a></li>';
        $first = empty($this->PagesLink['first']) ? '' : '<li><a href="'.$this->PagesLink['first'].'" class="'.$this->NavigatorClass.'">1</a></li>';
        $last = empty($this->PagesLink['last'])  ? '' : '<li><a href="'.$this->PagesLink['last'].'" class="'.$this->NavigatorClass.'">'.$this->LastPage.'</a></li>';
        $middle = '';

        foreach ($this->PagesLink['pages'] as $Page => $Link) {
            $middle .= empty($Link) ? '<li '.($this->CurrentPageClass ? 'class="'.$this->CurrentPageClass.'"' : '').'><a href="#">'.$Page.'</a></li>' : '<li><a href="'.$Link.'" class="'.$this->NavigatorClass.'">'.$Page.'</a></li>';
        }

        $this->HTML = '<ul class="pagination">'.$previous.$first.(!empty($first) ? $separator : '').$middle.(!empty($last) ? $separator : '').$last.$next.'</ul>';

        return $this->HTML;
    }

    /**
     *  \brief Print the HTML format.
     */
    public function show()
    {
        echo $this->makeHTML();
    }
}
