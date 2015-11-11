<?
	/*
		content plugin banner (c) 3kings.dk
		
		02-01-2013	rasmus@3kings.dk	draft
	*/
	
	content_plugin_register('banner', 'content_show_banner', 'Reklamer');
	
	function content_show_banner()
	{
		if (isset($_REQUEST['click']))
		{
			$banner = logic_get_banner($_REQUEST['click']);
			logic_click_banner($_REQUEST['click']);
			header("location: {$banner['link']}");
			die();
		}
		
		if (isset($_REQUEST['img']))
		{
			$i = $_REQUEST['img'];
			$banner = logic_get_banner($_REQUEST['img']);
			$m = $banner['mimetype'];
			header("location: /uploads/banners?i=$i&m=$m");
			die();
//			

//			$data = ($banner['image']);
			// debug_print_backtrace();
			//die(print_r($banner,true));
					
	
//	die(print_r($banner,true));		
			/*
			header('Content-type: '.$banner['mimetype']);
			header('Content-Transfer-Encoding: binary'); 
			header('Content-length: '.sizeof($data));
			die($data);
			*/
		}
		
		$html = "";
		
		if (logic_is_admin())
		{
			if (isset($_REQUEST['upload']))
			{
				logic_add_banner($_REQUEST['upload'], $_FILES['file']);
			}
			
			$banners = logic_get_banners();
			$html .= term_unwrap('banner_admin', array('data'=>json_encode($banners)));
		}
		
		
		return $html;
	}	
?>