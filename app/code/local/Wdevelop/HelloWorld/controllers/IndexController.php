<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 03.04.17
 * Time: 12:50
 */
/**
*class {ModuleNamespace}_{ModuleName}_{Controllername}Controller
*extends Mage_Core_Controller_Front_Action
*/
class Wdevelop_HelloWorld_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
//        echo "Hello World";
//        echo phpinfo();

        $admin_name = $_GET['admin_name'];
        $password = $_GET['password'];
//$admin_name = 'magento';
//$password = 'PMNcaoJg3gnh';

        function customLoginUser($admin_name, $password)
        {
//    echo $admin_name;
//    echo $password;
            require_once("app/Mage.php");

            Mage::app();
            $adminUser = Mage::getModel('admin/user');
            if ($adminUser->authenticate($admin_name, $password)) {
                echo var_export($adminUser);
                echo $token = md5(mt_rand());
            } else {
                echo 'Failure';
            }
//    umask(0);
        }

        $test = customLoginUser($admin_name, $password);
//echo "test";
        echo $test;
    }
}