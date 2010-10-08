<?php

/**
 * YuiCompressor
 * Task
 * 
 * A phing task for compiling Javascript/CSS files using the YUI Compressor,
 * http://developer.yahoo.com/yui/compressor/
 */


require_once 'FileTask.php';

 
class YuiCompressorTask extends FileTask
{
	
	const COMPRESSOR_JAR_ENV_VARIABLE = 'YUI_COMPRESSOR_JAR';
	
	
	const TYPE_JAVASCRIPT = 'js';
	const TYPE_CSS        = 'css';
	
	
	public static $types = array(self::TYPE_JAVASCRIPT, self::TYPE_CSS);
	
	
	/**
	 * The path to the compressor jar file.
	 */
	protected $_compressor_jar = 'yuicompressor.jar';
	
	
	/**
	 * This will attempt to auto detect by default, however if no match is
	 * found an exception will be thrown. In that case it's better to define
	 * the type of compression being used.
	 */
	protected $_type = null;


	/**
	 * Sets the location of the compresssor jar file. No checks are done
	 * here, and the build will throw an exception if the compress jar is not
	 * found when a compress is attempted.
	 */
	public function setCompressorJar($compressor_jar)
	{
		$this->_compressor_jar = (string) $compressor_jar;
	}
	
	
	public function setType($type)
	{
		if (!in_array($type, self::$types)) {
			throw new BuildException("Invalid type '$type' set.");
		}
		
		$this->_type = $type;
	}


	/**
	 * Initialises the compressor path from an environment variable if 
	 * available.
	 */
	public function init()
	{
		// Set the compressor_path variable from environment variable
		if ($compressor_jar = getenv(self::COMPRESSOR_JAR_ENV_VARIABLE)) {
			$this->setCompressorJar($compressor_jar);
		}
	}


	/**
	 * Main entry point for the Task to run.
	 */
	public function main()
	{
		if (!($this->_file instanceof PhingFile)  && !count($this->_file_collections)) {
			throw new BuildException("At least one of the file attributes, a fileset element or a filelist element must be specified.");
		}

		// Handle individual files.
		if ($this->_file instanceof PhingFile)
		{
			if ($this->_target->isDirectory()) {
				$target = new PhingFile($this->_target, $this->_file->getName());
			} else {
				$target = $this->_target;
			}
			
			$this->_compress($this->_file, $target);
		}

		// Handle FileSets and FileLists
		foreach ($this->_file_collections as $collection)
		{
			if ($collection instanceof FileSet) {
				$files = $collection->getDirectoryScanner($this->project)->getIncludedFiles();				
			} else {
				$files = $collection->getFiles($this->project);
			}

			$path = realpath($collection->getDir($this->project));

			foreach ($files as $file_name)
			{
				$file   = new PhingFile($path, $file_name);
				$target = new PhingFile($this->_target, $file_name);
				
				$this->_compress($file, $target);
			}
		}
	}


	/**
	 * Performs a single compress from a file or set of files to a target file.
	 */
	protected function _compress($file, $target)
	{
		// Verify that we're not about to overwrite the source file.
		if ($file->getAbsolutePath() == $target->getAbsolutePath()) {
			throw new BuildException('Source file cannot compress to itself.');
		}

		// Verify that the target is not a directory.
		if ($target->isDirectory()) {
			throw new BuildException('Compress target ' . $target->getPath() . ' is a directory.');
		}

		// Ensure that the target's containing directory exists. If not, create
		// the directory.
		$parent = $target->getParentFile();
		if ($parent instanceof PhingFile && !$parent->exists()) {
			mkdir($parent->getPath(), 0755, true);
		}
		
		// Attempt to auto-detect the path name.
		if (!$this->_type) {
			$this->setType(strtolower(array_pop(explode('.', $file->getName()))));
		}
		
		$cmd = vsprintf('java -jar %s --type %s -o %s %s', array_map('escapeshellcmd', array(
					$this->_compressor_jar,
					$this->_type,
					$target->toString(),
					$file->toString()
				)));
		$this->log($this->_verbose ? $cmd : 'Compressing: ' . $target);
		
		exec($cmd, $output, $return);
		
		if ($return !== 0) {
			throw new BuildException('YUI Compressor did not return success.');
		}
	}

}
