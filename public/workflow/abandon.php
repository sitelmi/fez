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
include_once('../config.inc.php');
include_once(APP_INC_PATH . 'class.background_process.php');
include_once(APP_INC_PATH . 'class.background_process_list.php');
include_once(APP_INC_PATH . 'class.auth.php');
include_once(APP_INC_PATH . 'class.api.php');
include_once(APP_INC_PATH . 'class.record_general.php');
include_once(APP_INC_PATH.'class.workflow_status.php');
include_once(APP_INC_PATH.'najax_classes.php');

$id = $_REQUEST['id'];
$res = WorkflowStatusStatic::remove($id);

if (APP_API) {
    if ($res > 0) {
        $arr = API::makeResponse('OK', "Removed workflow (result: $res).");
        $httpcode = 200;
    } else {
        $arr = API::makeResponse('FAIL', "Failed to remove workflow (result: $res).");
        $httpcode = 417;
    }
    API::reply($httpcode, $arr, APP_API);
    exit;
}

if ($res > 0) {
    Session::setMessage("Abandoned workflow");
    $bgp_list = new BackgroundProcessList;
    $bgp_list->autoDeleteOld(Auth::getUserID());

} else {
    Session::setMessage("Couldn't remove workflow");
}
$href = $_REQUEST['href'];
if (empty($href)) {
    $href = APP_RELATIVE_URL.'my_fez.php';
}
Auth::redirect($href);

?>
