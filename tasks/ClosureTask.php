<?php

/**
 * ClosureTask
 * 
 * A phing task for compiling Javascript files using the Google Closure 
 * compiler. Based on http://github.com/JanGorman/ClosureTask
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
	 * A single file to compile.
	 */
	protected $_file = null;
	
	
	/**
	 * An array of file lists to compile.
	 */
	protected $_file_lists = array();
	
	
	/**
	 * An array of file sets to compile.
	 */
	protected $_file_sets = array();
	
	
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
	public function setTarget($target)
	{
		$this->_target = (string) $target;
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
		$num = array_push($this->_file_lists, new FileList());
		return $this->_file_lists[$num-1];
	}
	
	
	/**
	 * Pushes a new FileSet object onto $this->_file_lists and returns it
	 * for population.
	 */
	public function createFileSet()
	{
		$num = array_push($this->_file_sets, new FileSet());
		return $this->_file_sets[$num - 1];
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
	 * 
	 */
	public function main()
	{
		
	}
	
	
	/**
	 * Performs a single compile from a file or set of files to a target file.
	 */
	protected function _compile($files, $target)
	{
		// Create the target directory if it doesn't exist.
		if (file_exists(dirname($target)) === false) {
			mkdir(dirname($target), 0755, true);
		}
		
		if (is_array($files)) {
			$files = implode(' --js ', $files);
		}
		
		$cmd = escapeshellcmd("java -jar $this->_compiler_path --charset $this->_charset --compilation_level $this->_compilation_level --js_output_file $target --js $input");
		$this->log($this->_verbose ? $cmd : 'Compiling: ' . $target);
		exec($cmd, $output, $return);
		
		if ($return !== 0) {
			throw new BuildException('Closure did not return success.');
		}
	}
	
}
