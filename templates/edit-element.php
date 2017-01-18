<?php
	$category_id = rex_request('category_id', 'int');
	$clang = rex_request('clang', 'int');
	$type = rex_request('type', 'string', null);
	$active_template_id = rex_template::getDefaultId();
	$id = rex_request('id', 'int', 0);
	$article_name = '';

	if($type == null OR !rex::getUser()->getComplexPerm('structure')->hasCategoryPerm($category_id)) {
		return;
	}
	if($category_id >= 0) {
		if(is_object($category = rex_category::get($category_id))) {
			$active_template_id = $category->getTemplateId();
		}
		elseif($category_id>0) {
			return;
		}
	}
	else if($id >= 0) {
		if(!is_object($article = rex_article::get($id))) {
			return;
		}
		$active_template_id = $article->getTemplateId();
		$article_name = $article->getName();
	}

?><div class="treestructure--edit-node">
	<div class="input-group">
		<input type="text" class="form-control" name="name" value="<?php echo $article_name; ?>" placeholder="<?php echo rex_i18n::msg('treestructure_new_'.$type); ?>" aria-label="<?php echo rex_i18n::msg('treestructure_new_'.$type); ?>" />
		<input type="hidden" name="category_id" value="<?php echo $category_id; ?>" />
		<input type="hidden" name="type" value="<?php echo $type; ?>" />
		<input type="hidden" name="action" value="edit" />
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		<div class="input-group-btn">
			<button type="submit" class="btn btn-default"><?php echo rex_i18n::msg('treestructure_new_'.$type.'_save'); ?></button>
			<?php
				// get templates for this category
				$templates = rex_template::getTemplatesForCategory($category_id);
				if(count($templates)>1) { ?>
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="caret"></span>
				<span class="sr-only"><?php echo rex_i18n::msg('treestructure_toggle_dropdown'); ?></span>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				<div class="btn-group" data-toggle="buttons">
					<span class="btn-group-title"><?php echo rex_i18n::msg('treestructure_template'); ?></span>
				<?php foreach($templates as $template_id => $template_name): ?>
					<label class="btn btn-default<?php if($active_template_id == $template_id) echo ' active' ?>">
						<input type="radio" name="template_id" value="<?php echo $template_id; ?>"<?php if($active_template_id == $template_id) echo ' checked="checked"' ?>/><?php echo htmlspecialchars($template_name); ?>
					</label>
				<?php endforeach; ?>
				</div>
			</div>
			<?php
				}
				unset($templates, $template_id, $template_name);
			?>
			<button type="reset" class="btn btn-default">
				<span class="fa fa-times"></span>
				<span class="sr-only"><?php echo rex_i18n::msg('treestructure_cancel'); ?></span>
			</button>
		</div>
	</div>
</div>