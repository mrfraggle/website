<?php
/*
		content plugin stats (c) 3kings.dk
		
		22-02-2013	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('news', 'content_handle_news', 'News');


	
	function content_handle_news()
	{
		if (!logic_is_member())
    {
      header("location: ?aid=-1");
      die();
    }
    else
    {
      $html = "";
    	
    	if (isset($_REQUEST['comment']))
    	{
			$nid = $_REQUEST['news'];
    		logic_save_comment($nid,$_REQUEST['comment'],0);
    	}

      if (isset($_REQUEST['news']) && is_numeric($_REQUEST['news']))
      {
      	$nid = $_REQUEST['news'];
      	$data = logic_fetch_specific_news($nid);
        $did = $data['did'];
      	set_title($data['title']);
      	$html .= term_unwrap("news_item_show", $data);    	
      	$comment_data = logic_get_news_comments($nid);
      	$comment_data['nid'] = $nid;
      	$html.= term_unwrap("news_item_comment", $comment_data, true);
      }    	
      else $did=0;

      $html.= term_unwrap("news_archive", logic_get_news($did,10),true);
    	return $html;
    }
	}
?>
