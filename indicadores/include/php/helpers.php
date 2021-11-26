<?php

function showMessage($msg){
	echo "<script>document.getElementById('progress_msg').innerHTML = '$msg'; </script>";
	echo str_pad('',4096)."\n";    
    ob_flush();
    flush();
}

function showSubMessage($msg){
	echo "<script>document.getElementById('progress_sub_msg').innerHTML = '$msg'; </script>";
	echo str_pad('',4096)."\n";    
    ob_flush();
    flush();
}

function mostrarArreglo($arr){
	echo '<pre>';print_r($arr);echo '</pre>';
}

function debug($message,$line_break = true){
	require 'config.php';
	
	$lb = $line_break ? '<br>' : '';
	
	if($debug_mode)
		echo "$message$lb";
}

?>