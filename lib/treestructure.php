<?php

/**
 * @package redaxo\treestructure
 *
 * @internal
 */

abstract class rex_treestructure {

	private static function isAllowed($category_id, $type=null) {
		if ($category_id instanceof rex_category) {
			$category = $category_id;
		}
		else if($category_id instanceof rex_article) {
			$category = $category_id->getCategory();
		}
		else {
			$category_id = (int) $category_id;
			if(!is_nan($category_id) && $category_id > 0) {
				if(($category = rex_category::get($category_id)) instanceof rex_category) {
				}
				else if(($category = rex_article::get($category_id)) instanceof rex_article) {
					$category = $category->getCategory();
				}
				else {
					$category = null;
				}
			}
		}

		if(empty($category)) {
			$category_id = 0;
		}
		else {
			$category_id = $category->getId();
		}

		$KATPERM = rex::getUser()->getComplexPerm('structure')->hasCategoryPerm($category_id);

		switch($type) {
			case 'delete' :
				return self::isAllowed($category->getParent());
			case 'publishCategory' :
				return $KATPERM && rex::getUser()->hasPerm('publishCategory[]');
				break;
			case 'publishArticle' :
				return $KATPERM && rex::getUser()->hasPerm('publishArticle[]');
				break;
			case 'moveCategory' :
				return $KATPERM && rex::getUser()->hasPerm('moveCategory[]');
				break;
			case 'moveArticle' :
				return $KATPERM && rex::getUser()->hasPerm('moveArticle[]');
				break;
			case 'category' :
				return join(' ',array_filter(array(
					$KATPERM && rex::getUser()->hasPerm('publishCategory[]') ? 'can-publish' : 'cannot-publish',
					$KATPERM && rex::getUser()->hasPerm('moveCategory[]') ? 'can-move' : 'cannot-move',
					$KATPERM ? 'can-edit' : 'cannot-edit',
					$category_id && self::isAllowed($category->getParent()) ? 'can-delete' : 'cannot-delete'
				)));
				break;
			case 'article' :
				return join(' ',array_filter(array(
					$KATPERM && rex::getUser()->hasPerm('publishArticle[]') ? 'can-publish' : 'cannot-publish',
					$KATPERM && rex::getUser()->hasPerm('moveArticle[]') ? 'can-move' : 'cannot-move',
					$KATPERM ? 'can-edit can-delete' : 'cannot-edit cannot-delete'
				)));
				break;
			default :
				return $KATPERM;
				break;
		}
	}

	private static function deepDelete($category) {
		$message = '';

		if(is_object($category)) {
			if(!self::isAllowed($category, 'delete')) {
				return rex_i18n::msg('treestructure_error_not_enough_rights');
			}

			// remove articles...
			foreach($category->getArticles() as $article) {
				if($article->getValue('startarticle') == 1) {
					continue;
				}
				try {
					rex_article_service::deleteArticle($article->getId());
				}
				catch (Exception $e) {
					throw new rex_api_exception($e->getMessage());
				}
			}

			foreach($category->getChildren() as $subcat) {
				try {
					self::deepDelete($subcat);
				}
				catch (Exception $e) {
					throw new rex_api_exception($e->getMessage());
				}
			}

			try {
				$message = rex_category_service::deleteCategory($category->getId());
			} catch (Exception $e) {
				throw new rex_api_exception($e->getMessage());
			}
		}

		return $message;
	}

