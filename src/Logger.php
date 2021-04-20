<?php
namespace Kros\Logger;
/**
 * Basic logger.
 * 
 * @author Oscar Ruiz <ormartin@gmail.com>
 *
 */
class Logger{
	/** log levels and priority*/
	public static $levels=[
		'OFF'=>0, 
		'FATAL'=>1, 
		'ERROR'=>2,
		'WARN'=>3,
		'INFO'=>4,
		'DEBUG'=>5,
		'TRACE'=>6,
		'ALL'=>7
	];
	/** File name */
	private $fileName="";
	
	/** File name format
	 * 
	 * You can include tokens like:
	 *    #date{format}#
	 *    #server{$_SERVER_index_key}#
	 *    #level#
	 *    
	 * @example default-#date{Ymd}#.log   ---->   default-20150621.log
	 * @example log_#server{PHP_SELF}#.log   ---->    log_index.php.log
	 * 
	 */
	private $fileNameFormat="";
	
	/** File name format in case no one is provided */
	private $defaultFileNameFormat='default-#date{Ymd}#.log';
	
	/** 
	 * Log format for every log entry
	 * 
	 * You can include tokens like:
	 *    #date{format}#
	 *    #server{$_SERVER_index_key}#
	 *    #level#
	 *    #text#
	 *    #file# -> fileName where log function was called
	 *    #line# -> line numer where log function was called
	 *    
	 * Remember to include the #text# token . That is the token where the log text is going to be placed.
	*/
	private $logFormat=null;
	
	/** Default log format if no one is provided */
	private $defaultLogFormat="[#date{Y-m-d H:i:s}#]\t[#level#]\t[#file#]\t[#line#]\t#text#";
	
	/** If TRUE, the file name is calculated every time */
	private $updateFileName;
	
	/** Minimun log level to register */
	private $logLevel=null;
	
	/** Minimun log level if no one is provided */
	private $defaultLogLevel="ERROR";	
	
	/**
	* Constuctor method.
	*
	* @param $props Properties array to configure logger. Available properties are:
	*               fileNameFormat: Format of the file name.
	*               updateFileName: Set if the file name change in time.
	*               logLevel: Log level to trace.
	*               logFormat: Log entry format.
	*               fileName: File name. If provided, fileNameFormat and updateFileName are ignored.
	*/
	public function __construct($props=NULL){
		if ($props==null){
			$props=[];
		}else if (is_string($props)){
			$props=['fileNameFormat'=>$props];
		}else if (!is_array($props)){
			throw new \Exception('Wrong properties array provided');
		}
		$this->updateFileName=(array_key_exists('updateFileName', $props) && !array_key_exists('fileName', $props)?filter_var($props['updateFileName'], FILTER_VALIDATE_BOOLEAN):FALSE);
		if (array_key_exists('fileName', $props)){
			$this->fileNameFormat=$props['fileName'];
		}else{
			$this->fileNameFormat=(array_key_exists('fileNameFormat', $props)?$props['fileNameFormat']:$this->defaultFileNameFormat);
		}
		$this->setLogLevel((array_key_exists('logLevel', $props)?$props['logLevel']:$this->getLogLevel()));
		$this->setLogFormat((array_key_exists('logFormat', $props)?$props['logFormat']:$this->getLogFormat()));
		$this->loadLogFileName();
	}

	public function setLogFormat($format){
		$this->logFormat=$format;
		return $this;
	}
	
	public function getLogFormat(){
		if($this->logFormat==null){
			$this->logFormat=$this->defaultLogFormat;
		}
		return $this->logFormat;
	}
		
	public function getDefaultLogLevel(){
		return $this->defaultLogLevel;
	}
	
	public function setLogLevel($level){
		$uLevel=strtoupper($level);
		if (!array_key_exists(strtoupper($uLevel), Logger::$levels)){
			throw new \Exception ("Log level unknown '$uLevel'");
		}

		$this->logLevel=$uLevel;
		return $this;
	}
	
	public function getLogLevel(){
		if($this->logLevel==null){
			$this->logLevel=$this->defaultLogLevel;
		}
		return $this->logLevel;
	}
	
	public function getFileName(){
		return $this->fileName;
	}
	
	private function composeString($format, $text='', $level=''){
		$res=$format;

		//date token
		$pattern='/#date{([\w:\s\/.+\-]*)}#/';
		while (preg_match_all($pattern, $res, $out)) {
			$res=str_replace($out[0][0], date($out[1][0]), $res);
		}

		//server token
		$pattern='/#server{([\w]*)}#/';
		while (preg_match_all($pattern, $res, $out)){
			$res=str_replace($out[0][0], $_SERVER[$out[1][0]], $res);
		}

		//text token
		$pattern='/#text#/';
		if (preg_match_all($pattern, $res, $out)){
			$res=str_replace($out[0][0], $text, $res);
		}
		
		//level token
		$pattern='/#level#/';
		while (preg_match_all($pattern, $res, $out)){
			$res=str_replace($out[0][0], $level, $res);
		}
		[$file,$line]=$this->getCallerInfo();
		//file token
		$pattern='/#file#/';
		while (preg_match_all($pattern, $res, $out)){
			$res=str_replace($out[0][0], $file, $res);
		}
		//line token
		$pattern='/#line#/';
		while (preg_match_all($pattern, $res, $out)){
			$res=str_replace($out[0][0], $line, $res);
		}

		return $res;		
	}

	private function getCallerInfo(){
		$debug=debug_backtrace();
		foreach($debug as $call){
			if ($call['file']!=__FILE__){
				return [array_pop(explode(DIRECTORY_SEPARATOR,$call['file'])), $call['line']];
			}
		}
		return null;
	}
	
	private function loadLogFileName(){
		$this->fileName=$this->composeString($this->fileNameFormat);
	}
	
	public function log($logText, $level=NULL){

		if($level==null){
			throw new \Exception("Log level must be provided");
		}else{
			$uLevel=strtoupper($level);
			if (!array_key_exists(strtoupper($uLevel), Logger::$levels)){
				throw new \Exception ("Log level unknown '$uLevel'");
			}
		}

		//only write log if proper level
		if (Logger::$levels[$level]>Logger::$levels[$this->getLogLevel()]){
			return false;
		}
		if ($this->updateFileName){
			$this->loadLogFileName();
		}	
		
		$handler = fopen($this->fileName, 'a');
		if (flock($handler, LOCK_EX)){
			fwrite($handler, $this->composeString($this->getLogFormat(),$logText, $level)."\n");
			flock($handler, LOCK_UN);
		}
		fclose($handler);
		return true;
	} 
	
	/**
	* Use any log level name as a method to log in that log level
	*/
	public function __call($name, $arguments){
		$uName=strtoupper($name);
		if (array_key_exists(strtoupper($uName), Logger::$levels)){
			if (sizeof($arguments)>0){
				$logText=$arguments[0];
				$this->log($logText, $uName);
			}
		}else{
			throw new \Exception ("Methond not found $name" );
		}
	}
}

?>
