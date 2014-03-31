<?php
	function resultToArray($result) {
		$rows = array();
		while($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		return $rows;
	}

	$con=mysqli_connect("localhost","root","root","itpir");
	// Check connection
	if (mysqli_connect_errno()){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	$query = mysqli_query($con,$_GET['query']);
	$survey = resultToArray($query);

	echo json_encode($survey);
?>