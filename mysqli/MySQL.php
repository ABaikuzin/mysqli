<?php
    /** 
    * PHP5 OOP DB Connection Class with MySQLi using Singleton, which support multiple connections.
    * MySQLi connection avaliable to all MVC modules.
    * 
    * 
    * @example    $mysqli = MySQL::getConnect();
    *             $mysqli = MySQL::getConnect('log');
    *             $mysqli->query();
    * 
    * 
    * @author    Maxim Baikuzin
    *            http://www.baikuzin.com 
    *            
    *            I WANT A JOB! Please e-mail me at <maxim@baikuzin.com> 
    *            
    * @version   01/20/2014
    * @license   GNU GPLv3
    */

    
    mysqli_report(MYSQLI_REPORT_STRICT);
    
    
    class MySQL {
        
        /**
        * Connection instance
        */
        private static $_instance = array();
        
        
        /**
        * Configuration values
        * @var array ( db => (host, username, passwd, dbname, port) ) 
        */
        private static $_config = array();

        
        private function __construct() {}
        
        
        /**
        * Read config from file
        */
        private static function readConfig() {
            
            if (is_file(__DIR__ . '/Config.php')) {
                require(__DIR__ . '/Config.php');
            }
            
            
            if (is_file(dirname(__DIR__) . '/Config/MySQL.php')) {
                require(dirname(__DIR__) . '/Config/MySQL.php');
            }
            
            return;
        }   
        
        
        /**
        * Get a configuration value
        * @param string $_db
        * @return array
        */
        private static function getConfig($session) {
            if (empty(self::$_config)) {
                self::readConfig();
            }   
            if (isset(self::$_config[$session])) {
                return self::$_config[$session];
            }
            return null;
        }


        /**
        * Set a configuration value
        * @param string $session
        * @param string $profiler
        * @param string $host
        * @param string $username
        * @param string $passwd
        * @param string $dbname
        * @param string $port
        */
        private static function setConfig($session = 'local', $profiler = false, $host = 'localhost', $username = 'root', $passwd = '', $dbname = '', $port = '3306') {
            self::$_config[$session] = array('profiler' => $profiler, 'host' => $host, 'username' => $username, 'passwd' => $passwd, 'dbname' => $dbname, 'port' => $port);
        }
                  
                                       
        /**
        * Get connection
        * @param string local, replica, log or etc
        */
        public static function getConnect($session = 'local') {
            // Connect / Reconnect
            if (!isset(self::$_instance[$session]) OR null === self::$_instance[$session] OR self::$_instance[$session]->ping() === false) {
                try {
                    $config = self::getConfig($session);
                    if ($config['profiler'] === false) {
                        echo "1";
                        self::$_instance[$session] = new mysqli($config['host'], $config['username'], $config['passwd'], $config['dbname'], $config['port']);
                    }
                    else {
                        require_once(__DIR__ . '/MySQLprofiler.php');
                        self::$_instance[$session] = new MySQLprofiler($config['host'], $config['username'], $config['passwd'], $config['dbname'], $config['port']);
                    }
                    
                }
                catch (Exception $e) {
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
                    fwrite($fp, "[{$date}] {$e->getMessage()} \r\n");
                    fclose($fp);
                    exit;
                }
            }
            
                
            return self::$_instance[$session]; 
                                                  
        }
        
               
        private function __clone() { } 
        private function __wakeup(){ }        
        
 
    }


?>