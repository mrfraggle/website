<?php
	/*
		article box plugin (c) 3kings.dk
		
		31-10-2012	rasmus@3kings.dk	draft
	*/
	
	if ($_SERVER['REQUEST_URI'] == $_SERVER['PHP_SELF']) header("location: /");
	
	plugin_register('ARTICLEBOX', 'articlebox');
	

	$box_article_html = "";

	function box_walk_articles(&$item)
	{	
		global $box_article_html;
	
	
		if ($item['public'] || logic_is_member())
		{
			
			if (!empty($item['children']))
			{
				$box_article_html .= "<li class=parent><a href=?aid={$item['aid']}>{$item['title']}</a>";
				$box_article_html .= "<ul>";
				array_walk($item['children'], 'box_walk_articles');
				$box_article_html .= "</ul>";
				
			}
			else
			{
				$box_article_html .= "<li>\n<a href=?aid={$item['aid']}>{$item['title']}</a>";
			}
			
			$box_article_html .= "</li>\n";
		}
	}

	
	
	function articlebox()
	{
		global $box_article_html, $current_user;
		$content = term('article_pretext');
		
    $articles = cache_get("article_menu");
    if (!$articles)
    {
      $articles = logic_get_articles();
      cache_put("article_menu", $articles);
    }
		array_walk($articles, 'box_walk_articles');
		//$content .= "<ul id=articles>$box_article_html</ul>";	
/*		
		$content .= "
		<script>$(function() {
				$('#adminmenu').menu();
			});</script>
		<ul id=adminmenu>
			<li><a href=#>".term('admin_edit_article')."</a>
			<ul>
			$admin_article_html
			<li><a href=?admin=article&edit=-1>".term('admin_create_article')."</a></li>
			</ul></li>
		</ul>
		";
*/		        
		//$menu_list = "<ul class='menu' id='articles'>";
        //$menu_list = '';
		/*$menu_items = wp_get_nav_menu_items('Top Menu');
        $i=1;              
		foreach ( (array) $menu_items as $key => $menu_item ) {
	    $title = $menu_item->title;
	    $url = $menu_item->url;
            if($i <= 6) {
	        $menu_list .= '<li><a href="' . $url . '">' . $title . '</a></li>';        
            }
        $i++;        
        }  */      
		//$menu_list .= "</ul>";
        		        
        //$menu_list = recursive_function($parent=0);
        //$content .= $menu_list;	
		//return $content;
        return wp_nav_menu(array('menu' => 'top-menu', 'echo' => 0, 'menu_class' => 'collapse navbar-collapse nav slide', 'menu_id' => 'main-menu'));       
	}       
    
/*function recursive_function($parent,$list='') {  
global $wpdb, $post;
$list = '';
$itemsWant = "SELECT *
        FROM wp_rtdposts p1
        INNER JOIN wp_rtdterm_relationships AS TR
        ON TR.object_id = p1.ID
        INNER JOIN wp_rtdpostmeta AS PM
        ON PM.post_id = p1.ID
        INNER JOIN wp_rtdpostmeta AS PM1
        ON PM1.post_id = p1.ID
        INNER JOIN wp_rtdposts AS p2
        ON p2.ID = PM.post_id        
        WHERE p1.post_type = 'nav_menu_item' 
        AND TR.term_taxonomy_id = ( SELECT wp_rtdterms.term_id FROM wp_rtdterms WHERE wp_rtdterms.slug = 'top-menu')
        AND PM1.meta_key = '_menu_item_object_id' AND PM.meta_key = '_menu_item_menu_item_parent' AND PM.meta_value = '".$parent."'
            ORDER BY p1.menu_order ASC";

       $terms = $wpdb->get_results($itemsWant);     
       if(count($terms) > 0 && $terms != '') {   
        if($parent == 0) {  
            $list .= '<ul id="main-menu" class="collapse navbar-collapse nav slide custom">'; 
       }
       else
       {
           $list .= '<ul class="dropdown">'; 
       }
                     
                foreach ($terms as $term) {                                          
                    $list .= '<li>'; 
                    if($term->meta_value != '' && get_the_title($term->meta_value) != '') { 
                        $list .= '<a href="#">'.get_the_title($term->meta_value).'</a>'; 
                        $list .= ''.recursive_function($term->ID).'';
                    }
                     $list .= '</li>';                                        
                } 
        
        if($parent == 0)
        {
            $list .= '<li class="search-nav" style="padding-bottom: 30px;">
                <a class="btn-search" href="#"><i class="fa fa-search"></i></a>
            </li>'; 
        }
        $list .= '</ul>';
        }
        return $list;
}*/
?>