	public static function action($action) {
		$category_id = rex_request('category_id', 'int');
		$id = rex_request('id', 'int');
		$clang = rex_request('clang', 'int');
		$type = rex_request('type', 'string', null);
		$name = rex_request('name', 'string', null);
		$prior = rex_request('prior', 'int', 0);
		$template_id = rex_request('template_id', 'int', -1);
		$status = rex_request('status', 'int', -1);

		$result = array();

		if($action == 'editform') {
			$result = array(
				'html' => self::getHtml('edit-element')
			);
		}
		else if($action == 'jsontree') {
			$result = self::getJSONTreeData();
		}
		else if($action == 'htmltree') {
			$result = self::getJSONTreeData(true);
		}
		else if($action == 'delete' && !empty($id) && !empty($type)) {
			if(!self::isAllowed($id, 'delete')) {
				$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
			}
			else if($type == 'article') {
				try {
				   $result['notice'] = rex_article_service::deleteArticle($id);
				} catch (Exception $e) {
					$result['error'] = $e->getMessage();
				}
			}
			else {
				if(rex_request('force', 'string', false) == 'true') {
					try {
						$result['notice'] = self::deepDelete(rex_category::get($id));
					}
					catch (Exception $e) {
						$result['error'] = $e->getMessage();
					}
				}
				else {
					try {
						$result['notice'] = rex_category_service::deleteCategory($id);
					} catch (Exception $e) {
						$result['error'] = $e->getMessage();
					}
				}
			}
		}
		else if($action == 'status' && $category_id >= 0 && $id > 0 && $status > -1) {
			if($type == 'article' && !self::isAllowed($id, 'publishArticle')) {
				$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
			}
			else if($type == 'category' && !self::isAllowed($id, 'publishCategory')) {
				$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
			}
			else {
				try {
					if($type == 'article') {
						$result['notice'] = rex_i18n::msg('treestructure_' . $type . '_status_' . rex_article_service::articleStatus($id, $clang, (bool) $status));
					}
					else {
						$result['notice'] = rex_i18n::msg('treestructure_' . $type . '_status_' . rex_category_service::categoryStatus($id, $clang, (bool) $status));
					}
				} catch (Exception $e) {
					$result['error'] = $e->getMessage();
				}
			}
		}
		else if($action == 'edit' && !empty($name) && !empty($type)) {
			if(!self::isAllowed($id)) {
				$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
			}
			else {
				$data = array(
					'catname' => $name,
					'name' => $name
				);

				if($template_id>-1) {
					$data['template_id'] = $template_id;
				}
				else
				{
					$data['template_id'] = rex::getProperty('default_template_id');
				}

				if($id == 0) {
					$data['catpriority'] = 99999999;
					$data['category_id'] = $category_id;
				}

				if($type == 'article') {
					if(!empty($data['catpriority'])) {
						$data['priority'] = $data['catpriority']; unset($data['catpriority']);
					}
				}

				if($id == 0 && $category_id >= 0) {

					try {
						if($type == 'article') {
							$result['notice'] = rex_article_service::addArticle($data);
						}
						else {
							$result['notice'] = rex_category_service::addCategory($category_id, $data);
						}
					} catch (Exception $e) {
						$result['error'] = $e->getMessage();
					}
				}
				else if($id > 0) {
					try {
						if($type == 'article') {
							$result['notice'] = rex_article_service::editArticle($id, $clang, $data);
						}
						else {
							$result['notice'] = rex_category_service::editCategory($id, $clang, $data);
						}
					} catch (Exception $e) {
						$result['error'] = $e->getMessage();
					}

					if($type != 'article') {
						// make sure that catname and artname are the same...
						try {
							rex_article_service::editArticle($id, $clang, ['name' => $data['catname'], 'template_id' => $data['template_id']]);
						} catch (Exception $e) {

						}
					}
				}
			}
		}
		else if($action == 'move' && !empty($id) && !empty($prior) && !empty($type)) {
			$data = array();

			if($type == 'category' && is_object($src = rex_category::get($id, $clang))) {
				if($src->getValue('parent_id') != $category_id) {

					if(!self::isAllowed($src->getId(), 'moveCategory')) {
						$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
					}
					else {
						// we have to move the category!
						try {
							if(rex_category_service::moveCategory($src->getId(), $category_id)) {
								$result['notice'] = rex_i18n::msg('treestructure_' . $type . '_moved', $src->getName());
							}
							else {
								$result['error'] = rex_i18n::msg('treestructure_' . $type . '_not_moved', $src->getName());
							}
						}
						catch (Exception $e) {
							$result['error'] = $e->getMessage();
						}

						if(empty($result['error'])) {
							$src = rex_category::get($id, $clang);
						}
					}
				}

				if($prior != $src->getPriority()) {
					$data['catpriority'] = $prior;
				}
			}
			elseif($type == 'article' && $src = rex_article::get($id, $clang)) {
				if($src->getCategoryId() != $category_id) {
					if(!self::isAllowed($src->getId(), 'moveArticle')) {
						$result['error'] = rex_i18n::msg('treestructure_error_not_enough_rights');
					}
					else {
						// we have to move the article!
						try {
							if(rex_article_service::moveArticle($src->getId(), $src->getCategoryId(), $category_id)) {
								$result['notice'] = rex_i18n::msg('treestructure_' . $type . '_moved', $src->getName());
							}
							else {
								$result['error'] = rex_i18n::msg('treestructure_' . $type . '_not_moved', $src->getName());
							}
						}
						catch (Exception $e) {
							$result['error'] = $e->getMessage();
						}
					}
				}

				if($prior != $src->getPriority()) {
					$data['priority'] = $prior;
					$data['name'] = $src->getName();
					$data['template_id'] = $src->getTemplateId();
				}
			}

			if(empty($result['error']) && !empty($data)) {
				try {
					if($type == 'article') {
						$result['notice'] = (isset($result['notice']) ? $result['notice'] . "\n" : '') . rex_article_service::editArticle($id, $clang, $data);
					}
					else {
						$result['notice'] = (isset($result['notice']) ? $result['notice'] . "\n" : '') . rex_category_service::editCategory($id, $clang, $data);
					}
				} catch (Exception $e) {
					$result['error'] = $e->getMessage();
				}

				if(empty($result['error'])) {
					$parent = $type == 'article' ? $src->getCategory() : $src->getParent();
					if(is_object($parent)) {
						foreach($parent->getArticles() as $child) {
							rex_article_cache::generateMeta($child->getId(), $clang);
						}
					}
				}
			}
		}

		if(empty($result)) {
			$result['error'] = rex_i18n::msg('treestructure_error');
		}
		return $result;
	}

