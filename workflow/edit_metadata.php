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
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.group.php");
include_once(APP_INC_PATH . "class.author.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.controlled_vocab.php");
include_once(APP_INC_PATH . "class.collection.php");
include_once(APP_INC_PATH . "class.community.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.doc_type_xsd.php");
include_once(APP_INC_PATH . "class.fedora_api.php");
include_once(APP_INC_PATH . "class.xsd_relationship.php");
include_once(APP_INC_PATH . "class.xsd_html_match.php");
include_once(APP_INC_PATH . "class.workflow_trigger.php");
include_once(APP_INC_PATH . "class.workflow.php");
include_once(APP_INC_PATH . "class.org_structure.php");
include_once(APP_INC_PATH . "najax/najax.php");
include_once(APP_INC_PATH . "najax_objects/class.select_org_structure.php");
include_once(APP_INC_PATH . "najax_objects/class.suggestor.php");
NAJAX_Server::allowClasses(array('SelectOrgStructure', 'Suggestor'));
if (NAJAX_Server::runServer()) {
	exit;
}
$tpl = new Template_API();
$tpl->setTemplate("workflow/index.tpl.html");
$tpl->assign("type", "edit_metadata");
Auth::checkAuthentication(APP_SESSION, $HTTP_SERVER_VARS['PHP_SELF']."?".$HTTP_SERVER_VARS['QUERY_STRING']);
$username = Auth::getUsername();
$tpl->assign("isUser", $username);
$isAdministrator = User::isUserAdministrator($username);
if (Auth::userExists($username)) { // if the user is registered as a Fez user
	$tpl->assign("isFezUser", $username);
}
$tpl->assign("isAdministrator", $isAdministrator);

$wfstatus = &WorkflowStatusStatic::getSession(); // restores WorkflowStatus object from the session
$pid = $wfstatus->pid;
$wfstatus->setTemplateVars($tpl);
$tpl->assign("submit_to_popup", true);
$wfstatus->checkStateChange();
 
$collection_pid=$pid;
$community_pid=$pid;
$tpl->assign("collection_pid", $pid);
$tpl->assign("community_pid", $pid);
$debug = @$_REQUEST['debug'];
if ($debug == 1) {
	$tpl->assign("debug", "1");
} else {
	$tpl->assign("debug", "0");	
}
$community_list = Community::getAssocList();
$collection_list = Collection::getEditListAssoc();
/* $internal_user_list = User::getAssocList();
$internal_group_list = Group::getAssocListAll(); */
$extra_redirect = "";
if (!empty($collection_pid)) {
	$extra_redirect.="&collection_pid=".$pid;
}
if (!empty($community_pid)) {
	$extra_redirect.="&community_pid=".$pid;
}
$parents = Record::getParentsAll($pid);
$tpl->assign("parents", $parents);
$tpl->assign("pid", $pid);
$record = new RecordObject($pid);
$record->getDisplay();
$xdis_id = $record->getXmlDisplayId();
$xdis_title = XSD_Display::getTitle($xdis_id);
//$author_list = Author::getAssocListAll();
$tpl->assign("xdis_title", $xdis_title);
$tpl->assign("extra_title", "Edit ".$xdis_title);
$xdis_collection_list = XSD_Display::getAssocListCollectionDocTypes(); // @@@ CK - 13/1/06 added for communities to be able to select their collection child document types/xdisplays
$xdis_list = XSD_Display::getAssocListDocTypes(); // @@@ CK - 24/8/05 added for collections to be able to select their child document types/xdisplays
$strict = false;
/*$workflows = $record->getWorkflowsByTriggerAndXDIS_ID('Update', $xdis_id, $strict);
$workflows1 = array();
if (is_array($workflows)) {
	foreach ($workflows as $trigger) {
		if (Workflow::canTrigger($trigger['wft_wfl_id'], $pid)) {
			$workflows1[] = $trigger;
		}
	}
	$workflows = $workflows1;
} 
*/

