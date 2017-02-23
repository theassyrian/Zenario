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

//Check to see if this file is being directly accessed, when the index.php file in the directory below should be used to access this file
if (file_exists('visitorheader.inc.php') && file_exists('../index.php')) {
	header('Location: ../');
	exit;

//Check to see if the config file has been created, and if not, link to the installer.
} elseif (!file_exists('zenario_siteconfig.php') || filesize('zenario_siteconfig.php') < 20) {
	echo '
		<html>
		  <head>
			<title>Welcome to Zenario</title>
		  </head>
		  <body>
			<script type="text/javascript">
				document.location.href = "zenario/admin/welcome.php";
			</script>
			<h1>Welcome to Zenario</h1>
			<p>A new zenario-powered website is coming soon at this location.</p>
			<p style="font-size: 70%">If you own this site and wish to continue with the installation please enable JavaScript to continue.</p>
		  </body>
		</html>';
	exit;

} elseif (isset($_GET['method_call'])) {
	//RSS feeds are handled by ajax.php
	if ($_GET['method_call'] == 'showRSS') {
		chdir('zenario');
		require 'ajax.php';
		exit;
	
	//Sitemaps are handled by Storekeeper
	} elseif ($_GET['method_call'] == 'showSitemap') {
		chdir('zenario/admin');
		require 'organizer.ajax.php';
		exit;
	}
}

require 'basicheader.inc.php';
startSession();

//Run pre-load actions
require editionInclude('index.pre_load');

define('CHECK_IF_MAJOR_REVISION_IS_NEEDED', true);
require CMS_ROOT. 'zenario/visitorheader.inc.php';
require CMS_ROOT. 'zenario/includes/twig.inc.php';


if ($checkPriv = checkPriv()) {
	require CMS_ROOT. 'zenario/adminheader.inc.php';
	checkForChangesInCssJsAndHtmlFiles();
	
	//setAdminSession($_SESSION['admin_userid'], $_SESSION['admin_global_id']);

//Don't directly show a Content Item if the site is disabled
} elseif (!setting('site_enabled')) {
	showStartSitePageIfNeeded();
	exit;
}



//Attempt to get this page.
$cID = $cType = $content = $version = $redirectNeeded = $aliasInURL = false;
resolveContentItemFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $_GET, $_REQUEST, $_POST);

if ($redirectNeeded && empty($_POST) && !($redirectNeeded == 302 && $checkPriv)) {
	
	//When fixing the language code in the URL, make sure we redirect using the full path
	//as the language code might be in the domain/subdomain.
	$fullPath = $redirectNeeded == 302;
	
	$requests = $_GET;
	unset($requests['cID']);
	unset($requests['cType']);
	
	header('location: '. ifNull(linkToItem($cID, $cType, $fullPath, $requests), SUBDIRECTORY), true, $redirectNeeded);
	exit;
}

//Run pre-header actions
require editionInclude('index.pre_header');



//Look up more details on the content item we are going to show
$status = getShowableContent($content, $version, $cID, $cType, request('cVersion'), $checkRequestVars = true);
	//N.b. an empty string ('') is used for a private page, if a visitor is not logged in
	//A 0 is used if a visitor is logged in and still can't see the page

//If a page was requested but couldn't be shown...
if ($status === ZENARIO_403_NO_PERMISSION) {
	//Show the no-access if this page is not accessible
	header('HTTP/1.0 403 Forbidden');
	langSpecialPage('zenario_no_access', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);

} elseif ($status === ZENARIO_401_NOT_LOGGED_IN) {
	//Set the destination so the Visitor can come back here when logged in
	$_SESSION['destCID'] = $content['id'];
	$_SESSION['destCType'] = $content['type'];
	$_SESSION['destURL'] = httpOrHttps(). httpHost(). $_SERVER['REQUEST_URI'];
	$_SESSION['destTitle'] = $version['title'];
	cms_core::$canCache = false;
	
	//Show the login page
	header('HTTP/1.0 401 Authentication Required');
	langSpecialPage('zenario_login', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);

} elseif (!$status) {
	//Show the no-access if this page does not exist
	header('HTTP/1.0 404 Not Found');
	langSpecialPage('zenario_not_found', $cID, $cType);
	$status = getShowableContent($content, $version, $cID, $cType);
	
	//Log error if errors module is running
	if (inc('zenario_error_log')) {
		$httpReferer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$requestURI = rtrim($_SERVER['REQUEST_URI'], '/');
		$URI = explode('/', $requestURI);
		$pageAlias = end($URI);
		zenario_error_log::log404Error($pageAlias, $httpReferer);
	}
}

