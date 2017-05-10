<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 03.04.17
 * Time: 16:28
 */
phpinfo();
// v2 call
//$client = new SoapClient('http://magento.pixy.pro/api/v2_soap/?wsdl=1');
//$session = $client->login('UserNameDemoSOAP', 'ApiKeyDemoSOAP');
//$result = $client->customapimoduleProductList($session);
//$client->endSession($session);
//echo '<pre>';
//print_r(json_encode($result));


// v1 call
//$client = new SoapClient('http://magento.pixy.pro/api/soap/?wsdl=1');
//$session = $client->login('UserNameDemoSOAP', 'ApiKeyDemoSOAP');
//$result = $client->call($session, 'product.list', array(array()));
//$client->endSession($session);
//echo '<pre>';
//print_r(json_encode($result));

// Make the API-call
// Magento login information
//$mage_url = 'http://magento.pixy.pro/api/soap/?wsdl';
//$mage_user = 'UserNameDemoSOAP';
//$mage_api_key = 'ApiKeyDemoSOAP';
//// Initialize the SOAP client
//$soap = new SoapClient($mage_url);
//// Login to Magento
//$session_id = $soap->login($mage_user, $mage_api_key);
//$resources = $soap->resources($session_id);
//?>
<?php //if (is_array($resources) && !empty($resources)) { ?>
<!--    --><?php //foreach ($resources as $resource) { ?>
<!--        <h1>--><?php //echo $resource['title']; ?><!--</h1>-->
<!--        Name: --><?php //echo $resource['name']; ?><!--<br/>-->
<!--        Aliases: --><?php //echo implode(',', $resource['aliases']); ?>
<!--        <table>-->
<!--            <tr>-->
<!--                <th>Title</th>-->
<!--                <th>Path</th>-->
<!--                <th>Name</th>-->
<!--            </tr>-->
<!--            <style>-->
<!--                td {-->
<!--                    margin: 0 10px;-->
<!--                    border: 1px solid black;-->
<!--                }-->
<!--            </style>-->
<!--            --><?php //foreach ($resource['methods'] as $method) { ?>
<!--                <tr>-->
<!--                    <td>--><?php //echo $method['title']; ?><!--</td>-->
<!--                    <td>--><?php //echo $method['path']; ?><!--</td>-->
<!--                    <td>--><?php //echo $method['name']; ?><!--</td>-->
<!--                    <td>--><?php //echo implode(',', $method['aliases']); ?><!--</td>-->
<!--                </tr>-->
<!--            --><?php //} ?>
<!--        </table>-->
<!--    --><?php //} ?>
<?php //} ?>

<?php
//$email = $_GET['email'];
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
die;
?>
