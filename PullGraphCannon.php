<?php
$output = shell_exec('git pull 2>&1');
if(!$output){
	$output = "Error running script";
}
echo "<html><head>Status: $output</head></html>";
?>