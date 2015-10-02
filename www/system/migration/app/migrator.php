<?php
/** \file
 *  FVAL PHP Framework for Web Applications
 *
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val\n
 *  
 *  \brief     Script da classe de acesso a banco de dados
 *  \warning   Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version   0.1 beta
 *  \author    Fernando Val  - fernando.val@gmail.com
 *  \ingroup   framework
 */

namespace FW;

use FW\URI;
use FW\DB;

class Migrator extends DB
{
	const MSG_INFORMATION = 0;
	const MSG_WARNING = 1;
	const MSG_ERROR = 2;
	
	const DIR_UP = 1;
	const DIR_DOWN = -1;
	
	private $mgPath = "";
	private $revPath = "";
	private $revFile = "";
	private $command = null;
	private $target = null;
	private $parameter = null;
	private $error = false;
	
	/**
	 *  \brief Initiate the class
	 */
	public function __construct()
	{
		$this->mgPath = $GLOBALS['SYSTEM']['MIGRATION_PATH'];
		$this->revPath = $this->mgPath . DS . 'revisions' . DS;
		$this->revFile = $this->revPath . 'current';
		
		$this->disableReportError();
		
		parent::__construct();
	}
	
	/**
	 *  \brief Run the migrator
	 */
	public function run()
	{
		ob_end_flush();
		$this->output(
			array(
				'FVAL PHP Framework for Web Applications - Database Migration Tool v0.1' => self::MSG_INFORMATION,
				'----------------------------------------------------------------------' => self::MSG_INFORMATION,
				'Application: ' . $GLOBALS['SYSTEM']['SYSTEM_NAME'].' v'.$GLOBALS['SYSTEM']['SYSTEM_VERSION'] => self::MSG_INFORMATION,
			)
		);
		
		// Verify permissions over revision control file
		$currentRevision = $this->getCurrentRevision();
		$this->setCurrentRevision( $currentRevision );
		if ($this->error !== false) {
			$this->systemAbort($this->error);
		}
		
		$this->output('Current Revision: ' . $currentRevision );
		
		// Get all revisions and check what is new
		$allRevisions = $this->getRevisions();
		$revisions = array();
		foreach ($allRevisions as $revision) {
			if (intval($revision) > $currentRevision) {
				$revisions[] = $revision;
			}
		}
		
		$this->getArguments();
		
		if ($this->command == 'status') {
			if ( empty($revisions) ) {
				$this->output('No revisions to be applied');
			} else {
				$this->output(count($revisions) . ' revision(s) to be applied');
			}
		} elseif ($this->command == 'migrate') {
			$this->migrate($revisions);
		} elseif ($this->command == 'rollback') {
			unset($revisions);
			$revisions = array();
			foreach ($allRevisions as $revision) {
				if (intval($revision) <= $currentRevision) {
					$revisions[] = $revision;
				}
			}
			rsort($revisions, SORT_NUMERIC);
			$this->revert($revisions);
		} elseif ($this->command == 'help') {
			$this->output('Not implemented yet!');
		} else {
			$this->output('Invalid command!', self::MSG_WARNING);
		}
		
		$this->output('');
		$this->output('Done!');
		exit (0);
	}
	
	/**
	 *  \brief Get arguments passed to the program
	 */
	private function getArguments()
	{
		$args = getopt('hsmr', array('help', 'revision:'));
		
		if ( $this->validateArgument($args, array('m'), true) ) {
			$this->command = 'migrate';
		}
		if ( $this->validateArgument($args, array('r'), true) ) {
			$this->command = 'rollback';
		}
		if ( $this->validateArgument($args, array('s'), true) ) {
			$this->command = 'status';
		}
		if ( $this->validateArgument($args, array('h', 'help'), true) ) {
			$this->command = 'help';
		}
		if ( $this->validateArgument($args, array('revision')) ) {
			$this->target = $this->parameter;
			$this->parameter = null;
		}
	}
	
