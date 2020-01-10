<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    bankstatementimport/admin/setup.php
 * \ingroup bankstatementimport
 * \brief   BankStatementImport setup page.
 */

// Load Dolibarr environment
$mainIncludePath = '../../main.inc.php';
for ($resInclude = 0, $depth = 0; !$resInclude && $depth < 5; $depth++) {
	$resInclude = @include $mainIncludePath;
	$mainIncludePath = '../' . $mainIncludePath;
}
if (!$resInclude) die ('Unable to include main.inc.php');

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/bankstatementimport.lib.php';

// Translations
$langs->loadLangs(array("admin", "bankstatementimport@bankstatementimport"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$defaultParameters = array(
	'css'       => 'minwidth500',
	'enabled'   => 1,
	'type'      => 'text'
);
$specificParameters=array(
	'BANKSTATEMENTIMPORT_SEPARATOR'                           => array('pattern' => '^.$'),
	'BANKSTATEMENTIMPORT_MAPPING'                             => array(),
	'BANKSTATEMENTIMPORT_DATE_FORMAT'                         => array(),
	'BANKSTATEMENTIMPORT_HEADER'                              => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_MAC_COMPATIBILITY'                   => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_HISTORY_IMPORT'                      => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_ALLOW_INVOICE_FROM_SEVERAL_THIRD'    => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_ALLOW_DRAFT_INVOICE'                 => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_UNCHECK_ALL_LINES'                   => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_AUTO_CREATE_DISCOUNT'                => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_MATCH_BANKLINES_BY_AMOUNT_AND_LABEL' => array('type' => 'bool'),
	'BANKSTATEMENTIMPORT_ALLOW_FREELINES'                     => array('type' => 'bool')
);
$arrayofparameters = array_map(
	function($specificParameters) use ($defaultParameters) {
		return $specificParameters + $defaultParameters; // specific parameters override default
	},
	$specificParameters
);

/*
 * Actions: in update mode, automatically set config values for parameters that exist in the keys of $arrayofparameters
 */
//if ((float) DOL_VERSION >= 6) include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */

$page_name = "BankStatementImportSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_bankstatementimport@bankstatementimport');

// Configuration header
$head = bankstatementimportAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "bankstatementimport@bankstatementimport");

function get_conf_label($confName, $parameters, $form) {
	global $langs;
	$confHelp = $langs->trans($confName . '_Help');
	$confLabel = sprintf(
		'<label for="%s">%s</label>',
		$confName,
		$langs->trans($confName)
	);

	if (!empty($langs->tab_translate[$confName . '_Help'])) {
		// help translation found: display help picto
		return $form->textwithpicto($confLabel, $confHelp);
	} else {
		// help translation not found: only display label
		return $confLabel;
	}
}

function get_conf_input($confName, $parameters) {
	global $conf, $langs;
	$confValue = isset($conf->global->{$confName}) ? $conf->global->{$confName} : '';
	$inputAttrs = sprintf(
		'name="%s" id="%s" class="%s"',
		htmlspecialchars($confName, ENT_COMPAT),
		htmlspecialchars($confName, ENT_COMPAT),
		htmlspecialchars($parameters['css'], ENT_COMPAT)
	);
	switch ($parameters['type']) {
		case 'bool':
			$input = ajax_constantonoff($confName);
			break;
		case 'text':
			if (isset($parameters['pattern'])) {
				$inputAttrs .= ' pattern="' . $parameters['pattern'] . '"';
			}
			$input = sprintf(
				'<input %s type="text" value="%s" /> <button class="but" id="btn_save_%s">%s</button>',
				$inputAttrs,
				htmlspecialchars($confValue, ENT_COMPAT),
				$confName,
				$langs->trans('Modify')
			) . '<script type="text/javascript">$(()=>ajaxSaveOnClick("'.htmlspecialchars($confName, ENT_COMPAT).'"));</script>';
			break;
		default:
			$input = $confValue;
	}
	return '<form method="POST" id="form_save_' . $confName . '" action="' . $_SERVER['PHP_SELF'] . '">'
		   . '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />'
		   . '<input type="hidden" name="action" value="update" />'
		   . $input
		   . '</form>';
}
$form = new Form($db);
// Setup page goes here
?>
<p><?php echo $langs->trans("BankStatementImportSetupPage"); ?></p>
<table class="noborder" width="100%">
	<colgroup><col id="setupConfLabelColumn"/><col id="setupConfValueColumn" /></colgroup>
	<thead>
	<tr class="liste_titre">
		<td class="titlefield">
			<?php echo $langs->trans("Parameter"); ?>
		</td>
		<td>
			<?php echo $langs->trans("Value"); ?>
		</td>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ($arrayofparameters as $confName => $confParams) {
		printf(
			'<tr class="oddeven">'
			. '<td>%s</td>'
			. '<td>%s</td>'
			. '</tr>',
			get_conf_label($confName, $arrayofparameters[$confName], $form),
			get_conf_input($confName, $arrayofparameters[$confName])
		);
	}
	?>
	</tbody>
</table>
<?php

// Page end
dol_fiche_end();

llxFooter();
$db->close();

