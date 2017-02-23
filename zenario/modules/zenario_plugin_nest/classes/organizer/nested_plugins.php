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


class zenario_plugin_nest__organizer__nested_plugins extends zenario_plugin_nest {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$instance = getPluginInstanceDetails(get('refiner__nest'));
		$c = $instance['class_name'];
		
		$panel['key']['skinId'] = request('skinId');
		
		
		//Get a list of types of plugins that can be put in this nest
		$key = array('status' => 'module_running', 'is_pluggable' => 1, 'nestable' => 1);
		if ($instance['content_id']) {
			$key['can_be_version_controlled'] = 1;
		}
		$modules = getRowsArray('modules', 'display_name', $key, 'display_name');
		$ord = 222;
		
		$twigSnippets = array();
		if (canActivateModule('zenario_twig_snippet')) {
			foreach (moduleDirs('twig/') as $moduleClassName => $dir) {
				if (canActivateModule($moduleClassName)) {
					foreach (scandir(CMS_ROOT. $dir) as $file) {
						if (substr($file, -10) == '.twig.html') {
							$twigSnippets[] = array($moduleClassName, substr($file, 0, -10), $file);
						}
					}
				}
			}
		}

		
		foreach (array('collection_buttons', 'item_buttons') as $buttonType) {
			if (!empty($panel[$buttonType])) {
				foreach ($panel[$buttonType] as &$button) {
					if (is_array($button)) {
						//Fix a problem in 7.0.6 where the buttons were missing their class names
						if (empty($button['class_name'])) {
							$button['class_name'] = 'zenario_plugin_nest';
						}
					
						//Plugin pickers need different paths for Wireframe modules
						if ($instance['content_id']) {
							if (isset($button['pick_items']['path_if_wireframe'])) {
								$button['pick_items']['path'] = $button['pick_items']['path_if_wireframe'];
							}
						}
					}
				}
				
				//Automatically create drop-down menus for quickly adding plugins
				if (!empty($panel[$buttonType]['add_plugin'])) {
			
					foreach ($modules as $moduleId => $name) {
						$panel[$buttonType]['add_plugin_'. $moduleId] =
							array(
								'ord' => ++$ord,
								'parent' => 'add_plugin',
								'label' => $name,
								'ajax' => array(
									'class_name' => $c,
									'request' => array(
										'add_plugin' => 1,
										'moduleId' => $moduleId
							)));
					}
				}
				
				//Automatically create drop-down menus for quickly adding twig-snippets
				if (!empty($panel[$buttonType]['add_twig_snippet'])) {
			
					foreach ($twigSnippets as $i => $twigSnippet) {
						$panel[$buttonType]['add_twig_snippet_'. $i] =
							array(
								'ord' => ++$ord,
								'parent' => 'add_twig_snippet',
								'label' => $twigSnippet[0]. '/twig/'. $twigSnippet[2],
								'ajax' => array(
									'class_name' => $c,
									'request' => array(
										'add_twig_snippet' => 1,
										'moduleClassName' => $twigSnippet[0],
										'snippetName' => $twigSnippet[1]
							)));
					}
				}
			}
		}
		
		//Find out the largest number of columns used on a layout, or just guess at 12 if there are no layouts yet
		$maxCols = (int) ifNull(selectMax('layouts', 'cols'), 12);
		for ($i = 2; $i < $maxCols; ++$i) {
			$label = adminPhrase('[[cols]] cols', array('cols' => $i));
			
			$panel['columns']['cols']['values'][$i] =
				array(
					'ord' => ++$ord,
					'label' => $label);
			
			$panel['item_buttons'][$i] =
				array(
					'parent' => 'cols',
					'ord' => ++$ord,
					'label' => $label,
					'ajax' =>array(
						'class_name' => $c,
						'request' =>
							array('set_cols' => 1, 'cols' => $i)));
		}
		
