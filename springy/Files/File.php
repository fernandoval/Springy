<?php

/**
 * File manipulation class.
 *
 * @copyright 2007 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.1.3
 */

namespace Springy\Files;

use finfo;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;

/**
 * File class.
 */
class File extends SplFileInfo
{
    /**
     * Constructor.
     *
     * @param string $filename
     * @param bool   $checkFile
     *
     * @throws InvalidArgumentException
     */
    public function __construct($filename, $checkFile = true)
    {
        if ($checkFile && !is_file($filename)) {
            throw new InvalidArgumentException();
        }

        parent::__construct($filename);
    }

    /**
     * Gets file extension.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getBasename(), PATHINFO_EXTENSION);
    }

    /**
     * Gets file MIME type.
     *
     * @return void
     */
    public function getMimeType()
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($this->getPathname());
    }

    /**
     * Moves the file.
     *
     * @param string $directory directory path to move
     * @param string $name      new file name
     *
     * @throws RuntimeException
     *
     * @return self
     */
    public function moveTo($directory, $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        if (!@rename($this->getPathname(), $target)) {
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

    /**
     * Gets new File object.
     *
     * @param string $directory directory path to move
     * @param string $name      new file name
     *
     * @throws RuntimeException
     *
     * @return self
     */
    protected function getTargetFile($directory, $name = null)
    {
        if (!is_dir($directory) && (mkdir($directory, 0777, true) === false)) {
            throw new RuntimeException(sprintf('Unable to create the "%s" directory', $directory));
        } elseif (!is_writable($directory)) {
            throw new RuntimeException(sprintf('Unable to write in the "%s" directory', $directory));
        }

        $target = rtrim($directory, '/\\') . DS
            . (null === $name ? $this->getBasename() : $this->getName($name));

        return new self($target, false);
    }

    /**
     * Gets original file name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getName($name)
    {
        $originalName = str_replace('\\', '/', $name);
        $pos = strpos($originalName, '/');
        $originalName = $pos === false ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }
}