	/**
	 *  \brief Verify if two or more incompatible arguments was passed
	 */
	private function validateArgument($arguments, $list, $isExclusive=false)
	{
		$count = 0;
		foreach ($list as $arg) {
			if (isset($arguments[$arg])) {
				if ($isExclusive && isset($this->command)) {
					$this->systemAbort(
						array(
							'Syntax error!' => self::MSG_ERROR,
							'You cannot execute two or concurrent commands at a time.' => self::MSG_INFORMATION
						)
					);
				}
				$count++;
				
				if ($arguments[$arg] !== false) {
					$this->parameter = $arguments[$arg];
				}
			}
			
			if ($count > 1) {
				$this->systemAbort(
					array(
						'Syntax error!' => self::MSG_ERROR,
						'Please, use only short or long form of a parameter, not both.' => self::MSG_INFORMATION
					)
				);
			}
		}
		
		return ($count > 0);
	}
	
	/**
	 *  \brief Execute migrations
	 */
	private function migrate($revisions)
	{
		if ( is_null($this->target) ) {
			$target = -1;
		} elseif ( !is_numeric($this->target) ) {
			$this->systemAbort(
				array(
					'Syntax error!' => self::MSG_ERROR,
					'Invalid revision number.' => self::MSG_INFORMATION
				)
			);
		} else {
			$target = intval($this->target);
		}
		
		$this->output('');
		$this->output('Starting migration proccess.');
		
		foreach ($revisions as $revision) {
			if ($revision > $target && $target >= 0) {
				return;
			}
			
			$this->output('');
			$this->output('Applying revision #'.$revision);
			$files = $this->getRevisionFiles($revision, self::DIR_UP);
			
			$error = false;
			if (empty($files)) {
				$this->output('Nothing to do at revision #'.$revision, self::MSG_WARNING);
			} else {
				foreach ($files as $file) {
					$this->output('Running script '.$file);
					if ( !$this->runFile($this->getScriptsPath($revision, self::DIR_UP) . DS . $file) ) {
						if (is_array($this->error)) {
							$this->output($this->error[2], self::MSG_ERROR);
						} else {
							$this->output($this->error, self::MSG_ERROR);
						}
						$error = true;
					}
				}
			}
			
			$this->output('Revision #' . $revision . ' applied with' . ($error ? "" : 'out') . ' errors', $error ? self::MSG_WARNING : self::MSG_INFORMATION);
			
			$this->setCurrentRevision( $revision );
			
			if ($error) {
				$this->systemAbort();
			}
		}
	}

	/**
	 *  \brief Execute migrations
	 */
	private function revert($revisions)
	{
		if ( is_null($this->target) ) {
			$target = intval( count($revisions) ? $revisions[0] : 0 );
		} elseif ( !is_numeric($this->target) ) {
			$this->systemAbort(
				array(
					'Syntax error!' => self::MSG_ERROR,
					'Invalid revision number.' => self::MSG_INFORMATION
				)
			);
		} else {
			$target = intval($this->target);
		}
		
		$this->output('');
		$this->output('Starting rollback proccess.');
		
		foreach ($revisions as $revision) {
			if ($revision <= $target) {
				return;
			}
			
			$this->output('');
			$this->output('Applying rollback of revision #'.$revision);
			$files = $this->getRevisionFiles($revision, self::DIR_DOWN);
			
			$error = false;
			if (empty($files)) {
				$this->output('Nothing to do at revision #'.$revision, self::MSG_WARNING);
			} else {
				foreach ($files as $file) {
					$this->output('Running script '.$file);
					if ( !$this->runFile($this->getScriptsPath($revision, self::DIR_DOWN) . DS . $file) ) {
						if (is_array($this->error)) {
							$this->output($this->error[2], self::MSG_ERROR);
						} else {
							$this->output($this->error, self::MSG_ERROR);
						}
						$error = true;
					}
				}
			}
			
			$this->output('Rollback to revision #' . $revision . ' applied with' . ($error ? "" : 'out') . ' errors', $error ? self::MSG_WARNING : self::MSG_INFORMATION);
			
			$this->setCurrentRevision( $revision );
			
			if ($error) {
				$this->systemAbort();
			}
		}
	}

	/**
	 *  \brief Get the current revision from control file
	 */
	private function getCurrentRevision()
	{
		// Check if revision control file exists and is writable
		if (file_exists($this->revFile)) {
			return intval(file_get_contents($this->revFile));
		}
		
		return 0;
	}

