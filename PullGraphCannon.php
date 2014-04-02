<?php
if(!$_GET['branch']){
	$_GET['branch'] = "master";
}
$output = shell_exec("git pull origin ".$_GET['branch'].":".$_GET['branch']." 2>&1");
if(!$output){
	$output = "Error running script";
}
echo "<html><head>Status: $output</head></html>";
?>