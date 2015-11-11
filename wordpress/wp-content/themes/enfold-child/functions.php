<?php
/*
* Add your own functions here. You can also copy some of the theme functions into this file. 
* Wordpress will use those functions instead of the original functions then.
*/


add_filter( 'wp_nav_menu_items', 'add_search_to_nav', 10, 2 );

function add_search_to_nav( $items, $args )
{
    $items .= '<li class="search-nav" style="padding-bottom: 30px;">
                <a class="btn-search" href="#"><i class="fa fa-search"></i></a>
            </li>';
    return $items;
}

function menu_item()
{
    return wp_nav_menu(array('menu' => 'top-menu', 'echo' => 0, 'menu_class' => 'collapse navbar-collapse nav slide', 'menu_id' => 'main-menu'));
}