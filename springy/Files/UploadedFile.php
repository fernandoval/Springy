<?php

/**
 * Handler of files that have been uploaded to the file system.
 *
 * @copyright 2014 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   0.1.5
 */

namespace Springy\Files;

use RuntimeException;

class UploadedFile extends File
{
    /** @var string Original file name */
    protected $originalName;
    /** @var string MimeType of file fetched by PHP upload data */
    protected $mimeType;
    /** @var int File size fetched by PHP upload data */
    protected $size;
    /** @var int Upload error code, if any */
    protected $error;

    /**
     * Constructor.
     *
     * @param string $filename
     * @param string $originalName
     * @param string $mimeType
     * @param int    $size
     * @param int    $error
     *
     * @throws RuntimeException
     */
    public function __construct(
        string $filename,
        string $originalName,
        ?string $mimeType = null,
        ?int $size = null,
        ?int $error = null
    ) {
        if (!ini_get('file_uploads')) {
            throw new RuntimeException(
                sprintf(
                    'Unable to create UploadedFile because "file_uploads" is disabled in your php.ini file (%s)',
                    get_cfg_var('cfg_file_path')
                )
            );
        }

        $this->originalName = $this->getName($originalName);
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->size = $size;
        $this->error = $error ?? UPLOAD_ERR_OK;

        parent::__construct($filename, UPLOAD_ERR_OK === $this->error);
    }

    /**
     * Returns the original name of the uploaded file.
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Returns the original extension of the uploaded file.
     *
     * @return string
     */
    public function getOriginalExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Returns the original MIME type of the uploaded file.
     *
     * @return string
     */
    public function getOriginalMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Returns the original size of the uploaded file.
     *
     * @return void
     */
    public function getOriginalSize()
    {
        return $this->size;
    }

    /**
     * Returns the upload error code, if any.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error;
    }

    /**
     * Returns the upload error message.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        static $errors = [
            UPLOAD_ERR_INI_SIZE => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d kb).',
            UPLOAD_ERR_FORM_SIZE => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        ];

        $errorCode = $this->error;
        $maxFilesize = $errorCode === UPLOAD_ERR_INI_SIZE ? self::getMaxFilesize() / 1024 : 0;

        return sprintf(
            $errors[$errorCode] ?? 'The file "%s" was not uploaded due to an unknown error.',
            $this->getOriginalName(),
            $maxFilesize
        );
    }

    /**
     * Returns if the uploaded file is valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->error === UPLOAD_ERR_OK) && is_uploaded_file($this->getPathname());
    }

    /**
     * Moves the uploaded file to the indicated directory.
     *
     * @param string      $directory
     * @param string|null $name
     *
     * @throws RuntimeException
     *
     * @return \Springy\Files\File
     */
    public function moveTo($directory, $name = null)
    {
        if ($this->isValid()) {
            $target = $this->getTargetFile($directory, $name);

            if (!@move_uploaded_file($this->getPathname(), $target)) {
                $error = error_get_last();

                throw new RuntimeException(
                    sprintf(
                        'Could not move the file "%s" to "%s" (%s)',
                        $this->getPathname(),
                        $target,
                        strip_tags($error['message'])
                    )
                );
            }

            chmod($target, 0666 & ~umask());

            return $target;
        }

        throw new RuntimeException($this->getErrorMessage());
    }

    /**
     * Returns the maximum file size that can be uploaded.
     *
     * @return int
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

        $pos = strpos('kmgt', substr($iniMax, -1));

        return $max * pow(1024, $pos === false ? 0 : $pos + 1);
    }

    /**
     * Turns the $_FILES superglobal array of files into a collection of
     * Springy\Files\UploadedFile objects for easy manipulation.
     *
     * @param array $files $_FILES superglobal
     *
     * @return array
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
     * Turn a single item from the $_FILES superglobal array into a Springy\Files\UploadedFile object.
     *
     * @param array $file
     *
     * @return \Springy\Files\UploadedFile
     */
    protected static function convertPHPSinglePHPUploadedFile($file)
    {
        if (is_array($file['tmp_name'])) {
            $keys = array_keys($file['tmp_name']);
            $files = [];
            foreach ($keys as $key) {
                $files[$key] = [
                    'name' => $file['name'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'type' => $file['type'][$key],
                    'size' => $file['size'][$key],
                    'error' => $file['error'][$key],
                ];
            }

            return self::convertPHPUploadedFiles($files);
        }

        return new self($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']);
    }
}
