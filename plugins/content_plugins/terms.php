<?
	/*
		content plugin admin terms - used to login as other users (c) 3kings.dk
		
		05-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('terms', 'content_handle_terms', 'Tekster');


	function content_handle_terms()
	{
		global $terms;
		
	
		if (!logic_is_admin()) return term('admin_required');

		if (!empty($_REQUEST['terms']))
		{
			$t = serialize($_REQUEST['terms']);
			get_db()->execute("insert into terms (ts,data) values (now(), '$t')");
		}


		$html = '
						<h1>'.term('admin_term_edit').'</h1>
						<form action=. method=post>
						<table width=100% border=1>
						';
						
		foreach ($terms as $key => $value)
		{
			$value = trim($value);
			$html .= "
				<tr>
					<td width=20%>$key</td>
					<td><textarea class=ckeditor name=\"terms[$key]\">$value</textarea></td>
				</tr>
			";
		}
		
		$html .= "
							</table>
							<input type=submit class=btn>
							</form>
							";
		
		return $html;		
	}

?>