		$this->setTitleAndCheckPermissions($path, $panel, $refinerName, $refinerId, $mode, $instance);
	}
	
	protected function setTitleAndCheckPermissions($path, &$panel, $refinerName, $refinerId, $mode, $instance) {
		
		
		//Check permissions for Wireframe modules
		if ($instance['content_id'] && !isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = array();
			$panel['collection_buttons']['help'] = array(
				'css_class' => 'help',
				'help' => array(
					'message' =>
						adminPhrase('This nest is on a published, hidden or archived content item and cannot be edited.<br /><br />Create a Draft to make changes.')));
			
			$panel['item_buttons'] = array(
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']);
			
			unset($panel['reorder']);
		
		} elseif ($instance['content_id'] && !checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = array();
			$panel['collection_buttons']['help'] = array(
				'css_class' => 'help',
				'help' => array(
					'message' =>
						adminPhrase("This content item is locked by another administrator, or you don't have the permissions to modify it.")));
			
			$panel['item_buttons'] = array(
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']);
			
			unset($panel['reorder']);
		
		} elseif (!$instance['content_id'] && !checkPriv('_PRIV_VIEW_REUSABLE_PLUGIN')) {
			exit;
		}
		
		
		//Check permissions for Reusable modules
		if (!$instance['content_id'] && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			$panel['collection_buttons'] = array();
			$panel['item_buttons'] = array(
				'view' => $panel['collection_buttons']['view'],
				'plugin_settings' => $panel['collection_buttons']['plugin_settings']);
		
		}
		
		if (!$instance['content_id'] && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			unset($panel['reorder']);
		}
		
		
		if (false !== strpos($instance['class_name'], 'slide')) {
			if ($instance['content_id']) {
				$panel['title'] = adminPhrase('Editing the slideshow on [[slot_name]]', $instance);
			} else {
				$panel['title'] = adminPhrase('Editing the slideshow "[[instance_name]]"', $instance);
			}
		} else {
			if ($instance['content_id']) {
				$panel['title'] = adminPhrase('Editing the nest on [[slot_name]]', $instance);
			} else {
				$panel['title'] = adminPhrase('Editing the nest "[[instance_name]]"', $instance);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$statesToSlides = array();
		if ($usesConductor = conductorEnabled(get('refiner__nest'))) {
			foreach ($panel['items'] as $id => &$item) {
				if ($item['states']) {
					foreach (explode(',', $item['states']) as $state) {
						$statesToSlides[$state] = $item['ordinal'];
					}
				}
			}
		}
		
		require_once CMS_ROOT. 'zenario/libraries/public_domain/convert_to_roman/convert_to_roman.php';
		
		foreach ($panel['items'] as $id => &$item) {
			$item['traits'] = array();
			if ($item['is_slide']) {
				$item['traits']['is_slide'] = true;
				$item['css_class'] = 'zenario_nest_tab';
				$item['cols'] = ' ';
				$item['small_screens'] = ' ';
				$item['prefix'] = $item['ordinal']. '. ';
				
				//Get a list of slide numbers/states that this state can go to
				if ($usesConductor && $item['states']) {
					$toStates = sqlFetchAssocs('
						SELECT to_state, commands
						FROM [[DB_NAME_PREFIX]]nested_paths AS path
						WHERE path.instance_id = [[0]]
						  AND path.from_state IN ([[1]])',
						[$refinerId, explode(',', $item['states'])]
					);
					
					$toText = array();
					foreach ($toStates as $toState) {
						if (isset($statesToSlides[$toState['to_state']])) {
							$toText[] = $toState['commands']. ' → '. $statesToSlides[$toState['to_state']]. '/'. $toState['to_state'];
						}
					}
					
					if (!empty($toText)) {
						$item['name_or_title'] .= ' | '. implode(', ', $toText);
					}
				}
			
			} else {
				$item['traits']['is_not_tab'] = true;
				$item['prefix'] = strtolower(convertToRoman($item['ordinal'])). '. ';
				
				if ($item['checksum']) {
					$img = '&c='. $item['checksum'];
					$item['traits']['has_image'] = true;
					$item['image'] = 'zenario/file.php?og=1'. $img;
					$item['list_image'] = 'zenario/file.php?ogl=1'. $img;
				} else {
					$item['image'] = getModuleIconURL($item['module_class_name']);
				}
			}
		}
	}
	
	
	//Check to see if the current Admin has the rights to change this nest, exit if not
	public function exitIfNoEditPermsOnNest($instance) {
		
		if (!inc($instance['class_name'])) {
			exit;
		
		} elseif ($instance['content_id'] && !isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif ($instance['content_id'] && !checkPriv('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif (!$instance['content_id'] && !post('reorder') && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		
		} elseif (!$instance['content_id'] && post('reorder') && !checkPriv('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		}
		
		//If this is a Wireframe Plugin, and a submit is being made, update the latest modification date
		if ($instance['content_id'] && !empty($_POST)) {
			updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$instance = getPluginInstanceDetails(request('refiner__nest'));
		$this->exitIfNoEditPermsOnNest($instance);
		
		//Add a tab.
		//Also, if we're adding a new plugin, ensure that at least one tab has been made.
		if (post('add_slide') || post('upload_banner') || post('add_plugin') || post('add_twig_snippet') || post('copy_plugin_instance')) {
			if (post('add_slide') || !checkRowExists('nested_plugins', array('instance_id' => post('refiner__nest'), 'is_slide' => 1))) {
				static::addTab(post('refiner__nest'));
			}
		}
		
		//Add a new plugin or banner
		if (post('add_plugin')) {
			return static::addPlugin(post('moduleId'), post('refiner__nest'), $ids, false, true);
		
		} elseif (post('copy_plugin_instance')) {
			if ($ids2) {
				return static::addPluginInstance($ids2, post('refiner__nest'), $ids, true);
			} else {
				return static::addPluginInstance($ids, post('refiner__nest'));
			}
		
		} elseif (post('upload_banner')) {
			if ($imageId = addFileToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true)) {
				return static::addBanner($imageId, post('refiner__nest'), $ids, true);
			} else {
				return false;
			}
		
		} elseif (post('add_twig_snippet')) {
			return static::addTwigSnippet(post('moduleClassName'), post('snippetName'), post('refiner__nest'), $ids, true);
		
		} elseif ((get('duplicate_plugin') || get('duplicate_plugin_and_add_tab'))) {
			echo $this->duplicatePluginConfirm($ids);
			
		} elseif (post('duplicate_plugin')) {
			return static::duplicatePlugin($ids, post('refiner__nest'));
		
		} elseif (post('duplicate_plugin_and_add_tab')) {
			static::addTab(post('refiner__nest'));
			return static::duplicatePlugin($ids, post('refiner__nest'));
		
		//Change the number of columns that a plugin takes up
		} elseif (post('set_cols')) {
			
			$cols = (int) post('cols');
			
			foreach (explode(',', $ids) as $id) {
				updateRow('nested_plugins',
					array('cols' => post('cols')),
					array('instance_id' => post('refiner__nest'), 'is_slide' => 0, 'id' => $id));
				
				//"only" is only a valid option for full width columns (0) or groupings (-1).
				//If this isn't a full width or a grouping, then change any "only"s to "show"s.
				if ($cols > 0) {
					updateRow('nested_plugins',
						array('small_screens' => 'show'),
						array('instance_id' => post('refiner__nest'), 'is_slide' => 0, 'id' => $id, 'small_screens' => 'only'));
				}
			}
		
		//Set a plugin to either show or hide on mobile view.
		} elseif (post('small_screens') && in(post('small_screens'), 'show', 'hide')) {
			foreach (explode(',', $ids) as $id) {
				updateRow('nested_plugins',
					array('small_screens' => post('small_screens')),
					array('instance_id' => post('refiner__nest'), 'is_slide' => 0, 'id' => $id));
			}
		
		//Set a plugin to only be shown on mobile view.
		//Note that this is only valid for full width columns (0) or groupings (-1).
		} elseif (post('small_screens') && post('small_screens') == 'only') {
			foreach (explode(',', $ids) as $id) {
				updateRow('nested_plugins',
					array('small_screens' => post('small_screens')),
					array('instance_id' => post('refiner__nest'), 'is_slide' => 0, 'cols' => array(-1, 0), 'id' => $id));
			}
		
		} elseif (get('remove_plugin')) {
			echo $this->removePluginConfirm($ids, post('refiner__nest'));
			
		} elseif (post('remove_plugin')) {
			foreach (explode(',', $ids) as $id) {
				static::removePlugin($instance['class_name'], $id, post('refiner__nest'));
			}
		
		} elseif ((get('remove_tab'))) {
			echo $this->removeTabConfirm($ids, post('refiner__nest'));
			
		} elseif (post('remove_tab')) {
			foreach (explode(',', $ids) as $id) {
				$this->removeTab($instance['class_name'], $id, post('refiner__nest'));
			}
			
		} elseif (post('reorder')) {
			//Each specific Nest may have it's own rules for ordering, so be sure to call the correct reorder method for this Nest
			call_user_func(array($instance['class_name'], 'reorderNest'), $ids);
			call_user_func(array($instance['class_name'], 'resyncNest'), post('refiner__nest'));
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}