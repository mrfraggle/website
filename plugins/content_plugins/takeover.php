<?
	/*
		content plugin admin takeover - used to login as other users (c) 3kings.dk
		
		05-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('takeover', 'content_handle_takeover', 'Profile takeover');


	function content_handle_takeover()
	{
		if (!logic_is_admin()) return term('admin_required');

		$html = '
						<h1>'.term('admin_takeover').'</h1>
						<form action=. method=post>
						Username / UID:<br>
						<input type=text name=takeover value="'.$_REQUEST['takeover'].'"><br>
						<input type=submit class=btn>
						</form>
						';
		
		if ($_REQUEST['takeover']!='')
		{
			$takeover = $_REQUEST['takeover'];
			if (is_numeric($takeover)) $user = logic_get_user_by_id($takeover);
			else $user = logic_get_user_by_username($takeover);
			
			if (!$user) echo "Error: unable to find user";
			else 
			{
				$_SESSION['user'] = $user;
				$_SESSION['user']['active_roles'] = fetch_active_roles($user['uid']);
				header("location: /");
			}
			
		}

		return $html;		
	}

?>