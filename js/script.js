
jQuery(document).ready(function(){


	var width = jQuery('.graphwindow').width(),
	    height = 500,
	    color = d3.scale.category20c();

	

	setTimeout(function(){ renderGraph([]); }, 1000);
	  

	function renderGraph(cats) {
	
			jQuery('#graph-canvas').empty();

			var w = jQuery('#graph-container').width(),
			    h = jQuery('#graph-container').height()
			var offset_x = (w/2)*-1,
			    offset_y = (h/2)*-1;
			    
			if(jQuery('#graph-container-nav').length > 0) { 
			     offset_x = offset_x - (offset_x * 0.2);
			 }

			var svg = d3.select("#graph-canvas").append("svg")
			    .attr("width", '100%')
			    .attr("height", '100%')
			    .attr("preserveAspectRatio", "xMinYMin meet")
            		    .attr("viewBox", offset_x+" "+offset_y+" "+w+" "+h);

			var force = d3.layout.force()
			    .gravity(.05)
			    .distance(100)
			    .charge(-100);
			    //.size([width, height]);

			var url = catgraphvars.ajaxurl+"?action=catgraphdata";
			if(cats.length > 0) { url += "&cats="+cats.join(','); }

			d3.json(url, function(json) {
			  force
			      .nodes(json.nodes)
			      .links(json.links)
			      .start();

			  var link = svg.selectAll(".link")
			      .data(json.links)
			    .enter().append("line")
			      .attr("class", "link")
			    .style("stroke-width", function(d) { return Math.sqrt(d.weight); });

			  var node = svg.selectAll(".node")
			      .data(json.nodes)
			    .enter().append("g")
			      .attr("class", "node")
			      .call(force.drag);

			  node.append("circle")
			      .attr("r","12")
			      .attr("fill", function(d) { return color(d.group); });;

			  node.append("text")
			      .attr("dx", 12)
			      .attr("dy", ".35em")
			      .text(function(d) { return d.name });

			  force.on("tick", function() {
			    link.attr("x1", function(d) { return d.source.x; })
				.attr("y1", function(d) { return d.source.y; })
				.attr("x2", function(d) { return d.target.x; })
				.attr("y2", function(d) { return d.target.y; });

			    node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
			  });
			});
	
		
	}


	jQuery('.setcat').click(function(e){
	   var id = jQuery(this).attr('rel');
	   renderGraph([id]);
	   e.preventDefault();
	});


});
