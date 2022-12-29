<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       mastereditor/mastereditorindex.php
 *	\ingroup    mastereditor
 *	\brief      Home page of mastereditor top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/modulebuilder.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';

require_once __DIR__.'/class/bookmark.class.php';

// Load translation files required by the page
$langs->loadLangs(array("mastereditor@mastereditor"));

// Security check
// if (! $user->rights->mastereditor->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Mask for creating/editing files
$newmask = 0;
if (empty($newmask) && !empty($conf->global->MAIN_UMASK)) {
	$newmask = $conf->global->MAIN_UMASK;
}
if (empty($newmask)) {	// This should no happen
	$newmask = '0664';
}


/*
 * Actions
 */
 $action = GETPOST('action', 'aZ09');
 $path = GETPOST('path') !== "" ? GETPOST('path') : DOL_DOCUMENT_ROOT;
 $file = GETPOST('file') !== "" ? GETPOST('file') : '';
 $favname = GETPOST('favname') !== "" ? GETPOST('favname') : '';

if ($action === "addfavorit")
{
	$object = new Bookmark($db);
	$object->label = $favname;
	$object->target = $path;
	$object->file = $file;
	$object->create($user);

	setEventMessages("Added to favorites", null);
	//$action = "gotofolder"; //GOTO same folder

	$link = "/custom/mastereditor/mastereditorindex.php?action=gotofolder&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($path);
	if ($file !== "")
	{
		$link = "/custom/mastereditor/mastereditorindex.php?action=editfile&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($path) . '&file=' . $file;
	}
	header("Location: "  . $link);
	exit;
}

/*
 * View
 */

//https://fontawesome.com/v5/search?q=enter&o=r&s=regular

llxHeader("", $langs->trans("MasterEditorArea"));

print load_fiche_titre($langs->trans("MasterEditorArea"), '', 'mastereditor.png@mastereditor');

print '<div class="fichecenter"><div style="float: left; width: calc(15% - 14px);">';

//Bookmarks
//TODO Star (change color if bokkmarked and remove on somple click)
$bookmarks = array();
$sql = 'SELECT * FROM llx_mastereditor_bookmark';
$res = $db->query($sql);

if ($res)
{
	while($rec = $db->fetch_object($res))
	{
		$bookmarks[] = array($rec->type, $rec->target, $rec->file, $rec->label);
	}
}
else
	dol_print_error($db);


print '<h3>Favoris</h3>';
foreach($bookmarks as $bookmark)
{
	if ($bookmark[3] !== "")
	{
		$link = '';
		if ($bookmark[2] === "") //Path to folder
		{
			$link = "/custom/mastereditor/mastereditorindex.php?action=gotofolder&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($bookmark[1]);
			print '<span class="fa fa-folder"></span> <a href="'  . $link . '" title="' . $bookmark[1] . '">' . $bookmark[3] . '</a><br>';
		}
		else //Path to file
		{
			$link = "/custom/mastereditor/mastereditorindex.php?action=editfile&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($bookmark[1]) . "&file=" . $bookmark[2];
			print '<span class="fa fa-file-o"></span> <a href="'  . $link . '" title="' . $bookmark[1] . '/' . $bookmark[2] . '">' . $bookmark[3] . '</a><br>';
		}
		
	}
}

print '</div>';
print '<div style="float: right; width: calc(85% - 14px);">';

//Breadcrumb
$pathparts = explode("/", $path);
$pathpart = "";
$breadcrumb = "";
foreach($pathparts as $part)
{
	if ($part !== "")
	{
		$pathpart .= "/" . $part;
		$link = "/custom/mastereditor/mastereditorindex.php?action=gotofolder&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($pathpart);
		$breadcrumb .= '/<a href="' . $link . '">' . $part . '</a>';
		$favname = $part;
	}
}
$favlink = "/custom/mastereditor/mastereditorindex.php?action=addfavorit&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($pathpart);
if ($file !== "")
{
	$breadcrumb .= '/' . $file;
	$favlink .= '&file=' . $file;
	$favname = $file;
}
$favlink .= '&favname=' . $favname;
print '<h3>Chemin ' . $breadcrumb . ' <a href="' . $favlink . '"><span class="fa fa-star" style=" color: orange;" title="Ajouter aux favoris"></span></a></h3>';

