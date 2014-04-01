<?php 
	// Set up connection to local mysql database
	$con=mysqli_connect("localhost","root","root","itpir");
	// Check connection
	if (mysqli_connect_errno()){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	//used to get array data from the mysql server
	function resultToArray($result) {
		$rows = array();
		while($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}
		return $rows;
	}

	//Pull the list of survey id's from the server
	$query = mysqli_query($con,'SELECT survey.id, survey.title FROM sample_survey survey');
	$survey_list = resultToArray($query);

	//survey_list_js is referenced later in the js as a json object
	// representing the survey/question/answer structure used for
	// navigation and populating the graphs
	$survey_list_js = '';//{ "name": "Surveys", "children": [';
	$survey_name = '';

	//iterate through the list of surveys adding them the the survey_list_js var
	foreach($survey_list as $survey_obj){
		$survey_id = $survey_obj['id'];
		$survey_name = $survey_obj['title'];
		//grab title of this survey from the db
		$query = mysqli_query($con,"SELECT title FROM sample_survey WHERE id = $survey_id");
		$survey_info = mysqli_fetch_object($query);
		//grab titles of the questions from the 
		$query = mysqli_query($con,"SELECT title,id FROM sample_survey_questions WHERE survey_id = $survey_id");
		$survey_questions = resultToArray($query);

		$survey_list_js .= '{"name": "'.$survey_info->title.'", "children": [';

		$question_id_list = array();

		foreach($survey_questions as $question){
			$question_id = $question['id'];
			array_push($question_id_list, $question_id);
			$query = mysqli_query($con,"SELECT * FROM sample_survey_answers WHERE survey_id = $survey_id AND question_id = $question_id");
			$question_answers = resultToArray($query);
			$survey_list_js .= '{"name": "'.$question['title'].'","id":'.$question['id'].',"type": "question", "children":[';
			
			foreach($question_answers as $answer){
				$answer_id = $answer['id'];
				$survey_list_js .= '{"name": "'.$answer['answer_label'].'","size":1,"id":'.$answer_id.'},';
				
			}
			$survey_list_js = substr($survey_list_js,0,strlen($survey_list_js)-1);
			$survey_list_js .= ']},';
		}
		$survey_list_js = substr($survey_list_js,0,strlen($survey_list_js)-1);
		$survey_list_js .= ']}';

		//Collect votes for each answer
		$votes_js = '{';
		$query = mysqli_query($con,"SELECT COUNT(*) as amt,answer_id FROM `sample_survey_response_details` GROUP BY answer_id");
		$question_votes = resultToArray($query);

		foreach($question_votes as $question_stats){
			$votes_js .= '"'.$question_stats['answer_id'].'" : '.$question_stats['amt'].',';
		}
		$votes_js = substr($votes_js,0,strlen($votes_js)-1).'}';

		//Collect male votes information
		$votes_male_js = '{';
		$query = mysqli_query($con,"SELECT COUNT(*) as amt, s.answer_id FROM sample_survey_response_details s, sample_survey_keys k, sample_wm_add_details d WHERE s.response_id = k.response_id AND k.contact_id = d.contact_id AND d.gender = 'm' GROUP BY s.answer_id");
		$question_male_votes = resultToArray($query);

		foreach($question_male_votes as $male_stats){
			$votes_male_js .= '"'.$male_stats['answer_id'].'" : '.$male_stats['amt'].',';
		}
		$votes_male_js = substr($votes_male_js,0,strlen($votes_male_js)-1).'}';

		//Collect female votes information
		$votes_female_js = '{';
		$query = mysqli_query($con,"SELECT COUNT(*) as amt, s.answer_id FROM sample_survey_response_details s, sample_survey_keys k, sample_wm_add_details d WHERE s.response_id = k.response_id AND k.contact_id = d.contact_id AND d.gender = 'f' GROUP BY s.answer_id");
		$question_female_votes = resultToArray($query);

		foreach($question_female_votes as $female_stats){
			$votes_female_js .= '"'.$female_stats['answer_id'].'" : '.$female_stats['amt'].',';
		}
		$votes_female_js = substr($votes_female_js,0,strlen($votes_female_js)-1).'}';

		//Collect post-tenure votes information
		$votes_posttenure_js = '{';
		$query = mysqli_query($con,"SELECT COUNT(*) as amt, s.answer_id FROM sample_survey_response_details s, sample_survey_keys k, sample_wm_add_details d WHERE s.response_id = k.response_id AND k.contact_id = d.contact_id AND d.tenure = 'post' GROUP BY s.answer_id");
		$question_posttenure_votes = resultToArray($query);

		foreach($question_posttenure_votes as $posttenure_stats){
			$votes_posttenure_js .= '"'.$posttenure_stats['answer_id'].'" : '.$posttenure_stats['amt'].',';
		}
		$votes_posttenure_js = substr($votes_posttenure_js,0,strlen($votes_posttenure_js)-1).'}';

		//Collect pre-tenure votes information
		$votes_pretenure_js = '{';
		$query = mysqli_query($con,"SELECT COUNT(*) as amt, s.answer_id FROM sample_survey_response_details s, sample_survey_keys k, sample_wm_add_details d WHERE s.response_id = k.response_id AND k.contact_id = d.contact_id AND d.tenure = 'pre' GROUP BY s.answer_id");
		$question_pretenure_votes = resultToArray($query);

		foreach($question_pretenure_votes as $pretenure_stats){
			$votes_pretenure_js .= '"'.$pretenure_stats['answer_id'].'" : '.$pretenure_stats['amt'].',';
		}
		$votes_pretenure_js = substr($votes_pretenure_js,0,strlen($votes_pretenure_js)-1).'}';
	}

?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" href="http://mbostock.github.io/d3/talk/20111018/style.css"/>
		<script type="text/javascript" src="http://mbostock.github.io/d3/talk/20111018/d3/d3.js"></script>
		<script type="text/javascript" src="http://mbostock.github.io/d3/talk/20111018/d3/d3.layout.js"></script>
		<link type="text/css" rel="stylesheet" href="graph.css"/>
		</style>
		<div>
			<div style='float:left;width:50%;height:100%;background-color:#aaaaaa;' align='center'>
				<input type="button" value="Export" onclick="exportGraph()"></input>
				<div id='graph'></div>
			</div>
			<div style='float:left;width:50%;height:100%;'>
				<div style='height:60%;width:100%;background-color:#aaffaa;' id='surveySelect'></div>
				<div style='height:40%;width:100%;background-color:#aaaaff;' id='graphConfig'>
					
					<!-- Drop down menu -->
					<div id = "dropDown">
						<form>
							<select id = "GraphType">
								<option value="default">    Select Graph...     </option>
								<option value="0">          Bar Graph           </option>
								<option value="1">          Pie Graph           </option>
								<option value="2">          Line Graph          </option>
								<option value="3">          Bubble Graph        </option>
							</select>
							<!--<input type="submit">-->
						</form>
					</div>
					<div id = "BarGraph" style = "display : none">
						<div> Bar Graph Display Options </div>
						<div id = "checkBoxDualScale">
							<form>
								<input id="dualScale" type="checkbox" >
									Enable Dual-Scale
								</input>
							</form>

							<!-- START BAR 1 -->
							<div id = "bar1" value = "0" style = "display : none;">
								Bar 1
								<form action="demo_form.asp">
									<select id = "bar1Data" onchange = "">
										<!-- Survey Specific Data Types -->
										<option value="dataType1">      Data Type 1      </option>
										<option value="dataType2">      Data Type 2      </option>
										<option value="dataType3">      Data Type 3      </option>
										<option value="dataType4">      Data Type 4      </option>
									</select>
								</form>
							</div>
							
							<!-- START BAR 2 -->
							<div id = "bar2" value = "1" style = "display : none;" >
								Bar 2
								<form action="demo_form.asp">
									<select id = "bar2Data"			 onchange = "" >
										<!-- Survey Specific Data Types -->
										<option value="dataType1">		Data Type 1 	</option>
										<option value="dataType2"> 		Data Type 2  	</option>
										<option value="dataType3"> 		Data Type 3  	</option>
										<option value="dataType4">		Data Type 4  	</option>
									</select>
								</form>
							</div>
						</div>
						<!-- END CHECK BOX FOR DUAL SCALE -->

						<!-- START ORIENTATION -->
						<div id = "orientation">
							Orientation
							<div id = "orientationDropBox">
								<form>
									<select id = "graphOrientationDisplay" onchange = "" >
										<!-- Survey Specific Data Types -->
										<option value="horizontalDisplay">		Horizontal 	</option>
										<option value="verticalDisplay"> 		Vertical  	</option>
									</select>
								</form>
							</div>	

							<div id = "xAxisOrientation">		
								X - Axis
								<form action="demo_form.asp"><br>
									Min Val: 	<input type="text" style ="width:60"	id="minimumXValue">
									Max Val: 	<input type="text" style ="width:60" 	id="maximumXValue">
								</form>
							</div>
							<div id = "yAxisOrientation">		
								Y - Axis
								<form action="demo_form.asp"><br>
									Min Val:	<input type="text" style ="width:60"	id="minimumYValue">
									Max Val:	<input type="text" style ="width:60"	id="maximumYValue">
								</form>
							</div>
						</div>
						<!-- END ORIENTATION -->

						<!-- START EDIT LABLES	-->
						<div id = "editLables">
							<form action="demo_form.asp">
								Graph Title: 		<input type = "text" style ="width:100"	id = "graphTitle" onclick ="
						d3.event.stopPropagation();"onchange='updateTitle()'><br>
								X - Axis Lable:		<input type = "text" style ="width:100"	id = "xAxisLable">
								Y - Axis Lable: 	<input type = "text" style ="width:100"	id = "yAxisLable">
							</form>
						</div>
						<!-- END EDIT LABLES	-->
						</div>
						<div id = "PieGraph" 		style =	"display : none">
							<div> 	Pie Graph Display Options 	</div>
						</div>
						<div id = "LineGraph" 		style = "display : none">
							<div> 	Line Graph Display Options 	</div>
						</div>
						<div id = "BubbleGraph" 	style =	"display : none">
							<div> 	Bubble Graph Display Options </div>
						</div>
					</div>
			</div>
			<script src="http://d3js.org/d3.v3.min.js"></script>
			<script type="text/javascript" src="functions.js"></script><script>createBarGraph(131);</script>
			<script type="text/javascript">
				var select = document.getElementById("GraphType");
				var checked = document.getElementById("dualScale");
				var last_thing = 131;
				var last_title = '';

				select.onchange = toggleDisplays;
				checked.onchange = toggleDualScale;

				function toggleDisplays()
				{
					var ele1 = document.getElementById("BarGraph");
					var ele2 = document.getElementById("PieGraph");
					var ele3 = document.getElementById("LineGraph");
					var ele4 = document.getElementById("BubbleGraph");

					var elements = [ele1, ele2, ele3, ele4];


					for (var i = 0; i < elements.length; i++)
					{
						if (i == select.value)
						{
							elements[i].style.display = "block";
						}
						else
						{
							elements[i].style.display = "none";
						}
					}
				}

				
				function toggleDualScale()
				{
					var ele2 = document.getElementById("bar2");

					if (checked.checked == true)
					{
						ele2.style.display = "block";
					}
					else
					{
						ele2.style.display = "none";
					}
				}
				//var ele = document.getElementById('test');
				var votes = <?php echo $votes_js ?>;
				var votes_female = <?php echo $votes_male_js ?>;
				var votes_male = <?php echo $votes_female_js ?>;
				var votes_tenure_post = <?php echo $votes_posttenure_js ?>;
				var votes_tenure_pre = <?php echo $votes_pretenure_js ?>;

				var json_data = <?php echo $survey_list_js ?>;

				//Set up navigation==========================================================

				function onQuestionSelected(question){
					//DO SOMETHING
					document.getElementById('graph').innerHTML='';
					last_thing = question.id;
					createBarGraph(question.id);
				}

				var w = window.innerWidth/2,
					h = window.innerHeight*3/5,
					x = d3.scale.linear().range([0, w]),
					y = d3.scale.linear().range([0, h]);

				var vis = d3.select("#surveySelect").append("div")
					.attr("class", "chart")
					.style("width", w + "px")
					.style("height", h + "px")
					.append("svg:svg")
					.attr("width", w)
					.attr("height", h);

				var partition = d3.layout.partition()
					.value(function(d) { return d.size; });
					root = json_data;
				var g = vis.selectAll("g")
					.data(partition.nodes(root))
					.enter().append("svg:g")
					.attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; })
					.on("click", click);

				var kx = w / root.dx,
					ky = h / 1;

				g.append("svg:rect")
					.attr("width", root.dy * kx)
					.attr("height", function(d) { return d.dx * ky; })
					.attr("class", function(d) { return d.children ? "parent" : "child"; });

				g.append("svg:text")
					.attr("transform", transform)
					.attr("dy", ".35em")
					.style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; })
					.text(function(d) { return d.name; })
					.call(wrap, root.dy * kx);
					
				function wrap(text, width) {
					text.each(function() {
						var text = d3.select(this),
							words = text.text().split(/\s+/).reverse(),
							word,
							line = [],
							lineNumber = 1,
							lineHeight = 1.0, // ems
							y = text.attr("y"),
							dy = parseFloat(text.attr("dy")),
							tspan = text.text(null).append("tspan").attr("x", 0).attr("y", y).attr("dy", dy + "em");
						while (word = words.pop()) {
							line.push(word);
							tspan.text(line.join(" "));
							if (tspan.node().getComputedTextLength() > width) {
								line.pop();
								tspan.text(line.join(" "));
								line = [word];
								tspan = text.append("tspan").attr("x", 0).attr("y", y).attr("dy", lineNumber * lineHeight + dy + "em").text(word);
							}
						}
					});
				}

				d3.select(window)
					.on("click", function() { click(root); })

				function click(d) {
					if (!d.children) {
						d3.event.stopPropagation();
						//toggle this leaf?
						return;
					}
					if(d.type && d.type === "question"){
						onQuestionSelected(d);
					}

					kx = (d.y ? w - 40 : w) / (1 - d.y);
					ky = h / d.dx;
					x.domain([d.y, 1]).range([d.y ? 40 : 0, w]);
					y.domain([d.x, d.x + d.dx]);

					var t = g.transition()
						.duration(d3.event.altKey ? 7500 : 750)
						.attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; });

					t.select("rect")
						.attr("width", d.dy * kx)
						.attr("height", function(d) { return d.dx * ky; });

					t.select("text")
						.attr("transform", transform)
						.style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; });

					d3.event.stopPropagation();
				}

				function transform(d) {
					return "translate(8," + d.dx * ky / 2 + ")";
				}
				function exportGraph(){
					var text = '<body><div id = "graph"><\/div><script type="text\/javascript" src="http:\/\/mbostock.github.io\/d3\/talk\/20111018\/d3\/d3.js"><\/script><script type="text\/javascript" src="http:\/\/mbostock.github.io\/d3\/talk\/20111018\/d3\/d3.layout.js"><\/script><script src="http:\/\/d3js.org\/d3.v3.min.js"><\/script><link type="text\/css" rel="stylesheet" href="http:\/\/54.186.251.162\/graph.css"\/><script type="text\/javascript" src="http:\/\/54.186.251.162\/functions.js"><\/script><script>createBarGraph('+last_thing+');<\/script><\/body>';


				 	 window.prompt("Copy to clipboard: Ctrl+C, DICK!", text);
				}
				function updateTitle(){
					var title = document.getElementById('graphTitle').value;
					document.getElementById('graph').innerHTML='';
					createBarGraph(last_thing,title);
				}

			</script>
	</head>
</html>