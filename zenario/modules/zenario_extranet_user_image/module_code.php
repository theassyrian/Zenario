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

class zenario_extranet_user_image extends module_base_class {
	
	protected $mergeFields = array();
	protected $sections = array();
	
	public function init() {
		if (!session('extranetUserID')) {
			return checkPriv();
		}
		
		if (post('extranet_add_image')) {
			if (setting('max_content_image_filesize') < $_FILES['extranet_upload_image']['size']) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = array('Error' => $this->phrase('Your image must be smaller than [[bytes]] bytes', array('bytes'=>setting('max_content_image_filesize'))));
			} elseif (empty($_FILES['extranet_upload_image']) || empty($_FILES['extranet_upload_image']['type'])) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = array('Error' => $this->phrase('_ERROR_NO_IMAGE_SELECTED'));
			
			} elseif (!isImage($_FILES['extranet_upload_image']['type'])) {
				$this->sections['Errors'] = true;
				$this->sections['Error'] = array('Error' => $this->phrase('_ERROR_INVALID_FILE_TYPE'));
			
			} else {
				$image = getimagesize($location = $_FILES['extranet_upload_image']['tmp_name']);
				
				$minWidth = ifNull((int) $this->setting('min_width'), 375);
				$minHeight = ifNull((int) $this->setting('min_height'), 500);
				if ($image[0] >= $minWidth && $image[1] >= $minHeight) {
					//Remove the User's old image, if they had one
					$this->removeUserImage();
					if ($imageId = addFileToDatabase('user', $location, rawurldecode($_FILES['extranet_upload_image']['name']), true)) {
						updateRow('users', array('image_id' => $imageId), session('extranetUserID'));
					}
				} else {
					$this->sections['Errors'] = true;
					$this->sections['Error'] = array('Error' => $this->phrase('_ERROR_YOUR_UPLOADED_IMAGE_WAS_TOO_SMALL_IMAGE_SHOULD_BE_AT_LEAST_X_PIXELS_WIDE_AND_X_PIXELS_HIGH', 
																					array(	'width' => $minWidth,
																							'height' => $minHeight
																						) 
																			));
				}
							

			}
		
		} elseif (post('extranet_remove_image_confirm') && $this->setting('allow_remove')) {
			$this->removeUserImage();
		}
		
		$url = $width = $height = false;
		if (($imageId = getRow('users', 'image_id', session('extranetUserID')))
		 && imageLink($width, $height, $url, $imageId, ifNull((int) $this->setting('max_width'), 375), ifNull((int) $this->setting('max_height'), 500))) {
			$this->sections['Existing_Image'] = true;
		 	$this->mergeFields['Image_Src'] = htmlspecialchars($url);
		 	$this->mergeFields['Image_Width'] = $width;
		 	$this->mergeFields['Image_Height'] = $height;
			$this->mergeFields['Upload_Button_Phrase'] = $this->phrase('_REPLACE');
		} else {
			$this->sections['Existing_Image'] = false;
			$this->mergeFields['Upload_Button_Phrase'] = $this->phrase('_ADD');
		}
		
		$this->mergeFields['Image_Title'] = $this->setting('title');
		
		if($this->setting('allow_upload')) {
			$this->sections['Allow_Upload'] = true;
			
			
		}
		
		$this->sections['Remove_Image'] = (bool) ($this->setting('allow_remove') && (!post('extranet_remove_image')));
		$this->sections['Remove_Image_Confirm'] = (bool) ($this->setting('allow_remove') && post('extranet_remove_image'));
		$this->sections['New_Image'] = true;
		
		return true;
	}
	
	function showSlot() {
		
		if (!session('extranetUserID')) {
			if (checkPriv()) {
				echo adminPhrase('You must be logged in as an Extranet User to see this Plugin.');
			}
			return;
		}
		echo $this->openForm('', 'enctype="multipart/form-data"');
			$this->framework('Outer', $this->mergeFields, $this->sections);
		echo $this->closeForm();
	}
	
	protected function removeUserImage() {
		//Remove the image for a user
		updateRow('users', array('image_id' => 0), session('extranetUserID'));
		
		//Delete any unlinked images
		$sql = "
			DELETE f FROM ". DB_NAME_PREFIX. "files AS f
			LEFT JOIN ". DB_NAME_PREFIX. "users AS u
			   ON u.image_id = f.id
			WHERE f.`usage` = 'user'
			  AND u.image_id IS NULL";
		
		sqlQuery($sql);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {

		if(!$values['allow_upload']) {
			$fields['first_tab/min_width']['hidden'] = true;
			$fields['first_tab/min_height']['hidden'] = true;
			$fields['first_tab/note_1']['hidden'] = true;
		} else {
			$fields['first_tab/min_width']['hidden'] = false;
			$fields['first_tab/min_height']['hidden'] = false;
			$fields['first_tab/note_1']['hidden'] = false;
		}

	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if(!$values['allow_upload']) {

		}
	}

}
?>