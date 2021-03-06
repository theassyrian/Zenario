<?php
/*
 * Copyright (c) 2018, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require 'visitorheader.inc.php';
header('Content-Type: text/javascript; charset=UTF-8');


if (!$requestedPath = ze::ifNull($_REQUEST['name'] ?? false, ($_REQUEST['path'] ?? false))) {
	echo 'No wizard specified';
	exit;
}

//Load the yaml files for this wizard
$box = [];
$tags = [];
$moduleFilesLoaded = [];
ze\tuix::load($moduleFilesLoaded, $tags, 'wizards', $requestedPath);
$removedColumns = [];
ze\tuix::parse2($tags, $removedColumns, 'wizards');

if (empty($tags[$requestedPath])) {
	echo 'Wizard not found';
	exit;
	
} else {
	$tags = $tags[$requestedPath];
}


//Check to see which modules are running this Wizard
$modules = [];
if (!empty($tags['tabs']) && is_array($tags['tabs'])) {
	foreach ($tags['tabs'] as &$tab) {
		if (!empty($tab['class_name'])) {
			ze\tuix::includeModule($modules, $tab, 'wizards', $requestedPath, '');
		}
		
		if (!empty($tab['fields']) && is_array($tab['fields'])) {
			foreach ($tab['fields'] as &$field) {
				if (!empty($field['class_name'])) {
					ze\tuix::includeModule($modules, $field, 'wizards', $requestedPath, '');
				}
			}
		}
	}
}


if (($_POST['_format'] ?? false) || ($_POST['_validate'] ?? false)) {
	$filling = false;
	$clientTags = json_decode($_POST['_box'], true);
	
	ze\tuix::syncFromClientToServer($tags, $clientTags);
	
	if (!empty($clientTags['tab'])) {
		$tags['tab'] = $clientTags['tab'];
	}

} else {
	$filling = true;
	$clientTags = [];
	
	$fields = [];
	$values = [];
	$changes = [];
	ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = true);
	
	//Run the fill method(s)
	foreach ($modules as $className => &$module) {
		$module->fillWizard($requestedPath, $tags, $fields, $values);
	}
}
//$getRequest = json_decode($_GET['get'], true);





$fields = [];
$values = [];
$changes = [];
ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors = true);

foreach ($modules as $className => &$module) {
	$module->formatWizard($requestedPath, $tags, $fields, $values, $changes);
}

$_SESSION['allow_file_uploads_in_the_installer'] = true;




echo json_encode($tags);