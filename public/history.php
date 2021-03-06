<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.history.php 1.4 03/01/16 01:47:32-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.record_general.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.db_api.php");

$tpl = new Template_API();
if (APP_API) {
    $tpl->setTemplate("history.tpl.xml");
} else {
    $tpl->setTemplate("history.tpl.html");
}

$isUser = Auth::getUsername();

//Three displays exists, dosn't exist and not logged in
if (!$isUser) {
    $tpl->assign("exists", 2);
} else {
    $pid = $_GET["pid"];
    $record = new RecordObject($pid);
    if (!$record->checkExists()) {
	$tpl->assign("exists", 0);
    } else {
	$tpl->assign("exists", 1);
	$tpl->assign("pid", $pid);
	$tpl->assign("changes", History::getListing($_GET["pid"]));
    }

}
$tpl->displayTemplate();