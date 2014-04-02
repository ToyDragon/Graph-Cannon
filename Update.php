<html>
	<head>
		<script>
			function pullFromBranch(){
				var branch = document.getElementById("branchName").value;
				document.getElementById("branchName").innerHTML += branch;
			}
		</script>
		<div>Pull from branch:</div>
		<div>
			<form>
				Branch:
				<input type="text" onclick="pullFromBranch();" value="master" id="branchName">
			</form>
		</div>
		<div>
			<h1>Log:</h1>
			<div id="log"/>
		</div>
	</head>
</html>