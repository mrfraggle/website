<?php
include "wp-load.php";
global $wpdb;

echo $wpdb->query("LOAD DATA INFILE '".$_SERVER['DOCUMENT_ROOT']."/rtd11.csv'
         INTO TABLE user_path
         FIELDS TERMINATED BY ','
         OPTIONALLY ENCLOSED BY '\"' 
         LINES TERMINATED BY '\\n'
         ");
         echo $wpdb->show_errors();          
   echo  $wpdb->print_error();                     
?>