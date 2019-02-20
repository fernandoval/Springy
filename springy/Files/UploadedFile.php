<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe para manipulação de arquivos que foram criados por upload no sistema de arquivos.
 *
 *  \copyright  Copyright (c) 2007-2018 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.2.3
 *  \ingroup    framework
 */

namespace Springy\Files;

use RuntimeException;

/**
 * \brief Classe para manipulação de arquivos que foram criados por upload no sistema de arquivos.
 */
class UploadedFile extends File
{
    /// Nome original do arquivo
    protected $originalName;
    /// MimeType do arquivo trazido pelos dados do upload PHP
    protected $mimeType;
    /// Tamanho do arquivo trazido pelos dados do upload PHP
    protected $size;
    /// Código do erro de upload, se houve algum
    protected $error;

    /**
     * \brief Construtor da classe.
     *
     * \param [in] (string) $filename - Caminho atual do arquivo
     * \param [in] (string) $originalName - Nome original do arquivo
     * \param [in] (string) $mimeType - MimeType do arquivo
     * \param [in] (integer) $size - Tamanho do arquivo em bytes
     * \param [in] (integer) $error - Código do erro
     *
     * \throws RuntimeException.
     */
    public function __construct($filename, $originalName, $mimeType = null, $size = null, $error = null)
    {
        if (!ini_get('file_uploads')) {
            throw new RuntimeException(sprintf('Unable to create UploadedFile because "file_uploads" is disabled in your php.ini file (%s)', get_cfg_var('cfg_file_path')));
        }

        $this->originalName = $this->getName($originalName);
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->size = $size;
        $this->error = $error ?: UPLOAD_ERR_OK;

        parent::__construct($filename, UPLOAD_ERR_OK === $this->error);
    }

    /**
     * \brief Retorna o nome original do arquivo enviado.
     *
     * \return (string).
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * \brief Retorna a extensão original do arquivo enviado.
     *
     * \return (string).
     */
    public function getOriginalExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * \brief Retorna o mimeType original do arquivo enviado.
     *
     * \return (string).
     */
    public function getOriginalMimeType()
    {
        return $this->mimeType;
    }

    /**
     * \brief Retorna o tamanho original do arquivo enviado.
     *
     * \return (integer).
     */
    public function getOriginalSize()
    {
        return $this->size;
    }

    /**
     * \brief Retorna o código do erro do upload se houve algum.
     *
     * \return (integer).
     */
    public function getErrorCode()
    {
        return $this->error;
    }

    /**
     * \brief Retorna a mensagem de erro do upload.
     *
     * \staticvar array $errors
     *
     * \return type.
     */
    public function getErrorMessage()
    {
        static $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d kb).',
            UPLOAD_ERR_FORM_SIZE  => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.',
        ];

        $errorCode = $this->error;
        $maxFilesize = $errorCode === UPLOAD_ERR_INI_SIZE ? self::getMaxFilesize() / 1024 : 0;
        $message = isset($errors[$errorCode]) ? $errors[$errorCode] : 'The file "%s" was not uploaded due to an unknown error.';

        return sprintf($message, $this->getOriginalName(), $maxFilesize);
    }

    /**
     * \brief Retorna se o arquivo enviado é válido.
     *
     * \return (bool).
     */
    public function isValid()
    {
        return ($this->error === UPLOAD_ERR_OK) && is_uploaded_file($this->getPathname());
    }

    /**
     * \brief Move o arquivo enviado para o diretório indicado.
     *
     * \param [in] (string) $directory - Caminho do diretório
     * \param [in] (string) $name - Novo nome do arquivo
     *
     * \return Springy\Files\File
     *
     * \throws RuntimeException.
     */
    public function moveTo($directory, $name = null)
    {
        if ($this->isValid()) {
            $target = $this->getTargetFile($directory, $name);

            if (!@move_uploaded_file($this->getPathname(), $target)) {
                $error = error_get_last();

                throw new RuntimeException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
            }

            @chmod($target, 0666 & ~umask());

            return $target;
        }

        throw new RuntimeException($this->getErrorMessage());
    }

    /**
     * \brief Retorna o tamanho máximo do arquivo que pode ser enviado por upload.
     *
     * \return int.
     */
    public static function getMaxFilesize()
    {
        $iniMax = strtolower(ini_get('upload_max_filesize'));

        if ('' === $iniMax) {
            return PHP_INT_MAX;
        }

        $max = ltrim($iniMax, '+');
        if (0 === strpos($max, '0x')) {
            $max = intval($max, 16);
        } elseif (0 === strpos($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = intval($max);
        }

        switch (substr($iniMax, -1)) {
            case 't': $max *= 1024;
            case 'g': $max *= 1024;
            case 'm': $max *= 1024;
            case 'k': $max *= 1024;
        }

        return $max;
    }

    /**
     * \brief Transforma o array superglobal de arquivos $_FILES em uma
     *        coleção de objetos Springy\Files\UploadedFile para manipulação facilitada.
     *
     * \param [in] (array) $files - $_FILES superglobal
     *
     * \return (array).
     */
    public static function convertPHPUploadedFiles($files)
    {
        $convertedFiles = [];

        foreach ($files as $name => $info) {
            $convertedFiles[$name] = self::convertPHPSinglePHPUploadedFile($info);
        }

        return $convertedFiles;
    }

    /**
     * \brief Transforma um item unico do arrey superglobal $_FILES em um objeto Springy\Files\UploadedFile.
     *
     * \param [in] (array) $file
     *
     * \return \Springy\Files\UploadedFile.
     */
    protected static function convertPHPSinglePHPUploadedFile($file)
    {
        if (is_array($file['tmp_name'])) {
            $keys = array_keys($file['tmp_name']);
            $files = [];
            foreach ($keys as $key) {
                $files[$key] = [
                    'name'     => $file['name'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'type'     => $file['type'][$key],
                    'size'     => $file['size'][$key],
                    'error'    => $file['error'][$key],
                ];
            }

            return self::convertPHPUploadedFiles($files);
        } else {
            return new self($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
        }
    }
}