if ($action === "")
	$action = "gotofolder";

if ($action === "createfile")
{
	$pathoffile = $path . "/" . $file;

	$result = file_put_contents($pathoffile, "");
	if ($result) {
		@chmod($pathoffile, octdec($newmask));

		setEventMessages($langs->trans("FileSaved"), null);
		$action = "editfile"; //GOTO edit
		$path = $path . "/" . $file;
	} else {
		setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
		$action = "gotofolder"; //GOTO same foler
	}
}
if ($action === "deletefileconfirm")
{
	$pathoffile = $path . "/" . $file;

	print '<h2>Etes vous sur de vouloir supprimer le fichier ' . $file . '</h2>';
	print '<h3>Situé dans le dossier ' . $path . '</h3>';
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="deletefile">';
	print '<input type="hidden" name="path" value="' . dol_escape_htmltag($path) . '">';
	print '<input type="hidden" name="file" value="' . $file . '">';
	print '<input type="submit" class="button smallpaddingimp" name="savefile" value="'.dol_escape_htmltag($langs->trans("Yes")).'">';
	print '</form>';
}
if ($action === "deletefile")
{
	$pathoffile = $path . "/" . $file;

	$result = dol_delete_file($pathoffile);
	if ($result) {
		setEventMessages($langs->trans("FileDeleted"), null);
		$action = "gotofolder"; //GOTO parent foler
	} else {
		setEventMessages($langs->trans("ErrorFailedToDeleteFile"), null, 'errors');
		$action = "gotofolder"; //GOTO parent foler
	}
}
if ($action === "createfolder")
{
	$pathoffile = $path . "/" . $file;

	$result = dol_mkdir($file, $path, $newmask);
	if ($result) {
		setEventMessages($langs->trans("FileSaved"), null);
		$action = "gotofolder"; //GOTO new folder
		$path = $path . "/" . $file;
	} else {
		setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
		$action = "gotofolder"; //GOTO same foler
	}
}
if ($action === "deletefolderconfirm")
{
	$pathoffile = $path . "/" . $file;

	print '<h2>Etes vous sur de vouloir supprimer le dossier ' . $file . '</h2>';
	print '<h3>Situé dans le dossier ' . $path . '</h3>';
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="deletefolder">';
	print '<input type="hidden" name="path" value="' . dol_escape_htmltag($path) . '">';
	print '<input type="hidden" name="file" value="' . $file . '">';
	print '<input type="submit" class="button smallpaddingimp" name="savefile" value="'.dol_escape_htmltag($langs->trans("Yes")).'">';
	print '</form>';
}
if ($action === "deletefolder") //TODO validate twice
{
	$pathoffile = $path . "/" . $file;

	$result = dol_delete_dir_recursive($pathoffile);

	if ($result > 0) {
		setEventMessages($langs->trans("DirWasRemoved", $modulelowercase), null);

		clearstatcache(true);
		if (function_exists('opcache_invalidate')) {
			opcache_reset();	// remove the include cache hell !
		}
	} else {
		setEventMessages($langs->trans("PurgeNothingToDelete"), null, 'warnings');
	}
	$action = "gotofolder"; //GOTO parent foler
}

