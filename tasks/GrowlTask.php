<?php


require_once 'phing/Task.php';


/**
 * Uses the growlnotify command line tool to send an message to Growl.
 * 
 * e.g. growlnotify phing -m 'Deploy successful'
 */
class GrowlTask extends Task
{
	
	protected $_sender = 'phing';
	
	
	protected $_message = '';
	
	
	protected $_log = false;
	
	
	public function setSender($sender)
	{
		$this->_sender = (string) $sender;
	}
	
	
	public function setMessage($message)
	{
		$this->_message = (string) $message;
	}
	
	
	/**
	 * Support <growl>Message</growl> syntax.
	 */
    public function addText($text)
	{
		$this->setMessage($text);
	}
	
	
	public function setLog($log)
	{
		$this->_log = (boolean) $log;
	}
	
	
	public function main()
	{
		exec(sprintf('growlnotify %s -m %s', escapeshellarg($this->_sender),
			escapeshellarg($this->_message)));
		
		if ($this->_log) {
			$this->log($this->_message);
		}
	}
	
}
