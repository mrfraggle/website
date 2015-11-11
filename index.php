<?php		   
	require_once 'config.php';
	require_once 'config_terms.php';
	require_once './includes/logic.php';
	require_once './includes/cache.php';
	require_once './includes/sessionhandler.php';
    
    
	 include './wordpress/wp-load.php';
    	
    global $current_user;
		
	$plugins = array();
	$title = '--NOT SET--';
	
	function set_title($t)
	{
		global $title;
		$title = $t;
	}
	
	function get_title()
	{
		global $title;
		return $title;
	}


	function plugin_register($keyword, $callback)
	{ 
		global $plugins;
		$plugins[$keyword] = $callback;
	}

  
	function sanitize_output($buffer)
	{
	    $search = array(
	        '/\>[^\S ]+/s', //strip whitespaces after tags, except space
	        '/[^\S ]+\</s', //strip whitespaces before tags, except space
	        '/(\s)+/s'  // shorten multiple whitespace sequences
	        );
	    $replace = array(
	        '>',
	        '<',
	        '\\1'
	        );
	    $buffer = preg_replace($search, $replace, $buffer);
	
	    return $buffer;
	}
	
	require_once './config_plugins.php';
	
	/*if (!session_start())
	{
		die("Error starting PHP session!");
	}*/


	setlocale(LC_ALL,RT_LOCALE);
  
	
  
  if (isset($_REQUEST['print']))
  {
    $template_html = file_get_contents(RT_TEMPLATE_PRINT);
  }
  else
  {
	$template_html = file_get_contents(RT_TEMPLATE);
  }
	
	foreach ($plugins as $keyword => $callback)
	{
		$value = $callback();
		$template_html = str_replace("%%$keyword%%", $value, $template_html);
	}
	
	logic_update_tracker();
	if (logic_is_member()) logic_update_last_page_view();
	
	$template_html = str_replace("%%TITLE%%", $title, $template_html);
	
	echo $template_html;
	
	// echo "<!---- ".print_r($_SESSION,true)."--->";
    
if(isset($_GET['aid']) && $_GET['aid'] == '15')
{
    header("location: http://dev.rtd.dk/?news"); //to redirect back to "index.php" after logging out
    exit(); 
}
else if(isset($_GET['aid']) && $_GET['aid'] == '13')
{
    header("location: http://dev.rtd.dk/?mummy"); //to redirect back to "index.php" after logging out
    exit();
}


if(is_user_logged_in()) 
{
    ?>
    <script>
    jQuery(document).ready(function($) { 
		if(!$('#page-content').hasClass('not_home')) 
		{       
        	$('.col-xs-12.home-right').insertBefore('.col-xs-12.col-md-10');
		}
		else
		{
			$('.col-xs-12#banners').insertBefore('.col-xs-12.col-md-10');
		}
        
        $('#page-content').prev('div.col-md-10').prev('div.col-md-2').addClass('firsadvert');
        
        if(!$('body').hasClass('not-loggin')) {
            if($('.col-sm-4.col-md-2').hasClass('home-right')) {
                $('.container-light.statistik .col-xs-12.random_mem').insertBefore('div.firsadvert .banner2');
            }
            else
            {
                $('.container-light.statistik .col-xs-12.random_mem').insertBefore('.col-sm-4.col-md-2 .banner2');
            }
        }
        
        if($('.col-sm-4.col-md-2').hasClass('home-right')) {
            $('body').addClass('homepage');
        }
                                  
    });
    </script>
    <style>
    .nivoSlider img{height:497px !important;} 
    .page_content #banners.col-md-2, .page_content .col-xs-12.col-md-10 #content .col-md-2.home-right {display:none !important;}
    #page-content .container #banners.col-md-2, #page-content .container .col-xs-12.col-md-10 #content .col-md-2.home-right {display:none !important;}
	#page-content .container .col-md-2.home-right, .page_content .col-md-2.home-right {display:none !important;}
    /*#page-content .container .page-slider-wrap {display:none !important;}*/
    #page-content .container .right-part .grid-wrap #projects {margin-left:-80px !important;}
	
	#content .title-section h1 {clear:both;}
    object embed {width:350px; height:200px;}    
    #latestmemberss {clear:both;}
    </style>
    <?php
}
else
{
    ?>
    <script>
    jQuery(document).ready(function($) {     
        $('body').addClass('not-loggin');
        $('.container .right-part').addClass('col-xs-12 col-sm-8 col-md-10');
    });
    </script>    
    <?php
}

