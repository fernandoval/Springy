#!/usr/bin/php
<?php

/**
 * Components manager.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   4.0.14
 *
 * This is script is not a Composer plugin.
 *
 * This post install/update script for Composer is not a packager version number.
 * It is a helper program to copy (and minify) component files from the download
 * destination directories to final folders in the web server accessible tree.
 * Than you can use your favorite package manager like Composer, NPM, Yarn, etc.
 *
 * The composer.json file is loaded and the "extra" section is used to it's
 * configuration.
 *
 * If the script find a "post-install" section inside the "extra" section, it
 * do a copy of files downloaded by Composer to the "target" defined for every
 * "vendor/package" listed.
 *
 * If there is no "files" defined for every "vendor/package", their bower.json
 * file is used by this script to decide which files will be copied.
 *
 * Also a "components.json" file will be loaded if it exists. Then the list of
 * components listed inside "components" entry.
 *
 * The following attributes will be used:
 *
 * "source" - (only in components.json) The source folder where to find the files;
 * "target" - Destination folder to the files;
 * "ignore-subdirs" - If true all files will be sabed in same folder;
 * "minify" - "on" or "off" to minify or not the Javascript and CSS files;
 * "files" - The array of files or the file to be copied. Wildcards accepted.
 *
 * NOTE: To minify CSS and JS files, is recommended the use of the Minify class
 * by Matthias Mullie.
 * https://github.com/matthiasmullie/minify
 */

define('DS', DIRECTORY_SEPARATOR);

define('LF', "\n");
define('CS_GREEN', "\033[32m");
define('CS_RED', "\033[31m");
define('CS_RESET', "\033[0m");

define('TAB', '    ');

define('LOCK_FILE', 'components.lock');
define('BOWER_FILE', 'bower.json');

new Main();

/**
 * The program's main class.
 */
class Main
{
    private $installed = [];
    private $components = [];
    private $vendorDir = 'vendor';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->loadComposerJson();
        $this->loadComponentsJson();

        // Load the Composer's autoload file
        if (file_exists($this->vendorDir . DS . 'autoload.php')) {
            require $this->vendorDir . DS . 'autoload.php';
        }

        echo CS_GREEN, 'Starting the installation of the extra components', CS_RESET, LF;

        $this->loadLockFile();
        $this->checkRemovedComponents();

        // Process every component
        foreach ($this->components as $component => $data) {
            $this->procede($component, $data);
        }

