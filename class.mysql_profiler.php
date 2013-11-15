<?php
    /** 
    * PHP MySQL Profiler
    * PHP MySQL profiler allows you to trace SQL queries made in PHP code, the number of times they are called 
    * and the break down of their open, execution, prepare and fetch results times.
    * 
    * 
    * @author	Maxim Baikuzin <maxim@baikuzin.com> 
    * 			http://www.baikuzin.com
    * @version	15.11.2013
    * @license 	GNU GPLv3
    */

    
    class MySQL_Profiler {
        private $log = array();

        public function __construct(){
        } 
        /**
        * Add entry to Log
        *  
        * @param mixed $dbname	- Database on which the query was executed
        * @param mixed $query	- Query
        * @param mixed $time	- Real time
        * @param mixed $count	- Number of rows affected/selected, if applicable
        * @param mixed $error 	- Error Message from MySQL
        * @param mixed $errcode	- Error Code from MySQL
        * @param mixed $explain	- EXPLAINed query
        * @param mixed $rewritten-
        */
        public function addToLog($dbname, $query, $time, $count, $error, $errcode, $explain, $rewritten){
        	$this->log[] = array(
        						'dbname' => $dbname,
        						'query' => $query,
        						'time' => $time,
        						'count' => $count,
        						'error' => $error,
        						'errcode' => $errcode,
        						'explain' => $explain,
        						'rewritten' => $rewritten,
        						'backtrace' => $this->getBackTrace()
        						);
        }                                        
        public function getLog(){
        	return $this->log;
        }
		private function getBackTrace(){
			$backtrace = debug_backtrace();
			$len_backtrace = count($backtrace);
			$backtrace_for_log = '';
			for ($i=0; $i<$len_backtrace; ++$i) {
				if (!isset($backtrace[$i]['file'])) {
					$backtrace[$i]['file'] = '(undefined)';
				}
				if (!isset($backtrace[$i]['line'])) {
					$backtrace[$i]['line'] = '(undefined)';
				}
				if (!isset($backtrace[$i]['class'])) {
					$backtrace[$i]['class'] = '';
				}
				if (!isset($backtrace[$i]['function'])) {
					$backtrace[$i]['function'] = '';
				}
				if (defined('ABSPATH') && !strncmp(ABSPATH, $backtrace[$i]['file'], strlen(ABSPATH))) {
					$backtrace[$i]['file'] = substr($backtrace[$i]['file'], strlen(ABSPATH));
				}
				//This entry can be ignored - it is a call from class Prfiler or class MySQL
                if (strpos($backtrace[$i]['file'], '/class.mysql') === false) {
					$backtrace_for_log .= $backtrace[$i]['file'] . ', ' . $backtrace[$i]['line'];
					if ($backtrace[$i]['class'] || $backtrace[$i]['function']) {
						$backtrace_for_log .= ' (';
						if ($backtrace[$i]['class']) {
							$backtrace_for_log .= $backtrace[$i]['class'] . '::';
						}
						$backtrace_for_log .= $backtrace[$i]['function'] . ')';
					}
                $backtrace_for_log .= "\n";
                }
			}
			//
			$backtrace_for_log = substr($backtrace_for_log, 0, -1);
			if (empty($backtrace_for_log)) {
				$backtrace_for_log = 'undefined location';
			}
			return $backtrace_for_log;
		}
		public function getDecorLog()
		{
			$cnt = count($this->log);
			if (!$cnt) return '';
			$time_total = 0;
			$decor_log = '';
			//
			foreach ($this->log as $no => $entry) {
				$query = $entry['query'];
				$dbname = $entry['dbname'];
				$errcode = $entry['errcode'];
				$error = $entry['error'];
				$count = $entry['count'];
				$time = $entry['time'];
				$backtrace = $entry['backtrace'];
				$explain = $entry['explain'];
				$time_total += $time;
				//
				$query_td = '';
				if ($errcode) {
					$errcode = '<span style="color: red; font-weight: bold">' . intval($errcode) . '</span>';
					$query_td .= '<span style="color: red">' . $query . '</span>';
					$query_td .= "<div><i style=\"color: #F00; white-space: normal;\">" . $error . "</i></div>";
				}
				else {
					$query_td .= "<span class=\"query\">$query</span>";
				}
				$query_td .= "<span class=\"query\">/*$dbname*/</span>";
                // explain to table        
				if (!empty($explain)) {
					$query_td .= $this->ExplainToTable($explain);
				}
                //
				if (trim($backtrace)) {
					$query_td .= "<div><span style=\"color: #777777; white-space: pre-wrap;\">$backtrace</span></div>";
				}  
				if ($count < 1) $count = '&mdash;';

                //
				$decor_log .= <<<HTML
					<tr>
						<td>{$no}</td>
						<td style="white-space: pre-wrap;">{$query_td}</td>
						<td>{$errcode}</td>
						<td>{$count}</td>
						<td>{$time}</td>
					</tr>
HTML;
			}
            //
			$decor_log = <<<HTML
				<style type="text/css">
				.sqldebug {
					width: 99%;
					clear: both;
					background: #CCC;
					border: 2px solid #777;
					border-collapse: collapse;
					font-family: monaco, monospace;
					margin: 0 auto;
				}
				.sqldebug .sqldebug {
					border-color: #BBB;
				}
				.sqldebug td, .sqldebug th {
					border: 1px solid #CCC;
					padding: 2px;
				}
				.sqldebug tr > td {
					border-bottom: 0 solid #777;
				}
				.sqldebug .sqldebug tr > td {
					border-bottom: 1px solid #CCC;
				}
				.sqldebug thead, .sqldebug tbody {
					color: #514E4E;
					background: #EEE;
					font: x-small Verdana, sans-serif;
					text-align: left;
				}
				.sqldebug .query {
					color: #2C428C;
					font-size: 12px;
					line-height: 140%;
					display: block;
					background: #C9DCF7;
					padding: 5px;
				}
				.sqldebug .explain {
					margin: 3px 0;
				}
				.sqldebug .red {
					color: red!important;
				}
				.sqldebug .green {
					color: green!important;
				}
				.sqldebug .orange {
					color: orange!important;
				}
				.sqldebug .darkcyan {
					color: #008B8B!important;
				}
				.sqldebug.res thead, .sqldebug.res tbody {
					color: #222;
				}
				.sqldebug .warning {
					color: red;
					font-weight: 700;
				}
				.sqldebug .title {
					color: green;
					font-weight: bolder;
					margin: 10px 0 0;
				}
				</style>
				<table cellpadding="2" class="sqldebug">
					<thead>

						<tr>
							<th>#</th>
							<th>Query</th>
							<th>ErrCode</th>
							<th>Results</th>
							<th>Time</th>
						</tr>
					</thead>
					<tbody style="vertical-align: top">
						$decor_log
					    <tr>
					        <td colspan='4' style='font-weight: bold'>Total $cnt queries, time taken:</td>
					        <td>$time_total</td>
					    </tr>
					</tbody>
				</table>
HTML;
			return $decor_log;
		}
		
		
		private function ExplainToTable($entry)	{
			foreach ($entry as $k => $x) {
				$select_type   = &$x['select_type'];
				$type          = &$x['type'];
				$possible_keys = &$x['possible_keys'];
				$key           = &$x['key'];
				$key_len       = &$x['key_len'];
				$ref           = &$x['ref'];
				$rows          = &$x['rows'];
				$filtered      = &$x['filtered'];
				$extra         = &$x['Extra'];
				// 
				switch ($select_type) {
					case 'UNCACHEABLE SUBQUERY': $select_type = "<strong class='red'>{$select_type}</strong>"; break;
					case 'DEPENDENT SUBQUERY':   $select_type = "<span class='red'>{$select_type}</span>"; break;
				}
                //
				switch ($type) {
					case 'ALL':             $type = "<strong class='red'>{$type}</strong>"; break;
					case 'index':           $type = "<span class='red'>{$type}</span>"; break;
					case 'system':
					case 'const':           $type = "<strong class='green'>{$type}</strong>"; break;
					case 'eq_ref':
					case 'unique_subquery': $type = "<span class='green'>{$type}</span>"; break;
					case 'ref':
					case 'ref_or_null':
					case 'fulltext':
					case 'index_subquery':  $type = "<span class='darkcyan'>{$type}</span>"; break;
					case 'range':
					case 'index_merge':     $type = "<strong class='orange'>{$type}</strong>"; break;
				}
                //
				if (empty($key)) {
					$key = '<strong class="red">&mdash;</strong>';
				}
                //
				if (empty($possible_keys)) {
					$possible_keys = '<strong class="red">&mdash;</strong>';
				}
                //
				if ($key_len <= 8)       { $key_len = "<strong class='green'>{$key_len}</strong>"; }
				elseif ($key_len <= 16)  { $key_len = "<span class='green'>{$key_len}</span>"; }
				elseif ($key_len <= 32)  { $key_len = "<span class='orange'>{$key_len}</span>"; }
				elseif ($key_len <= 100) { $key_len = "<span class='red'>{$key_len}</span>"; }
				else                     { $key_len = "<strong class='red'>{$key_len}</strong>"; }
                //
				if ($rows < 500) {}
				elseif ($rows < 1000) { $rows = "<span class='orange'>{$rows}</span>"; }
				elseif ($rows < 5000) { $rows = "<span class='red'>{$rows}</span>"; }
				else                  { $rows = "<strong class='red'>{$rows}</strong>"; }
                //
				$e = array_map('trim', explode(';', $extra));
				if (!empty($e)) {
					foreach ($e as $thekey => $v) {
						switch ($v) {
							case 'No tables':
							case 'Not exists':
							case 'Select tables optimized away':
							case 'Impossible WHERE noticed after reading const tables':
								$v = "<strong class='green'>{$v}</strong>";
								break;

							case 'Using index':
							case 'Using index for group-by':
							case 'Using where with pushed condition':
								$v = "<span class='green'>{$v}</span>";
								break;

							case 'Distinct':
								$v = "<span class='darkcyan'>{$v}</span>";
								break;

							case 'Full scan on NULL key':
								$v = "<span class='orange'>{$v}</span>";
								break;

							case 'Using filesort':
							case 'Using temporary':
								$v = "<strong class='red'>{$v}</strong>";
								break;

							default:
								if ('Range checked for each record' == substr($v, 0, strlen('Range checked for each record'))) {
									$v = "<strong class='orange'>{$v}</strong>";
								}
						}
						$e[$thekey] = $v;
					}
					$extra = implode('; ', $e);
				}
                //
				$entry[$k] = $x;
			}
            // Converts an array to XHTML table
			$html = '';
			if (!empty($entry)) {
				$html = '<table class="sqldebug res explain" cellpadding="2" cellspacing="1"><thead><tr>';
				foreach ($entry[0] as $key => $value) {
					$html .= '<th>' . $key . '</th>';
				}
				$html .= '</tr></thead><tbody>';
				foreach ($entry as $e) {
					$html .= '<tr>';
					foreach ($e as $value) {
						$html .= '<td>' . $value . '</td>';
					}
					$html .= '</tr>';
				}
				$html .= '</tbody></table>';
			}
			return $html;
		}
	}

?>
