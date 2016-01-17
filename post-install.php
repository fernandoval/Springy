<?php
/** \file
 *  FVAL PHP Framework for Web Applications.
 *  
 *  Post Install/Update Script for Composer
 *  
 *  \copyright Copyright ₢ 2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright ₢ 2015 Fernando Val\n
 *  
 *  \brief    Post Install/Update Script for Composer
 *  \version  2.1.4
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
 *  \ingroup framework
 */
define('DS', DIRECTORY_SEPARATOR);

define('LF', "\n");
define('CS_RESET', "\033[0m");
define('CS_RED', "\033[31m");

if (!$str = file_get_contents('composer.json')) {
    echo 'Can\'t open composer.json file.';
    exit(1);
}

$composer = json_decode($str, true);
if (json_last_error() !== JSON_ERROR_NONE) {
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

// foreach($require as $component => $target) {
foreach ($composer['extra']['post-install'] as $component => $data) {
    $path = $vendorDir.DS.implode(DS, explode('/', $component));

    if (!isset($data['target'])) {
        exit(0);
    }
    $target = $data['target'];
    $noSubdirs = isset($data['ignore-subdirs']) && $data['ignore-subdirs'];
    $minify = isset($data['minify']) ? $data['minify'] : 'off';

    if (is_dir($path)) {
        if (isset($data['files'])) {
            $files = $data['files'];
        } elseif (file_exists($path.DS.'bower.json')) {
            if (!$str = file_get_contents($path.DS.'bower.json')) {
                echo CS_RED, 'Can\'t open "'.$path.DS.'bower.json" file.', LF;
                exit(1);
            }

            $bower = json_decode($str, true);
            if (!isset($bower['main'])) {
                echo CS_RED, 'Main section does not exists in "'.$path.DS.'bower.json" file.', LF;
                exit(1);
            }

            if (is_array($bower['main'])) {
                $files = $bower['main'];
            } else {
                $files = [$bower['main']];
            }
        } else {
            $files = '*';
        }

        $destination = implode(DS, explode('/', $target));
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                echo CS_RED, 'Can\'t create "'.$destination.'" directory.', LF;
                exit(1);
            }
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                $file = implode(DS, explode('/', $file));
                if ($noSubdirs) {
                    $dstFile = array_pop(explode('/', $file));
                } else {
                    $dstFile = $file;
                }
                copy_r($path.DS.$file, $destination.DS.$dstFile, $minify);
            }
        } else {
            copy_r($path, $destination, $minify);
        }
    } else {
        echo CS_RED, 'Component "'.$path.'" does not exists.', LF;
        exit(1);
    }
}

/**
 *  \brief Recursive Copy Function.
 */
function copy_r($path, $dest, $minify = 'off')
{
    if (is_dir($path)) {
        $objects = scandir($path);
        if (count($objects) > 0) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (is_dir($path.DS.$file)) {
                    copy_r($path.DS.$file, $dest.DS.$file, $minify);
                } else {
                    // Copy only if destination does not existis or source is newer
                    if (!is_file($dest.DS.$file) || filemtime($path.DS.$file) > filemtime($dest.DS.$file)) {
                        if (!is_dir($dest)) {
                            mkdir($dest, 0775, true);
                        }
                        if (!realCopy($path.DS.$file, $dest.DS.$file, $minify)) {
                            echo CS_RED, '[ERROR] Can not copy (', $path.DS.$file, ') to (', $dest.DS.$file, ')', CS_RESET, LF;
                        }
                    }
                }
            }
        }

        return true;
    } elseif (is_file($path)) {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        // Copy only if destination does not existis or source is newer
        if (!is_file($dest) || filemtime($path) > filemtime($dest)) {
            return realCopy($path, $dest, $minify);
        } else {
            return true;
        }
    } else {
        $success = false;
        $dest = dirname($dest);
        foreach (glob($path) as $filename) {
            $success = copy_r($filename, $dest.DS.basename($filename), $minify);
            if (!$success) {
                echo CS_RED, '[ERROR] Copying (', $filename, ') to (', $dest.DS.basename($filename), ')', CS_RESET, LF;
                break;
            }
        }

        return $success;
    }
}

/**
 *  \brief Copy file minifyint if necessary.
 */
function realCopy($source, $destiny, $minify = 'auto')
{
    if ($minify == 'auto') {
        $minify = (substr($source, -4) == '.css' ? 'css' : (substr($source, -3) == '.js' ? 'js' : 'off'));
    }

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
