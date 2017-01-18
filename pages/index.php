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

$clang = rex_request('clang', 'int');
$clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();

$context = new rex_context([
    'page' => 'treestructure',
    'clang' => $clang,
]);

// --------------------- Extension Point
echo rex_extension::registerPoint(new rex_extension_point('PAGE_TREESTRUCTURE_HEADER_PRE', '', [
    'context' => $context,
]));

// --------------------------------------------- TITLE
echo rex_view::title(rex_i18n::msg('title_structure'));

// --------------------------------------------- Languages
echo rex_view::clangSwitchAsButtons($context);

// --------------------------------------------- Header
echo '<div class="rex-breadcrumb"><ol class="breadcrumb"><li>' . rex_i18n::msg('treestructure_title') . '</li></ol></div>';


// DO SOME OWN STUFF
$echo = '';

$translations = array(
    'type_in_name_to_confirm' => rex_i18n::msg('treestructure_type_in_name_to_confirm')
);

$echo = '<form id="treetructure--tree" class="treestructure" method="post" action="' . $context->getUrl() .'" data-url="' . $context->getUrl(['action' => 'jsontree']) .'" data-translations="' . htmlspecialchars(json_encode($translations)) . '"></form>';

// create option icons so we can clone them...
$echo.='<span class="treestructure--options">';
$echo.='<span class="treestructure--options--new-category fa fa-plus-square" title="' . rex_i18n::msg('add_category') . '">' . rex_i18n::msg('add_category') . '</span>';
$echo.='<span class="treestructure--options--new-article fa fa-plus-square-o" title="' . rex_i18n::msg('add_article') . '">' . rex_i18n::msg('article_add') . '</span>';
$echo.='<span class="treestructure--options--expand-all fa fa-caret-square-o-down" title="' . rex_i18n::msg('treestructure_expand_all') . '">' . rex_i18n::msg('treestructure_expand_all') . '</span>';
$echo.='<span class="treestructure--options--collapse-all fa fa-caret-square-o-up" title="' . rex_i18n::msg('treestructure_collapse_all') . '">' . rex_i18n::msg('treestructure_collapse_all') . '</span>';
$echo.='<span class="treestructure--options--edit rex-icon rex-icon-edit" title="' . rex_i18n::msg('change') . '">' . rex_i18n::msg('change') . '</span>';
$echo.='<span class="treestructure--options--view rex-icon rex-icon-view" title="' . rex_i18n::msg('show') . '">' . rex_i18n::msg('show') . '</span>';
$echo.='<span class="treestructure--options--status rex-icon rex-icon-online" title="' . rex_i18n::msg('treestructure_status') . '">' . rex_i18n::msg('treestructure_status') . '</span>';

$echo.='<span class="treestructure--options-extras">';
$echo.='<span class="treestructure--options--delete rex-icon rex-icon-delete" data-confirm="' . rex_i18n::msg('treestructure_confirm_deletion') . '" title="' . rex_i18n::msg('delete') . '">' . rex_i18n::msg('delete') . '</span>';

if(rex_addon::get('metainfo')->isAvailable()) {
	$echo.='<span class="treestructure--options--metadata rex-icon rex-icon-metainfo" title="' . rex_i18n::msg('metadata') . '">' . rex_i18n::msg('metadata') . '</span>';
}

$echo.='<span class="treestructure--options--functions rex-icon rex-icon-metafuncs" title="' . rex_i18n::msg('metafuncs') . '">' . rex_i18n::msg('metafuncs') . '</span>';
$echo.='</span>';
$echo.= '</span>';

// --------------------------------------------- API MESSAGES
echo rex_api_function::getMessage();

// --------------------------------------------- KATEGORIE LISTE

$fragment = new rex_fragment();
$fragment->setVar('class', 'treestructure--categories', false);
$fragment->setVar('content', $echo, false);
echo $fragment->parse('core/page/section.php');
