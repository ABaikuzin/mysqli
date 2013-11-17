PHP MySQLi Class (Multiple connections / PHP MySQL Profiler)
----------
PHP5 OOP DB Connection Class with MySQLi using Singleton, which support multiple connections.
MySQLi connection avaliable to all MVC modules.

#### Use ####
Setting up config vars into **config_db.php**

	class config_db {
		// DB #1
		const DB_HOST_local = 'localhost';
		const DB_USER_local = 'user';  
		const DB_PASS_local = 'pass'; 
		const DB_NAME_local = 'db1'; 
	
		// DB #2
		const DB_HOST_log = 'localhost';
		const DB_USER_log = 'user';  
		const DB_PASS_log = 'pass'; 
		const DB_NAME_log = 'db2';
	}
  
Insert this into your php script

    require_once(__DIR__ . '/class.mysql.php');
    require_once(__DIR__ . '/class.mysql_profiler.php');
	
	// Turn On MySQL Profiler
    MySQL::profiler_on();
    
    $mysqli = MySQL::get_connect();				// DB #1 (Default - local)
    $mysqli_log = MySQL::get_connect('log');	// DB #2, If you need multiple connections
	
	$Query = "
            	SELECT
	                * 
                FROM 
	                `m4`
	            WHERE 
	            	`m4_id` = '100'
	            LIMIT
	            	1
	            ;";
	$result = $mysqli->query($Query);     
		
	$Query = "
            	SELECT
	                * 
                FROM 
	                `parser`
	            ORDER BY 
	            	`id` DESC
	            ;";
	$result = $mysqli_log->query($Query);    

	
    print_r(MySQL::profiler_getDecorLog());		// Get decorated table
	// print_r(MySQL::profiler_getLog());	// Get simple array


#### PHP MySQL Profiler ####
PHP MySQL profiler allows you to trace SQL queries made in PHP code, the number of times they are called and the break down of their open, execution, prepare and fetch results times.

![PHP MySQL Profiler](http://www.baikuzin.com/GitHub/mysqli/mysqli_profiler.gif)

Analyze the code profiling results in the profiler window. PHP SQL profiler provides the following options to use for the inspection of the results and php code navigation:  
1. **Location** - Shows the code lines where SQL query is executed.   
2. **Connection** - Shows the time spent to open connection to the database  
3. **Time** - Shows time spent to fetch this SQL results  
4. **Info** - Shows the columns - select_type, type, possible_keys, key, key_len, ref, rows, filtered, extra. 
 