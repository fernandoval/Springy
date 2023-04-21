#!/usr/bin/php
<?php

/**
 * Components manager.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   5.0.1
 *
 * This is script is not a Composer plugin.
 *
 * This post install/update script for Composer is not a packager version number.
 * It is a helper program to copy (and minify) component files from the download
 * destination directories to final folders in the web server accessible tree.
 * Than you can use your favorite package manager like Composer, NPM, Yarn, etc.
 *
 * The "components.json" file will be loaded if it exists. Then the list of
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
 * If there is no "files" defined for every component, their bower.json file is
 * used by this script to decide which files will be copied.
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

define('LOCK_FILE', __DIR__ . DS . 'components.lock');
define('BOWER_FILE', 'bower.json');

/**
 * Gets the component source path.
 *
 * Also checks if the destination is defined.
 *
 * @param array $data
 *
 * @return string
 */
function checkComponentPath(array $data): string
{
    if (!isset($data['source'])) {
        fatalError(TAB . 'Component source path undefined.');
    }

    // Component sub directory
    $path = __DIR__ . DS . implode(DS, explode('/', $data['source']));

    // Check component's source path
    if (!is_dir($path)) {
        echo TAB, CS_RED, 'Component\'s "', $path, '" does not exists.', CS_RESET, LF;

        return '';
    }

    // Check compnent's configuration
    if (!isset($data['target'])) {
        echo TAB, CS_RED, 'Target directory not defined.', CS_RESET, LF;

        return '';
    }

    return $path;
}

/**
 * Copies all files from a directory.
 *
 * @param string $path
 * @param string $dest
 * @param string $minify
 *
 * @return array
 */
function copyDir(string $path, string $dest, string $minify): array
{
    $installed = [];
    $objects = scandir($path);

    foreach ($objects as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $installed = array_merge(
            $installed,
            recursiveCopy($path . DS . $file, $dest . DS . $file, $minify)
        );
    }

    return $installed;
}

/**
 * Copy a file.
 *
 * @param string $path
 * @param string $dest
 * @param string $minify
 *
 * @return void
 */
function copyFile(string $path, string $dest, string $minify): void
{
    // The source is a file
    $dir = dirname($dest);
    grantDestination($dir);

    // Copy only if source is new or newer
    if (is_file($dest) && filemtime($path) < filemtime($dest)) {
        return;
    } elseif (!realCopy($path, $dest, $minify)) {
        echo TAB, CS_RED, '[ERROR] Copying (', $path, ') to (', $dest, ')', CS_RESET, LF;
    }
}

/**
 * Removes an empty directory.
 *
 * @param array $dir
 *
 * @return void
 */
function delDir(array $dir): void
{
    $type = $dir['type'] ?? '';
    $path = $dir['path'] ?? '';

    if ($type !== 'd' || !$path) {
        return;
    } elseif (is_dir($path) && count(getDir($path)) === 0 && !rmdir($path)) {
        echo TAB, CS_RED, 'Fail to delete "', $path, '" file.', CS_RESET, LF;
    }
}

/**
 * Deletes a file.
 *
 * @param array $file
 *
 * @return void
 */
function delFile(array $file): void
{
    $type = $file['type'] ?? '';
    $path = $file['path'] ?? '';

    if ($type !== 'f' || !$path) {
        return;
    } elseif (is_file($path) && !unlink($path)) {
        echo TAB, CS_RED, 'Fail to delete "', $path, '" file.', CS_RESET, LF;
    }

    $dir = dirname($path);
    $files = glob($dir . DS . '{.[!.],}*', GLOB_BRACE);

    if (is_array($files) && !count($files)) {
        delDir([
            'type' => 'd',
            'path' => $dir,
        ]);
    }
}

/**
 * Verifies all installed components that is no more listed inside Json.
 *
 * @param array $components
 *
 * @return void
 */
function delRemovedComponents(array $components): void
{
    $installed = loadLockFile();

    // Verify if any component was removed
    foreach (array_reverse($installed) as $name => $files) {
        if (isset($components[$name])) {
            continue;
        }

        echo '  - Deleting ', CS_GREEN, $name, CS_RESET, ' files', LF;

        foreach (array_reverse($files) as $file) {
            delDir($file);
            delFile($file);
        }
    }
}

/**
 * Terminates the program with an error message.
 *
 * @param string $error
 *
 * @return void
 */
function fatalError(string $error): void
{
    echo CS_RED, $error, CS_RESET, LF;

    exit(1);
}

