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

//Loop through each changed slide, and set its ordinal
foreach (explode(',', $ids) as $id) {
	$key = array('id' => $id, 'instance_id' => post('refiner__nest'), 'is_slide' => 1);
	if (checkRowExists('nested_plugins', $key)) {
		$newOrd = $_POST['ordinals'][$id];
		$newParent = $_POST['parent_ids'][$id];
		
		//Tabs sholudn't be children of other tabs
		if (!$newParent) {
			updateRow('nested_plugins', array('slide_num' => $newOrd), $key);
		}
	}
}

//Loop through each changed plugin
foreach (explode(',', $ids) as $id) {
	$key = array('id' => $id, 'instance_id' => post('refiner__nest'), 'is_slide' => 0);
	if (checkRowExists('nested_plugins', $key)) {
		$newOrd = $_POST['ordinals'][$id];
		$newParent = $_POST['parent_ids'][$id];
		
		//Plugins must be children of tabs
		if ($newParent) {
			//Convert the parent id to a slide number
			$slideNum = getRow('nested_plugins', 'slide_num', array('id' => $newParent, 'instance_id' => post('refiner__nest'), 'is_slide' => 1));
			//Update to the new slide number
			updateRow('nested_plugins', array('slide_num' => ifNull($slideNum, 0, 0), 'ord' => $newOrd), $key);
		}
	}
}