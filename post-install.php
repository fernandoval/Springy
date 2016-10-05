<?php
/** \file
 *  Springy.
 *
 *  Post Install/Update Script for Composer
 *
 *  \copyright Copyright (c) 2015-2016 Fernando Val
 *
 *  \brief    Post Install/Update Script for Composer
 *  \version  3.0.0.8
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

if (!$str = file_get_contents('composer.json')) {
    echo 'Can\'t open composer.json file.';
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
if (!isset($composer['config']) || !isset($composer['config']['vendor-dir'])) {
    $vendorDir = 'vendor';
} else {
    $vendorDir = $composer['config']['vendor-dir'];
}

// Load the Composer's autoload file
if (file_exists($vendorDir.DS.'autoload.php')) {
    require $vendorDir.DS.'autoload.php';
}

echo CS_GREEN, 'Starting the installation of the extra components', CS_RESET, LF;

// Process every component
foreach ($composer['extra']['post-install'] as $component => $data) {
    echo '  - Processing ', CS_GREEN, $component, CS_RESET, ' files', LF;

    // Component sub directory
    $path = $vendorDir.DS.implode(DS, explode('/', $component));

    // Check component's source path
    if (!is_dir($path)) {
        echo '    ', CS_RED, 'Component\'s "'.$path.'" does not exists.', CS_RESET, LF;
        continue;
    }

    // Check compnent's configuration
    if (!isset($data['target'])) {
        echo '    ', CS_RED, 'Target directory not defined.', CS_RESET, LF;
        continue;
    }

    $files = '*';
    $target = $data['target'];
    $noSubdirs = isset($data['ignore-subdirs']) && $data['ignore-subdirs'];
    $minify = isset($data['minify']) ? $data['minify'] : 'off';

    // Define the files of the component
    if (isset($data['files'])) {
        $files = $data['files'];
    } elseif (file_exists($path.DS.'bower.json')) {
        if (!$str = file_get_contents($path.DS.'bower.json')) {
            echo '    ', CS_RED, 'Can\'t open "'.$path.DS.'bower.json" file.', CS_RESET, LF;
            continue;
        }

        $bower = json_decode($str, true);
        if (!isset($bower['main'])) {
            echo '    ', CS_RED, 'Main section does not exists in "'.$path.DS.'bower.json" file.', CS_RESET, LF;
            continue;
        }

        if (is_array($bower['main'])) {
            $files = $bower['main'];
        } else {
            $files = [$bower['main']];
        }
    }

    // Check the destination directory
    $destination = implode(DS, explode('/', $target));
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            echo '    ', CS_RED, 'Can\'t create "'.$destination.'" directory.', CS_RESET, LF;
            continue;
        }
    }

    if (is_array($files)) {
        foreach ($files as $file) {
            $file = implode(DS, explode('/', $file));
            if ($noSubdirs) {
                $dstFile = explode('/', $file);
                $dstFile = array_pop($dstFile);
            } else {
                $dstFile = $file;
            }
            copy_r($path.DS.$file, $destination.DS.$dstFile, $minify);
        }

        continue;
    }

    copy_r($path.DS.$files, $destination.DS.$files, $minify);
}

/**
 *  \brief Recursive Copy Function.
 */
function copy_r($path, $dest, $minify = 'off')
{
    // Is the source a directory?
    if (is_dir($path)) {
        $objects = scandir($path);
        foreach ($objects as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            copy_r($path.DS.$file, $dest.DS.$file, $minify);
            // // Is a sub directory?
            // if (is_dir($path.DS.$file)) {

                // continue;
            // }

            // // Copy only if destination does not existis or source is newer
            // if (!is_file($dest.DS.$file) || filemtime($path.DS.$file) > filemtime($dest.DS.$file)) {
                // if (!is_dir($dest)) {
                    // mkdir($dest, 0775, true);
                // }
                // if (!realCopy($path.DS.$file, $dest.DS.$file, $minify)) {
                    // echo '    ', CS_RED, '[ERROR] Can not copy (', $path.DS.$file, ') to (', $dest.DS.$file, ')', CS_RESET, LF;
                // }
            // }
        }

        return true;
    }
    // Is the source a file?
    elseif (is_file($path)) {
        // Destination exists?
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Copy only if source is new or newer
        if (is_file($dest) && filemtime($path) < filemtime($dest)) {
            return true;
        }

        $success = realCopy($path, $dest, $minify);
        if (!$success) {
            echo '    ', CS_RED, '[ERROR] Copying (', $filename, ') to (', $dest.DS.basename($filename), ')', CS_RESET, LF;
        }

        return $success;
    }

    // Oh! Is a wildcard path.

    $success = false;
    $dest = dirname($dest);
    foreach (glob($path) as $filename) {
        $success = copy_r($filename, $dest.DS.basename($filename), $minify);
    }

    return true;
}

/**
 *  \brief Copy file minifyint if necessary.
 */
function realCopy($source, $destiny, $minify = 'auto')
{
    if ($minify == 'auto') {
        $minify = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
    }

    if ($minify != 'off' && class_exists('MatthiasMullie\Minify\Minify')) {
        switch ($minify) {
            case 'css':
                $minifier = new MatthiasMullie\Minify\CSS($source);
                break;
            case 'js':
                $minifier = new MatthiasMullie\Minify\JS($source);
                break;
            default:
                return true;
        }

        $minifier->minify($destiny);
        chmod($destiny, 0664);

        return true;
    }

    // Matthias Mullie's Minify class not found. I Will try by myself but this is not the best way.

    $buffer = file_get_contents($source);
    if ($buffer == false) {
        return false;
    }

    if ($minify == 'css') {
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        $buffer = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '     '], '', $buffer);
        $buffer = preg_replace(['(( )+{)', '({( )+)'], '{', $buffer);
        $buffer = preg_replace(['(( )+})', '(}( )+)', '(;( )*})'], '}', $buffer);
        $buffer = preg_replace(['(;( )+)', '(( )+;)'], ';', $buffer);
    } elseif ($minify == 'js') {
        $buffer = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/", '', $buffer);
        $buffer = str_replace(["\r\n", "\r", "\t", "\n", '  ', '    ', '     '], '', $buffer);
        $buffer = preg_replace(['(( )+\))', '(\)( )+)'], ')', $buffer);
    }

    $return = file_put_contents($destiny, $buffer);
    if ($return !== false) {
        chmod($destiny, 0664);
    }

    return $return;
}
