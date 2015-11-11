<?php
/*$link = mysql_connect('localhost', 'rtd_dk', 'ryed3VB2VfRdeLpK');
if (!$link) {
    die('Not connected : ' . mysql_error());
}

$db_selected = mysql_select_db('test_wp', $link);
if (!$db_selected) {
    die ('Can\'t use test_wp : ' . mysql_error());
}
    
    echo recursive_function($parent=0);
    
    function recursive_function($parent) {          
       $itemsWant = "
    SELECT *
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
                        
                
        ?>
        <ul>
            <?php
            $i=1;
            $result = mysql_query($itemsWant);
            while($row = mysql_fetch_array($result))
            {                
                if($row['meta_key'] == '_menu_item_object_id')
                {   
                    $q = mysql_query('select ID,post_title from wp_rtdposts where ID='.$row['meta_value']);
                    $array = mysql_fetch_array($q);           
                }
                                                
                ?>
                <li><?php echo $array['post_title']; ?>
                <?php recursive_function($row['ID']); ?>
                </li>
                <?php
                $i++;               
            }  
                            
            ?>
        </ul>
        <?php } */

include "wp-load.php";  

global $current_user;
get_currentuserinfo();
echo "<pre>";
print_r($current_user);

echo wp_nav_menu(array('menu' => 'top-menu', 'echo' => 0, 'menu_class' => 'collapse navbar-collapse nav slide', 'menu_id' => 'main-menu'));
      
?>