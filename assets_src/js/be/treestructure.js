/*
 REDAXO TreeStructure JavaScript library
 */

var rex_treestructure = {
	treeCss : 'treetructure--tree',
	new_article : null,
	new_category : null,
	is_touch : "ontouchstart" in document.documentElement,

	init : function() {
		this.createEvents();
		this.createTree();
		if(this.is_touch) {
			$('#' + this.treeCss).addClass('is--touch');
		}
		else {
			$('#' + this.treeCss).addClass('is--no-touch');
		}
	},

	createEvents : function() {
		var tree = $('#' + this.treeCss);

		// attach click events on option icons...
		tree.on({
			'click.treestructure' : function(e) {

				var action = $(this).attr('class').match(/treestructure--options--([^ ]+)( |$)/),
					$tree = $(this).parents('#'+rex_treestructure.treeCss).first(),
					$li = $(this).parents('li').first(),
					id = $li.attr('data-id'),
					node = $tree ? $tree.tree('getNodeById', id) : null;

				if(!action || !$tree || $(this).css('opacity')<1) {
					return false;
				}

				action = action[1].replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });

				if(typeof rex_treestructure.itemActions[action] == 'function') {
					if($(this).attr('data-confirm')) {
						var valid = true;
						if(e.shiftKey) {
							valid = node.name == prompt(rex_treestructure._translate($(this).attr('data-confirm'),{name:node.name}) + "\n" + rex_treestructure._translate('type_in_name_to_confirm',{name:node.name}));
						}
						else {
							valid = confirm(rex_treestructure._translate($(this).attr('data-confirm'),{name:node.name}));
						}

						if(!valid) {
							return false;
						}
					}

					rex_treestructure.itemActions[action](node, $li, $tree, e.shiftKey);
				}

				return false;
			},
			'mousedown.treestructure' : function(e) {
				// prevent dragging on option spans
				try { e.preventDefault(); }	catch(ev) {}
				return false;
			}

		},'.treestructure--options span[class*="treestructure--options--"]');

		// submit forms within the tree
		tree.on({
			'submit.treestructure' : function(e) {
    			try { e.preventDefault(); } catch(ev) {};

				if($(this).find('input[name="name"]').length) {
					$(this).find('input[name="name"]').val($.trim($(this).find('input[name="name"]').val()).replace(/\s/g,' ').replace(/ +/g,' '));

					if($(this).find('input[name="name"]').val() == '') {
						return false;
					}
				}

				rex_treestructure.ajaxAction($(this).serialize(), rex_treestructure.updateTree);

				return false;
			},
			'reset.treestructure' : function() {
				var node = $(this).tree('getNodeById', -1) || null;

				if(node) {
					// there is a "new" node open - remove it!
					$(this).tree('removeNode', node);
				}

				// remove any open edit fields...
				if($(this).find('.treestructure--edit-node').length) {
					$(this).find('.treestructure--edit-node').each(function(i, el){
						var $li = $(el).parents('li').first();
						$li.removeClass('is--editing');
						$li.find('>.jqtree-element>.jqtree-title').html($li.data('tmp-html'));
						$li.data('tmp-html', null);

						$(el).remove();
					});
				}

				return false;
			}
		});
	},

	updateTree : function() {
		var tree = $('#' + rex_treestructure.treeCss),
			states = $('#' + rex_treestructure.treeCss).tree('getState');

		tree.tree(
			'loadDataFromUrl',
			tree.attr('data-url'),
			null,
			function() {
				tree.tree('setOption', 'dragAndDrop', true);
				tree.tree('setState', states);

				rex_treestructure.editTree(false);
			}
		);
	},

	editTree : function() {
		var tree = $('#' + rex_treestructure.treeCss);

		if(typeof arguments[0] != 'undefined') {
			var on = arguments[0] === true;
			if(on) {
				// disable Drag&Drop
				tree.tree('setOption', 'dragAndDrop', false);
				
				// add CSS class
				tree.addClass('is--edit');

				// watch ESC key to disable edit mode again
				$(document).on('keyup.treestructure', function(e) {
					if (e.keyCode == 27) { // escape key maps to keycode `27`
						rex_treestructure.editTree(false);
					}
				});
			}
			else {
				// remove any form
				tree.trigger('reset.treestructure');

				// remove any ESC functionality
				$(document).off('keyup.treestructure');

				// enable Drag&Drop again
				tree.tree('setOption', 'dragAndDrop', true);
				
				// remove CSS class
				tree.removeClass('is--edit');
			}
		}
		else {
			// return if tree is in edit mode...
			return tree.hasClass('is--edit');
		}
	},

	loadingTree : function() {
		var on = typeof arguments[0] != 'undefined' && arguments[0] === true,
			tree = $('#' + rex_treestructure.treeCss);

		if(on) {
			// add CSS class
			tree.addClass('is--loading');
		}
		else {
			// add CSS class
			tree.removeClass('is--loading');
		}

		return tree;
	},

	_translate : function(key, replacements) {
		var $tree = $('#' + rex_treestructure.treeCss) || null,
			translations = null;

		if($tree.attr('data-translations')) {
			translations = JSON.parse($tree.attr('data-translations'));

			if(typeof translations == 'object') {
				if(typeof translations[key] != 'undefined') {
					key = translations[key];
				}
			}
		}

		if(typeof replacements == 'object') {
			for(var p in replacements) {
				key = key.replace('{{' + p + '}}', replacements[p]);
			}
		}

		return key;
	},

	ajaxAction : function(data) {
		if(typeof(data) == 'undefined') {
			return false;
		}

		var tree = rex_treestructure.loadingTree(true),
			jqxhr,
			onSuccess = typeof(arguments[1]) == 'function' ? arguments[1] : null,
			formData;

		// convert it into params
		if(data instanceof FormData || typeof data == 'string') {
			formData = data
		}
		else {
			formData = Object.keys(data).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }).join('&');
		}

		var request = new XMLHttpRequest();
		request.open(tree.attr('method') || 'GET', tree.attr('action'), true);
		request.setRequestHeader("Content-type","application/x-www-form-urlencoded");

		if(onSuccess) {
			request.onSuccess = onSuccess;
		}

		request.onload = function() {
			var data = JSON.parse(this.responseText),
				status = this.status,
				tree = rex_treestructure.loadingTree(false),
				alert,
				duration = 2000,
				wait = 0;

			if (request.status >= 200 && request.status < 400) {
				// SUCCESS!
				
		    	if(this.onSuccess) {
					this.onSuccess(data);
				}

		  	} else {
		    	// ERROR
		    	
			}

			// ALWAYS...

			// display alerts?!
			if(typeof data.notice != 'undefined') {
				alert = $('<div id="treestructure--alert" class="alert alert-success" />').text(data.notice);
			}
			else if(typeof data.error != 'undefined') {
				alert = $('<div id="treestructure--alert" class="alert alert-danger" />').text(data.error);
				wait = 3000;
			}

			if(alert) {
				alert.insertBefore(tree);

				alert.data('fadeFunc', function(duration){ $(this).animate({'opacity' : 0}, duration, function(){ $(this).remove(); }); }.bind(alert, duration));

				if(wait > 0) {
					alert.data('timeout', window.setTimeout(alert.data('fadeFunc'), wait));
				}
				else {
					alert.data('fadeFunc')();
				}
			}
		};

		request.send(formData);
	},

	itemActions : {
		delete : function(node, $li, $tree) {
			if($li.hasClass('cannot-delete')) {
				return false;
			}

			rex_treestructure.ajaxAction({
				action : 'delete',
				id : node.id,
				type : node.type,
				force : arguments[3] === true
			}, rex_treestructure.updateTree);
		},

		edit : function(node, $li, $tree) {
			if($li.hasClass('cannot-edit')) {
				return false;
			}
			rex_treestructure.editTree(true);

			rex_treestructure.ajaxAction({
				action : 'editform',
				category_id : -1,
				id : node.id,
				type : node.type
			}, function(data) {
				$li.addClass('is--editing').data('tmp-html', $li.find('>.jqtree-element>.jqtree-title').html());
				$li.find('>.jqtree-element>.jqtree-title').html(data.html);

				// focus input fields
				$li.find('>.jqtree-element>.jqtree-title').find('input').focus();
			}.bind($li)
			);

		},

		status : function(node, $li, $tree) {
			if($li.hasClass('cannot-publish')) {
				return false;
			}
			rex_treestructure.ajaxAction({
				action : 'status',
				id : node.id,
				type : node.type,
				status : node.status ? false : true
			}, rex_treestructure.updateTree);
		},

		newCategory : function(node, $li, $tree) {
			if($li.hasClass('cannot-edit')) {
				return false;
			}
			this._newElement(
				node,
				$li,
				$tree,
				'category'
			);
		},

		newArticle : function(node, $li, $tree) {
			if($li.hasClass('cannot-edit')) {
				return false;
			}
			this._newElement(
				node,
				$li,
				$tree,
				'article'
			);
		},

		_newElement : function(node, $li, $tree, type) {
			rex_treestructure.editTree(true);

			rex_treestructure.ajaxAction({
				action : 'editform',
				category_id : node.id,
				type : type
			}, function(data) {

				this.newNode.label = data.html;

				if(this.targetNode != null) {
					this.targetNode = this.$tree.tree('getNodeById', this.targetNode.attr('data-id'));
					var test = this.$tree.tree('addNodeAfter', this.newNode, this.targetNode);
				}
				else {
					var test = this.$tree.tree('appendNode', this.newNode, this.node);
				}

				// open li if not already opened...
				this.$tree.tree('openNode', this.node, false);

				// focus input fields
				$(test.element).find('input').focus();
			}.bind({
				node : node,
				newNode : {
					'id' : -1,
					'type' : type,
					'status' : false,
					'has_children' : false,
					'cat_perm' : node.cat_perm
				},
				$tree : $tree,
				targetNode : $li.find('>ul.jqtree_common>li.treestructure--'+type).length ? $li.find('>ul.jqtree_common>li.treestructure--'+type).last() : null
			})
			);
		},

		view : function(node, $li, $tree) {
			window.open(node.url);
		},

		metadata : function(node, $li, $tree) {
			window.location.href = '?page=content/metainfo&article_id=' + node.id;
		},

		functions : function(node, $li, $tree) {
			window.location.href = '?page=content/functions&article_id=' + node.id;
		},

		_expandCollapseAll : function($tree) {
			var open = (arguments[1] === true && typeof arguments[2] == 'undefined') || (arguments[2] === true),
				tree = typeof arguments[2] != 'undefined' ? arguments[1] : $tree.tree('getTree');

			if(tree.type != 'root') {
				$tree.tree(open ? 'openNode' : 'closeNode', tree);
			}

			tree.iterate(
			    function(node, level) {
		            $tree.tree(open ? 'openNode' : 'closeNode', node);
					return true;
			    }
			);
		},

		expandAll : function(node, $li, $tree) {
			this._expandCollapseAll($tree, node, true);
		},

		collapseAll : function(node, $li, $tree) {
			this._expandCollapseAll($tree, node, false);
		},
	},

	createTree : function() {
		$('#' + this.treeCss).tree({
			autoEscape : false,
			autoOpen : 1,
			useContextMenu : false,
			dragAndDrop: true,
    		saveState: 'treestructure-tree',
    		selectable : false,
			onCanMoveTo: function(moved_node, target_node, position) {
				var sameCategory = moved_node.parent.id == target_node.parent.id,
					nextTargetItem = target_node.getNextSibling(),
					prevTargetItem = target_node.getPreviousSibling();


				if(position != 'inside' && !target_node.parent.parent) {
					// cannot move element outside first element
					return false;
				}

				if(position == 'inside') {

					if(target_node.type == 'article') {
						// cannot move element into articles
						return false;
					}

					if(moved_node.type == 'article' && moved_node.parent.id == target_node.id) {
						// cannot move element when it is already within target category
						return false;
					}

					if(moved_node.permissions.match(/cannot-move/) && moved_node.parent.id != target_node.id) {
						// can only move element within its parent category (permissions!)
						return false;
					}
				}
				else {
					if(moved_node.permissions.match(/cannot-move/) && moved_node.parent.id != target_node.parent.id) {
						// can only move element within its parent category (permissions!)
						return false;
					}
				}

				if(!sameCategory) {
					p = target_node.parent;
					while(p) {
						if(p.id == moved_node.id) {
							// don't move within itself
							return false;
							break;
						}
						p = p.parent;
					}
				}

				var moveNodeBeforeTargetNode = true;

				for(var i = 0; i < moved_node.parent.children.length; i++) {
					var child = moved_node.parent.children[i];
					
					if(child.id == moved_node.id) {
						break;
					}
					else if(child.id == target_node.id) {
						moveNodeBeforeTargetNode = false;
						break;
					}
				}

				if(moveNodeBeforeTargetNode) {
					nextTargetItem = nextTargetItem ? nextTargetItem.getNextSibling() : nextTargetItem;
					prevTargetItem = prevTargetItem ? prevTargetItem.getNextSibling() : prevTargetItem;
				}	

				if(moved_node.type == 'article' && position == 'after' && nextTargetItem && nextTargetItem.type != 'article') {
					return false;
				}

				if(moved_node.type == 'category' && position == 'after')  {
					if(nextTargetItem && prevTargetItem) {
						if(prevTargetItem.type != 'category') {
							return false;
						}
					}
					else if(prevTargetItem && prevTargetItem.type != 'category') {
						return false;
					}
					else if(target_node.type != 'category') {
						return false;
					}
				}

				if(position == 'inside' && target_node.children.length) {
					position = 'after';

					for (var i=target_node.children.length-1; i >= 0;  i--) {
					    var child = target_node.children[i];
					    if(child.type == moved_node.type) {
					    	target_node = child;
					    	break;
					    }
					}
					return true;
				}

				return true;
			},
			onCanMove : function(node) {
				if (! node.parent.parent) {
					// Example: Cannot move root node
					return false;
				}
				else {
					return true;
				}
			},
			onCreateLi: function(node, $li) {

				// attach classes...
				$li.addClass('treestructure--' + node.type);
				$li.addClass(node.status ? 'is--online' : 'is--offline');
				if(node.has_children) {
					$li.addClass('has--children');
				}

				if(node.cat_perm) {
					$li.addClass('is--allowed');
				}

				if(node.permissions) {
					$li.addClass(node.permissions);
				}

				$li.addClass('has--prior-' + node.prior);

				if(node.id < 0) {
					$li.addClass('is--new');
				}
				else {
					$('#' + rex_treestructure.treeCss).next('.treestructure--options').clone(1,1).insertAfter($li.find('.jqtree-title'));
				}

				// add ID to LI
				$li.attr('data-id', node.id);
			}
		}).on({
			'tree.move' : function(event) {
				event.preventDefault();

				var move_info = event.move_info;

				target_node = null;
				position = null;

				move_info.do_move(target_node, position);

				// and now get all data to submit movement to REDAXO:
				rex_treestructure.ajaxAction({
					action : 'move',
					id : move_info.moved_node.id,
					category_id : move_info.position == 'inside' ? move_info.target_node.id : move_info.target_node.parent.id,
					prior : move_info.position == 'inside' ? 1 : parseInt(move_info.target_node.prior),
					type : move_info.moved_node.type
				}, rex_treestructure.updateTree);

				return true;
			}
		});
	}
}


 $(document).on('rex:ready', function (event, container) {
 	rex_treestructure.init();
});