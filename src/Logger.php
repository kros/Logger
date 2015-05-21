<?php
namespace Kros\Logger;
/**
 * Basic logger.
 * 
 * @author Oscar Ruiz <ormartin@gmail.com>
 *
 */
class Logger{
	private $fileName="";
	private $fileNameFormat="";
	private $defaultFileNameFormat='default-#date{Ymd}#.log';
	private $logFormat=null;
	private $defaultLogFormat="[#date{Y-m-d H:i:s}#]\t[#level#]\t[#server{PHP_SELF}#]\t#text#";
	private $updateFileName;
	private $minLogLevel=null;
	private $defaultMinLogLevel="ERR";
	private $defaultLogLevel="ERR";
	
	public function __construct($fileNameFormat=NULL, $updateFileName=FALSE){
		$this->updateFileName=$updateFileName;
		if ($fileNameFormat!=NULL){
			$this->fileNameFormat=$fileNameFormat;
		}else{
			$this->fileNameFormat=$this->defaultFileNameFormat;
		}
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
	
	public function setDevaultLogLevel($level){
		$this->defaultLogLevel=$level;
		return $this;
	}
	
	public function getDefaultLogLevel(){
		return $this->defaultLogLevel;
	}
	
	public function setMinLogLevel($level){
		$this->minLogLevel=$level;
		return $this;
	}
	
	public function getMinLogLevel(){
		if($this->minLogLevel==null){
			$this->minLogLevel=$this->defaultMinLogLevel;
		}
		return $this->minLogLevel;
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
		var_dump($res);
		return $res;		
	}
	
	private function loadLogFileName(){
		$this->fileName=$this->composeString($this->fileNameFormat);
	}
	
	public function log($logText, $level=NULL){
		if ($this->updateFileName){
			$this->loadLogFileName();
		}
		
		if($level==null){
			$level=$this->getDefaultLogLevel();
		}
		
		$handler = fopen($this->fileName, 'a');
		if (flock($handler, LOCK_EX)){
			//TODO compare $level with $this->logLevel
			fwrite($handler, $this->composeString($this->getLogFormat(),$logText, $level)."\n");
			flock($handler, LOCK_UN);
		}
		fclose($handler);
	} 
}
?>