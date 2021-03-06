<?php
/*
 * Fez 
 * Univeristy of Queensland Library
 * Created by Matthew Smith on 1/06/2007
 * This code is licensed under the GPL, see
 * http://www.gnu.org/copyleft/gpl.html
 * 
 */
 
//require_once('unit_test_setup.php');
 
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'RecordLockTests::main');
}
 
//require_once APP_PEAR_PATH.'PHPUnit/TextUI/TestRunner.php';
 
//require_once APP_TEST_PATH.'record_lock/RecordLockGetLockTest.php';
//require_once APP_TEST_PATH.'record_lock/RecordLockReleaseLockTest.php';
//require_once APP_TEST_PATH.'record_lock/RecordLockGetListTest.php';
//require_once APP_TEST_PATH.'record_lock/RecordLockGetOwnerTest.php';



class Unit_RecordLockTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
 
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('RecordLockTests Suite');
 
        $suite->addTestSuite('Unit_RecordLock_GetLockTest');
        $suite->addTestSuite('Unit_RecordLock_ReleaseLockTest');
        $suite->addTestSuite('Unit_RecordLock_GetOwnerTest');
        $suite->addTestSuite('Unit_RecordLock_GetListTest');
        
        // ...
 
        return $suite;
    }
}
 
if (PHPUnit_MAIN_METHOD == 'RecordLockTests::main') {
    RecordLockTests::main();
} 
 
?>