	private static function getHtml($type) {
		if(file_exists($file = dirname(__FILE__) . '/../templates/'.$type.'.php')) {
			ob_start();
			include($file);
			$output.= ob_get_contents();
			ob_end_clean();

			return $output;
		}

		return null;
	}

	private static function getTreeData($category = 0, $asHtml = false) {
		$cat = array(
			'id' => 0,
			'name' => rex::getServerName(),
			'status' => true,
			'children' => array(),
			'articles' => array(),
			'prior' => -1,
			'clang' => rex_request('clang', 'int', rex_clang::getStartId())
		);

		if(empty($category)) {
			$cat['children'] = rex_category::getRootCategories(false, $cat['clang']);
			$cat['articles'] = rex_article::getRootArticles(false, $cat['clang']);
		}
		else {
			if (! $category instanceof rex_category) {
				$category = rex_category::get(is_object($category) ? $category->getId() : (int) $category);
			}

			if ($category instanceof rex_category) {
				$cat = array(
					'id' => $category->getId(),
					'name' => $category->getName(),
					'status' => (bool) $category->isOnline(),
					'children' => $category->getChildren(),
					'articles' => $category->getArticles(),
					'prior' => $category->getPriority(),
					'clang' => $cat['clang']
				);
			}
		}

		$hasChildren = !empty($cat['children']) || (!empty($cat['articles']) && count($cat['articles'])>1);

		// render jqTree Data Array
		$data = array(
			'label' => htmlspecialchars($cat['name']),
			'id' => $cat['id'],
			'type' => $cat['id']==0 ? 'root' : 'category',
			'status' => $cat['status'],
			'has_children' => $hasChildren,
			'permissions' => self::isAllowed($cat['id'], 'category'),
			'prior' => $cat['prior'],
			'url' => rex_getUrl($cat['id'], $cat['clang'])
		);

		if(!(bool) $asHtml) {
			$data['state'] = array('opened'=>true);
		}

		if($hasChildren) {
			$data['children'] = array();

			foreach($cat['children'] as $obj) {
				$data['children'][] = self::getTreeData($obj, $asHtml);
			}

			foreach($cat['articles'] as $obj) {
				if(!$obj->isStartArticle()) {
					$data['children'][] = array(
						'label' => htmlspecialchars($obj->getName()),
						'id' => $obj->getId(),
						'type' => 'article',
						'status' => $obj->isOnline(),
						'prior' => $obj->getValue('priority'),
						'permissions' => self::isAllowed($obj, 'article'),
						'url' => $obj->getUrl()
					);
				}
			}
		}

		return $data;
	}