if(isset($_GET['country']))
{
    ?>
    <script>
    jQuery(document).ready(function($) {     
        $('body').addClass('country_pages');       
    });
    </script>
    <?php
}

if(isset($_GET['uid']))
{
    ?>
    <style>
    #page-content.not_home {padding:25px;}
    #page-content .container #content .title.title-section {margin-top:25px; margin-bottom:25px;}
    #page-content .container #content .col-xs-5 {width:41.66666667%%;}
    #page-content .container #banners.col-md-2 {display:block !important;}  
    #page-content .container .container-light .row .col-xs-12 {width: 80%;}
    #page-content .container .container.container-image {width: 100%;} 
    #page-content .container #banners.col-md-2 + .col-md-10 {width: 80%;}
    #page-content .container #banners.col-md-2 + .col-md-10 .container {width:100%;}
    #page-content .container .col-md-10 .right-part #content table.table.table-striped tr td {font-size:13px;}
    #page-content + .container.container-light {clear:both;}
    </style>
    <script>
    jQuery(document).ready(function(){
        jQuery('#page-content .container .col-md-10:eq(0) .meetstatistic_data').insertAfter("#page-content");
        jQuery('#page-content .container .col-md-10:eq(0) .container.container-image').insertAfter(".container.container-light:eq(0)");
        
        if($('.userpage li a.facebook').attr('href') == '')
        {
            $('.userpage li a.facebook').hide();
        }
        if($('.userpage li a.linkedin').attr('href') == '')
        {
            $('.userpage li a.linkedin').hide();
        }
        if($('.userpage li a.twitter').attr('href') == '')
        {
            $('.userpage li a.twitter').hide();
        }
        
        if($('.userpage li a.facebook').attr('href') == '' && $('.userpage li a.facebook').attr('href') == '' && $('.userpage li a.twitter').attr('href') == '')
        {
            $('ul.userpage').hide();
        }        
    })
    </script>
    <?php
}
else
{
    if(is_user_logged_in()) {
    ?>
    <script>
    jQuery(document).ready(function(){
        jQuery('#page-content .container .col-md-10:eq(0)').wrap("<div class='page_content'></div>");
        jQuery('#page-content .container .page_content').insertBefore('#page-content');  
    })
    </script>
    <?php
    }
}

if(isset($_GET['cal']))
{
    ?>
    <style>
    #page-content.not_home {width:80%; display:block; padding:0 0 0 10px;}
    #banners + .col-xs-12.col-md-10 {display:none;}
    </style>
    <?php
}

if(isset($_GET['cid']))
{
    ?>
    <style>
    .page_content .col-md-10.club-page .container-image.future_meetings {margin-top:290px;}
    </style>
    <script>
    $(window).load(function(){
       $('.page_content .col-xs-12 .container.clubpg_last_sec').insertAfter('#page-content .container');
    });
    </script>
    <?php
}
?>

<script type="text/javascript">
$(window).load(function(){
    menu_coten();
    $('#main-menu ul.sub-menu').show();
});

function menu_coten()
{
    $('ul#main-menu li').each(function(index, element) {
        var title = jQuery(this).children('a').attr('title');
        if($(this).hasClass('menu-item-has-children'))
        {             
            if(title != '' && typeof title != 'undefined') 
            {    
                jQuery(this).children('a').append('<i class="carret"></i><span>'+title+'</span>');
            }
            else
            {
                jQuery(this).children('a').append('<i class="carret"></i>');
            }
        }
        else
        {
            if(title != '' && typeof title != 'undefined') 
            {    
                jQuery(this).children('a:eq(0)').append('<span>'+title+'</span>');
            }            
        }
    });
}
</script>

<?php
if(isset($_GET['country']))
{
    ?>
    <script>
    jQuery(document).ready(function($) {  
        $('.col-md-10:eq(0) .page-slider-wrap').hide();  
        $('.page_content .tp-banner-container').insertAfter('.col-md-10:eq(0) .page-slider-wrap');
        
        if($('.country_social li a.facebook').attr('href') == '')
        {
            $('.country_social li a.facebook').hide();
        }
        if($('.country_social li a.linkedin').attr('href') == '')
        {
            $('.country_social li a.linkedin').hide();
        }
        if($('.country_social li a.twitter').attr('href') == '')
        {
            $('.country_social li a.twitter').hide();
        }
        
        if($('.country_social li a.facebook').attr('href') == '' && $('.country_social li a.facebook').attr('href') == '' && $('.country_social li a.twitter').attr('href') == '')
        {
            $('ul.country_social').hide();
        }
    });
    </script>
    <?php
}
?>