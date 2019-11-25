<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe para manipulação de arquivos do sistema de arquivos.
 *
 *  \copyright  Copyright (c) 2007-2018 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.0.1
 *  \ingroup    framework
 */

namespace Springy\Files;

use finfo;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

/**
 * \brief Classe para manipulação de arquivos do sistema de arquivos.
 */
class File extends SplFileInfo
{
    /**
     * \brief Construtor da classe.
     *
     * \param [in] (string) $filename - Caminho do arquivo
     * \param [in} (bool) $checkFile - Indicador se o arquivo deve ser checado
     *
     * \throws InvalidArgumentException.
     */
    public function __construct($filename, $checkFile = true)
    {
        if ($checkFile && !is_file($filename)) {
            throw new InvalidArgumentException();
        }

        parent::__construct($filename);
    }

    /**
     * \brief Retorna a extensão do arquivo.
     *
     * \return (string).
     */
    public function getExtension()
    {
        return pathinfo($this->getBasename(), PATHINFO_EXTENSION);
    }

    /**
     * \brief Retorna o mimeType do arquivo.
     *
     * \return (string).
     */
    public function getMimeType()
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($this->getPathname());
    }

    /**
     * \brief Move o arquivo para outro diretório e retorna um objeto representando-o.
     *
     * \param [in] (string) $directory - Caminho do diretório para mover
     * \param [in] (string) $name - Novo nome do arquivo
     *
     * \return (Springy\Files\File)
     *
     * \throws RuntimeException.
     */
    public function moveTo($directory, $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@rename($this->getPathname(), $target)) {
            $error = error_get_last();

            throw new RuntimeException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
        }

        @chmod($target, 0666 & ~umask());

        return $target;
    }

    /**
     * \brief Cria um objeto reresentando que será movido no futuro.
     *
     * \param [in] (string) $directory - Camino do diretório para mover
     * \param [in] (string) $name - Novo nome do arquivo
     *
     * \return Springy\Files\File
     *
     * \throws RuntimeException.
     */
    protected function getTargetFile($directory, $name = null)
    {
        if (!is_dir($directory) && (@mkdir($directory, 0777, true) === false)) {
            throw new RuntimeException(sprintf('Unable to create the "%s" directory', $directory));
        } elseif (!is_writable($directory)) {
            throw new RuntimeException(sprintf('Unable to write in the "%s" directory', $directory));
        }

        $target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . (null === $name ? $this->getBasename() : $this->getName($name));

        return new self($target, false);
    }

    /**
     * \brief Retorna o nome real do arquivo.
     *
     * \param [in] (string) $name
     *
     * \return (string).
     */
    protected function getName($name)
    {
        $originalName = str_replace('\\', '/', $name);
        $pos = strpos($originalName, '/');
        $originalName = $pos === false ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }
}
