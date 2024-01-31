<?php

/**
 * Classe para gerenciamento de dados de input de usuário (GET e POST).
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version 0.3.1
 */

namespace Springy\Core;

use Springy\Files\UploadedFile;
use Springy\Session;
use Springy\URI;
use Springy\Utils\ArrayUtils;

class Input
{
    // Dados contidos no GET e POST
    protected $data;
    // Dados de input antigos, guardados na sessão para consulta em um próximo request
    protected $oldData;
    // Classe helper para manipulação de arrays
    protected $arrUtils;
    // Chave identificadora dos dados de input antigos que serão guardados na sessão
    protected $oldDataSessionKey = '__OLDINPUT__';
    // Arquivos de upload enviados pelo usuário
    protected $files;

    public function __construct()
    {
        $this->arrUtils = new ArrayUtils();

        // Concatena os dados de input do GET e do POST,
        // dando prioridade ao POST em dados com a mesma chave
        $this->data = $this->sanitizeInputData($_POST) + $this->sanitizeInputData(URI::getParams());

        // Carrega os dados de dados de input que foram salvos pelo ultimo request
        $this->oldData = Session::get($this->oldDataSessionKey) or [];

        // Converte o array de dados dos arquivos enviados pelo usuário para
        // um array de objetos de Springy\Files\UploadedFile, que são bem mais fáceis de manipular
        $this->files = UploadedFile::convertPHPUploadedFiles($_FILES);

        // Reseta os dados de inputs antigos
        Session::unregister($this->oldDataSessionKey);
    }

    public function __destruct()
    {
        $this->storeForNextRequest();
    }

    /**
     * Prepara dados de input para uso seguro.
     *
     * @param array $data Dados de input serem preparados para manipulação.
     *
     * @return array
     */
    protected function sanitizeInputData($data)
    {
        $sanitizedData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->sanitizeInputData($value);
            } else {
                $value = trim($value);
            }

            $sanitizedData[$key] = $value;
        }

        return $sanitizedData;
    }

    /**
     * Retorna se a requisição atual é um POST ou não.
     *
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Retorna se a requisição atual é via AJAX ou não.
     *
     * @return bool
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Retorna se o input atual contém um arquivo de upload com o nome passado por parâmetro.
     *
     * @param string $key Chave identificadora do arquivo.
     *
     * @return bool
     */
    public function hasFile($key)
    {
        return isset($this->files[$key]);
    }

    /**
     * Retorna todos os arquivos de upload enviados pelo usuário no input atual.
     *
     * @return array
     */
    public function allFiles()
    {
        return $this->files;
    }

    /**
     * Retorna o arquivo de upload com o nome passado por parâmetro.
     *
     * @param string $key Chave identificadora do arquivo.
     *
     * @return mixed
     */
    public function file($key)
    {
        return $this->arrUtils->dottedGet($this->files, $key);
    }

    /**
     * Retorna todos os dados de input contidos no request atual.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Retorna somente os dados de input contidos no request atual equivalentes
     * às chaves identificadoras passadas por parâmetro.
     *
     * @param array $keys Chaves identificadoras dos dados.
     *
     * @return array
     */
    public function only(array $keys)
    {
        return $this->arrUtils->only($this->data, $keys);
    }

    /**
     * Retorna todos os dados de input contidos no request atual, exceto os
     * equivalentes as chaves identificadoras passadas por parâmetro.
     *
     * @param array $keys Chaves identificadoras dos dados.
     *
     * @return array
     */
    public function except(array $keys)
    {
        return $this->arrUtils->except($this->data, $keys);
    }

    /**
     * Verifica se existe um dado de input na request atual com a chave
     * identificadora passada por parâmetro.
     *
     * @param string $key Chave identificadora.
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Retorna o dado de input contido no request atual equivalente a chave
     * passada por parâmetro.
     *
     * @param string $key     Chave identificadora.
     * @param mixed  $default Valor padrão a ser retornado caso  a chave não
     *                        seja encontrada.
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->arrUtils->dottedGet($this->data, $key, $default);
    }

    /**
     * Retorna o dado de input contido na request anterior equivalente a chave
     * passada por parâmetro.
     *
     * @param string $key     Chave identificadora.
     * @param mixed  $default Valor padrão a ser retornado caso  a chave não
     *                        seja encontrada.
     *
     * @return mixed
     */
    public function old($key, $default = null)
    {
        return $this->arrUtils->dottedGet($this->oldData, $key, $default);
    }

    /**
     * Guarda na sessão os dados de input do request atual por mais um request.
     *
     * @return void
     */
    public function storeForNextRequest()
    {
        if (!empty($this->data)) {
            Session::set($this->oldDataSessionKey, $this->data);
        }
    }
}