	private static function getJSONTreeData($asHtml=false) {

		$category_id = rex_request('category_id', 'int');
		$mountpoints = rex::getUser()->getComplexPerm('structure')->getMountpoints();
		$data = array();

		if (count($mountpoints) == 1) {
			$category_id = current($mountpoints);
		}

		if (count($mountpoints) > 0 && $category_id == 0) {
		}
		else {
			$mountpoints = [$category_id];
		}

		foreach($mountpoints as $category) {
			$data[] = self::getTreeData($category, $asHtml);
		}

		if($asHtml) {
			$data = array(
				'html' => self::getHtmlData($data)
			);
		}

		return $data;
	}

	private static function getHtmlData($items) {
		$out = '';

		if(!empty($items)) {
			$out.= '<ol class="treestructure--tree">';

			while($item = array_pop($items)) {
				$out.= '<li class="treestructure--tree--item"';
				foreach($item as $k=>$v) {
					if(!is_array($v)) {
						$out.=' data-' . preg_replace('/_/','',$k) . '="' . (string) $v . '"';
					}
					if($k=='permissions') {
						$out.=' data-permissions="' . (string) $v . '"';
					}
					else if(!empty($v)) {
						$out.=' data-has-' . $k . '="1"';
					}
				}
				unset($k, $v);

				$out.= '><div class="tree--item--handle">' . $item['label'];

				// create icons
				$out.='<span class="treestructure--options">';

				$out.='<span class="treestructure--options--new-category" title="' . rex_i18n::msg('add_category') . '">' . rex_i18n::msg('add_category') . '</span>';
				$out.='<span class="treestructure--options--new-article" title="' . rex_i18n::msg('add_article') . '">' . rex_i18n::msg('article_add') . '</span>';
				$out.='<span class="treestructure--options--new-category" title="' . rex_i18n::msg('add_category') . '">' . rex_i18n::msg('add_category') . '</span>';
				$out.='<span class="treestructure--options--new-article" title="' . rex_i18n::msg('add_article') . '">' . rex_i18n::msg('article_add') . '</span>';
				$out.='<span class="treestructure--options--edit" title="' . rex_i18n::msg('change') . '">' . rex_i18n::msg('change') . '</span>';
				$out.='<span class="treestructure--options--view" title="' . rex_i18n::msg('show') . '">' . rex_i18n::msg('show') . '</span>';
				$out.='<span class="treestructure--options--status" title="' . rex_i18n::msg('treestructure_status') . '">' . rex_i18n::msg('treestructure_status') . '</span>';

				$out.='<span class="treestructure--options-extras">';
				$out.='<span class="treestructure--options--delete" data-confirm="' . rex_i18n::msg('treestructure_confirm_deletion_', trim(strip_tags($item['text']))) . '" title="' . rex_i18n::msg('delete') . '">' . rex_i18n::msg('delete') . '</span>';
				$out.='<span class="treestructure--options--metadata" title="' . rex_i18n::msg('metadata') . '">' . rex_i18n::msg('metadata') . '</span>';
				$out.='<span class="treestructure--options--functions" title="' . rex_i18n::msg('metafuncs') . '">' . rex_i18n::msg('metafuncs') . '</span>';
				$out.='</span>';
				$out.= '</span>';

				$out.= '</div>';


				if(!empty($item['children'])) {
					$out.= self::getNestableData($item['children']);
				}

				$out.= '</li>';
			}
			unset($item);

			$out.= '</ol>';
		}
		return $out;
	}
}