$access_ok = $record->canEdit();
if ($access_ok) {

    if (!is_numeric($xdis_id)) {
        $xdis_id = @$HTTP_POST_VARS["xdis_id"] ? $HTTP_POST_VARS["xdis_id"] : $HTTP_GET_VARS["xdis_id"];	
        if (is_numeric($xdis_id)) { // must have come from select xdis so save xdis in the FezMD
            Record::updateAdminDatastream($pid, $xdis_id);
        }
    }

    if (!is_numeric($xdis_id)) { // if still can't find the xdisplay id then ask for it
        Auth::redirect(APP_RELATIVE_URL . "select_xdis.php?return=update_form&pid=".$pid.$extra_redirect, false);
    }

    $sta_id = $record->getPublishedStatus();
    if (!$sta_id) {
        $sta_id = 1;
    }
    $tpl->assign('sta_id', $sta_id); 

    $jtaskData = "";
    $maxG = 0;
    $xsd_display_fields = $record->display->getMatchFieldsList(array("FezACML"), array(""));  // XSD_DisplayObject
    $parent_relationships = array();
	$datastream_isMemberOf = array(0 => $pid);
    foreach ($parents as $parent) {
		array_push($datastream_isMemberOf, $parent['pid']);
        $parent_record = new RecordObject($parent['pid']);
        $parent_xdis_id = $parent_record->getXmlDisplayId();
        $parent_relationship = XSD_Relationship::getColListByXDIS($parent_xdis_id);
        array_push($parent_relationship, $parent_xdis_id);
        $parent_relationships = Misc::array_merge_values($parent_relationships, $parent_relationship);
    }
    //print_r($xsd_display_fields);
    $author_ids = Author::getAssocListAllBasic();

    $tpl->assign("author_ids", $author_ids);
    //@@@ CK - 26/4/2005 - fix the combo and multiple input box lookups - should probably move this into a function somewhere later
    foreach ($xsd_display_fields  as $dis_key => $dis_field) {
        if ($dis_field["xsdmf_enabled"] == 1) {
			if ($dis_field["xsdmf_html_input"] == 'org_selector') {
				if ($dis_field["xsdmf_org_level"] != "") {
					$xsd_display_fields[$dis_key]['field_options'] = Org_Structure::getAssocListByLevel($dis_field["xsdmf_org_level"]);
				}
			}
			if ($dis_field["xsdmf_html_input"] == 'author_selector') {
				if ($dis_field["xsdmf_use_parent_option_list"] == 1) {
					// Loop through the parents - there is only one parent for entering metadata
					if (in_array($dis_field["xsdmf_parent_option_xdis_id"], $parent_relationships)) {
						foreach ($parents as $parent) {
							$parent_record = new RecordObject($parent['pid']);
							$parent_details = $parent_record->getDetails();
							if (is_numeric(@$parent_details[$dis_field["xsdmf_parent_option_child_xsdmf_id"]])) {
								$authors_sub_list = Org_Structure::getAuthorsByOrgID($parent_details[$dis_field["xsdmf_parent_option_child_xsdmf_id"]]);
								$xsd_display_fields[$dis_key]['field_options'] = $authors_sub_list;
							}
						}
					}
				}
			}
			if ($dis_field["xsdmf_html_input"] == 'author_suggestor') {
	
				foreach ($xsd_display_fields as $dis_key2 => $dis_field2) {
					if ($dis_field2['xsdmf_id'] == $dis_field['xsdmf_asuggest_xsdmf_id']) {
						$suggestor_count = $dis_field2['xsdmf_multiple_limit'];
					}
				}
				
				if (!is_numeric($suggestor_count)) {
					$suggestor_count = 1;
				}
				for ($x=1;$x<=$suggestor_count;$x++) {
				 $tpl->headerscript .= "window.oTextbox_xsd_display_fields_{$dis_field['xsdmf_id']}_".$x."_lookup
						= new AutoSuggestControl(document.wfl_form1, 'xsd_display_fields_{$dis_field['xsdmf_id']}_".$x."', document.getElementById('xsd_display_fields_{$dis_field['xsdmf_asuggest_xsdmf_id']}_".$x."'), document.getElementById('xsd_display_fields_{$dis_field['xsdmf_id']}_".$x."_lookup'),
								new StateSuggestions('Author',false,
									'class.author.php'));
						";  			
				}
			}		
			if ($dis_field["xsdmf_html_input"] == 'combo' || $dis_field["xsdmf_html_input"] == 'multiple') {
				if (!empty($dis_field["xsdmf_smarty_variable"]) && $dis_field["xsdmf_smarty_variable"] != "none") {
					eval("\$xsd_display_fields[\$dis_key]['field_options'] = " . $dis_field["xsdmf_smarty_variable"] . ";");
				}
				if (!empty($dis_field["xsdmf_dynamic_selected_option"]) && $dis_field["xsdmf_dynamic_selected_option"] != "none") {
					eval("\$xsd_display_fields[\$dis_key]['selected_option'] = " . $dis_field["xsdmf_dynamic_selected_option"] . ";");
				}
	
				if ($dis_field["xsdmf_use_parent_option_list"] == 1) { // if the display field inherits this list from a parent then get those options
					// Loop through the parents
					if (in_array($dis_field["xsdmf_parent_option_xdis_id"], $parent_relationships)) {
						$parent_details = $parent_record->getDetails(); // this only works for one parent for now.. need to loop over them again
						if (is_array($parent_details[$dis_field["xsdmf_parent_option_child_xsdmf_id"]])) {
							$xsdmf_details = XSD_HTML_Match::getDetailsByXSDMF_ID($dis_field["xsdmf_parent_option_child_xsdmf_id"]);
							if ($xsdmf_details['xsdmf_smarty_variable'] != "" && $xsdmf_details['xsdmf_html_input'] == "multiple") {
								$temp_parent_options = array();
								$temp_parent_options_final = array();
								eval("\$temp_parent_options = ". $xsdmf_details['xsdmf_smarty_variable'].";");
								$xsd_display_fields[$dis_key]['field_options'] = array();
								foreach ($parent_details[$dis_field["xsdmf_parent_option_child_xsdmf_id"]] as $parent_smarty_option) {
									if (array_key_exists($parent_smarty_option, $temp_parent_options)) {
										$xsd_display_fields[$dis_key]['field_options'][$parent_smarty_option] = $temp_parent_options[$parent_smarty_option];
									}
								}
							} else {
								$xsd_display_fields[$dis_key]['field_options'] = array();
								foreach ($parent_details[$dis_field["xsdmf_parent_option_child_xsdmf_id"]] as $parent_detail_text) {
									$xsd_display_fields[$dis_key]['field_options'][$parent_detail_text] = $parent_detail_text;
								}
							}
						}
					}
				}	
			}	
			if (($dis_field["xsdmf_html_input"] == 'contvocab')) {
				$xsd_display_fields[$dis_key]['field_options'] = $cvo_list['data'][$dis_field['xsdmf_cvo_id']];
			}

			
		}
    }

    $tpl->assign("xsd_display_fields", $xsd_display_fields);

    $tpl->assign("xdis_id", $xdis_id);

    $details = $record->getDetails();
//    print_r($parents);
//    $controlled_vocabs = Controlled_Vocab::getAssocListAll();
    //@@@ CK - 26/4/2005 - fix the combo and multiple input box lookups - should probably move this into a function somewhere later
    foreach ($xsd_display_fields  as $dis_field) {
		if ($dis_field["xsdmf_enabled"] == 1) {
			if ($dis_field["xsdmf_html_input"] == 'combo' || $dis_field["xsdmf_html_input"] == 'multiple' || $dis_field["xsdmf_html_input"] == 'contvocab' || $dis_field["xsdmf_html_input"] == 'contvocab_selector') {
				if (@$details[$dis_field["xsdmf_id"]]) { // if a record detail matches a display field xsdmf entry
					if (($dis_field["xsdmf_html_input"] == 'contvocab_selector') && ($dis_field['xsdmf_cvo_save_type'] != 1)) {			
						$tempArray = $details[$dis_field["xsdmf_id"]];
						if (is_array($tempArray)) {
							$details[$dis_field["xsdmf_id"]] = array();
							foreach ($tempArray as $cv_key => $cv_value) {
								$details[$dis_field["xsdmf_id"]][$cv_value] = Controlled_Vocab::getTitle($cv_value);
							}
						} else {
							$tempValue = $details[$dis_field["xsdmf_id"]];
							$details[$dis_field["xsdmf_id"]] = array();
							$details[$dis_field["xsdmf_id"]][$tempValue] = Controlled_Vocab::getTitle($tempValue);
						}
						

					} elseif (is_array($dis_field["field_options"])) { // if the display field has a list of matching options
						foreach ($dis_field["field_options"] as $field_key => $field_option) { // for all the matching options match the set the details array the template uses
							if (is_array($details[$dis_field["xsdmf_id"]])) { // if there are multiple selected options (it will be an array)
								foreach ($details[$dis_field["xsdmf_id"]] as $detail_key => $detail_value) {
									if ($field_option == $detail_value) {
										$details[$dis_field["xsdmf_id"]][$detail_key] = $field_key;
									}
								}					
							} else {
								if ($field_option == $details[$dis_field["xsdmf_id"]]) {
									$details[$dis_field["xsdmf_id"]] = $field_key;
								}
							}
						}
					}
				}
//			} elseif ($dis_field["xsdmf_html_input"] == 'author_selector') { // fix author id drop down combo if attached

			} elseif ($dis_field['xsdmf_html_input'] == "xsdmf_id_ref") {
				$xsdmf_details_ref = XSD_HTML_Match::getDetailsByXSDMF_ID($dis_field['xsdmf_id_ref']);
				$xsdmf_id_ref = $xsdmf_details_ref['xsdmf_id'];
				if (($xsdmf_details_ref['xsdmf_html_input'] == 'contvocab') || ($xsdmf_details_ref['xsdmf_html_input'] == 'contvocab_selector')) {
					if (!empty($details[$dis_field['xsdmf_id_ref']])) {
						$details[$xsdmf_id_ref] = array(); //clear the existing data
						if (is_array($details[$dis_field['xsdmf_id']])) {
							foreach ($details[$dis_field['xsdmf_id']] as $ckey => $cdata) {
								$details[$xsdmf_id_ref][$cdata] = Controlled_Vocab::getTitle($cdata);
	//										$details[$xsdmf_id_ref][$ckey] = "<a class='silent_link' href='".APP_BASE_URL."list.php?browse=subject&parent_id=".$cdata."'>".$controlled_vocabs[$cdata]."</a>";
							}
						} else {
							$details[$xsdmf_id_ref][$details[$dis_field['xsdmf_id']]] = Controlled_Vocab::getTitle($details[$dis_field['xsdmf_id']]);
						}
					}				
				}					

			
			} elseif (($dis_field["xsdmf_multiple"] == 1) && (!@is_array($details[$dis_field["xsdmf_id"]])) ){ // makes the 'is_multiple' tagged display fields into arrays if they are not already so smarty renders them correctly
				$tmp_value = @$details[$dis_field["xsdmf_id"]];
				$details[$dis_field["xsdmf_id"]] = array();
				array_push($details[$dis_field["xsdmf_id"]], $tmp_value);
			}
		}
    }

    $securityfields = Auth::getAllRoles();
    $datastreams = Fedora_API::callGetDatastreams($pid);
    $datastreams = Misc::cleanDatastreamList($datastreams);

    $datastream_workflows = WorkflowTrigger::getListByTrigger('-1', 5); //5 is for datastreams

    foreach ($datastreams as $ds_key => $ds) {
		if ($datastreams[$ds_key]['controlGroup'] == 'R' && $datastreams[$ds_key]['ID'] != 'DOI') {
			$datastreams[$ds_key]['location'] = trim($datastreams[$ds_key]['location']);
			// Check for APP_LINK_PREFIX and add if not already there
			if (APP_LINK_PREFIX != "") {
				if (!is_numeric(strpos($datastreams[$ds_key]['location'], APP_LINK_PREFIX))) {
					$datastreams[$ds_key]['location'] = APP_LINK_PREFIX.$datastreams[$ds_key]['location'];
				}
			}
		} elseif ($datastreams[$ds_key]['controlGroup'] == 'M') {
            $FezACML_DS = array();
            $FezACML_DS = Record::getIndexDatastream($pid, $ds['ID'], 'FezACML');
            $return = array();
            foreach ($FezACML_DS as $result) {
                if (in_array($result['xsdsel_title'], $securityfields)  && ($result['xsdmf_element'] != '!rule!role!name') && is_numeric(strpos($result['xsdmf_element'], '!rule!role!')) )  {
                    if (!is_array($return[$result['rmf_rec_pid']]['FezACML'][0][$result['xsdsel_title']][$result['xsdmf_element']])) {
                        $return[$result['rmf_rec_pid']]['FezACML'][0][$result['xsdsel_title']][$result['xsdmf_element']] = array();
                    }
                    if (!in_array($result['rmf_'.$result['xsdmf_data_type']], $return[$result['rmf_rec_pid']]['FezACML'][0][$result['xsdsel_title']][$result['xsdmf_element']])) {
                        array_push($return[$result['rmf_rec_pid']]['FezACML'][0][$result['xsdsel_title']][$result['xsdmf_element']], $result['rmf_'.$result['xsdmf_data_type']]); // need to array_push because there can be multiple groups/users for a role
                    }
                }
                if ($result['xsdmf_element'] == '!inherit_security') {
                    if (!is_array($return[$result['rmf_rec_pid']]['FezACML'][0]['!inherit_security'])) {
                        $return[$result['rmf_rec_pid']]['FezACML'][0]['!inherit_security'] = array();
                    }
                    if (!in_array($result['rmf_'.$result['xsdmf_data_type']], $return[$result['rmf_rec_pid']]['FezACML'][0]['!inherit_security'])) {
                        array_push($return[$result['rmf_rec_pid']]['FezACML'][0]['!inherit_security'], $result['rmf_'.$result['xsdmf_data_type']]);
                    }
                }
            }

            $datastreams[$ds_key]['FezACML'] = @$return[$pid]['FezACML'];
            $datastreams[$ds_key]['workflows'] = $datastream_workflows;
            $parentsACMLs = array();
            if (count($FezACML_DS) == 0 || $datastreams[$ds_key]['FezACML'][0]['!inherit_security'][0] == "on") {
                // if there is no FezACML set for this row yet, then is it will inherit from above, so show this for the form
                if ($datastreams[$ds_key]['FezACML'][0]['!inherit_security'][0] == "on") {
                    $datastreams[$ds_key]['security'] = "include";
                    $parentsACMLs = $datastreams[$ds_key]['FezACML'];
                } else {
                    $datastreams[$ds_key]['security'] = "inherit";
                    $parentsACMLs = array();
                } 
                $parents = Record::getParents($pid);
                Auth::getIndexParentACMLMemberList(&$parentsACMLs, $pid, $datastream_isMemberOf);
//				print_r($parentsACMLs);
                $datastreams[$ds_key]['FezACML'] = $parentsACMLs;			
            } else {
                $datastreams[$ds_key]['security'] = "exclude";			
            }
        }
    } 

    $datastreams = Auth::getIndexAuthorisationGroups($datastreams);
    $parents = $record->getParents(); // RecordObject
    $tpl->assign("parents", $parents);
    $title = $record->getTitle(); // RecordObject
    $tpl->assign("title", $title);
    if ($record->isCollection()) {
        $tpl->assign('record_type', 'Collection');
        $tpl->assign('parent_type', 'Community');
        $tpl->assign('view_href', APP_RELATIVE_URL."list.php?collection_pid=$pid");
    } elseif ($record->isCommunity()) {
        $tpl->assign('record_type', 'Community');
        $tpl->assign('view_href', APP_RELATIVE_URL."list.php?community_pid=$pid");
    } else {
        $tpl->assign('record_type', 'Record');
        $tpl->assign('parent_type', 'Collection');
        $tpl->assign('view_href', APP_RELATIVE_URL."view.php?pid=$pid");
    }
//	print_r($details);
//    print_r($datastreams);
    $tpl->assign("datastreams", $datastreams);
    $tpl->assign("fez_root_dir", APP_PATH);
    $tpl->assign("eserv_url", APP_BASE_URL."eserv.php?pid=".$pid."&dsID=");
    $tpl->assign("local_eserv_url", APP_RELATIVE_URL."eserv.php?pid=".$pid."&dsID=");
    $tpl->assign('triggers', count(WorkflowTrigger::getList($pid)));
    $tpl->assign("ds_get_path", APP_FEDORA_GET_URL."/".$pid."/");
    $tpl->assign("isEditor", 1);
	$tpl->assign("details", $details);
    $setup = Setup::load();

    // if user is a fez user then get prefs
    if (Auth::userExists($username)) {
        $prefs = Prefs::get(Auth::getUserID());
    }
    $tpl->assign("user_prefs", $prefs);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->assign('najax_header', NAJAX_Utilities::header(APP_RELATIVE_URL.'include/najax'));
$tpl->assign('najax_register', NAJAX_Client::register('SelectOrgStructure', 'edit_metadata.php')."\n".NAJAX_Client::register('Suggestor', 'edit_metadata.php'));
$tpl->displayTemplate();

?>
