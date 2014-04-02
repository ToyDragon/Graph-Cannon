<html>
	<head>
		<script>
			function pullFromBranch(){
				alert('test');
				var branch = document.getElementById("branchName").value;
				document.getElementById("branchName").innerHTML += branch;
				alert(branch);
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