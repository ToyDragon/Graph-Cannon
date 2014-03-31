var scripts = document.getElementsByTagName('script');
var myScript = scripts[0];//gets this script

//parses incoming query string into a JSON object
var parseQueryString = function(url) {
    var a = document.createElement('a');
    a.href = url;
    str = a.search.replace(/\?/, '');

    return deparam(str, true /* coerce values, eg. 'false' into false */);
};

var isArray = Array.isArray || function(obj) {
    return Object.prototype.toString.call(obj) == '[object Array]';
};

var deparam = function( params, coerce ) {
    var obj = {},
        coerce_types = { 'true': !0, 'false': !1, 'null': null };

    // Iterate over all name=value pairs.
    each( params.replace( /\+/g, ' ' ).split( '&' ), function(v, j){
        var param = v.split( '=' ),
            key = decodeURIComponent( param[0] ),
            val,
            cur = obj,
            i = 0,

        // If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
        // into its component parts.
            keys = key.split( '][' ),
            keys_last = keys.length - 1;

        // If the first keys part contains [ and the last ends with ], then []
        // are correctly balanced.
        if ( /\[/.test( keys[0] ) && /\]$/.test( keys[ keys_last ] ) ) {
            // Remove the trailing ] from the last keys part.
            keys[ keys_last ] = keys[ keys_last ].replace( /\]$/, '' );

            // Split first keys part into two parts on the [ and add them back onto
            // the beginning of the keys array.
            keys = keys.shift().split('[').concat( keys );

            keys_last = keys.length - 1;
        } else {
            // Basic 'foo' style key.
            keys_last = 0;
        }

        // Are we dealing with a name=value pair, or just a name?
        if ( param.length === 2 ) {
            val = decodeURIComponent( param[1] );

            // Coerce values.
            if ( coerce ) {
                val = val && !isNaN(val)            ? +val              // number
                    : val === 'undefined'             ? undefined         // undefined
                    : coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
                    : val;                                                // string
            }

            if ( keys_last ) {
                // Complex key, build deep object structure based on a few rules:
                // * The 'cur' pointer starts at the object top-level.
                // * [] = array push (n is set to array length), [n] = array if n is
                //   numeric, otherwise object.
                // * If at the last keys part, set the value.
                // * For each keys part, if the current level is undefined create an
                //   object or array based on the type of the next keys part.
                // * Move the 'cur' pointer to the next level.
                // * Rinse & repeat.
                for ( ; i <= keys_last; i++ ) {
                    key = keys[i] === '' ? cur.length : keys[i];
                    cur = cur[key] = i < keys_last
                        ? cur[key] || ( keys[i+1] && isNaN( keys[i+1] ) ? {} : [] )
                        : val;
                }

            } else {
                // Simple key, even simpler rules, since only scalars and shallow
                // arrays are allowed.

                if ( isArray( obj[key] ) ) {
                    // val is already an array, so push on the next value.
                    obj[key].push( val );

                } else if ( obj[key] !== undefined ) {
                    // val isn't an array, but since a second value has been specified,
                    // convert val into an array.
                    obj[key] = [ obj[key], val ];

                } else {
                    // val is a scalar.
                    obj[key] = val;
                }
            }

        } else if ( key ) {
            // No value was defined, so set something meaningful.
            obj[key] = coerce
                ? undefined
                : '';
        }
    });

    return obj;
};

var each = function (arr, fnc) {
    var data = [];
    for (i = 0; i < arr.length; i++)
        data.push(fnc(arr[i]));
    return data;
};

var info = parseQueryString(myScript.src);

if(info.question)
	createBarGraph(info.question, info.title, info.element_name);
function createBarGraph(question_id, title, element_name){
	if(!element_name)element_name = '#graph'
	if(title == undefined)title = getQuestionTitle(question_id)[0].title;

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
function getAnswers(question_id){
	var query = "SELECT id, answer_label FROM sample_survey_answers WHERE question_id="+question_id;
	var xmlHttp = new XMLHttpRequest();
	xmlHttp.open( "GET", 'query.php?query='+query, false );
	xmlHttp.send( null );
	return JSON.parse(xmlHttp.responseText);
}
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
function getQuestionTitle(question_id){
	var xmlHttp = new XMLHttpRequest();
	var query = "SELECT title FROM sample_survey_questions WHERE id="+question_id;
	xmlHttp.open( "GET", 'query.php?query='+query, false );
	xmlHttp.send( null );
	return JSON.parse(xmlHttp.responseText);
}