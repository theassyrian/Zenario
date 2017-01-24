<?php
/*
 * Copyright (c) 2017, Tribal Limited
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


class zenario_common_features__admin_boxes__migrate_old_documents extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		// Only show button if ctype_document module is running
		if (getModuleStatusByClassName('zenario_ctype_document') == 'module_running') {
			if ($box['key']['id']) {
				
				$fields['details/html']['snippet']['html'] = adminPhrase('For each document meta data field below, select a dataset field to migrate the data to. If no dataset field is chosen then the data won\'t be saved. (<a [[link]]>Edit dataset fields</a>)', array('link' => 'href="'. htmlspecialchars(absCMSDirURL(). 'zenario/admin/organizer.php#zenario__administration/panels/custom_datasets'). '" target="_blank"'));
				
				// Set select lists for dataset fields
				$link = '';
				$datasetDetails = getDatasetDetails('documents');
				if ($details = getDatasetDetails('documents')) {
					$link = absCMSDirURL(). 'zenario/admin/organizer.php?#zenario__administration/panels/custom_datasets//'.$details['id'];
				}
				$textDocumentDatasetFields = getRowsArray('custom_dataset_fields', array('label', 'default_label'), array('type' => 'text', 'dataset_id' => $datasetDetails['id']), 'ord');
				$textDocumentDatasetFields = array_map(function($e) { return $e['label'] ? $e['label'] : $e['default_label']; }, $textDocumentDatasetFields);
				
				if (empty($textDocumentDatasetFields)) {
					$fields['details/title']['hidden'] = $fields['details/language_id']['hidden'] = true;
					$fields['details/title_warning']['hidden'] = $fields['details/language_id_warning']['hidden'] = false;
					$fields['details/title_warning']['snippet']['html'] = 
					$fields['details/language_id_warning']['snippet']['html'] = 
						'No "Text" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/title']['values'] = $fields['details/language_id']['values'] = $textDocumentDatasetFields;
				}
				$textAreaDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'textarea', 'dataset_id' => $datasetDetails['id']));
				if (empty($textAreaDocumentDatasetFields)) {
					$fields['details/description']['hidden'] = $fields['details/keywords']['hidden'] = true;
					$fields['details/description_warning']['hidden'] = $fields['details/keywords_warning']['hidden'] = false;
					$fields['details/description_warning']['snippet']['html'] = 
					$fields['details/keywords_warning']['snippet']['html'] = 
						'No "Textarea" type fields found in the document dataset, go <a href="'.$link.'" target="_blank">here</a> to create one.';
				} else {
					$fields['details/description']['values'] = $fields['details/keywords']['values'] = $textAreaDocumentDatasetFields;
				}
				$editorDocumentDatasetFields = 
					getRowsArray('custom_dataset_fields', 'label', array('type' => 'editor', 'dataset_id' => $datasetDetails['id']));
				if (empty($editorDocumentDatasetFields)) {
					$fields['details/content_summary']['hidden'] = true;
					$fields['details/content_summary_warning']['hidden'] = false;
					$fields['details/content_summary_warning']['snippet']['html'] = 'No "Editor" type fields found in the document dataset, go <a href="'.$link.'">here</a> to create one.';
				} else {
					$fields['details/content_summary']['values'] = $editorDocumentDatasetFields;
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		$datasetDetails = getDatasetDetails('documents');
		$documentList = explode(',',$box['key']['id']);
		$documentData = array();
		
		// Get folder ID
		$folder_id = 0;
		if ($values['details/put_in_folder'] && $values['details/folder']) {
			$folder_id = $values['details/folder'];
		}
		
		// Get next ordinal in folder
		$sql = '
			SELECT MAX(ordinal)
			FROM '.DB_NAME_PREFIX.'documents
			WHERE folder_id = '.(int)$folder_id;
		$result = sqlSelect($sql);
		$maxOrdinal = sqlFetchArray($result);
		$ordinal = empty($maxOrdinal[0]) ? 1 : (int)$maxOrdinal[0] + 1;
		$failed = 0;
		$succeeded = 0;
		
		// Get filenames of all documents in this folder in to check for uniqueness
		$existingDocumentNamesInFolder = array();
		$result = getRows('documents', array('filename'), array('folder_id' => $folder_id));
		while ($row = sqlFetchAssoc($result)) {
		    $existingDocumentNamesInFolder[$row['filename']] = true;
		}
		
		foreach($documentList as $tagId) {
			// Get old document details
			$documentData = array();
			$sql = '
				SELECT c.language_id, v.title, v.description, v.keywords, v.content_summary, v.file_id, v.created_datetime, v.filename
				FROM '.DB_NAME_PREFIX.'content_items AS c
				INNER JOIN '.DB_NAME_PREFIX.'content_item_versions AS v
					ON (c.tag_id = v.tag_id AND c.admin_version = v.version)
				WHERE c.tag_id = "'.sqlEscape($tagId).'"';
			$result = sqlSelect($sql);
			$documentData = sqlFetchAssoc($result);
			
			if (isset($existingDocumentNamesInFolder[$documentData['filename']])) {
			    $failed++;
			    continue;
			}
			
			$documentProperties = array(
				'ordinal' => $ordinal,
				'type' => 'file', 
				'file_id' => $documentData['file_id'], 
				'folder_id' => $folder_id,
				'file_datetime' => $documentData['created_datetime'],
				'filename' => $documentData['filename']);
			$extraProperties = zenario_common_features::addExtractToDocument($documentProperties['file_id']);
			
			$properties = array_merge($documentProperties, $extraProperties);
			// Create new document
			$documentId = insertRow('documents', $properties);
			
			$userAssignedMetaDataFields = array('title', 'language_id', 'description', 'keywords', 'content_summary');
			foreach ($userAssignedMetaDataFields as $metaField) {
			    if (!empty($values['details/' . $metaField])) {
			        $datasetField = getDatasetFieldDetails($metaField, $datasetDetails['id']);
			        if ($datasetField['is_system_field']) {
			            updateRow('documents', array($metaField => $documentData[$metaField]), $documentId);
			        } else {
			            setRow('documents_custom_data', array($metaField => $documentData[$metaField]), array('document_id' => $documentId));
			        }
			    }
			}
			$succeeded++;
			
			// Hide document
			updateRow('content_items', array('status' => 'hidden'), array('tag_id' => $tagId));
			$ordinal++;
		}
		// Code to show success messages after migrating documents
		$box['popout_message'] = '';
		
		if ($failed && !$succeeded) {
			$box['popout_message'] .= "<!--Message_Type:Error-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[failed]] file could not be migrated as a document with the same filename already exists in that folder.',
				'[[failed]] files could not be migrated as a document with the same filename already exists in that folder.',
				$failed,
				array('failed' => $failed));
			$box['popout_message'] .= '</p>';
		} elseif ($failed && $succeeded) {
			$box['popout_message'] .= "<!--Message_Type:Warning-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[failed]] file could not be migrated as a document with the same filename already exists in that folder.',
				'[[failed]] files could not be migrated as a document with the same filename already exists in that folder.',
				$failed,
				array('failed' => $failed));
			$box['popout_message'] .= '</p>';
			
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[succeeded]] file was successfully migrated.',
				'[[succeeded]] files were successfully migrated.',
				$succeeded,
				array('succeeded' => $succeeded));
			$box['popout_message'] .= '</p>';
			
		} else {
			$box['popout_message'] .= "<!--Message_Type:Success-->";
			$box['popout_message'] .= '<p>';
			$box['popout_message'] .= nAdminPhrase(
				'[[succeeded]] file was successfully migrated.',
				'[[succeeded]] files were successfully migrated.',
				$succeeded,
				array('succeeded' => $succeeded));
			$box['popout_message'] .= '</p>';
		}
	}
}
