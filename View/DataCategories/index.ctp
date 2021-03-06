<style type="text/css" media="screen">
	<!-- @import url( /js/ext-2.0.1/resources/css/ext-custom.css ); -->
	div.auto_complete    {
	     position         :absolute;
	     width            :250px;
	     background-color :white;
	     border           :1px solid #888;
	     margin           :0px;
	     padding          :0px;
	} 
	.auto_complete ul li {border-bottom: 1px dotted #aaa; list-style-type: none;}
	li.selected    { background-color: #ffb; }
	#trace_results {border: 1px solid #ccc; margin: 20px 0; padding: 10px;}
	#trace_results ul {margin-left: 15px;}
	#tree-div {height: 500px; margin-bottom: 20px;}
	#DataCategoryIndexForm label {display: block; margin-top: 10px;}
	h2.data_category_index {font-size: 200%; font-weight: bold;} 
</style>

<script type="text/javascript" src="/js/ext-2.0.1/ext-custom.js"></script>

<script type="text/javascript">
	Ext.BLANK_IMAGE_URL = '<?php echo $this->Html->url('/js/ext-2.0.1/resources/images/default/s.gif') ?>';
	
	Ext.onReady(function(){
	
		var getnodesUrl = '<?php echo $this->Html->url('/data_categories/getnodes/') ?>';
		var reorderUrl = '<?php echo $this->Html->url('/data_categories/reorder/') ?>';
		var reparentUrl = '<?php echo $this->Html->url('/data_categories/reparent/') ?>';
		
		var Tree = Ext.tree;
		
		var tree = new Tree.TreePanel({
			el:'tree-div',
			autoScroll:true,
			animate:true,
			enableDD:true,
			containerScroll: true,
			rootVisible: true,
			loader: new Ext.tree.TreeLoader({
				dataUrl:getnodesUrl,
				preloadChildren: true
			})
		});
		
		var root = new Tree.AsyncTreeNode({
			text:'Data Categories',
			draggable:false,
			id:'root'
		});
		tree.setRootNode(root);
		
		
		// track what nodes are moved and send to server to save
		
		var oldPosition = null;
		var oldNextSibling = null;
		
		tree.on('startdrag', function(tree, node, event){
			oldPosition = node.parentNode.indexOf(node);
			oldNextSibling = node.nextSibling;
		});
		
		tree.on('movenode', function(tree, node, oldParent, newParent, position){
		
			if (oldParent == newParent){
				var url = reorderUrl;
				var params = {'node':node.id, 'delta':(position-oldPosition)};
			} else {
				var url = reparentUrl;
				var params = {'node':node.id, 'parent':newParent.id, 'position':position};
			}
			
			// we disable tree interaction until we've heard a response from the server
			// this prevents concurrent requests which could yield unusual results
			
			tree.disable();
			
			Ext.Ajax.request({
				url:url,
				params:params,
				success:function(response, request) {
				
					// if the first char of our response is not 1, then we fail the operation,
					// otherwise we re-enable the tree
					
					if (response.responseText.charAt(0) != 1){
						alert(response.responseText);
						request.failure();
					} else {
						tree.enable();
					}
				},
				failure:function() {
				
					// we move the node back to where it was beforehand and
					// we suspendEvents() so that we don't get stuck in a possible infinite loop
					
					tree.suspendEvents();
					oldParent.appendChild(node);
					if (oldNextSibling){
						oldParent.insertBefore(node, oldNextSibling);
					}
					
					tree.resumeEvents();
					tree.enable();
					
					alert("Oh no! Your changes could not be saved!");
				}
			
			});
		
		});
		
		// render the tree
		tree.render();
		root.expand();
	});

	function isNumeric(input) {
		return (input - 0) == input && input.length > 0;
	}

	function findCategory() {
		var input = $('DataCategoryName').value;
		if (isNumeric(input)) {
			var id = input;
		} else {
			var l_bound = input.lastIndexOf('(');
			var r_bound = input.lastIndexOf(')');
			if (l_bound == -1 || r_bound == -1) {
				alert('Error. That input box is expected to contain a category id# wrapped in parentheses somewhere in it.');
				return false;
			}
			var id = input.substring(l_bound + 1, r_bound);
		}
		var url = '/data_categories/trace_category/' + id; 
		new Ajax.Request(url, {
			method: 'get',
			onLoading: function() {
				$('trace_results').show();
				$('trace_results').html("Tracing...");
				$('data_category_autocomplete_loading').show();
			},
			onSuccess: function(transport) {
				if (transport.responseText != undefined) {
					var suggestions = transport.responseText;
				} else {
					var suggestions = '(Error)';
				}
				$('trace_results').html(suggestions);
				$('data_category_autocomplete_loading').hide();
			},
			onFailure: function(transport) {
				$('trace_results').html("Error tracing this data category.");
				$('data_category_autocomplete_loading').hide();
			}
		});
		return false;
	}
	
</script>

<h2 class="data_category_index">Data Categories</h2>
<div id="tree-div"></div>

<div style="margin-top: 20px;">
	<h2 class="data_category_index">Add a Data Category</h2>
	<?php echo $this->Form->create('DataCategory', array('url' => array('controller' => 'data_categories', 'action' => 'add'))); ?>
	<strong>Category Name</strong>(s)<br />
	Multiple names go on separate lines. Child-categories can be indented under parent-categories with one hyphen or tab per level. Example:
<pre style="background-color: #eee; font-size: 150%; margin-left: 20px; width: 200px;">Fruits
-Apples
--Granny Smith
--Red Delicious
-Nanners
Vegetables
-Taters</pre>
	<?php echo $this->Form->input('name', array('type' => 'textarea', 'label' => null, 'style' => 'width: 100%;')); ?>
	<?php echo $this->Form->input('parent_id', array('label' => 'Parent ID (optional)', 'type' => 'text', 'style' => 'width: 400px;')); ?>
	<?php echo $this->Form->end('Submit'); ?>
</div>