<?
	/*
		content plugin article (c) 3kings.dk
		
		02-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('aid', 'content_show_article', 'Artikler');
	
	function content_show_article()
	{
	
		if (!isset($_REQUEST['aid']))
		{
			$aid = -1;
	/*		if (!logic_is_member())
			{
				header("location: /?aid=".LANDING_PAGE_PUBLIC);
			}
			else
			{
				header("location: /?aid=".LANDING_PAGE_PRIVATE);
			}
			die();*/
		}
		else $aid = $_REQUEST['aid'];

    $article = false;//cache_get("article-{$aid}");
    if (!$article)
    {
      $article = logic_get_article($aid);
      cache_put("article-{$aid}", $article);
    }
	
	if (isset($article['link']) && $article['link']!='')
	{
		header("location: ".$article['link']);
		die();
	}
		
		$html = "";
		
		if (logic_is_admin())
		{
			if ($aid<0) $aid = LANDING_PAGE_PRIVATE;
			$html .= "<p><a href=?admin=article&edit=$aid>".term('article_edit')."</a></p>";
		}
		
		if ($article['public'] || logic_is_member()) $html .= "<h1>{$article['title']}</h1>".$article['content'];
		else $html .= "<div id=error title=\"".term('dialog_error')."\">".term('article_must_be_logged_in')."</div>";

		if ($article['public']) $html .= term('addthis');		
		
		set_title($article['title']);
		return $html;
	}	
?>