/*
 * createBarGraph(question_id, title, element_name)
 *
 * Creates a bar graph representation of the question identified the by
 * question_id, with the given title. Appends the bar graph to the element
 * with the id element_name.
 */
function createBarGraph(question_id, title, element_name){
	if(!element_name)element_name = '#graph'
	if(title == undefined)title = title;

	var answers = getAnswers(question_id);
	var votes = getQuestionResponses(question_id);

	var margin = {top: 60, right: 40, bottom: 120, left: 40},
	width = window.innerWidth/2 - margin.left - margin.right,
	height = window.innerHeight - margin.top - margin.bottom;
		var x = d3.scale.ordinal()
			.rangeRoundBands([0, width], .1);

		var y = d3.scale.linear()
			.range([height, 0]);

		var xAxis = d3.svg.axis()
			.scale(x)
			.orient("bottom");

		var yAxis = d3.svg.axis()
			.scale(y)
			.orient("left")
			.ticks(10);
		
		x.domain(answers.map(function(answer) { return answer.answer_label; }));
		y.domain([0, d3.max(answers, function(answer) { return votes[answer.id]; })]);
		
	var svg = d3.select(element_name).append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");


	svg.append("g")
		.attr("class", "x axis")
		.attr("transform", "translate(0," + height + ")")
		.call(xAxis)
		.selectAll(".tick text")
		.call(wrap, x.rangeBand());

	svg.append("g")
		.attr("class", "y axis")
		.call(yAxis)
		.append("text")
		.attr("transform", "rotate(-90)")
		.attr("y", 6)
		.attr("dy", ".71em")
		.style("text-anchor", "end")
		.text("Votes");

	svg.selectAll(".bar")
		.data(answers)
		.enter().append("rect")
		.attr("class", "bar")
		.attr("x", function(answer) { return x(answer.answer_label); })
		.attr("width", x.rangeBand())
		.attr("y", function(answer) { return y(votes[answer.id]); })
		.attr("height", function(answer) { return height - y(votes[answer.id]); });
	svg.append("text")
        .attr("x", (width / 2))             
        .attr("y", 0 - (margin.top / 2))
        .attr("text-anchor", "middle")  
        .style("font-size", "16px") 
        .style("text-decoration", "underline")  
        .text(title);
	function wrap(text, width) {
		text.each(function() {
			var text = d3.select(this),
				words = text.text().split(/\s+/).reverse(),
				word,
				line = [],
				lineNumber = 0,
				lineHeight = 1.1, // ems
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
					tspan = text.append("tspan").attr("x", 0).attr("y", y).attr("dy", ++lineNumber * lineHeight + dy + "em").text(word);
				}
			}
		});
	}
}
/* 
 * getAnswers(question_id)
 *
 * Queries the database to retreive an array of javascript objects with
 * id and answer_label fields
 *
 * [
 *  {"id":"309","answer_label":"Yes"},
 *  {"id":"310","answer_label":"Maybe"},
 *  {"id":"311","answer_label":"No"}
 * ]
 */
function getAnswers(question_id){
	var query = "SELECT id, answer_label FROM sample_survey_answers WHERE question_id="+question_id;
	var xmlHttp = new XMLHttpRequest();
	xmlHttp.open( "GET", 'query.php?query='+query, false );
	xmlHttp.send( null );
	return JSON.parse(xmlHttp.responseText);
}
/* 
 * getQuestionResponses(question_id)
 *
 * Queries the database to retreive a javascript object with keys representing the answer
 * ids paired with the number of votes.
 *
 * {"417":"500","210","200"}
 */
function getQuestionResponses(question_id){
	var answers = getAnswers(question_id);
	var query = "SELECT COUNT(*) as amt, answer_id FROM sample_survey_response_details WHERE ";
	for(var i = 0; i < answers.length; i++){
		if(i!=0)query+="OR ";
		query+="answer_id = " + answers[i].id+" ";
	}
	query+="GROUP BY answer_id"
	var xmlHttp = new XMLHttpRequest();
	xmlHttp.open( "GET", 'query.php?query='+query, false );
	xmlHttp.send( null );
	var raw = JSON.parse(xmlHttp.responseText);
	var processed = {};
	for(var key in raw){
		processed[raw[key]['answer_id']] = +raw[key]['amt'];
	}
	return processed;
}
/* 
 * getQuestionTitle(question_id)
 *
 * Queries the database to retreive a string representing the title of
 * the question.
 *
 * "Do you believe that Syria will fulfill its obligations under the agreement by the June deadline?"
 */
function getQuestionTitle(question_id){
	var xmlHttp = new XMLHttpRequest();
	var query = "SELECT title FROM sample_survey_questions WHERE id="+question_id;
	xmlHttp.open( "GET", 'query.php?query='+query, false );
	xmlHttp.send( null );
	return JSON.parse(xmlHttp.responseText)[0].title;
}