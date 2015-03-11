<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *  
 *  Post Install/Update Script for Composer
 *  
 *  \copyright Copyright ₢ 2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright ₢ 2015 Fernando Val\n
 *  
 *  \brief    Post Install/Update Script for Composer
 *  \version  1.0.0
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
define('VENDOR_PATH', 'system' . DS . 'other');

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
if (!isset($composer['extra']))
	exit(0);
if (!isset($composer['extra']['post-install']))
	exit(0);

// foreach($require as $component => $target) {
foreach($composer['extra']['post-install'] as $component => $data) {
	$path = VENDOR_PATH . DS . implode(DS, explode('/', $component));
	
	if (!isset($data['target']))
		exit(0);
	$target = $data['target'];
	
	if (is_dir($path)) {
		if (isset($data['files'])) {
			$files = $data['files'];
		} elseif (file_exists($path . DS . 'bower.json')) {
			if (!$str = file_get_contents($path . DS . 'bower.json')) {
				echo 'Can\'t open "' . $path . DS . 'bower.json" file.';
				exit(1);
			}
			
			$bower = json_decode($str, true);
			if (!isset($bower['main'])) {
				echo 'Main section does not exists in "' . $path . DS . 'bower.json" file.';
				exit(1);
			}
			
			if (is_array($bower['main'])) {
				$files = $bower['main'];
			} else {
				$files = array($bower['main']);
			}
		} else {
			$files = '*';
		}
		
		$destination = implode(DS, explode('/', $target));
		if (!is_dir($destination)) {
			if (!mkdir($destination, 0755, true)) {
				echo 'Can\'t create "' . $destination . '" directory.';
				exit(1);
			}
		}
		
		if (is_array($files)) {
			foreach($files as $file) {
				$file = implode(DS, explode('/', $file));
				copy_r($path.DS.$file, $destination.DS.$file);
			}
		} else {
			copy_r($path, $destination);
		}
	} else {
		echo 'Component "' . $path . '" does not exists.';
		exit(1);
	}
}

/**
 *  \brief Recursive Copy Function
 */
function copy_r($path, $dest)
{
	if (is_dir($path)) {
		@mkdir($dest);
		$objects = scandir($path);
		if (sizeof($objects) > 0) {
			foreach ($objects as $file) {
				if ($file == "." || $file == "..")
					continue;
				
				if (is_dir($path.DS.$file)) {
					copy_r($path.DS.$file, $dest.DS.$file);
				} else {
					copy($path.DS.$file, $dest.DS.$file);
				}
			}
		}
		return true;
	}
	elseif (is_file($path)) {
		$dir = dirname($dest);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return copy($path, $dest);
	}
	else {
		return false;
	}
}