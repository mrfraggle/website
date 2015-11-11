<?
	/*
		front page stats plugin (c) 3kings.dk
		
		03-01-2013	rasmus@3kings.dk	draft
	*/
	
	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
	ini_set('default_charset', 'utf-8');
	plugin_register('LATEST_MINUTES', 'latest_minutes');
    plugin_register('LATEST_MEETINGS', 'latest_meetings');
  
	
	function latest_minutes()
	{
    $minutes = logic_get_latest_minutes();
    $html = "";
    for ($i=0;$i<sizeof($minutes);$i++)
    {
      $m = $minutes[$i];
      $html .= "<li><a href=?mid={$m['mid']}>{$m['title']}</a>";
    }
    $html .= "";
     return utf8_encode($html);
	}

	function latest_meetings()
	{
    $minutes = logic_get_latest_meetings();
    $html = "";
    for ($i=0;$i<sizeof($minutes);$i++)
    {
      $m = $minutes[$i];
      $html .= "<li><a href=?mid={$m['mid']}>{$m['title']}</a>";
    }
    $html .= "";
    return utf8_encode($html);
	}

?>