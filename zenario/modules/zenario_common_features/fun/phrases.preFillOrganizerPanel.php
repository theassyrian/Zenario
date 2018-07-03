<?php
/*
 * Copyright (c) 2016, Tribal Limited
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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

//Call the getNumLanguages() function to define the ZENARIO_NUM_LANGUAGES constant

if (!$refinerName || $refinerName == 'language' || $refinerName == 'language_and_plugin') {
	$mrg = array('lang_name' => htmlspecialchars(getLanguageName(FOCUSED_LANGUAGE_ID__NO_QUOTES)));
	
	if ($refinerName == 'language_and_plugin') {
		if ($module = getModuleDetails(get('refiner__language_and_plugin'))) {
			$mrg['display_name'] = $module['display_name'];
			$panel['key']['moduleClass'] = $module['class_name'];
			
			$panel['title'] = adminPhrase('Phrases in the Language "[[lang_name]]" (source: "[[display_name]]" Module)', $mrg);
			$panel['no_items_message'] = adminPhrase('There are no "[[lang_name]]" Phrases for the "[[display_name]]" Module', $mrg);
		}
		
		unset($panel['columns']['module_class_name']);
	
	} elseif ($refinerName == 'language') {
		$panel['title'] = adminPhrase('Phrases in the Language "[[lang_name]]"', $mrg);
		$panel['no_items_message'] = adminPhrase('There are no Phrases in the Language "[[lang_name]]"', $mrg);
	}
	
	$panel['db_items']['where_statement'] = $panel['db_items']['custom_where_statement_if_no_refiner'];
	$panel['key']['language_id'] = FOCUSED_LANGUAGE_ID__NO_QUOTES;
	
	if (isset($panel['item_buttons']['delete'])) {
		unset($panel['item_buttons']['delete']['ajax']['confirm']['message']);
		unset($panel['item_buttons']['delete']['ajax']['confirm']['multiple_select_message']);
		
		if (FOCUSED_LANGUAGE_ID__NO_QUOTES == setting('default_language')) {
			$panel['item_buttons']['delete']['ajax']['request']['delete_translated_phrases'] = 1;
		}
	}

} elseif ($refinerName == 'translations') {
	$mrg = getRow('visitor_phrases', array('code', 'module_class_name'), $refinerId);
	
	if ($mrg['display_name'] = getModuleDisplayNameByClassName($mrg['module_class_name'])) {
		$panel['title'] = adminPhrase('Translations of the Phrase "[[code]]" (source: "[[display_name]]" Module)', $mrg);
		$panel['no_items_message'] = adminPhrase('There are no translations of the Phrase "[[code]]" for the "[[display_name]]" Module', $mrg);
	} else {
		$panel['title'] = adminPhrase('Translations of the Phrase "[[code]]"', $mrg);
		$panel['no_items_message'] = adminPhrase('There are no translations of the Phrase "[[code]]"', $mrg);
	}
	
	unset($panel['columns']['localized_phrase']);
	unset($panel['columns']['localized_phrases']);
}

if ($path == 'zenario__languages/panels/phrases') {
	
	$languages = getLanguages(false, true, true);
	$ord = 2;
	foreach ($languages as $language) {
		$alias = '`'. sqlEscape('vp_'. $language['id']). '`';
		$panel['columns'][$language['id']] =
			array(
				'class_name' => 'zenario_common_features',
				'title' => 'Text in '.$language['english_name'],
				'show_by_default' => true,
				'searchable' => true,
				'ord' => $ord,
				'db_column' => "(
						SELECT local_text
						FROM ".DB_NAME_PREFIX. "visitor_phrases AS ". $alias. "
						WHERE ". $alias. ".code = vp.code
						  AND ". $alias. ".module_class_name = vp.module_class_name
						  AND ". $alias. ".language_id = '". sqlEscape($language['id']). "'
						LIMIT 1
					)"
			);
		$panel['columns']['protect_'. $language['id']] =
			array(
				'class_name' => 'zenario_common_features',
				'title' => 'Protect '.$language['english_name'],
				'show_by_default' => true,
				'format' => 'yes_or_no',
				'ord' => $ord + 0.01,
				'width' => 'xxsmall',
				'align_right' => true,
				'db_column' => "(
						SELECT protect_flag
						FROM ".DB_NAME_PREFIX. "visitor_phrases AS ". $alias. "
						WHERE ". $alias. ".code = vp.code
						  AND ". $alias. ".module_class_name = vp.module_class_name
						  AND ". $alias. ".language_id = '". sqlEscape($language['id']). "'
						LIMIT 1
					)"
			);
		
		$ord += 0.02;
	}
}

// Hide import button if not showing module phrases OR no phrases directory found
if (($refinerName == 'language_and_plugin') && file_exists(CMS_ROOT . moduleDir(getModuleClassName($refinerId)) . 'phrases/')) {
	$moduleDetails = getRow('modules', array('class_name', 'display_name'), $refinerId);
	$importFiles = scanModulePhraseDir($moduleDetails['class_name'], 'number and file');
	$list = array();
	$languages = getLanguages(false, true, true);
	
	foreach ($languages as $langId => $language) {
		if (isset($importFiles[$langId])
			|| (
				($pos = strpos($langId, '-')) !== false
				&& ($langId = substr($langId, 0, $pos))
				&& isset($importFiles[$langId])
			)
		) {
			$count = $importFiles[$langId]['added'] + $importFiles[$langId]['updated'];
			$list[] = nAdminPhrase(
				'[[name]] ([[count]] phrase)',
				'[[name]] ([[count]] phrases)',
				$count,
				array(
					'name' => $language['english_name'], 
					'count' => $count));
		}
	}
	$list = implode(', ', $list);
	$panel['collection_buttons']['reimport_phrases']['ajax']['confirm']['message'] = adminPhrase('
		Are you sure you wish to re-import phrases for the module [[display_name]] ([[class_name]])?
		
		This will re-import phrases from the config files in the module\'s "phrases" directory: [[list_of_languages_and_phrase_count]].
		
		Existing phrases that are marked as "protected" will not be overwritten, but all non-protected phrases will be overwritten.
	', array(
		'display_name' => $moduleDetails['display_name'], 
		'class_name' => $moduleDetails['class_name'],
		'list_of_languages_and_phrase_count' => $list));
	
	unset($panel['collection_buttons']['import_phrases']);
} else {
	unset($panel['collection_buttons']['reimport_phrases']);
	
	//For each language, add an export phrases button
	if (checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
		$languages = getLanguages(false, true, true);
	
		foreach ($languages as $langId => $language) {
			$panel['collection_buttons'][] = array(
				'class_name' => 'zenario_common_features',
				'parent' => 'export_phrases_dropdown',
				'label' => adminPhrase('Export [[english_name]] phrases', $language),
				'admin_box' => array(
					'path' => 'zenario_export_vlp',
					'key' => array(
						'id' => $langId
			)));
		}
	}
}

return false;