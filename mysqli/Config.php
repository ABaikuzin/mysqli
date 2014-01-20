<?php

/**
* Settings for your database MySQL    
*/
    // session, profiler, host, username, passwd, dbname, port
    self::setConfig('local', false, 'localhost', 'root', '', 'testdb', 3306);
    self::setConfig('log', true, 'localhost', 'root', '', 'logdb', 3306);
  
?>