<?php
/*
*/
namespace Tygh;

use DateTime;

class ManageMarkupLogger
{
    private static $settings;
    public static $dir;
    private $controller = '';
    private $_function = '';
    private $file;
    private $pid;
    
    function __construct()
    {
        $args = func_get_args(); 
        $num = func_num_args();
        self::$settings = fn_manage_markup_settings();
        self::$dir = implode(DIRECTORY_SEPARATOR, array(rtrim(fn_get_files_dir_path(),'/'),'manage_markup','logs'));
        if(!empty(self::$settings['manage_markup_logging_enable'])){
            if(self::$settings['manage_markup_logging_enable'] == 'Y' && $num == 1){
                $this->controller = $args[0];
                $this->file = implode(DIRECTORY_SEPARATOR, array(self::$dir, fn_manage_markup_create_date(NULL, "Ymd").'_'.($this->controller ? $this->controller : 'general').'.log'));
                $this->pid = (string)getmypid();
                
                fn_mkdir(self::$dir);
                if (!file_exists($this->file)) {
                    fclose(fopen($this->file, 'w'));
                    chmod($this->file, 0770 );
                }
                if (!is_writeable($this->file)) {
                    // throw new \Exception($this->file ." is not a valid file path");
                    error_log("ManageMarkupLogger: ".$this->file." is not a valid file path");
                    error_log("ManageMarkupLogger: disable logging");
                    self::$settings['manage_markup_logging_enable'] = 'N';
                }
            }           
        }
        else{
            self::$settings['manage_markup_logging_enable'] = 'N';
        }
    }
    
    public function message($message, $object = NULL)
    { 
        $uid = (string)uniqid();
        $result = $message;
        $trace = false;
        if($message && gettype($message)=="object"){
                $result = $message->getMessage();
                if(defined('DEBUG_MODE')){
                    if(DEBUG_MODE){
                        $trace = $message->getTraceAsString();
                    }
                }
                $message = $result;
        }
        if(self::$settings['manage_markup_logging_enable'] == 'Y'){
            $datetime = new DateTime();
            $datetime =  $datetime->format(DATE_ATOM);
            if($message || $object !== NULL){
                if($message){
                    self::_put($uid, $datetime, $message);
                }
                if($trace){
                    self::_put($uid, $datetime, $trace);
                }
                if($object !== NULL){
                    self::_put($uid, $datetime, '' , $object);
                }
            }
        }
        return $result;
    }
    
    public function instance($function){
        $result = clone $this;
        $result->_function = $function;
        return $result;
    }
    
    private function _put($uid, $datetime, $message, $object = NULL){
        file_put_contents( 
            $this->file, 
            "#[".$this->pid. "][" .$uid. "][" .$datetime. "][" .$this->controller. "]".($this->_function ? "[" . $this->_function. "]" : "") . ($message ? " message > " . $message : "") . ($object ? " values > " . var_export($object, true) : "") . "\n", 
            FILE_APPEND
        );  
    }
}
