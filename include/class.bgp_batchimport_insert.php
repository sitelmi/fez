<?php
include_once(APP_INC_PATH.'class.batchimport.php');
include_once(APP_INC_PATH.'class.background_process.php');

class BackgroundProcess_BatchImport_Insert extends BackgroundProcess
{
    function __construct() 
    {
        parent::__construct();
        $this->include = 'class.bgp_batchimport_insert.php';
        $this->name = 'Batch Import';
    }

    function run()
    {
        extract(unserialize($this->inputs));
        $batch = new BatchImport;
        $batch->setBackgroundObject($this);
        $batch->insert($objectimport, $directory, $xdis_id, $pid, $wftpl);
    }
}

?>
