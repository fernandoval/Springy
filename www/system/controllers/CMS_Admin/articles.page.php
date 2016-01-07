<?php
/*  ------------------------------------------------------------------------------------ --- -- -
    FVAL PHP Framework for Web Sites

    Copyright (c) 2007-2009 FVAL - Consultoria e Informática Ltda.
    Copyright (C) 2009 Fernando Val

    http://www.fval.com.br

    Developer team:
        Fernando Val  - fernando.val@gmail.com

    Framework version:
        1.0.0

    Script version:
        0.1.3

    This script:
        Script de administração de artigos
    ------------------------------------------------------------------------------------ --- -- - */

class Articles_Controller
{
    public function __construct()
    {
        if (!FW\CMS::logged_in_user()) {
            FW\URI::redirect('/'.FW\URI::relativePathPage().'/login');

            return false;
        }

        FW\Template::start();
    }

    public function _default()
    {
        if (($pg = FW\URI::getSegment(0, true)) && is_numeric($pg)) {
            $pg = (int) FW\URI::getSegment(0, true);
            if ($pg < 1) {
                $pg = 1;
            }
        } else {
            $pg = 1;
        }

        if ($articles = FW\CMS::getArticlesByCategory(null, false, 'ASC', ($pg - 1) * 20, 20, $qtde)) {
            FW\Template::assign('CMS_ArticleList', $articles);

            $pagination = new Pagination();
            $pagination->setSiteLink('/'.FW\URI::relativePathPage().'/articles/[page]');
            $pagination->CalculateNumPages($qtde, 20);
            FW\Template::assign('Pagination', $pagination->parse($pg));
        }
    }

    public function edit()
    {
        if (($id = FW\URI::getSegment(1, true)) && is_numeric($id) && ($article = FW\CMS::getArticleById($id, false))) {
            if (!empty($_POST)) {
                if (empty($_POST['published'])) {
                    $_POST['published'] = 0;
                }
                if (FW\CMS::updateArticle($id, $_POST, $error) !== false) {
                    FW\URI::redirect('/'.FW\URI::relativePathPage().'/articles');

                    return false;
                }
                die('error: '.$error);
            }
            FW\Template::assign('CMS_Article', $article);
            FW\Template::assign('CMS_Categories', FW\CMS::getCategories(true, 'title', 'ASC', 0, 0));
        } else {
            FW\Errors::displayError(404, 'Not found');
        }
    }

    public function add()
    {
        if (!empty($_POST)) {
            if (FW\CMS::insertArticle($_POST, $error) !== false) {
                FW\URI::redirect('/'.FW\URI::relativePathPage().'/articles');

                return false;
            }
            die('error: '.$error);
        } else {
            $article = ['article_id' => 0, 'published' => '1'];
        }
        FW\Template::assign('CMS_Article', $article);
        FW\Template::assign('CMS_Categories', FW\CMS::getCategories(true, 'title', 'ASC', 0, 0));
    }

    public function delete()
    {
        if (($id = FW\URI::getSegment(1, true)) && is_numeric($id) && ($article = FW\CMS::getArticleById($id, false))) {
            if (FW\CMS::deleteArticle($id, $error) !== false) {
                FW\URI::redirect('/'.FW\URI::relativePathPage().'/articles');

                return false;
            }

            $this->_default();
        } else {
            FW\Errors::displayError(404, 'Not found');
        }
    }
}
