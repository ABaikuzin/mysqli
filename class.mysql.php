<?php
    /** 
    * PHP5 OOP DB Connection Class with MySQLi using Singleton, which support multiple connections.
    * MySQLi connection avaliable to all MVC modules.
    * 
    * 
    * @example	$mysqli = MySQL::get_connect();
    * 			$mysqli = MySQL::get_connect('log');
    * 
    * 			MySQL::profiler_on();
    * 			MySQL::profiler_getLog();
    * 			MySQL::profiler_getDecorLog();
    * 
    * 
    * @author	Maxim Baikuzin <maxim@baikuzin.com> 
    * 			http://www.baikuzin.com
    * @version	17.11.2013
    * @license 	GNU GPLv3
    */

    require_once(__DIR__ . '/config_db.php');
    
    class MySQL extends mysqli implements config_db {
        private static $_instance = array();
        private static $_profiler;
        private $_db;

        private function __construct($_db){
            $this->_db = $_db;
            if (self::$_profiler) {
            	$this->profiler_connect_open();
            }
            else {
            	$this->connect_open();
            }
        }                                  
        /**
        * Get connection
        * 
        * @param mixed local, replica, log or etc
        */
        public static function get_connect($_db = 'local') {
        	// Connect / Reconnect
            if (!isset(self::$_instance[$_db]) OR null === self::$_instance[$_db] OR self::$_instance[$_db]->ping() === false) {
            	self::$_instance[$_db] = new self($_db);
            }                         
            return self::$_instance[$_db];                       
        }
        private function connect_open() {
            @parent::__construct(constant("self::DB_HOST_{$this->_db}"), constant("self::DB_USER_{$this->_db}"), constant("self::DB_PASS_{$this->_db}"), constant("self::DB_NAME_{$this->_db}"));
            if ($this->connect_error) self::error_503($this->connect_error);         
        }   
        public function query($Query) {
            if (self::$_profiler) {
            	$this->profiler_query($Query);
            }
            else {
            	parent::query($Query);
            }        	       	                       
        }                
        private function __clone() { } 
        private function __wakeup(){ }        
        private function error_503($error) {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 300'); 
            echo '        
            <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
            <html><head>
            <title>503 Service Temporarily Unavailable</title>
            </head><body>
            <h1>Service Temporarily Unavailable</h1>
            <p>The server is temporarily unable to service your
            request due to maintenance downtime or capacity
            problems. Please try again later.</p>
            </body></html>
            ';        
            //
            $date = date('Y-m-d H:i:s');
            $file_mysql_error = dirname(__DIR__) . '/error_log';
            // 
            $fp = fopen($file_mysql_error, 'a');
            fwrite($fp, "[{$date}] {$error} \r\n");
            fclose($fp);
            exit;
        }  
        
        // PROFILER'S METHODS

        /**
        * Turn on MySQL Profiler
        */
        public static function profiler_on() {
        	self::$_profiler = new MySQL_Profiler();
        }         
        private function profiler_connect_open() {
        	$real_time = microtime(true);	
            @parent::__construct(constant("self::DB_HOST_{$this->_db}"), constant("self::DB_USER_{$this->_db}"), constant("self::DB_PASS_{$this->_db}"), constant("self::DB_NAME_{$this->_db}"));
            if ($this->connect_error) self::error_503($this->connect_error);   
			$real_time = number_format(microtime(true) - $real_time, 7, '.', '');
			self::$_profiler->addToLog(constant("self::DB_NAME_{$this->_db}"), "CONNECT ".constant("self::DB_USER_{$this->_db}").":password@".constant("self::DB_HOST_{$this->_db}")."/".constant("self::DB_NAME_{$this->_db}")."", $real_time, 0, $this->connect_error, $this->connect_errno, '', '');
        }                                   
		private function profiler_query($Query) {
        	$real_time = microtime(true);	
            parent::query($Query);
			$real_time = number_format(microtime(true) - $real_time, 7, '.', '');
			$error = $this->error;
			$errcode = $this->errno;
			$affected_rows = $this->affected_rows;
			//
			$Query_Explain = $Query; 			
			//DELETE [table] FROM tables ... => SELECT * FROM tables
			$Query_Explain = preg_replace('/^(\\s*DELETE\\s.*?FROM)/ism', "SELECT * FROM\n", $Query_Explain);
			//UPDATE table SET data [WHERE...] => SELECT * FROM table [WHERE...]
			$Query_Explain = preg_replace('/^(\\s*UPDATE\\s+)/ism', "SELECT * FROM\n", $Query_Explain);
			$Query_Explain = preg_replace('/(\\s+SET\\s+.*?WHERE)/ism', "\nWHERE\n", $Query_Explain);
			$Query_Explain = preg_replace('/(\\s+SET\\s+.*?)/ism', "", $Query_Explain);
			//Trying to extract SELECT from INSERT/REPLACE INTO ... AS or CREATE TABLE ... AS      
			$matches = array();
			$explain = array();
			$rewritten = array();
			if (preg_match('/(SELECT\\s.*?FROM\\s.*$)/ism', $Query_Explain, $matches)) {
				//Got SELECT, now do EXPLAIN SELECT 
				//$matches[1] = str_replace('SELECT', 'SELECT SQL_NO_CACHE', $matches[1]);
				$result = parent::query("EXPLAIN EXTENDED \n" . $matches[1]);    // todo SQL_NO_CACHE
				if (false !== $result) {
					while ($array = $result->fetch_assoc()) {
						$explain[] = $array;
					}
					$result->close();
					$result = parent::query("SHOW WARNINGS");
					if (false !== $result) {
						while ($array = $result->fetch_assoc()) {
							$rewritten[] = $array['Message'];
						}
						$result->close();
					}
				}
			}  
            self::$_profiler->addToLog(constant("self::DB_NAME_{$this->_db}"), $Query, $real_time, $affected_rows, $error, $errcode, $explain, $rewritten);
		}          
        public static function profiler_getLog() {
        	return self::$_profiler->getLog();
        }                             
        public static function profiler_getDecorLog() {
        	return self::$_profiler->getDecorLog();
        }      
    }


?>