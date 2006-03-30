<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Fez - Digital Repository System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005, 2006 The University of Queensland,               |
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
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.fedora_api.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.controlled_vocab.php");
include_once(APP_INC_PATH . "class.workflow_trigger.php");

$tpl = new Template_API();
$tpl->setTemplate("cv_selector.tpl.html");

$cvo_id = @$HTTP_GET_VARS["cvo_id"] ? @$HTTP_GET_VARS["cvo_id"] : @$HTTP_POST_VARS["cvo_id"];
$xsdmf_cvo_min_level = @$HTTP_GET_VARS["xsdmf_cvo_min_level"] ? @$HTTP_GET_VARS["xsdmf_cvo_min_level"] : @$HTTP_POST_VARS["xsdmf_cvo_min_level"];
$element = @$HTTP_GET_VARS["element"] ? @$HTTP_GET_VARS["element"] : @$HTTP_POST_VARS["element"];
$form = @$HTTP_GET_VARS["form"] ? @$HTTP_GET_VARS["form"] : @$HTTP_POST_VARS["form"];
// get one level of the selected cvo_id
if (!is_numeric($cvo_id)) {
	$cvo_id = $_GET['cv_fields'];
}
$cvo_details = Controlled_Vocab::getDetails($cvo_id);

	$breadcrumb = Controlled_Vocab::getParentAssocListFullDisplay($cvo_id);
	$breadcrumb = Misc::array_merge_preserve($breadcrumb, Controlled_Vocab::getAssocListByID($cvo_id));

	$newcrumb = array();
	foreach ($breadcrumb as $key => $data) {
		array_push($newcrumb, array("cvo_id" => $key, "cvo_title" => $data));
	}
	$max_breadcrumb = (count($newcrumb) -1);

	$tpl->assign("max_subject_breadcrumb", $max_breadcrumb);
	$tpl->assign("subject_breadcrumb", $newcrumb);

$cvo_list = Controlled_Vocab::getAssocListFullDisplay($cvo_id, "", 0, 1);
$parent_list = Controlled_Vocab::getList();

$show_add = 1;
if ($xsdmf_cvo_min_level == 1) {
	foreach ($parent_list as $pdata) {
		if ($pdata['cvo_id'] == $cvo_id) {
			$show_add = 0;
		}
	}
}

$tpl->assign("cvo_details", $cvo_details);
$tpl->assign("cvo_list", $cvo_list);
$tpl->assign("show_add", $show_add);
$tpl->assign("xsdmf_cvo_min_level", $xsdmf_cvo_min_level);
$tpl->assign("form", $form);
$tpl->assign("element", $element);

$tpl->displayTemplate();
?>
