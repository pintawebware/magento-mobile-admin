<?php

class Pinta_Mobileapi_Model_Observer extends Varien_Event_Observer
{

    public function __construct()
    {

    }

    public function sendNotifications($observer)
    {
        require_once("app/Mage.php");

        Mage::app();

        $order = $observer->getEvent()->getOrder();

        $orderno=$order->getIncrementId();

        $order_id = $order->getEntityId();


        $devices = $this->getUserDevices();
        $ids = [];

        foreach($devices->getData() as $device){
            if(strtolower($device['os_type']) == 'ios'){
                $ids['ios'][] = $device['token'];

            }else{
                $ids['android'][] = $device['token'];

            }
        }

        $order = $this->getOrderById($order_id);

        if(count($order)>0) {
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

            $msg_android = array(

                'new_order'  => [
                    'order_id'      => $order_id,
                    'total'         => number_format( $order['grand_total'], 2, '.', '' ),
                    'currency_code' => $order['base_currency_code'],
                    'site_url'      => "http://" . $_SERVER['HTTP_HOST'],
                ],
                'event_type' => 'new_order'
            );

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

                $this->sendCurl( $fields );

            endforeach;
        }
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
    }

    private function getUserDevices()
    {
        Mage::app();
        $allUserAuthorizedDevices = Mage::getModel('pintamobileapi/userdevicestoken')
            ->getCollection();

        return $allUserAuthorizedDevices;
    }

    private function getOrderById($id)
    {
        Mage::app();

        $orderCollection = Mage::getModel('sales/order')
            ->load($id);

        return $orderCollection->getData();
    }
}