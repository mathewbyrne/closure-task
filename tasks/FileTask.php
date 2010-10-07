<?php

/*
	Copyright (c) 2007-2010 JB Interactive Pty. Ltd.
	All Rights Reserved
	http://www.jbinteractive.com.au/
*/


require_once 'phing/Task.php';


/**
 * This is an abstract helper for Tasks that need to collect a set of files
 * to work with, either through FileSet or FileList types, or through a File
 * param.
 */
abstract class FileTask extends Task
{
	
	/**
	 * The path to which the source files should compile to.
	 */
	protected $_target = null;
	
	
	/**
	 * A single file to compile.
	 */
	protected $_file = null;
	
	
	/**
	 * An array of FileList and FileSet objects to compile.
	 */
	protected $_file_collections = array();
	
	
	/**
	 * Whether or not to include verbose output.
	 */
	protected $_verbose = false;
	
	
	/**
	 * The target location to which output should be direted. If merge is set
	 * then this should be a file. Otherwise it should be a target directory.
	 */
	public function setTarget(PhingFile $target)
	{
		$this->_target = $target;
	}
	
	
	/**
	 * Sets the single file to be operated upon.
	 */
	public function setFile(PhingFile $file)
	{
		$this->_file = $file;
	}
	
	
	/**
	 * Pushes a new FileList object onto $this->_file_lists and returns it
	 * for population.
	 */
	function createFileList()
	{
		return $this->_createFileCollection(new FileList());
	}
	
	
	/**
	 * Pushes a new FileSet object onto $this->_file_lists and returns it
	 * for population.
	 */
	public function createFileSet()
	{
		return $this->_createFileCollection(new FileSet());
	}
	
	
	/**
	 * Abstraction for adding a File collection object.
	 */
	protected function _createFileCollection($collection)
	{
		array_push($this->_file_collections, $collection);
		return $collection;
	}
	
	
	/**
	 * If set to true, more verbose output will be given.
	 */
	public function setVerbose($verbose)
	{
		$this->_verbose = (bool) $verbose;
	}
	
}
