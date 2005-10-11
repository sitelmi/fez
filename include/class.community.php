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

/**
 * Class to handle the business logic related to the administration
 * of communities in the system.
 *
 * @version 1.0
 * @author Christiaan Kortekaas <c.kortekaas@library.uq.edu.au>
 * @author Matthew Smith <m.smith@library.uq.edu.au>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.validation.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.record.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.fedora_api.php");
include_once(APP_INC_PATH . "class.status.php");

class Community
{
    /**
     * Method used to get the default community XDIS_ID
     *
     * Developer Note: Need to make this able to be set in Administrative interface and stored in the Fez database.
     *	 
     * @access  public
     * @return  integer $community_xdis_id The XSD Display ID of a Fez community
     */
    function getCommunityXDIS_ID()
    {
		// will make this more dynamic later. (probably feed from a mysql table which can be configured in the gui admin interface).
		$community_xdis_id = 11;
		return $community_xdis_id;
    }

    /**
     * Method used to get the basic details for a given community from the Fez Index.
     *
     * @access  public
     * @param   integer $community_pid The community persistent ID
     * @return  array $return The basic community details
     */
    function getDetails($community_pid)
    {
        $stmt = "SELECT
                    * 
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field r1,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_display_matchfields x1
                 WHERE
				    r1.rmf_xsdmf_id = x1.xsdmf_id and
                    rmf_rec_pid = '".$community_pid."'";
		$returnfields = array("title", "description", "ret_id", "xdis_id", "sta_id");
		$res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
		$return = array();
		foreach ($res as $result) {
			if (in_array($result['xsdmf_fez_title'], $returnfields)) {
				$return[$result['rmf_rec_pid']]['pid'] = $result['rmf_rec_pid'];
				$return[$result['rmf_rec_pid']][$result['xsdmf_fez_title']] = $result['rmf_'.$result['xsdmf_data_type']];
			}
		}
		$return = array_values($return);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $return;
        }
    }

    /**
     * Method used to get the list of communities available in the 
     * system.
     *
     * @access  public
     * @param   integer $current_row The point in the returned results to start from.
     * @param   integer $max The maximum number of records to return	 
     * @return  array The list of communities
     */
    function getList($current_row = 0, $max = 25)
    {
	
        $start = $current_row * $max;

        $stmt = "SELECT
            * 
            FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field r1
            inner join " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_display_matchfields x1 
            ON r1.rmf_xsdmf_id = x1.xsdmf_id  
            left join " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_loop_subelement s1 
            on (x1.xsdmf_xsdsel_id = s1.xsdsel_id)
            WHERE
            rmf_rec_pid in (
                    SELECT r2.rmf_rec_pid 
                    FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field r2,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_key s2,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_display_matchfields x2  						
                    WHERE s2.sek_title = 'Object Type' AND x2.xsdmf_id = r2.rmf_xsdmf_id
                    AND s2.sek_id = x2.xsdmf_sek_id AND r2.rmf_varchar = '1')		
            AND rmf_rec_pid IN (
                    SELECT rmf_rec_pid FROM 
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field AS rmf
                    INNER JOIN " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_display_matchfields AS xdm
                    ON rmf.rmf_xsdmf_id = xdm.xsdmf_id
                    WHERE rmf.rmf_varchar=2
                    AND xdm.xsdmf_element='!sta_id'
                    )
            ";
		$returnfields = array("title", "description", "ret_id", "xdis_id", "sta_id", "Editor", "Creator", "Lister", "Viewer", "Approver", "Community Administrator", "Annotator", "Comment_Viewer", "Commentor");
		$res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
		$return = array();
		foreach ($res as $result) {		
			if (in_array($result['xsdsel_title'], $returnfields) 
                    && ($result['xsdmf_element'] != '!rule!role!name') 
                    && is_numeric(strpos($result['xsdmf_element'], '!rule!role!')) ) {
				$return[$result['rmf_rec_pid']]['FezACML'][0][$result['xsdsel_title']][$result['xsdmf_element']][]
                   = $result['rmf_'.$result['xsdmf_data_type']]; // need to array_push because there can be multiple groups/users for a role
			}
			if (in_array($result['xsdmf_fez_title'], $returnfields)) {
				$return[$result['rmf_rec_pid']]['pid'] = $result['rmf_rec_pid'];
				$return[$result['rmf_rec_pid']][$result['xsdmf_fez_title']][]
                   =  $result['rmf_'.$result['xsdmf_data_type']];
				sort($return[$result['rmf_rec_pid']][$result['xsdmf_fez_title']]);
			}
		}
        $return = array_values($return);
		$return = Auth::getIndexAuthorisationGroups($return);
		$hidden_rows = count($return);
		$return = Misc::cleanListResults($return);
		$total_rows = count($return);
		if (($start + $max) < $total_rows) {
	        $total_rows_limit = $start + $max;
		} else {
		   $total_rows_limit = $total_rows;
		}
		$total_pages = ceil($total_rows / $max);
        $last_page = $total_pages - 1;
		$return = Misc::limitListResults($return, $start, ($start + $max));
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return array(
                "list" => $return,
                "info" => array(
                    "current_page"  => $current_row,
                    "start_offset"  => $start,
                    "end_offset"    => $total_rows_limit,
                    "total_rows"    => $total_rows,
                    "total_pages"   => $total_pages,
                    "previous_page" => ($current_row == 0) ? "-1" : ($current_row - 1),
                    "next_page"     => ($current_row == $last_page) ? "-1" : ($current_row + 1),
                    "last_page"     => $last_page,
                    "hidden_rows"     => $hidden_rows - $total_rows
                )
            );
        }
    }

    /**
     * Method used to get an associative array of community ID and title
     * of all communities available in the system.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The list of collections
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field r1,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "xsd_display_matchfields x1
                 WHERE
				    r1.rmf_xsdmf_id = x1.xsdmf_id and
                    rmf_rec_pid in (
						SELECT r2.rmf_rec_pid 
						FROM  " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "record_matching_field r2
						WHERE rmf_xsdmf_id = 239 AND rmf_varchar = '1')
					
					
					";
		$returnfields = array("title");
		$res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
		$return = array();
		foreach ($res as $result) {
			if (in_array($result['xsdmf_fez_title'], $returnfields)) {
				$return[$result['rmf_rec_pid']] = $result['rmf_'.$result['xsdmf_data_type']];
			}
		}
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $return;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Community Class');
}
?>