//Try to go to the home page as a fallback if the Not Found/No Access/Login pages could not be used above
if (!$content || !$version || $status !== true) {
	$cID = cms_core::$homeCID;
	$cType = cms_core::$homeCType;
	$status = getShowableContent($content, $version, $cID, $cType);
}

//If none of the above gave us a page to show, the site probably isn't set up correctly, so show the installation message
if (!$content || !$version || $status !== true) {
	showStartSitePageIfNeeded();
	exit;
}
unset($cID);
unset($cType);
unset($menu);


setShowableContent($content, $version);


//Run post-display actions
require editionInclude('index.pre_get_contents');


$fakeLayout = false;
$hideLayout = false;
$specificSlot = false;
$specificInstance = false;
$overrideSettings = false;
$overrideFrameworkAndCSS = false;
$methodCall = isset($_REQUEST['method_call'])? $_REQUEST['method_call'] : false;

if (($methodCall == 'showSingleSlot' || $methodCall == 'showIframe')
 && (request('instanceId') || request('slotName'))) {
	
	$specificInstance = request('instanceId');
	if ($specificSlot = request('slotName')) {
		if (!$hideLayout = (bool) request('hideLayout')) {
			$fakeLayout = (bool) request('fakeLayout');
		}
	}
	
	if ($fakeLayout
	 && !empty($_REQUEST['overrideSettings'])
	 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT') || checkPriv('_PRIV_EDIT_DRAFT'))) {
		$overrideSettings = json_decode($_REQUEST['overrideSettings'], true);
	}
	
	if ($fakeLayout
	 && !empty($_REQUEST['overrideFrameworkAndCSS'])
	 && (checkPriv('_PRIV_CREATE_REVISION_DRAFT') || checkPriv('_PRIV_EDIT_DRAFT') || checkPriv('_PRIV_EDIT_CSS'))) {
		$overrideFrameworkAndCSS = json_decode($_REQUEST['overrideFrameworkAndCSS'], true);
	}

} elseif (!empty($_REQUEST['overrideFrameworkAndCSS']) && checkPriv('_PRIV_EDIT_CSS')) {
	$overrideFrameworkAndCSS = json_decode($_REQUEST['overrideFrameworkAndCSS'], true);
}

getSlotContents(
	cms_core::$slotContents,
	cms_core::$cID, cms_core::$cType, cms_core::$cVersion,
	cms_core::$layoutId, cms_core::$templateFamily, cms_core::$templateFileBaseName,
	$specificInstance, $specificSlot,
	false, true, false, $overrideSettings, $overrideFrameworkAndCSS);
useGZIP(setting('compress_web_pages'));



//Check whether we should allow cross-site iframes
do {
	//Never allow in admin mode
	if ($checkPriv) {
		break;
	}
	
	//Check what is allowed to be shown
	switch (setting('xframe_target')) {
		case 'all_slots':
			//Only allow slots to be shown
			if (!$specificSlot) {
				break 2;
			}
			break;
		
		case 'slots_with_nests':
			//Only allow slots with nests in them to be shown
			if (!$specificSlot || empty(cms_core::$slotContents[$specificSlot]['is_nest'])) {
				break 2;
			}
			break;
		
		default:
			//Allow either slots or whole content items
			if (!$specificSlot && $methodCall) {
				break 2;
			}
	}
	
	//Check domain options
	switch (setting('xframe_options')) {
		case 'all':
			//Allow from any domain (not recommended)
			break;
		case 'specific':
			//Allow from specific domains
			if (!isset($_SERVER['HTTP_REFERER'])
			 || !in_array(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST), explodeAndTrim(setting('xframe_domains')))) {
				break 2;
			}
			break;
		default:
			//Do not allow from third-party domains
			break 2;
	}
	
	//If we got past all of the 
	header('X-Frame-Options: ALLOWALL');
} while (false);


require editionInclude('index.post_get_contents');


$canonicalURL = linkToItem(cms_core::$cID, cms_core::$cType, true, '', false, true, true);


$specialPage = isSpecialPage(cms_core::$cID, cms_core::$cType);
if ($validDestURL = !$specialPage || $specialPage == 'zenario_home') {
	$_SESSION['destCID'] = cms_core::$cID;
	$_SESSION['destCType'] = cms_core::$cType;
	$_SESSION['destURL'] = $canonicalURL;
	$_SESSION['destTitle'] = cms_core::$pageTitle;
}



