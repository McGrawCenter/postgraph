<?php
/*
	Plugin Name: Post Graph
	Plugin URI:
	Description: Insert a network graph visualization based on blog posts and categories and/or tags using the shortcode [postgraph]. 
	Version: 1.0
	Author: Ben Johnston
*/


class PostGraph {

  function __construct() {
    add_action( 'wp_enqueue_scripts', array( $this, 'postgraph_scripts' ));
    add_shortcode( 'postgraph', array( $this, 'insert_postgraph_graph' ));
    add_action( 'wp_ajax_catgraphdata', array( $this, 'graph_data'));
  }
  
  
  	/******************
  	* Load css and js
  	**********************************/
  
	function postgraph_scripts() {
	
	  wp_register_style('postgraph-css', plugins_url('css/style.css',__FILE__ ));
	  wp_enqueue_style('postgraph-css');

	  
	  wp_enqueue_script( 'd3', plugins_url('js/d3/d3.v3.min.js',__FILE__ ) , array ( 'jquery' ), 1.1, true);
	  wp_enqueue_script( 'postgraph', plugins_url('/js/script.js',__FILE__ ) , array ( 'jquery' ), 1.1, true);  


	  $data = array( 'ajaxurl'=> admin_url( 'admin-ajax.php' ) );		
	  wp_localize_script( 'postgraph', 'catgraphvars', $data );  
	   
		 	       	
	  wp_register_style( 'd3-css', plugins_url('/js/d3/d3.css',__FILE__ ));
	  wp_enqueue_style( 'd3-css' );

	}  
  
	/******************
  	* Shortcode 'postgraph'
  	**********************************/

	function insert_postgraph_graph( $atts ){

	  $graph_js = plugins_url('/js/graph.js', __FILE__);
	  $adminurl = admin_url( 'admin-ajax.php' );
	  
	  $html = "<script>var ajaxurl = '{$adminurl}'</script>
	  <div id='graph-container'>
	    <div id='graph-canvas'></div>";
	  if(isset($atts['nav'])) { 
	     $html .= "  <div id='graph-container-nav'>".$this->postgraph_nav()."</div>";
	  }
	  $html .= "</div>";
	  return $html;
	}
	  


	/******************
  	* If nav attribute in shortcode, add nav
  	**********************************/

	function postgraph_nav() {

	  if($cats = get_categories()) {
	    $returnStr = "<ul>";
	    foreach($cats as $cat) {
	       $returnStr .= "<li><a href='#' class='setcat' rel='{$cat->term_id}'>{$cat->name}</a></li>";
	    } 
	    $returnStr .= "<li><a href='#' class='setcat' rel=''>Show All</a></li>";   
	    $returnStr .= "<ul>";    
	  }
	  
	  return $returnStr;
	}



	/******************* AJAX ******************/
	
	/******************
  	* node lookup, used in generating graph data
  	**********************************/

	function nodeLookup($term_id, $obj) {

	  foreach($obj as $o) {
	    if($term_id == $o->id) { return $o->nodecnt; }
	  }
	}


	
	/******************
  	* Generate graph data in json
  	**********************************/
	function graph_data() {

		$cats = explode(',',$_GET['cats']);
		
		$nodes = array();
		$links = array();
		$nodecnt = 0;

		$args = array(
		  'numberposts' => -1,
		  'post_type'   => 'post'
		);
		

		if($_GET['cats'] != "") { $args['category__in'] = $cats; }
		 
		if($objects = get_posts( $args )) {
		

		  foreach($objects as $key=>$object) {
		 
		    $title = (string) $object->post_title;
		    
		    $x = new StdClass();
		    $x->id = $object->ID;
		    $x->nodecnt = $nodecnt;
		    $x->type = "post";
		    $x->name = $title."";
		    $x->thumb = get_the_post_thumbnail($object->ID, 'thumbnail');
		    $x->link = get_permalink($object->ID);
		    $x->group = 1;
		    $x->links = array();
		    $nodes[] = $x;
		    
		    
		    
		    if($terms = get_the_terms( $object->ID, 'category' )) {
		       foreach($terms as $term) {
			if(!$target = $this->nodeLookup($term->term_id,$nodes)) { 
			  $nodecnt++; 
		    	  $y = new StdClass();
		    	  $y->id = $term->term_id;
		    	  $y->nodecnt = $nodecnt;
		    	  $y->type = "cat";
		    	  $y->name = $term->name;
		    	  $x->link = get_permalink($object->ID);
		    	  $y->group = 27;
		    	  $nodes[] = $y;
		    	  $target = $nodecnt;
			}
			$x->links[] = $target;
		       }// end foreach
		     } // end if
		     $nodecnt++;
		  } 
	  
		}// if objects
		
		
		
		foreach($nodes as $node) {
			foreach($node->links as $link) {
			  $z = new StdClass();
		  	  $z->source = $node->nodecnt;
		  	  $z->target = $link;
		  	  $z->weight = 1;
		  	  $links[] = $z;
			}
		}
		
		$returnObj = new StdClass();
		$returnObj->nodes = $nodes;
		$returnObj->links = $links;
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($returnObj);
		die();
	}

}

new PostGraph();
