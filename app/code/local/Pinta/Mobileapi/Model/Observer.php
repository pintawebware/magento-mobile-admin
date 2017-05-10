<?php
//require_once '../controllers/IndexController.php';
//use Pinta_Mobileapi_IndexController;

class Pinta_Mobileapi_Model_Observer extends Varien_Event_Observer
{

    public function __construct()
    {
//        echo "<p style='color:red'>Hello World .. </p>";
    }

    public function sendNotifications($observer)
    {
        require_once("app/Mage.php");

        Mage::app();
//        $order = $event->getOrder();

//        if ($observer->getEvent()->getControllerAction()->getFullActionName() == 'sales_order_place_after') {
//            //We are dispatching our own event before action ADD is run and sending parameters we need
//            Mage::dispatchEvent("sales_order_place_after", array('request' => $observer->getControllerAction()->getRequest()));
//
//            $orderIds = $observer->getData('order_id');
//            foreach ($observer->getData() as $item) {
//                $item->getQtyOrdered(); // Number of item ordered
//                Mage::log($item);
//                Mage::log($observer->getData('order_id'));
//                //$item->getQtyShipped()
//                //$item->getQtyInvoiced()
//
////           $optionArray = $item->getProductOptions();
////           // Todo : check to see if set and is array $optionArray['options']
////           foreach ($optionArray['options'] as $option) {
////                Mage::log($option);
////               //echo $option['label']
////               //$option['value']
////           }
//            }
//        }
//        return $observer;

//        $request = $observer->getEvent()->getRequest()->getParams();
//        Mage::log("Order ".$request['order_id']." will be created.");

        $order = $observer->getEvent()->getOrder();
//        Mage::log($order->getData());
//        $event = $observer->getEvent(); $invoice=$event->getInvoice(); $order =$invoice->getOrder();
        $orderno=$order->getIncrementId();
//        Mage::log(__METHOD__ . '() Hello'.$order->getEntityId().'!');
//        Mage::log($orderno);
//        Mage::log( '$order->getEntityId(): ' . $order->getEntityId());
        $order_id = $order->getEntityId();
//        Mage::log('$order_id: ' . $order_id);

        $devices = $this->getUserDevices();
        $ids = [];

        foreach($devices->getData() as $device){
            if(strtolower($device['os_type']) == 'ios'){
                $ids['ios'][] = $device['token'];
//                Mage::log('IOS user device: ' . $device['token']);
            }else{
                $ids['android'][] = $device['token'];
//                Mage::log('Android user device: ' . $device['token']);
            }
        }

        $order = $this->getOrderById($order_id);

//        var_export($order);
//        Mage::log('Before if($order)');
//        Mage::log('count($order): ' . count($order));
        if(count($order)>0) {
//            Mage::log('Before foreach ($order as $item)');
//            foreach ($order as $item) {
//                Mage::log('In start foreach ($order as $item)');

//            Mage::log('Grand order sum: ' . $order['grand_total']);
//            Mage::log('$_SERVER[HTTP_HOST]: ' . $_SERVER['HTTP_HOST']);
//            Mage::log('Base currency: ' . $order['base_currency_code']);
            $msg = array(
                'body'       => number_format( $order['grand_total'], 2, '.', '' ),
                'title'      => "http://" . $_SERVER['HTTP_HOST'],
                'vibrate'    => 1,
                'sound'      => 1,
                'badge'      => 1,
                'priority'   => 'high',
                'new_order'  => [
                    'order_id'      => $order_id,
                    'total'         => number_format( $order['grand_total'], 2, '.', '' ),
                    'currency_code' => $order['base_currency_code'],
                    'site_url'      => "http://" . $_SERVER['HTTP_HOST'],
                ],
                'event_type' => 'new_order'
            );
//                Mage::log('$msg is complete in foreach ($order as $item)');
            $msg_android = array(

                'new_order'  => [
                    'order_id'      => $order_id,
                    'total'         => number_format( $order['grand_total'], 2, '.', '' ),
                    'currency_code' => $order['base_currency_code'],
                    'site_url'      => "http://" . $_SERVER['HTTP_HOST'],
                ],
                'event_type' => 'new_order'
            );
//                Mage::log('$msg_android is complete in foreach ($order as $item)');
            foreach ( $ids as $k => $mas ):
                if ( $k == 'ios' ) {
                    $fields = array
                    (
                        'registration_ids' => $ids[$k],
                        'notification'     => $msg,
                    );
                } else {
                    $fields = array
                    (
                        'registration_ids' => $ids[$k],
                        'data'             => $msg_android
                    );
                }
//                Mage::log('Before called sendCurl');
                $this->sendCurl( $fields );
//                Mage::log('$fields was sended');
            endforeach;
//            }
//            Mage::log('After foreach ($order as $item)');
        }
//        Mage::log('after if($order)');
    }

    private function sendCurl($fields){
        $API_ACCESS_KEY = 'AAAAlhKCZ7w:APA91bFe6-ynbVuP4ll3XBkdjar_qlW5uSwkT5olDc02HlcsEzCyGCIfqxS9JMPj7QeKPxHXAtgjTY89Pv1vlu7sgtNSWzAFdStA22Ph5uRKIjSLs5z98Y-Z2TCBN3gl2RLPDURtcepk';
        $headers = array
        (
            'Authorization: key=' . $API_ACCESS_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_exec($ch);
        curl_close($ch);

//        Mage::log('sendCurl($fields) is worked');
    }

    private function getUserDevices()
    {
        Mage::app();
        $allUserAuthorizedDevices = Mage::getModel('pintamobileapi/userdevicestoken')
            ->getCollection();
//        Mage::log('User devices: ' . $allUserAuthorizedDevices);
//        Mage::log('getUserDevices() is worked');
        return $allUserAuthorizedDevices;
    }

    private function getOrderById($id)
    {
        Mage::app();
//        Mage::log('Order id in getOrderById(): ' . $id);
//        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $orderCollection = Mage::getModel('sales/order')
            ->load($id);

//        $orderCollection->getSelect()->join(
//            $tablesCollection->getTableName('sales_order_status'),
//            'main_table.status=' . $tablesCollection->getTableName('sales_order_status') . '.status',
//            array('*')
//        );
//        $checkShipppingCollection = Mage::getModel('sales/order_address')
//            ->getCollection()
//            ->addAttributeToFilter('parent_id', array('eq' => $id));
//        if (count($checkShipppingCollection->getData()) == 1) {
//            $orderCollection->getSelect()->join(
//                $tablesCollection->getTableName('sales_flat_order_address'),
//                'main_table.entity_id=' . $tablesCollection->getTableName('sales_flat_order_address') . '.parent_id',
//                array('telephone', 'address_type')
//            );
//        } else {
//            $orderCollection->getSelect()->join(
//                $tablesCollection->getTableName('sales_flat_order_address'),
//                'main_table.entity_id=' . $tablesCollection->getTableName('sales_flat_order_address') . '.parent_id',
//                array('telephone', 'address_type')
//            )->where("" . $tablesCollection->getTableName('sales_flat_order_address') . ".address_type = 'shipping'");
//        }
//        $orderCollection->addAttributeToFilter('entity_id', array('eq' => $id));

//        Mage::log('getOrderById($id) is workd');

        return $orderCollection->getData();
    }
}