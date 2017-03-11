<?php
/** \file
 *  Springy.
 *
 *  Post Install/Update Script for Composer
 *
 *  \copyright Copyright (c) 2015-2016 Fernando Val
 *
 *  \brief    Post Install/Update Script for Composer
 *  \version  3.2.0.11
 *  \author   Fernando Val - fernando.val@gmail.com
 *
 *  This script is executed by Composer after the install/update process.
 *
 *  The composer.json file is loaded and the "extra" section is used to it's configuration.
 *
 *  If the script find a "post-install" section inside the "extra" section, it do a copy of files
 *  downloaded by Composer to the "target" defined for every "vendor/package" listed.
 *
 *  If there is no "files" defined for every "vendor/package", their bower.json file is used by
 *  this script to decide which files will be copied.
 *
 *  \note To minify CSS and JS files, is recommended the use of the Minify class by Matthias Mullie.
 *      https://github.com/matthiasmullie/minify
 *
 *  \ingroup framework
 */
define('DS', DIRECTORY_SEPARATOR);

define('LF', "\n");
define('CS_GREEN', "\033[32m");
define('CS_RED', "\033[31m");
define('CS_RESET', "\033[0m");

define('TAB', '    ');

define('LOCK_FILE', 'components.lock');
define('BOWER_FILE', 'bower.json');

if (!$str = file_get_contents('composer.json')) {
    echo CS_RED, 'Can\'t open composer.json file.', CS_RESET, LF;
    exit(1);
}

// Load Composer configuration
$composer = json_decode($str, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo CS_RED;

    switch (json_last_error()) {
        case JSON_ERROR_DEPTH:
            echo 'The maximum stack depth has been exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo 'Invalid or malformed JSON';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo 'Control character error, possibly incorrectly encoded';
            break;
        case JSON_ERROR_SYNTAX:
            echo 'Syntax error';
            break;
        case JSON_ERROR_UTF8:
            echo 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        case JSON_ERROR_RECURSION:
            echo 'One or more recursive references in the value to be encoded';
            break;
        case JSON_ERROR_INF_OR_NAN:
            echo 'One or more NAN or INF values in the value to be encoded';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            echo 'A value of a type that cannot be encoded was given';
            break;
        default:
            echo 'Unknown error occurred';
    }

    echo CS_RESET, LF;

    exit(1);
}
if (!isset($composer['extra'])) {
    exit(0);
}
if (!isset($composer['extra']['post-install'])) {
    exit(0);
}
if (!is_array($composer['extra']['post-install'])) {
    echo CS_RED, 'Invalid format for extra.post-install entry', CS_RESET, LF;
    exit(1);
}

$vendorDir = 'vendor';
if (isset($composer['config']) && isset($composer['config']['vendor-dir'])) {
    $vendorDir = $composer['config']['vendor-dir'];
}

// Load the Composer's autoload file
if (file_exists($vendorDir.DS.'autoload.php')) {
    require $vendorDir.DS.'autoload.php';
}

echo CS_GREEN, 'Starting the installation of the extra components', CS_RESET, LF;

// The control lock file
$components = [];
if (file_exists(LOCK_FILE)) {
    if (!$components = file_get_contents(LOCK_FILE)) {
        echo CS_RED, 'Can\'t open ', LOCK_FILE, ' file.', CS_RESET, LF;
        exit(1);
    }

    $components = unserialize($components);
}

