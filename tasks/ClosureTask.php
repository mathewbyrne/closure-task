<?php

/**
 * ClosureTask
 * 
 * A phing task for compiling Javascript files using the Google Closure 
 * compiler, http://code.google.com/closure/compiler/
 * 
 * Adapted from http://github.com/JanGorman/ClosureTask
 */


require_once 'phing/Task.php';

 
class ClosureTask extends Task
{
	
	const WHITESPACE_ONLY        = 'WHITESPACE_ONLY';
	const SIMPLE_OPTIMIZATIONS   = 'SIMPLE_OPTIMIZATIONS';
	const ADVANCED_OPTIMIZATIONS = 'ADVANCED_OPTIMIZATIONS';
	
	
	public static $compilation_levels = array(
			self::WHITESPACE_ONLY,
			self::SIMPLE_OPTIMIZATIONS,
			self::ADVANCED_OPTIMIZATIONS
		);
	
	
	const COMPILER_PATH_ENV_VARIABLE = 'CLOSURE_JAR';
	
	
	/**
	 * The level of compilation that closure should use.
	 */
	protected $_compilation_level = self::SIMPLE_OPTIMIZATIONS;
	
	
	/**
	 * The path to the closure compiler jar file.
	 */
	protected $_compiler_path = 'compiler.jar';
	
	
	/**
	 * The path to which the source files should compile to.
	 */
	protected $_target = '.';
	
	
	/**
	 * If set to true then all specified files will be merged together into
	 * a single output source, defined by $this->_target.
	 */
	protected $_merge = false;
	
	
	/**
	 * A collection of PhingFile objects to be merge compiled.
	 */
	protected $_merge_files = array();
	
	
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
	 * Sets the compilation level. Must be one of the predefined constants
	 * contained in self::$compilation_level, of a BuildException will be 
	 * thrown.
	 */
	public function setCompilationLevel($compilation_level)
	{
		if (!in_array($compilation_level, self::$compilation_levels)) {
			throw new BuildException("The compilation level '$compilation_level' is not a valid option.");
		}
		
		$this->_compilation_level = $compilation_level;
	}
	
	
	/**
	 * Sets the location of the closure compiler jar file. No checks are done
	 * here, and the build will throw an exception if the compiler jar is not
	 * found when a compile is attempted.
	 */
	public function setCompilerPath($compiler_path)
	{
		$this->_compiler_path = (string) $compiler_path;
	}
	
	
	/**
	 * The target location to which output should be direted. If merge is set
	 * then this should be a file. Otherwise it should be a target directory.
	 */
	public function setTarget(PhingFile $target)
	{
		$this->_target = $target;
	}
	
	
	/**
	 * If set to true, all source files will be merged into a single source
	 * file $this->_target.
	 */
	public function setMerge($merge)
	{
		$this->_merge = (bool) $merge;
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
	
	
	/**
	 * Initialises the compiler path from an environment variable if 
	 * available.
	 */
	public function init()
	{
		// Set the compiler_path variable from environment variable
		if ($compiler_path = getenv(self::COMPILER_PATH_ENV_VARIABLE)) {
			$this->setCompilerPath($compiler_path);
		}
	}
	
	
	/**
	 * Main entry point for the Task to run.
	 */
	public function main()
	{
		if ($this->_file === null && !count($this->_file_collections)) {
			throw new BuildException("At least one of the file attributes, a fileset element or a filelist element must be specified.");
		}
		
		// Handle individual files.
		if ($this->_file instanceof PhingFile)
		{
			if ($this->_merge) {
				$this->merge_files[] = $this->_file;
			} else {
				if ($this->_target->isDirectory()) {
					$file_name = $this->_file->getPathWithoutBase($this->project->getBaseDir());
					$target    = new PhingFile($this->_target, $file_name);
				} else {
					$target = $this->_target;
				}
				
				$this->_compile($file, $target);
			}
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
				$file = new PhingFile($path, $file_name);
				
				if ($this->_merge)
				{
					$this->merge_files[] = $file;
				}
				else
				{
					$target = new PhingFile($this->_target, $file_name);
					$this->_compile($file, $target);
				}
			}
		}
		
		if ($this->_merge) {
			$this->_compile($this->merge_files, $this->_target);
		}
	}
	
	
	/**
	 * Performs a single compile from a file or set of files to a target file.
	 */
	protected function _compile($file, $target)
	{
		// Verify that we're not about to overwrite the source file.
		if ($file->getAbsolutePath() == $target->getAbsolutePath()) {
			throw new BuildException('Source file cannot compile to itself.');
		}
		
		// Verify that the target is not a directory.
		if ($target->isDirectory()) {
			throw new BuildException($this->_merge ? 'Merge target must be a file.'
				: 'Compile target ' . $target->getPath() . ' is a directory.');
		}
		
		// Ensure that the target's containing directory exists. If not, create
		// the directory.
		$parent = $target->getParentFile();
		if ($parent instanceof PhingFile && !$parent->exists()) {
			mkdir($parent->getPath(), 0755, true);
		}
		
		// For merge operations, join a set of files together.
		if (is_array($file)) {
			$file = implode(' --js ', $file);
		}
		
		$cmd = escapeshellcmd("java -jar $this->_compiler_path --compilation_level $this->_compilation_level --js_output_file $target --js $file");
		$this->log($this->_verbose ? $cmd : 'Compiling: ' . $target);
		
		exec($cmd, $output, $return);
		
		if ($return !== 0) {
			throw new BuildException('Closure did not return success.');
		}
	}
	
}
