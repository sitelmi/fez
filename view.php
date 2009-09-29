<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Fez - Digital Repository System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005, 2006, 2007 The University of Queensland,         |
// | Australian Partnership for Sustainable Repositories,                 |
// | eScholarship Project                                                 |
// |                                                                      |
// | Some of the Fez code was derived from Eventum (Copyright 2003, 2004  |
// | MySQL AB - http://dev.mysql.com/downloads/other/eventum/ - GPL)      |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Christiaan Kortekaas <c.kortekaas@library.uq.edu.au>,       |
// |          Matthew Smith <m.smith@library.uq.edu.au>                   |
// +----------------------------------------------------------------------+
//
//

include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.controlled_vocab.php");
include_once(APP_INC_PATH . "class.collection.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.author.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.xsd_html_match.php");
include_once(APP_INC_PATH . "class.xsd_relationship.php");
include_once(APP_INC_PATH . "class.fedora_api.php");
include_once(APP_INC_PATH . "class.filecache.php");

if ((defined('APP_BASIC_AUTH_IP') && ($_SERVER['REMOTE_ADDR'] == APP_BASIC_AUTH_IP)) && !isset($_SERVER['PHP_AUTH_USER'])) {
	header ("Location: https://".APP_HOSTNAME.APP_RELATIVE_URL."basicview.php?pid=".$_GET['pid']);
}

$tpl = new Template_API();
$tpl->setTemplate("view.tpl.html");
$pid = @$_REQUEST["pid"];

$show_tombstone = true;  // tell view2.php to show the tombstone if the record has a deleted fedora status
$savePage = true;

$logged_in = Auth::isValidSession($_SESSION);
$cache = new fileCache($pid, $_SERVER['QUERY_STRING'], $_GET['flushcache']);

if(!$logged_in && APP_FILECACHE == "ON") {
	$cache->checkForCacheFile();
}

include_once('view2.php');
$tpl->displayTemplateRecord($pid);

if(!$logged_in && APP_FILECACHE == "ON") {
	$cache->saveCacheFile($savePage);
}
