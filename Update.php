<html>
	<head>
		<select>
			<option>test1</option>
			<option>test2</option>
			<option>test3</option>
		</select>
		<div>Pull from branch:</div>
		<div>
			Branch: 
			<form>
				<input type="text" onclick="pullFromBranch();" value="master" id="branchName">
			</form>
		</div>
	</head>
	<script>
		function pullFromBranch(){
			var branch = document.getElementById("BranchName").value;
			alert(branch);
		}
	</script>
</html>