	/**
	 *  \brief Write current revision to control file
	 */
    private function setCurrentRevision($revision)
    {
        if (!@file_put_contents($this->revFile, $revision)) {
            $this->setError('Cannot write revision file');
        }
    }
	
	/**
	 *  \brief Get all revision directories
	 */
    private function getRevisions()
    {
        $return = array();

        foreach (new \DirectoryIterator($this->revPath) as $file) {
            if ($file->isDir() && !$file->isDot() && is_numeric($file->getBasename())) {
                $return[] = $file->getBasename();
            }
        }

        sort($return, SORT_NUMERIC);

        return $return;
    }
	
	/**
	 *  \brief Get script files from revision directory
	 */
    private function getRevisionFiles($revision, $direction)
    {
        $dir = $this->getScriptsPath($revision, $direction);
		
		if ( !is_dir($dir) ) {
			$this->systemAbort('Directory with ' . ($direction == self::DIR_UP ? 'MIGRATE' : 'ROLLBACK') . ' scripts for revision #' . $revision . ' not found.');
		}

        $return = array();
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'sql') {
                $return[] = $file->getBasename();
            }
        }

		if (self::DIR_UP) {
			sort($return, SORT_REGULAR);
		} else {
			rsort($return, SORT_REGULAR);
		}
        return $return;
    }
	
	/**
	 *  \brief Get the path of revisions' scripts
	 */
	private function getScriptsPath($revision, $direction)
	{
        return $this->revPath . DS . $revision . DS . $this->getScriptsSubdir($direction);
	}
	
	/**
	 *  \brief Get name of the scripts' subdirectory
	 */
	private function getScriptsSubdir($direction)
	{
		if ($direction == self::DIR_UP) {
			return 'migrate';
		} elseif ($direction == self::DIR_DOWN) {
			return 'rollback';
		}
		
		$this->systemAbort('Direction undefined');
	}
	
	/**
	 *  \brief Run a revision file
	 */
    private function runFile($file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'sql':
                $content = file_get_contents($file);
                if ($content === false) {
                    $this->setError( __("Cannot open file #{file}", array('file' => "<strong>$file</strong>")) );
                    return false;
                }

                try {
					if ( !$this->execute($content) ) {
						$this->setError( $this->statmentErrorInfo() );
						return false;
					}
					
                    return true;
                } catch (Exception $e) {
                    $this->setError("[{$e->getCode()}] {$e->getMessage()} in <strong>$file</strong>");
                }
                break;
        }

        return false;
    }
	
	/**
	 *  \brief Print a message to output device
	 */
	private function output($message, $type = 0)
	{
		if (is_array($message)) {
			foreach ($message as $part => $type) {
				$this->output($part, $type);
			}
		} else {
			if (PHP_SAPI === 'cli' || defined('STDIN')) {
				switch ($type) {
					case self::MSG_INFORMATION:
						$msgTemplate = '%s';
						break;
					case self::MSG_WARNING:
						$msgTemplate = 'WARNING: %s';
						break;
					case self::MSG_ERROR:
						$msgTemplate = 'ERROR: %s';
						break;
					default:
						$msgTemplate = '%s';
				}
			} else {
				switch ($type) {
					case self::MSG_INFORMATION:
						$msgTemplate = '%s';
						break;
					case self::MSG_WARNING:
						$msgTemplate = '<span style="color: #FFA700">WARNING:</span> %s';
						break;
					case self::MSG_ERROR:
						$msgTemplate = '<span style="color: #F00"><strong>ERROR</strong>:</span> %s';
						break;
					default:
						$msgTemplate = '%s';
				}
			}
			
			printf($msgTemplate, $message);
			
			if (PHP_SAPI === 'cli' || defined('STDIN')) {
				echo "\n";
			} else {
				echo '<br>';
			}
		}
	}
	
	/**
	 *  \brief Set a sistem error message
	 */
	private function setError($error)
	{
		$this->error = $error;
	}
	
	private function systemAbort($message = false)
	{
		if ($message) {
			$this->output($message, self::MSG_ERROR);
		}
		
		$this->output("");
		$this->output('System aborted!');
		exit (1);
	}
}