<?
	/*
		content plugin article admin (c) 3kings.dk
		
		02-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('admin', 'content_handle_admin', 'Artikeladmin');
	
	function admin_article()
	{		
		$aid = $_REQUEST['edit'];

		if (isset($_REQUEST['save']))
		{     
			$aid = save_article($aid, $_REQUEST['title'], $_REQUEST['content'], $_REQUEST['public'], $_SESSION['user']['uid'], $_REQUEST['weight'], $_REQUEST['parent_aid'], $_REQUEST['link']);
      cache_invalidate("article-{$aid}");
      cache_invalidate("article_menu");
		}
		
		$article = fetch_article($aid);
		$user = isset($article['uid'])?fetch_user($article['uid']):array('username'=>$_SESSION['user']['username']);
		$public = $article['public']==1?"checked":"";
		$private = $article['public']==0?"checked":"";
		$html = "
		<form action. method=post>
		<input type=hidden name=edit value=$aid>
		<input type=hidden name=save value=now>
		<h2>".term('article_title')."</h2>
		<input type=text name=title value=\"{$article['title']}\"><br>
		<h2>".term('article_access')."</h2>
		<div class=radio>
			<input type=radio name=public value=1 id=radio1 $public><label for=radio1>".term('article_public')."</label>
			<input type=radio name=public value=0 id=radio2 $private><label for=radio2>".term('article_private')."</label>
		</div>
		<h2>Link</h2>
		<input type=text name=link value=\"{$article['link']}\">
		<h2>".term('article_content')."</h2>
		<textarea class=ckeditor name=content>{$article['content']}</textarea><br>
		".term('article_last_update')." {$user['username']} / {$article['last_update']}<br>
		<h2>".term('article_placement')."</h2>
		".term('article_weight')."<br> <input type=text name=weight value=\"{$article['weight']}\"><br>
		".term('article_parent')."<br> <input type=text name=parent_aid value=\"{$article['parent_aid']}\"><br>
		<input type=submit value=\"".term('article_save')."\" class=\"btn\">
		</form>
		<h2>Filer</h2>
		<iframe src=\"/plugins/content_plugins/article_admin_upload.php?aid=$aid\" frameborder=0 width=100% height=200px></iframe>
		<script>
		$(function() {
			$( 'div[class=radio]' ).buttonset();
		});
		</script>		
		";
		
		
		return $html;
	}
		

	
	// handle admin
	function content_handle_admin()
	{
		if (!logic_is_admin())
		{
			return term('admin_required');
		}
		else
		{
			return admin_article();
		}
	}?>