        $this->writeLock();
    }

    /**
     * Adds the file or directory to list of created/copied.
     *
     * @param string $component the component name
     * @param array  $struc     the structured array with file or folder data.
     *
     * @return void
     */
    private function addInstalled($component, array $struc)
    {
        $this->installed[$component][] = $struc;
    }

    /**
     * Gets the component source path.
     *
     * Also checks if the destination is defined.
     *
     * @param array $data
     *
     * @return string|bool
     */
    private function checkComponentPath($data)
    {
        if (!isset($data['source'])) {
            $this->fatalError(TAB . 'Component source path undefined.');
        }

        // Component sub directory
        $path = '.' . DS . implode(DS, explode('/', $data['source']));

        // Check component's source path
        if (!is_dir($path)) {
            echo TAB, CS_RED, 'Component\'s "', $path, '" does not exists.', CS_RESET, LF;

            return false;
        }

        // Check compnent's configuration
        if (!isset($data['target'])) {
            echo TAB, CS_RED, 'Target directory not defined.', CS_RESET, LF;

            return false;
        }

        return $path;
    }

    /**
     * Verifies all installed components that is no more listed inside Json.
     *
     * @return void
     */
    private function checkRemovedComponents()
    {
        // Verify if any component was removed
        foreach (array_reverse($this->installed) as $component => $files) {
            if (!isset($this->components[$component])) {
                echo '  - Deleting ', CS_GREEN, $component, CS_RESET, ' files', LF;

                foreach (array_reverse($files) as $file) {
                    switch ($file['type']) {
                        case 'd':
                            if (is_dir($file['path'])) {
                                if (!rmdir($file['path'])) {
                                    echo TAB, CS_RED, 'Fail to delete "', $file['path'], '" file.', CS_RESET, LF;
                                }
                            }

                            break;
                        case 'f':
                            if (is_file($file['path'])) {
                                if (!unlink($file['path'])) {
                                    echo TAB, CS_RED, 'Fail to delete "', $file['path'], '" file.', CS_RESET, LF;
                                }
                            }

                            break;
                    }
                }
            }
        }

        $this->installed = [];
    }

    /**
     * Recursive Copy Function.
     *
     * @param string $path
     * @param string $dest
     * @param bool   $minify
     * @param string $component
     *
     * @return void
     */
    private function recursiveCopy($path, $dest, $minify, $component)
    {
        // Is the source a file?
        if (is_file($path)) {
            // Destination exists?
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    echo TAB, CS_RED, 'Can\'t create "', $dir, '" directory.', CS_RESET, LF;

                    return;
                }

                $this->addInstalled($component, [
                    'path' => $dir,
                    'type' => 'd',
                ]);
            }

            $this->addInstalled($component, [
                'path' => $dest,
                'type' => 'f',
            ]);

            // Copy only if source is new or newer
            if (is_file($dest) && filemtime($path) < filemtime($dest)) {
                return;
            }

            if (!$this->realCopy($path, $dest, $minify)) {
                echo TAB, CS_RED, '[ERROR] Copying (', $path, ') to (', $dest, ')', CS_RESET, LF;
            }

            return;
        }

        // Is the source a directory?
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $this->recursiveCopy($path . DS . $file, $dest . DS . $file, $minify, $component);
            }

            return;
        }

        // Oh! Is a wildcard path.

        $success = false;
        $dest = dirname($dest);
        foreach (glob($path) as $filename) {
            $success = $this->recursiveCopy($filename, $dest . DS . basename($filename), $minify, $component);
        }
    }

    /**
     * Terminates the program with an error message.
     *
     * @param string $error
     *
     * @return void
     */
    private function fatalError($error)
    {
        echo CS_RED, $error, CS_RESET, LF;

        exit(1);
    }

    /**
     * Gets the list of files of the component.
     *
     * @param array  $data
     * @param string $path
     *
     * @return array
     */
    private function getComponentFiles($data, $path)
    {
        if (isset($data['files'])) {
            return is_array($data['files']) ? $data['files'] : [$data['files']];
        }

        if (file_exists($path . DS . BOWER_FILE)) {
            if (!$str = file_get_contents($path . DS . BOWER_FILE)) {
                $this->fatalError(TAB . 'Can\'t open "' . $path . DS . BOWER_FILE . '" file.');
            }

            $bower = $this->parseJson($str);
            if (!isset($bower['main'])) {
                echo TAB, CS_RED, 'Main section does not exists in "' . $path . DS . BOWER_FILE . '" file.', CS_RESET, LF;

                return [];
            }

            return is_array($bower['main']) ? $bower['main'] : [$bower['main']];
        }

        return ['*'];
    }

    /**
     * Gets the destination folder for the component.
     *
     * Creates the folder if does not exists.
     *
     * @param string $component
     * @param array  $data
     *
     * @return string
     */
    private function getDestinantion($component, $data)
    {
        $destination = '.' . DS . implode(DS, explode('/', $data['target']));

        if (!is_dir($destination)) {
            if (!mkdir($destination, 0775, true)) {
                $this->fatalError(TAB . 'Can\'t create "' . $destination . '" directory.');
            }
        }

        $this->addInstalled($component, [
            'path' => $destination,
            'type' => 'd',
        ]);

        return $destination;
    }

    /**
     * Inserts the component structure for the lock file.
     *
     * @param string $component
     *
     * @return void
     */
    private function initializeComponent($component)
    {
        $this->installed[$component] = [];
    }

    /**
     * Loads the components.json file.
     *
     * @return void
     */
    private function loadComponentsJson()
    {
        if (!file_exists('components.json')) {
            return;
        }

        if (!$str = file_get_contents('components.json')) {
            $this->fatalError('Can\'t open components.json file.');
        }

        $components = $this->parseJson($str);

        if (!isset($components['components'])) {
            return;
        }

        if (!is_array($components['components'])) {
            $this->fatalError('Syntax error in components.json');
        }

        foreach ($components['components'] as $component => $data) {
            $this->components[$component] = $data;
        }
    }

    /**
     * Loads the composer.json file.
     *
     * @return void
     */
    private function loadComposerJson()
    {
        if (!$str = file_get_contents('composer.json')) {
            $this->fatalError('Can\'t open composer.json file.');
        }

        $composer = $this->parseJson($str);

        if (isset($composer['config']) && isset($composer['config']['vendor-dir'])) {
            $this->vendorDir = $composer['config']['vendor-dir'];
        }

        if (!isset($composer['extra'])) {
            return;
        }

        if (!isset($composer['extra']['post-install'])) {
            return;
        }

        if (!is_array($composer['extra']['post-install'])) {
            $this->fatalError('Invalid format for extra.post-install entry');
        }

        foreach ($composer['extra']['post-install'] as $component => $data) {
            $data['source'] = $this->vendorDir . DS . $component;
            $this->components[$component] = $data;
        }
    }

    /**
     * Loads the components.lock file.
     *
     * @return void
     */
    private function loadLockFile()
    {
        // The control lock file
        if (file_exists(LOCK_FILE)) {
            if (!$components = file_get_contents(LOCK_FILE)) {
                $this->fatalError('Can\'t open ' . LOCK_FILE . ' file.');
            }

            $this->installed = unserialize($components);
        }
    }

    /**
     * Minify the file if turned on.
     *
     * @param string $buffer
     * @param string $minify
     *
     * @return mixed
     */
    private function minify($buffer, $minify)
    {
        if ($minify == 'off') {
            return $buffer;
        }

        if (class_exists('MatthiasMullie\Minify\Minify')) {
            switch ($minify) {
                case 'css':
                    $minifier = new MatthiasMullie\Minify\CSS($buffer);
                    break;
                case 'js':
                    $minifier = new MatthiasMullie\Minify\JS($buffer);
                    break;
                default:
                    echo TAB, CS_RED, '[ERROR] Invalid minify method: ', $minify, CS_RESET, LF;

                    return false;
            }

            $buffer = $minifier->minify();

            return $buffer;
        }

        // Matthias Mullie's Minify class not found. I Will try by myself but this is not the best way.

        switch ($minify) {
            case 'css':
                $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
                $buffer = str_replace(["\r\n", "\r", "\n", "\t", '  ', TAB, '     '], '', $buffer);
                $buffer = preg_replace(['(( )+{)', '({( )+)'], '{', $buffer);
                $buffer = preg_replace(['(( )+})', '(}( )+)', '(;( )*})'], '}', $buffer);
                $buffer = preg_replace(['(;( )+)', '(( )+;)'], ';', $buffer);
                break;
            case 'js':
                $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", '', $buffer);
                $buffer = str_replace(["\r\n", "\r", "\t", "\n", '  ', TAB, '     '], '', $buffer);
                $buffer = preg_replace(['(( )+\))', '(\)( )+)'], ')', $buffer);
                break;
            default:
                echo TAB, CS_RED, '[ERROR] Invalid minify method: ', $minify, CS_RESET, LF;

                return false;
        }

        return $buffer;
    }

    /**
     * Parses the Json file.
     *
     * @param string $json
     *
     * @return array
     */
    private function parseJson($json)
    {
        $parsed = json_decode($json, true);
        $error = json_last_error();

        if ($error === JSON_ERROR_NONE) {
            return $parsed;
        }

        $jsonErrors = [
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
            JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
        ];

        if (!isset($jsonErrors[$error])) {
            $this->fatalError('Unknown error occurred');
        }

        $this->fatalError($jsonErrors[$error]);
    }

    /**
     * Procedes the copy of component files.
     *
     * @param string $component
     * @param array  $data
     *
     * @return void
     */
    private function procede($component, $data)
    {
        echo '  - Processing ', CS_GREEN, $component, CS_RESET, ' files', LF;

        $this->initializeComponent($component);

        if (!$path = $this->checkComponentPath($data)) {
            return;
        }

        // Component properties
        $files = $this->getComponentFiles($data, $path);
        $noSubdirs = isset($data['ignore-subdirs']) && $data['ignore-subdirs'];
        $minify = isset($data['minify']) ? $data['minify'] : 'off';
        $destination = $this->getDestinantion($component, $data);

        foreach ($files as $file) {
            $file = implode(DS, explode('/', $file));
            $dstFile = $file;
            if ($noSubdirs) {
                $dstFile = explode('/', $file);
                $dstFile = array_pop($dstFile);
            }

            $this->recursiveCopy($path . DS . $file, $destination . DS . $dstFile, $minify, $component);
        }
    }

    /**
     * Copy file minifyint if necessary.
     *
     * @param string $source
     * @param string $destiny
     * @param string $minify
     *
     * @return bool
     */
    private function realCopy($source, $destiny, $minify = 'auto')
    {
        if ($minify == 'auto' || $minify == 'on') {
            $minify = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
        }

        $buffer = file_get_contents($source);
        if ($buffer == false) {
            echo TAB, CS_RED, '[ERROR] Failed to open ', $source, CS_RESET, LF;

            return false;
        }

        $buffer = $this->minify($buffer, $minify);
        if ($buffer === false) {
            return false;
        }

        $return = file_put_contents($destiny, $buffer);
        if ($return !== false) {
            chmod($destiny, 0664);
        }

        return $return;
    }

    /**
     * Saves the components.lock file.
     *
     * @return void
     */
    private function writeLock()
    {
        // Write the lock file
        echo CS_GREEN, 'Writing lock file', CS_RESET, LF;
        if (!file_put_contents(LOCK_FILE, serialize($this->installed))) {
            $this->fatalError('Can\'t write ' . LOCK_FILE . ' file.');
        }
    }
}
