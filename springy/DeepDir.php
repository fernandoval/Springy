<?php
/** \file
 *  Springy.
 *
 *  \brief     Classe para pegar arquivos de toda uma árvore de diretórios.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.3.5
 *  \ingroup    framework
 *
 *  \b Exemplos:
 *
 *  \code
 *  $dir = new DeepDir();
 *  $dir->setDir('..');
 *  $dir->load();
 *  foreach($dir->getFiles() as $pathToFile) {
 *  	echo $pathToFile."\n";
 *  }
 *
 *  // or
 *
 *  $dir = new DeepDir('..');
 *  $dir->load();
 *  foreach($dir->getFiles() as $pathToFile) {
 *  	echo $pathToFile."\n";
 *  }
 *  \endcode
 *
 *  Esta classe foi baseada no excelente trabalho de Ilya Nemihin.\n
 *  Conversão para PHP 5, melhorias, documentação e adaptação por Fernando Val.
 *
 *  \author (c) Ilya Nemihin
 *
 *  Para baixar a classe original use o seguinte endereço:
 *  http://www.phpclasses.org/browse.html/package/1025.html
 */

namespace Springy;

/**
 *  \brief  Classe para pegar arquivos de toda uma árvore de diretórios.
 *	\author (c) Ilya Nemihin
 *	\author Fernando Val - fernando.val@gmail.com.
 */
class DeepDir
{
    private $dir = null;
    private $files = null;
    private $error = null;
    private $dirFILO = null;

    /**
     *	\brief Método construtor.
     */
    public function __construct($dir = '.')
    {
        $this->dirFILO = [];
        $this->setDir($dir);
    }

    /**
     *	\brief Define o diretório.
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
        $this->files = [];
        $this->error = false;
        $this->dirFILO = [];
        array_push($this->dirFILO, $this->dir);
    }

    /**
     *	\brief Pega o último erro encontrado.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *	\brief Pega os arquivos encontrados
     *	\return Retorna um \c array contendo a árvore de diretórios.
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     *	\brief Carrega a árvore de diretórios.
     */
    public function load()
    {
        while ($this->curDir = array_pop($this->dirFILO)) {
            $this->loadFromCurDir();
        }
    }

    /**
     *	\brief Carrega a relação de arquivos e diretórios do diretório corrente.
     */
    private function loadFromCurDir()
    {
        if ($handle = @opendir($this->curDir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $filePath = $this->curDir . '/' . $file;
                $fileType = filetype($filePath);
                if ($fileType == 'dir') {
                    array_push($this->dirFILO, $filePath);
                    continue;
                }
                $this->files[] = $filePath;
            }
            closedir($handle);
        } else {
            $this->error = 'error open dir "' . $this->curDir . '"';

            return false;
        }
    }
}
