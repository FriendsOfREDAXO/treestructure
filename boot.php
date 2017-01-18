<?php

/**
 * TreeStructure Addon.
 *
 * @author post[at]thomasgoellner[dot]de Thomas Goellner
 *
 * @package redaxo5
 *
 * @var rex_addon $this
 */

rex_extension::register('PACKAGES_INCLUDED', function ($params) {

	if (rex::isBackend() && rex::getUser()) {

	    rex_extension::register('CAT_ADDED', function(rex_extension_point $ep) {
	    	if($tid = rex_request('template_id','int',false)) {
	    		try {
	    			$t = rex_article_service::editArticle($ep->getParam('id'), $ep->getParam('clang'), ['name' => $ep->getParam('name'), 'template_id' => $tid]);
	    		}
	    		catch (Exception $e) {
	    			$t = $e->getMessage();
                }
	    	}
	    });

	    rex_extension::register('ART_UPDATED', function(rex_extension_point $ep) {
	    	rex_article_cache::generateMeta($ep->getParam('id'), $ep->getParam('clang'));
	    });


		if(rex_request('page','string', 'treestructure')) {

			// check for request from JS
			if($action = rex_request('action', 'string', false)) {
				if($output = rex_treestructure::action($action)) {
			        header('Content-type: application/json');
			        echo json_encode($output);
			        exit;
				}
			}

		    rex_view::addJsFile($this->getAssetsUrl('js/be.js'));
		    rex_view::addCssFile($this->getAssetsUrl('css/be.css'));
		}
	}
}, rex_extension::EARLY);
