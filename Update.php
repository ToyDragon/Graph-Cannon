<html>
	<head>
		<script>
			function pullFromBranch(){
				var branch = document.getElementById("branchName").value;
				var log = document.getElementById("log").value;
				alert(1);
				document.getElementById("branchName").innerHTML += branch;
				var xmlHttp = new XMLHttpRequest();
				alert(2);
				xmlHttp.open( "GET", "PullGraphCannon.php?branch="+branch, false );
				xmlHttp.send( null );
				alert(3);
				log.innerHTML = xmlHttp.responseText;
				alert(4);
			}
		</script>
		<div>Pull from branch:</div>
		<div>
			<form>
				Branch:
				<input type="text" value="master" id="branchName">
				<input type="button" onclick="pullFromBranch()" value="Pull!" id="pullButton">
			</form>
		</div>
		<div>
			<h1>Log:</h1>
			<div id="log"/>
		</div>
	</head>
</html>