<?php

include_once(APP_INC_PATH.'class.background_process.php');

class BackgroundProcessList 
{

    function getList($usr_id)
    {
        $usr_id = Misc::escapeString($usr_id);
        $dbtp = APP_DEFAULT_DB.'.'.APP_TABLE_PREFIX;
        $stmt = "SELECT bgp_id, bgp_status_message, bgp_progress, bgp_state, bgp_heartbeat,bgp_name,bgp_started
            FROM {$dbtp}background_process
            WHERE bgp_usr_id='$usr_id'
            ORDER BY bgp_started";
        $res = $GLOBALS['db_api']->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        return $res;
    }
}

?>
