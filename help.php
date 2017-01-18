<?php

/**
 * TreeStructure Addon.
 *
 * @author post[at]thomasgoellner[dot]de Thomas Goellner
 *
 * @package redaxo5
 */

	$lang = rex::getUser()->getLanguage() ? rex::getUser()->getLanguage() : rex::getProperty('lang');

?>

<h3>
	TreeStructure Addon
</h3>

<?php if(strtolower(substr($lang,0,2)) == 'de'): ?>
<p>
	AddOn um den Seitenbaum als Baumstruktur anzuzeigen.
</p>
<p>
	Kategorien lassen sich durch Klick auf das <i class="rex-icon rex-icon-media-category"></i> Ordner-Icon öffnen und schließen - um alle Unterordner einer Kategorie zu öffnen, das entsprechende <i class="fa fa-caret-square-o-down"></i> Aus-/<i class="fa fa-caret-square-o-up"></i> Einklapp-Icon verwenden.
</p>
<p>
	Kategorien und Artikel lassen sich - sofern der Nutzer entsprechende Rechte besitzt - per Drag &amp; Drop verschieben.
</p>
<p>
	Neue Kategorien (<i class="fa fa-plus-square"></i>) und Artikel (<i class="fa fa-plus-square-o"></i>) lassen sich über die entsprechenden Icons direkt im Baum anlegen.
</p>
<p>
	Weitere Funktionen (<i class="rex-icon rex-icon-delete"></i> Löschen, <i class="rex-icon rex-icon-metainfo"></i> Metadaten, <i class="rex-icon rex-icon-metafuncs"></i> Funktionen) erreicht man durch Öffnen des erweiterten Optionsmenüs: Mit der Maus auf das kleine <i class="fa fa-caret-right"></i> Dreieck am Ende der Standard-Funktions-Leiste fahren, dann werden die Standardfunktionen durch die erweiterten Funktionen ersetzt.
</p>
<p>
	<strong>Erweiterte Funktion:</strong> Um eine Kategorie samt Unterelementen zu entfernen, beim Klick auf das <i class="rex-icon rex-icon-delete"></i> Löschen-Icon die Shift-Taste gedrückt halten. Zur Bestätigung muss dann noch der exakte Kategorien-Name eingegeben werden.
</p>
<?php else: ?>
<p>
	AddOn to view structure of your site as a tree.
</p>
<p>
	Categories are opened and closed with a click on the <i class="rex-icon rex-icon-media-category"></i> folder icon - to open or close all child categories of a folder you can use the <i class="fa fa-caret-square-o-down"></i> expand and <i class="fa fa-caret-square-o-up"></i> collapse buttons.
</p>
<p>
	Categories and articles can be moved via drag and drop - if a user has the appropriate rights.
</p>
<p>
	New categories (<i class="fa fa-plus-square"></i>) and articles (<i class="fa fa-plus-square-o"></i>) are created by clicking the appropriate icons.
</p>
<p>
	Special functions (<i class="rex-icon rex-icon-delete"></i> Delete, <i class="rex-icon rex-icon-metainfo"></i> Meta data, <i class="rex-icon rex-icon-metafuncs"></i> Meta functions) can be accessed by opening up the extended options menu: Move the mouse over the <i class="fa fa-caret-right"></i> caret icon at the end of the standard functions and they will be replaced by the extended functions buttons.
</p>
<p>
	<strong>Additional function:</strong> To remove a complete category with all its child categories and articles you can press SHIFT key while clicking the <i class="rex-icon rex-icon-delete"></i> Delete button. To confirm the deletion you have to enter the exact category name.
</p>
<?php endif; ?>

<hr />
<h4>Changelog</h4>
<dl>
	<dt>Version 1.0.1 beta (2016-02-16)</dt>
	<dd>
		* fixed creating new categories and articles on root level<br />
		* updated template selector in edit mode
	</dd>
	<dt>Version 1 beta (2016-02-09)</dt>
	<dd>Initial version - uses an altered version (1.3.0) of <a href="http://mbraak.github.io/jqTree/" target="_blank">jqTree</a> to provide tree structure and drag&amp;drop ability.</dd>
</dl>