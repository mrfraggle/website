<?php
/*
Wordpress pages content
*/
function content()
{
    global $post;
    $post = get_post($_GET['wid']);
    $content = '';
    $content .= '<h2>'.get_the_title($post->ID).'</h2>
    <p>'.$post->post_content.'</p>';
    return $content;
}
