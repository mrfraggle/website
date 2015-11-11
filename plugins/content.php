<?php
/*
    content plugin (c) 3kings.dk
    
    31-10-2012	rasmus@3kings.dk	draft
    02-11-2012	rasmus@3kings.dk	rewritten as plugin based
*/

$query_age = (isset($_GET['wid']) ? $_GET['wid'] : null);

if($query_age != '')
{
    plugin_register('CONTENT', 'content');
    
    require_once './plugins/content_plugins/wp_page.php';    
}
else
{
	
	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
	
	plugin_register('CONTENT', 'content');
	
	

	$content_plugins = array();
	$content_title = array();

	function content_plugin_register($keyword, $callback, $title='')
	{ 
		global $content_plugins;
		global $content_title;
        
		$content_plugins[$keyword] = $callback;
		$content_title[$keyword] = $title;                
	}
	
	require_once $_SERVER['DOCUMENT_ROOT'].'/config_content_plugins.php';

	// handle content pane	
	function content()
	{		
		global $content_plugins;
		global $content_plugin_default;
		global $content_title;
		
		foreach ($content_plugins as $key => $callback)
		{
			if (isset($_REQUEST[$key])) 
			{
				set_title($content_title[$key]);
				return $callback();
			}
		}
        $test = $content_plugins[$content_plugin_default];

		return $content_plugins[$content_plugin_default]();
	}
}
/*CUSTOM START*/

plugin_register('MEDLEMMER_COUNT', 'Medlemmer_count');
function Medlemmer_count()
{ 
	return fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID);
}

plugin_register('HONORARY_COUNT', 'honorary_count');
function honorary_count()
{ 
	return fetch_num_active_roles("and R.rid=".HONORARY_ROLE_RID);
}

plugin_register('NEWMEMBERS_COUNT', 'newmembers_count');
function newmembers_count()
{ 
$ys = logic_get_club_year_start();

	return fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID." and R.start_date>='$ys'");
}

plugin_register('LEAVINGMEMBERS_COUNT', 'leavingmembers_count');
function leavingmembers_count()
{ 
$ys = logic_get_club_year_start();
$ye = logic_get_club_year_end();	
	return fetch_num_active_roles("and R.rid=".MEMBER_ROLE_RID." and (R.end_date>'$ys' and R.end_date='$ye')");
}

plugin_register('AVGAGE_COUNT', 'avgage_count');
function avgage_count()
{ 
	return round(fetch_avg_member_age("and profile_ended>now()"));
}

plugin_register('REVOLUTION_SLIDER', 'revolution_slider');
function revolution_slider()
{ 
    if(is_user_logged_in()) 
    {
        $contet = '<div class="col-xs-12 col-sm-8 col-md-10"><div class="page-slider-wrap">'.do_shortcode('[rev_slider homepage]').'</div></div>';
    }
    else
    {
        $contet = '<div class="page-slider-wrap">'.do_shortcode('[rev_slider homepage]').'</div>';
    }
    
    if(!isset($_GET['uid'])) {
	    return $contet;
    }
    else
    {
        return '';
    }
}	

plugin_register('LOGIN_BTN', 'login_btn');
function login_btn()
{   
global $current_user;
    $cont = '';
	if(is_user_logged_in() && isset($_SESSION['user'])) { 
        
		$cont = '<ul class="user-nav">
        <li><a style="color:#fff;" href="?uid='.$_SESSION['user']['uid'].'">'.utf8_encode($_SESSION['user']['profile_firstname'])." ".utf8_encode($_SESSION['user']['profile_lastname']).'</a> | </li>  
        <li><a style="color:#fff;" href="?uid='.$_SESSION['user']['uid'].'&edit">Rediger</a> | </li>  
        <li><a style="color:#fff;" href="?cid='.$_SESSION['user']['cid'].'">Min klub</a></li>       
			<li><a class="btn btn-primary btn-lg" href="/?logout">Log af</a></li>
		</ul>';
	}
	else
	{  
		$cont = '<ul class="user-nav">
			<li><a class="btn btn-primary btn-lg" href="#login-register" data-toggle="modal" title="Log in">Log Ind</a></li>
		</ul>';
	}

	return $cont;
}	

plugin_register('HOMECONTENT', 'homecontent');
function homecontent()
{
    global $current_user;
    $content = '';
    if(!is_user_logged_in()) { 
        $content = get_field('home_content',13);
        ?>
        <style>
        .index .container .home_content {display:none;}
        </style>
        <?php 
    }
    else
    {
        ?>
        <style>
        .index .container .home_content {display:none;}
        </style>
        <?php 
    }
    return $content;
}

/*CUSTOM END*/

?>