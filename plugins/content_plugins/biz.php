<?php
	/*
		content plugin admin terms - used to login as other users (c) 3kings.dk
		
		05-11-2012	rasmus@3kings.dk	draft
	*/

	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
		
	content_plugin_register('biz', 'content_handle_biz', 'Brancheoversigt');


	function content_handle_biz()
	{
		if (!logic_is_member()) return term('article_must_be_logged_in');

		$q = isset($_REQUEST['biz'])?$_REQUEST['biz']:"";
		$data = array('search' => $q, 'businesses' => logic_get_business_list(), 'results' => logic_get_users_per_business($q));
		set_title('Brancheoversigt '.$q);
		return term_unwrap('business_search', $data, true);		
	}

?>