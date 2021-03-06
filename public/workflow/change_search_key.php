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
//include_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config.inc.php");

include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.db_api.php");
include_once(APP_INC_PATH . "class.collection.php");
include_once(APP_INC_PATH . "class.community.php");
include_once(APP_INC_PATH . "class.doc_type_xsd.php");
include_once(APP_INC_PATH . "class.workflow_trigger.php");
include_once(APP_INC_PATH . "class.xsd_html_match.php");
include_once(APP_INC_PATH . "najax/najax.php");
include_once(APP_INC_PATH . "najax_objects/class.select_search_key.php");

$xtpl = new Template_API();
$xtpl->setTemplate("workflow/select_search_key_values.tpl.html");
$xtpl->assign('rel_url', APP_RELATIVE_URL);
$xtpl->assign('input_name', 'sek_value');
$xtpl->assign('field_type', "select_single");
Zend_Registry::set('xtpl', $xtpl);

NAJAX_Server::allowClasses('SelectSearchKey');
if (NAJAX_Server::runServer()) {
    exit;
}

Auth::checkAuthentication(APP_SESSION);

$tpl = new Template_API('workflow');
$tpl->setTemplate("workflow/index.tpl.html");
$tpl->assign("type", "change_search_key_form");
$tpl->assign("type_name", "Select Search Key");

$tpl->assign("jqueryUI", true);

$wfstatus = &WorkflowStatusStatic::getSession(); // restores WorkflowStatus object from the session
$wfstatus->setTemplateVars($tpl);

$cat = $_REQUEST['cat'];

if ($cat == 'submit') {

    $sek_value = $raw_value = $_REQUEST['sek_value'];

    // Prepare array data in Date format
    if (is_array($raw_value)){

        if (isset($raw_value['Year']) && isset($raw_value['Month']) && isset($raw_value['Day'])){
            $sek_value = $raw_value['Year'] . "-" . $raw_value['Month'] . "-" . $raw_value['Day'];
        }else{
            $sek_value = implode("", $raw_value);
        }
    }

    $wfstatus->sek_value = $sek_value;
    $wfstatus->sek_id = $_REQUEST['sek_id'];
    $wfstatus->reason_for_edit = $_REQUEST['reason_for_edit'];

}
$wfstatus->checkStateChange();

$search_keys_list = Search_Key::getBulkChangeAssocList();

$tpl->assign('search_keys_list', $search_keys_list);
$tpl->assign('search_keys_list_selected', $search_keys_list[0]);

$tpl->assign('najax_header', NAJAX_Utilities::header(APP_RELATIVE_URL . 'include/najax'));
$tpl->registerNajax(NAJAX_Client::register('SelectSearchKey', APP_RELATIVE_URL.'workflow/change_search_key.php'));

$tpl->displayTemplate();