// Save file
if ($action == 'savefile' && empty($cancel)) {

	$pathoffile0 = $path . "/" . $file;

	if (is_file($pathoffile0)) {

		$pathoffile = dol_buildpath($pathoffile0, 1);
		$pathoffilebackup = dol_buildpath($pathoffile0.'.back', 1);

		// Save old version //TODO coonfig
		if (dol_is_file($pathoffile)) {
			dol_copy($pathoffile, $pathoffilebackup, 0, 1);
		}

		$check = 'restricthtml';
		$srclang = dol_mimetype($pathoffile, '', 3);
		if ($srclang == 'md') {
			$check = 'restricthtml';
		}
		if ($srclang == 'lang') {
			$check = 'restricthtml';
		}
		if ($srclang == 'php') {
			$check = 'none';
		}

		$content = GETPOST('editfilecontent', $check);

		// Save file on disk
		if ($content) {
				dol_delete_file($pathoffile);
				$result = file_put_contents($pathoffile, $content);
				if ($result) {
						@chmod($pathoffile, octdec($newmask));

						setEventMessages($langs->trans("FileSaved"), null);
						$action = "gotofolder"; //GOTO same foler
				} else {
						setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
						$action = 'editfile';
				}
		} else {
				setEventMessages($langs->trans("ContentCantBeEmpty"), null, 'errors');
				$action = 'editfile';
		}
	}
}

if ($action === "gotofolder")
{
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="createfile">';
	print '<input type="hidden" name="path" value="' . dol_escape_htmltag($path) . '">';
	print '<input type="text" name="file" value="" placeholder="Fichier">';
	print '<input type="submit" class="button smallpaddingimp" name="savefile" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '</form>';

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="createfolder">';
	print '<input type="hidden" name="path" value="' . dol_escape_htmltag($path) . '">';
	print '<input type="text" name="file" value="" placeholder="Dossier">';
	print '<input type="submit" class="button smallpaddingimp" name="savefile" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '</form>';

	print '<br>';

	$protected = array(".", "..");

	$scan = scandir($path);
	foreach($scan as $element)
	{
		if(is_dir($path . "/" . $element))
		{
			if (!in_array($element, $protected))
			{
				$link = "/custom/mastereditor/mastereditorindex.php?action=gotofolder&token=" . $_SESSION['newtoken'] . "&path="  . dol_escape_htmltag($path) . "/" . $element;
				print '<span class="fa fa-folder"></span> <a href="' . $link . '">' . $element . ' <span class="fa fa-sign-in-alt" style=" color: #444;" title="Éditer"></span></a>';
				$dellink = "/custom/mastereditor/mastereditorindex.php?action=deletefolderconfirm&token=" . $_SESSION['newtoken'] . "&path=" . dol_escape_htmltag($path) . "&file=" . $element;
				print ' <a href="' . $dellink . '"><span class="fa fa-trash" style=" color: #444;" title="Supprimer"></span></a>';
				print '<br>';
			}
		}	
		else
		{
			$link = "/custom/mastereditor/mastereditorindex.php?action=editfile&token=" . $_SESSION['newtoken'] . "&format=php&path=" . dol_escape_htmltag($path) . "&file=" . $element;
			print '<span class="fa fa-file-o"></span> <a href="' . $link . '">' . $element . ' <span class="fas fa-pencil-alt" style=" color: #444;" title="Éditer"></span></a>';
			$dellink = "/custom/mastereditor/mastereditorindex.php?action=deletefileconfirm&token=" . $_SESSION['newtoken'] . "&path=" .dol_escape_htmltag($path) . "&file=" . $element;
			print ' <a href="' . $dellink . '"><span class="fa fa-trash" style=" color: #444;" title="Supprimer"></span></a>';
			print '<br>';
		}
	}
}

//Edit file
if ($action === "editfile")
{
	$pathoffile = $path . "/" . $file;

	if (is_file($pathoffile))
	{
		$fullpathoffile = dol_buildpath($pathoffile, 1);
		$content = file_get_contents($fullpathoffile);
		$doleditor = new DolEditor('editfilecontent', $content, '', '400', 'Full', 'In', true, false, 'ace');

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="savefile">';
		print '<input type="hidden" name="path" value="'.dol_escape_htmltag($path).'">';
		print '<input type="hidden" name="file" value="'.$file.'">';
		print $doleditor->Create(1, '', false, $langs->trans("File") . ' : '.$fullpathoffile, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
		print '<br>';
		print '<center>';
		print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</center>';
		print '</form>';
	}
}

print '</div></div>';

// End of page
llxFooter();
$db->close();