echo 
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="', $_SESSION["user_lang"], '" lang="', $_SESSION["user_lang"], '">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';

//If relative URLs with slashes are in use, add the "base" path to make it clear what the relative URL of this page should be.
//(N.b. most methods in the CMS automatically switch to using the full URL in this case, but this statement should help catch
// any hardcoded links that need correcting, e.g. in WYSIWYG Eidtors.)
if (setting('mod_rewrite_slashes')) {
	echo '
<base href="', absCMSDirURL(), '">';
}

echo '
<title>', htmlspecialchars(cms_core::$pageTitle), '</title>
<link rel="canonical" href="', htmlspecialchars($canonicalURL), '"/>
<meta property="og:url" content="', htmlspecialchars($canonicalURL), '"/>
<meta property="og:type" content="', htmlspecialchars(cms_core::$pageOGType), '"/>
<meta property="og:title" content="', htmlspecialchars(cms_core::$pageTitle), '"/>';

$imageWidth = $imageHeight = $imageURL = false;
if (cms_core::$pageImage && imageLink($imageWidth, $imageHeight, $imageURL, cms_core::$pageImage)) {
	echo '
<meta property="og:image" content="', htmlspecialchars(absCMSDirURL().$imageURL), '"/>';
}

echo '
<meta property="og:description" content="', htmlspecialchars(cms_core::$pageDesc), '"/>
<meta name="description" content="', htmlspecialchars(cms_core::$pageDesc), '" />
<meta name="generator" content="Zenario ', getCMSVersionNumber(), '" />
<meta name="keywords" content="', htmlspecialchars(cms_core::$pageKeywords), '" />';


//If we were to include a <base> tag, this would be a more reliable way of handling relative URLs
//on pages with slashes in their alias. However using the <base> tag will break links to #anchors that
//may be on the page.
//echo '
//<base href="', htmlspecialchars(absCMSDirURL()), '" />';

// Add hreflang tags
if (getNumLanguages() > 1) {
	// If there are no important get requests
	$getRequests = false;
	foreach(cms_core::$importantGetRequests as $getRequest => $defaultValue) {
		if (isset($_GET[$getRequest]) && $_GET[$getRequest] != $defaultValue) {
			$getRequests = true;
			break;
		}
	}
	if (!$getRequests) {
		$sql = "
			SELECT c.id, c.type, c.alias, c.equiv_id, c.language_id
			FROM ". DB_NAME_PREFIX. "content_items AS c
			INNER JOIN ". DB_NAME_PREFIX. "translation_chains AS tc
			   ON c.equiv_id = tc.equiv_id
			  AND c.type = tc.type
			WHERE tc.privacy = 'public'
			  AND c.equiv_id = ". (int) cms_core::$equivId. "
			  AND c.type = '". sqlEscape(cms_core::$cType). "'
			  AND c.status IN ('published_with_draft', 'published')";
		$result = sqlSelect($sql);
		if (sqlNumRows($result) > 1) {
			while($row = sqlFetchAssoc($result)) {
				$pageLink = linkToItem($row['id'], $row['type'], true, '', $row['alias'], true, true, $row['equiv_id'], $row['language_id']);
				echo '
<link rel="alternate" href="'. htmlspecialchars($pageLink). '" hreflang="'. htmlspecialchars($row['language_id']). '">';
			}
		}
	}
}

CMSWritePageHead('zenario/', false, true, $overrideFrameworkAndCSS);
echo "\n", setting('sitewide_head'), "\n</head>";


$contentItemDiv =
	"\n".
	'<div id="zenario_citem" class="';

if ($specialPage) {
	$contentItemDiv .= htmlspecialchars($specialPage). ' ';
}

$contentItemDiv .= 'lang_'. preg_replace('/[^\w-]/', '', cms_core::$langId);

if (cms_core::$itemCSS) {
	$contentItemDiv .= ' '. htmlspecialchars(cms_core::$itemCSS);
}

$contentItemDiv .= '">';


$templateDiv =
	"\n".
	'<div id="zenario_layout" class="'.
		'zenario_'. htmlspecialchars(cms_core::$cType). '_layout';

if (cms_core::$templateCSS) {
	$templateDiv .= ' '. htmlspecialchars(cms_core::$templateCSS);
}

$templateDiv .= '">';


$skinDiv =
	"\n".
	'<div id="zenario_skin" class="zenario_skin';

if (cms_core::$skinCSS) {
	$skinDiv .= ' '. htmlspecialchars(cms_core::$skinCSS). '';
}

$skinDiv .= '">';