// Verify if any component was removed
foreach (array_reverse($components) as $component => $files) {
    if (!isset($composer['extra']['post-install'][$component])) {
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
$components = [];

// Process every component
foreach ($composer['extra']['post-install'] as $component => $data) {
    echo '  - Processing ', CS_GREEN, $component, CS_RESET, ' files', LF;

    $components[$component] = [];

    // Component sub directory
    $path = $vendorDir.DS.implode(DS, explode('/', $component));

    // Check component's source path
    if (!is_dir($path)) {
        echo TAB, CS_RED, 'Component\'s "', $path, '" does not exists.', CS_RESET, LF;
        continue;
    }

    // Check compnent's configuration
    if (!isset($data['target'])) {
        echo TAB, CS_RED, 'Target directory not defined.', CS_RESET, LF;
        continue;
    }

    // Component properties
    $files = '*';
    $noSubdirs = isset($data['ignore-subdirs']) && $data['ignore-subdirs'];
    $minify = isset($data['minify']) ? $data['minify'] : 'off';

    // Define the files of the component
    if (isset($data['files'])) {
        $files = $data['files'];
    } elseif (file_exists($path.DS.BOWER_FILE)) {
        if (!$str = file_get_contents($path.DS.BOWER_FILE)) {
            echo TAB, CS_RED, 'Can\'t open "'.$path.DS.BOWER_FILE.'" file.', CS_RESET, LF;
            continue;
        }

        $bower = json_decode($str, true);
        if (!isset($bower['main'])) {
            echo TAB, CS_RED, 'Main section does not exists in "'.$path.DS.BOWER_FILE.'" file.', CS_RESET, LF;
            continue;
        }

        if (is_array($bower['main'])) {
            $files = $bower['main'];
        } else {
            $files = [$bower['main']];
        }
    }

    // Check the destination directory
    $destination = implode(DS, explode('/', $data['target']));
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            echo TAB, CS_RED, 'Can\'t create "', $destination, '" directory.', CS_RESET, LF;
            continue;
        }

        $components[$component][] = [
            'path' => $destination,
            'type' => 'd',
        ];
    }

    if (is_array($files)) {
        foreach ($files as $file) {
            $file = implode(DS, explode('/', $file));
            $dstFile = $file;
            if ($noSubdirs) {
                $dstFile = explode('/', $file);
                $dstFile = array_pop($dstFile);
            }

            copy_r($path.DS.$file, $destination.DS.$dstFile, $minify, $components[$component]);
        }

        continue;
    }

    copy_r($path.DS.$files, $destination.DS.$files, $minify, $components[$component]);
}

// Write the lock file
echo CS_GREEN, 'Writing lock file', CS_RESET, LF;
if (!file_put_contents(LOCK_FILE, serialize($components))) {
    echo CS_RED, 'Can\'t write ', LOCK_FILE, ' file.', CS_RESET, LF;
    exit(1);
}

/**
 *  \brief Recursive Copy Function.
 */
function copy_r($path, $dest, $minify = 'off', &$cFiles)
{
    // Is the source a file?
    if (is_file($path)) {
        // Destination exists?
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                echo TAB, CS_RED, 'Can\'t create "', $dir, '" directory.', CS_RESET, LF;

                return false;
            }

            $cFiles[] = [
                'path' => $dir,
                'type' => 'd',
            ];
        }

        $cFiles[] = [
            'path' => $dest,
            'type' => 'f',
        ];

        // Copy only if source is new or newer
        if (is_file($dest) && filemtime($path) < filemtime($dest)) {
            return true;
        }

        $success = realCopy($path, $dest, $minify);
        if (!$success) {
            echo TAB, CS_RED, '[ERROR] Copying (', $filename, ') to (', $dest.DS.basename($filename), ')', CS_RESET, LF;
        }

        return $success;
    }

    // Is the source a directory?
    if (is_dir($path)) {
        $objects = scandir($path);
        foreach ($objects as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            copy_r($path.DS.$file, $dest.DS.$file, $minify, $cFiles);
        }

        return true;
    }

    // Oh! Is a wildcard path.

    $success = false;
    $dest = dirname($dest);
    foreach (glob($path) as $filename) {
        $success = copy_r($filename, $dest.DS.basename($filename), $minify, $cFiles);
    }

    return true;
}

/**
 *  \brief Minify the file if turned on.
 */
function minify($buffer, $minify)
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
 *  \brief Copy file minifyint if necessary.
 */
function realCopy($source, $destiny, $minify = 'auto')
{
    if ($minify == 'auto' || $minify == 'on') {
        $minify = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
    }

    $buffer = file_get_contents($source);
    if ($buffer == false) {
        echo TAB, CS_RED, '[ERROR] Failed to open ', $source, CS_RESET, LF;

        return false;
    }

    $buffer = minify($buffer, $minify);
    if ($buffer === false) {
        return false;
    }

    $return = file_put_contents($destiny, $buffer);
    if ($return !== false) {
        chmod($destiny, 0664);
    }

    return $return;
}
