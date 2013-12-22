<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *	\copyright Copyright (c) 2007-2013 Fernando Val
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe para pegar arquivos de toda uma árvore de diretórios
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.2.2
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 *
 *	\b Exemplos:
 *
 *	\code
 *	$dir = new DeepDir();
 *	$dir->setDir('..');
 *	$dir->load();
 *	foreach($dir->getFiles() as $pathToFile) {
 *		echo $pathToFile."\n";
 *	}
 *
 *	// or
 *
 *	$dir = new DeepDir('..');
 *	$dir->load();
 *	foreach($dir->getFiles() as $pathToFile) {
 *		echo $pathToFile."\n";
 *	}
 *	\endcode
 *
 *	Esta classe foi baseada no excelente trabalho de Ilya Nemihin.\n
 *	Conversão para PHP 5, melhorias, documentação e adaptação por Fernando Val.
 *
 *	\author (c) Ilya Nemihin
 *
 *	Para baixar a classe original use o seguinte endereço:
 *	http://www.phpclasses.org/browse.html/package/1025.html
 */

namespace FW;

/**
 *  \brief Classe para pegar arquivos de toda uma árvore de diretórios
 *	\author (c) Ilya Nemihin
 */
class DeepDir extends Kernel {
	private $dir   = NULL;
	private $files = NULL;
	private $error = NULL;

	/**
	 *	\brief Método construtor
	 */
	public function __construct($dir='.') {
		$this->dir = $dir;
		$this->files = array();
		$this->dirFILO = new FILO;
	}

	/**
	 *	\brief Define o diretório
	 */
	public function setDir($dir) {
		$this->dir = $dir;
		$this->files = array();
		$this->error = false;
		$this->dirFILO->zero();
		$this->dirFILO->push( $this->dir );
	}

	/**
	 *	\brief Pega o último erro encontrado
	 */
	public function getError() {
		return $this->error;
	}
	
	/**
	 *	\brief Pega os arquivos encontrados
	 *	\return Retorna um \c array contendo a árvore de diretórios
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 *	\brief Carrega a árvore de diretórios
	 */
	public function load() {
		while ($this->curDir = $this->dirFILO->pop()) {
			$this->loadFromCurDir();
		}
	}

	/**
	 *	\brief Carrega a relação de arquivos e diretórios do diretório corrente
	 */
	private function loadFromCurDir() {
		if ($handle = @opendir($this->curDir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file == "." || $file == "..") continue;
				$filePath = $this->curDir . '/' . $file;
				$fileType = filetype($filePath);
				if ($fileType == 'dir') {
					$this->dirFILO->push($filePath);
					continue;
				}
				$this->files[] = $filePath;
			}
			closedir($handle);
		}
		else {
			$this->error = 'error open dir "'.$this->curDir.'"';
			return false;
		}
	}

}