//Functionality for only showing one Plugin in a slot
if ($specificInstance || $specificSlot) {
	
	//Just show the plugin, without any of the <div>s from the layout around it
	if ($hideLayout) {
		CMSWritePageBody('zenario_showing_plugin_without_layout', '', true);
		slot($specificSlot, 'grid');
	
	//Try and "fake" the grid, to get as many styles from the Skin as possible,
	//while still showing the plugin on its own taking up the full width
	} else {
		if ($fakeLayout) {
			echo '
				<link rel="stylesheet" type="text/css" href="', htmlspecialchars(absCMSDirURL()), 'zenario/styles/admin_plugin_preview.min.css">';
			
			CMSWritePageBody('zenario_showing_plugin_preview', '', true);
		} else {
			CMSWritePageBody('zenario_showing_standalone_plugin', '', true);
		}
		
		echo $skinDiv, $templateDiv, $contentItemDiv, '
			<div class="container ', empty($_GET['grid_container'])? '' : 'container_'. (int) $_GET['grid_container'], '">
				<div
					class="
						alpha span
						', empty($_GET['grid_columns'])? 'span1_1' : 'span'. (int) $_GET['grid_columns'], '
						', empty($_GET['grid_cssClass'])? '' : htmlspecialchars($_GET['grid_cssClass']), '
					"
					', empty($_GET['grid_pxWidth'])? '' : 'style="max-width: '. (int) $_GET['grid_pxWidth']. 'px;"', '
				>';
					slot($specificSlot, 'grid');
		echo '
				</div>
			</div>
		</div></div></div>';
	}
	
	
	echo '
		<script type="text/javascript">
			window.zenario_inIframe = true;
		</script>';
	
	CMSWritePageFoot('zenario/', false, false, false);

//Show a preview, without the Admin Toolbar or any JavaScript
} elseif (!empty($_REQUEST['_show_page_preview'])) {
	CMSWritePageBody('zenario_showing_preview', '', true);
	echo $skinDiv, $templateDiv, $contentItemDiv;
	require CMS_ROOT. cms_core::$templatePath. cms_core::$templateFilename;
	
	echo "\n", '</div></div></div>';
	
	if (!empty($_REQUEST['_add_js'])) {
		CMSWritePageFoot('zenario/', false, false, false);
	} else {
		echo '
		<script type="text/javascript" src="zenario/libraries/mit/jquery/jquery.min.js?v=', ZENARIO_VERSION, '"></script>';
	}
	
	echo '<script type="text/javascript">
			$(\'*\').each(function(i, el) {
				el.onclick = function() { return false; };
			});';
			
			if (!empty($_REQUEST['_scroll_to'])) {
				echo '
					$(document).scrollTop('. (int) $_REQUEST['_scroll_to']. ');';
			}
	echo '
		</script>';
	

//Normal functionality; show the whole page
} else {
	CMSWritePageBody('', '', true, true);
	showCookieConsentBox();
	echo $skinDiv, $templateDiv, $contentItemDiv;
	
	if (file_exists(CMS_ROOT. ($file = cms_core::$templatePath. cms_core::$templateFilename))) {
		require CMS_ROOT. $file;
		checkSlotsWereUsed();
	
	} else {
		echo 
			'<div style="padding:auto; margin:auto; text-align: center; position: absolute; top: 35%; width: 100%;">',
				htmlspecialchars($msg = adminPhrase('Template File "[[file]]" is missing. ', array('file' => $file))),
				'<a href="zenario/admin/organizer.php">Go to Organizer</a>',
			'</div>';
		
		if (!$checkPriv && defined('DEBUG_SEND_EMAIL') && DEBUG_SEND_EMAIL === true) {
			reportDatabaseError($msg);
		}
	}
	
	echo "\n", '</div></div></div>';
	CMSWritePageFoot('zenario/');
	
	//If someone just changed the CSS for a plugin, scroll down to that plugin to show the changes
	if ($checkPriv && !empty($_SESSION['scroll_slot_on_'. cms_core::$cType. '_'. cms_core::$cID])) {
		echo '
			<script type="text/javascript">
				zenario.scrollToSlotTop("'. jsEscape($_SESSION['scroll_slot_on_'. cms_core::$cType. '_'. cms_core::$cID]). '", false, 300);
			</script>';
		
		unset($_SESSION['scroll_slot_on_'. cms_core::$cType. '_'. cms_core::$cID]);
	}
}


echo "\n", setting('sitewide_foot'), "\n";


//Run post-display actions
require editionInclude('index.post_display');

echo "\n</body>\n</html>";


exit;