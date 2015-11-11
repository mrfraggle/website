<?php
/*
	mysql connect wrapper (c) 3kings.dk
	
	26-10-2012	rasmus@3kings.dk	init
	31-10-2012	rasmus@3kings.dk	uniform include path
*/


if (PHP_SAPI == 'cli')
{
    $path = "/var/www/vhosts/rtd.dk/test2012/";
    require_once $path.'/config.php';

}
else
{
  require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
}



class MySQLConnect3Kings
{
	var $m_Link;
	var $m_ShouldDie;

	function MySQLConnect3Kings($p_ShouldDie=true)
	{

		$this->m_ShouldDie = $p_ShouldDie;

		if ($this->m_ShouldDie)
		{
			$link = mysql_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD);
			
			if (!$link)
      {
          die("Database down. Please contact web@rtd.dk");
          exit();
      }
			mysql_select_db(DATABASE_NAME)
				or die("Could not select database '".DATABASE_NAME."'");
		} else
		{
			$link = mysql_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD);
			mysql_select_db(DATABASE_NAME);
		}
		
		mysql_set_charset('utf8',$link);
	

		$this->m_Link = $link;
	}

	function Close()
	{
		mysql_close($this->m_Link);
	}

    function Query($p_Query) {
        //$start = microtime();
        $rs = mysql_query($p_Query);
        //$end = microtime();
        //$duration = $end - $start;
        //echo "<!--- Query [$duration ms]: $p_Query --->\n";
        return $rs;
    }

	function InsertID() {
	    return mysql_insert_id($this->m_Link);
	}

	function Execute($p_Query)
	{
		if ($this->m_ShouldDie)
		{
		    $result = $this->Query($p_Query);
		    if(!$result) {
							require_once $_SERVER['DOCUMENT_ROOT'].'/includes/mail.php';
							ob_start();
							debug_print_backtrace();
							$trace = ob_get_contents();
							ob_end_clean(); 		    		
							
							$sender = MASS_MAILER_REPLY_WHO;
							$sender_mail = MASS_MAILER_REPLY_MAIL;  
							$mail = new PHPMailer();
									
							
							$error = "QUERY FAILED: \n\n".$p_Query."\n\nSQL ERROR:\n\n".mysql_error()."\n\nTRACE:\n\n".$trace."\n\nSESSION:\n\n".print_r($_SESSION,true)."\n\nSERVER:\n\n".print_r($_SERVER,true);

							$mail->From     = $sender_mail;
							$mail->FromName = $sender;
							$mail->AddAddress("web@rtd.dk", "web@rtd.dk");  
							$mail->Subject  =  "Automatic error message ".date("D, d M Y H:i:s");
							$mail->Body     =  $error;
											
							$mail->Send();


							if (stristr($_SERVER['SERVER_NAME'],'dev')!==false)
							{
								die($error);
							}
							//mail(ADMIN_MAIL, "Automatic error message ".date("D, d M Y H:i:s"), $error);
              die("An error occurred. System administrator has been notified. Please go back and try again - if the error persists please contact system administrator in person.");


		      }

		      //echo "<META HTTP-EQUIV=Refresh CONTENT=\"0;URL=/error/\">";
			//$result = mysql_query($p_Query) or die("*** Query failed ***<br>\n<br>\n$p_Query\n<br>".mysql_error());
		}
		else
		{
			echo "*** Query failed ***<br>\n<br>\n$p_Query\n<br>".mysql_error();
			$result = $this->Query($p_Query);
		}
		return $result;
	}

	function FetchArray($p_RS,$assoc=MYSQL_BOTH)
	{
		return mysql_fetch_array($p_RS,$assoc);
	}
	
	function FetchAssoc($p_RS) {
	    return mysql_fetch_assoc($p_RS);
	}

	function NumRows($p_RS)
	{
	    return mysql_num_rows($p_RS);
	}
	
	function FetchSingleValue($p_RS) {
	    list($value) = $this->FetchArray($p_RS);
	    return $value;
	}
	
	function FetchTableNames()
	{
	   $result = array();
	   $rs = mysql_list_tables(DATABASE_NAME);
	   while ($table = $this->FetchArray($rs))
	   {
	     $result[] = $table[0];
     }
	   return $result;
  }
  
  function DoesTableExist($table_name)
  {
    $tables = $this->FetchTableNames();
    return in_array($table_name, $tables);
  }
	
	function FormatExecute($str, $data_array) {
		$control_sequence = false;
		$query = '';
		$data_counter = 0;
		for ($i=0; $i<strlen($str); $i++) {
			if ($control_sequence) {
				switch ($str[$i]) {					
					case "S":	$query .= current($data_array);
							next($data_array);
							break;
							
					case "s": 	$query .="'".current($data_array)."'";
							next($data_array);
							break;

					case "i": 	$query .=current($data_array);
							next($data_array);
							break;
					
					default: $query .= "%".$str[$i];
				}
				$control_sequence = false;				
			} else {
				if ($str[$i] == '%') {
					$control_sequence = true;
				}
				else {
					$query .= $str[$i];
				}
			}
		}
		return $this->Execute($query);
	}
	
}

// globalize the nation
$g_db = new MySQLConnect3Kings();

?>