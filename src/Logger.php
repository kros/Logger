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
	private $defaultFileName='default-#date{Ymd}#.log';
	private $defaultLogFormat='[#date{Y-m-d H:i:s}#]\t[#level#]\t[#server{PHP_SELF}#\t#text#]';
	
	public function __construct($fileName=NULL){
		if ($fileName==null){
			$this->fileName=$fileName;
		}else{
			$this->fileName=$this->defaultFileName;
		}
	}
	
	private function composeFileName(){
		
	}
	
	private static function loadLogFileName(){
		$p = ProjectProperties::getInstance();
		if (isset($p->logFile))
			self::$fileName=$p->logFile;
		else
			self::$fileName=self::$defaulFileName;
	}
	public static function setLogFileName($name){
		if ($name!=''){
			self::$fileName=$name;
		}else{
			throw new Exception('Wrong log file name');
		}
	}
	public static function log($logText){
		if (self::$fileName=="")
			self::loadLogFileName();
		$handler = fopen(self::$fileName, 'a');
		if (flock($handler, LOCK_EX)){
			fwrite($handler, date('[Y-m-d H:i:s]' )."\t[".$_SERVER['PHP_SELF']."]\t".$logText."\n");
			flock($handler, LOCK_UN);
		}
		fclose($handler);
	} 
}
?>