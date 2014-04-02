<html>
	<head>
		<div>
			<div>This is a test</div>
			<select style="float:left">
				<option>test1</option>
				<option>test2</option>
				<option>test3</option>
			</select>
		</div>
		<div>Pull from branch:</div>
		<div>
			<p>Branch:</p> 
			<form style="float:left">
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