/**
 * Gets the list of files from Bower Json.
 *
 * @param string $path
 *
 * @return array
 */
function getBowerMain(string $path): array
{
    if (!file_exists($path . DS . BOWER_FILE)) {
        return ['*'];
    }

    $bower = loadJson($path . DS . BOWER_FILE);

    if (!isset($bower['main'])) {
        echo TAB, CS_RED, 'Main section does not exists in "' . $path . DS . BOWER_FILE . '" file.', CS_RESET, LF;

        return [];
    }

    return is_array($bower['main']) ? $bower['main'] : [$bower['main']];
}

/**
 * Gets the list of files of the component.
 *
 * @param array  $data
 * @param string $path
 *
 * @return array
 */
function getComponentFiles(array $data, string $path): array
{
    if (isset($data['files'])) {
        return is_array($data['files']) ? $data['files'] : [$data['files']];
    }

    return getBowerMain($path);
}

/**
 * Returns an array with directory content without . and .. special dirs.
 *
 * @param string $path
 *
 * @return array
 */
function getDir(string $path): array
{
    return array_filter(
        scandir($path),
        fn ($file) => $file !== '.' && $file !== '..'
    );
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
function getDestinantion(string $component, array $data)
{
    if (!is_string($data['target'])) {
        fatalError(TAB . 'No destination defined for "' . $component . '" component.');
    }

    $destination = __DIR__ . DS . implode(DS, explode('/', $data['target']));

    if (!is_dir($destination) && !mkdir($destination, 0775, true)) {
        fatalError(TAB . 'Can\'t create "' . $destination . '" directory.');
    }

    return $destination;
}

/**
 * Grants the existance of the directory.
 *
 * @param string $dir
 *
 * @return void
 */
function grantDestination(string $dir): void
{
    if (is_dir($dir)) {
        return;
    } elseif (!mkdir($dir, 0775, true)) {
        echo TAB, CS_RED, 'Can\'t create "', $dir, '" directory.', CS_RESET, LF;
    }
}

/**
 * Loads the components.json file and returns an array with components list.
 *
 * @return array
 */
function loadComponentsJson(): array
{
    $jsonpath = __DIR__ . DS . 'components.json';

    if (!file_exists($jsonpath)) {
        return [];
    }

    $json = loadJson($jsonpath);

    if (!is_array($json['components'] ?? null)) {
        fatalError('Syntax error in components.json');
    }

    $components = [];

    foreach ($json['components'] as $name => $data) {
        $components[$name] = $data;
    }

    return $components;
}

/**
 * Parses the Json file.
 *
 * @param string $json
 *
 * @return array
 */
function loadJson(string $filepath): array
{
    $jsonstr = file_get_contents($filepath);

    if (!$jsonstr) {
        fatalError('Can\'t open ' . $filepath . ' file.');
    }

    $parsed = json_decode($jsonstr, true);
    $error = json_last_error();

    if ($error === JSON_ERROR_NONE) {
        return $parsed;
    }

    fatalError(
        ([
            JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
            JSON_ERROR_SYNTAX => 'Syntax error',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
            JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
        ])[$error] ?? 'Unknown error occurred'
    );
}

/**
 * Loads the components.lock file.
 *
 * @return array
 */
function loadLockFile(): array
{
    if (!file_exists(LOCK_FILE)) {
        return [];
    }

    $lock = file_get_contents(LOCK_FILE);

    if (!$lock) {
        fatalError('Can\'t open ' . LOCK_FILE . ' file.');
    }

    return unserialize($lock);
}

/**
 * Minify a file using Mattias Mullie's component.
 *
 * @param string $buffer
 * @param string $ext
 *
 * @return string
 */
function matthiasMullie(string $buffer, string $ext): string
{
    if ($ext !== 'css' && $ext !== 'js') {
        echo TAB, CS_RED, '[WARNING] Invalid minify method: ', $ext, CS_RESET, LF;

        return $buffer;
    }

    $minifier = 'css'
        ? new MatthiasMullie\Minify\CSS($buffer)
        : new MatthiasMullie\Minify\JS($buffer);

    return $minifier->minify();
}

/**
 * Minify the file if turned on.
 *
 * @param string $buffer
 * @param string $minify
 *
 * @return string
 */
function minifyFile(string $buffer, string $minify): string
{
    if ($minify == 'off') {
        return $buffer;
    }

    if (class_exists('MatthiasMullie\Minify\Minify')) {
        return matthiasMullie($buffer, $minify);
    }

    // Matthias Mullie's Minify class not found. I Will try by myself but this is not the best way.

    switch ($minify) {
        case 'css':
            $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
            $buffer = str_replace(["\r\n", "\r", "\n", "\t", '  ', TAB, '    '], '', $buffer);
            $buffer = preg_replace(['(( )+{)', '({( )+)'], '{', $buffer);
            $buffer = preg_replace(['(( )+})', '(}( )+)', '(;( )*})'], '}', $buffer);
            $buffer = preg_replace(['(;( )+)', '(( )+;)'], ';', $buffer);
            break;
        case 'js':
            $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", '', $buffer);
            $buffer = str_replace(["\r\n", "\r", "\t", "\n", '  ', TAB, '    '], '', $buffer);
            $buffer = preg_replace(['(( )+\))', '(\)( )+)'], ')', $buffer);
            break;
        default:
            echo TAB, CS_RED, '[WARNING] Invalid minify method: ', $minify, CS_RESET, LF;
    }

    return $buffer;
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
function realCopy(string $source, string $destiny, string $minify = 'auto')
{
    if ($minify == 'auto' || $minify == 'on') {
        $minify = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
    }

    $buffer = file_get_contents($source);

    if ($buffer == false) {
        echo TAB, CS_RED, '[ERROR] Failed to open ', $source, CS_RESET, LF;

        return false;
    }

    $buffer = minifyFile($buffer, $minify);
    $return = file_put_contents($destiny, minifyFile($buffer, $minify));

    if ($return !== false) {
        chmod($destiny, 0664);
    }

    return $return;
}

/**
 * Copy files or directory recursively.
 *
 * @param string $path
 * @param string $dest
 * @param string $minify
 * @param string $component
 *
 * @return array
 */
function recursiveCopy(string $path, string $dest, string $minify): array
{
    $installed = [];

    if (is_dir($path)) {
        // The source is a directory
        return array_merge(
            $installed,
            copyDir($path, $dest, $minify)
        );
    } elseif (is_file($path)) {
        copyFile($path, $dest, $minify);
        $installed[] =  [
            'path' => $dest,
            'type' => 'f',
        ];

        return $installed;
    }

    /*
     * Oh! Is a wildcard path.
     */

    $dest = dirname($dest);

    foreach (glob($path) as $filename) {
        $installed = array_merge(
            $installed,
            recursiveCopy($filename, $dest . DS . basename($filename), $minify)
        );
    }

    return $installed;
}

/**
 * Adds Composer autoload if exists.
 *
 * @return void
 */
function requireComposerAutoload(): void
{
    $composer = loadJson(__DIR__ . DS . 'composer.json');
    $vendor = isset($composer['config']) && isset($composer['config']['vendor-dir'])
        ? $composer['config']['vendor-dir']
        : 'vendor';

    if (file_exists($vendor . DS . 'autoload.php')) {
        require $vendor . DS . 'autoload.php';
    }
}

/** @var array components list */
$components = loadComponentsJson();
/** @var array installed components list */
$installed = [];

echo CS_GREEN, 'Starting the installation of the extra components', CS_RESET, LF;

requireComposerAutoload();
delRemovedComponents($components);

// Process every component
foreach ($components as $name => $data) {
    echo '  - Processing ', CS_GREEN, $name, CS_RESET, ' files', LF;

    $installed[$name] = [];
    $path = checkComponentPath($data);

    if (!$path) {
        continue;
    }

    // Component properties
    $files = getComponentFiles($data, $path);
    $noSubdirs = $data['ignore-subdirs'] ?? false;
    $minify = $data['minify'] ?? 'off';
    $destination = getDestinantion($name, $data);
    $installed[$name][] = [
        'path' => $destination,
        'type' => 'd',
    ];

    foreach ($files as $file) {
        $file = implode(DS, explode('/', $file));
        $dstFile = $file;

        if ($noSubdirs) {
            $dstFile = explode('/', $file);
            $dstFile = array_pop($dstFile);
        }

        $installed[$name] = array_merge(
            $installed[$name],
            recursiveCopy($path . DS . $file, $destination . DS . $dstFile, $minify)
        );
    }
}

// Write the lock file
echo CS_GREEN, 'Writing lock file', CS_RESET, LF;

if (!file_put_contents(LOCK_FILE, serialize($installed))) {
    fatalError('Can\'t write ' . LOCK_FILE . ' file.');
}
