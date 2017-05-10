<?php


class Pinta_Mobileapi_IndexController extends Mage_Core_Controller_Front_Action
{

    private $API_VERSION = 1.0;

    /**
     * @api {post} /loginUser  Login
     * @apiVersion 0.1.0
     * @apiName Login
     * @apiGroup Login
     *
     * @apiParam {String} username User unique username.
     * @apiParam {Number} password User's  password.
     * @apiParam {String} os_type User's device's os_type for firebase notifications.
     * @apiParam {String} device_token User's device's token for firebase notifications.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {String} token  Token.
     * @apiSuccess {String} token  Token.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "token": "e9cf23a55429aa79c3c1651fe698ed7b",
     *          "version": 1.0,
     *          "status": true
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Incorrect username or password",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function loginUserAction()
    {
        $params = $this->getRequest()->getParams();
        require_once("app/Mage.php");

        Mage::app();
        $adminUser = Mage::getModel('admin/user');
        $adminUser->authenticate($params['username'], $params['password']);
        $adminUserIdFromDB = $adminUser->getId();

        if (!isset($params['username']) || !isset($params['password']) || !isset($adminUserIdFromDB)) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Incorrect username or password', 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($params['device_token']) && $params['device_token'] != '' && isset($params['os_type']) && $params['os_type'] != '') {
            $devices = $this->getUserDevices();
            $matches = 0;
            foreach ($devices as $device) {
                if ($params['device_token'] == $device->getToken()) {
                    $matches++;
                }
            }
            if ($matches == 0) {
                $this->setUserDeviceToken($adminUserIdFromDB, $params['device_token'], $params['os_type']);
            }

        }

        $token = $this->getUserToken($adminUserIdFromDB);
        if (!isset($token)) {
            $token = md5(mt_rand());
            $this->setUserToken($adminUserIdFromDB, $token);
        }

        $token = $this->getUserToken($adminUserIdFromDB);

        $answer_json = json_encode(['version' => $this->API_VERSION, 'response' => ['token' => $token], 'status' => true]);

        return $this->answer($answer_json);
    }

    /**
     * @api {get} /getStatistic  getDashboardStatistic
     * @apiVersion 0.1.0
     * @apiName getDashboardStatistic
     * @apiGroup Get dashboard statistics
     *
     * @apiParam {String} filter Period for filter(day/week/month/year).
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Array} xAxis Period of the selected filter.
     * @apiSuccess {Array} Clients Clients for the selected period.
     * @apiSuccess {Array} Orders Orders for the selected period.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} total_sales  Sum of sales of the shop.
     * @apiSuccess {Number} sale_year_total  Sum of sales of the current year.
     * @apiSuccess {Number} orders_total  Total orders of the shop.
     * @apiSuccess {Number} clients_total  Total clients of the shop.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *           "response": {
     *               "xAxis": [
     *                  1,
     *                  2,
     *                  3,
     *                  4,
     *                  5,
     *                  6,
     *                  7
     *              ],
     *              "clients": [
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0
     *              ],
     *              "orders": [
     *                  1,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0
     *              ],
     *              "total_sales": "1920.00",
     *              "sale_year_total": "305.00",
     *              "currency_code": "UAH",
     *              "orders_total": "4",
     *              "clients_total": "3"
     *           },
     *           "status": true,
     *           "version": 1.0
     *  }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Unknown filter set",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function getStatisticAction()
    {
        require_once("app/Mage.php");
        $params = $this->getRequest()->getParams();
        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['filter']) && $_REQUEST['filter'] != '') {
            $clients = $this->getTotalCustomers(array('filter' => $_REQUEST['filter']));
            $orders = $this->getTotalOrders(array('filter' => $_REQUEST['filter']));
            if ($clients === false || $orders === false) {

                $answer_json = json_encode(['error' => 'Unknown filter set', 'status' => false]);

                return $this->answer($answer_json);

            } else {
                $clients_for_time = [];
                $orders_for_time = [];
                if ($_REQUEST['filter'] == 'month') {
                    $hours = range(1, 30);
                    for ($i = 1; $i <= 30; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $day = strtotime($value['created_at']);
                            $day = date("d", $day);

                            if ($day == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $value) {

                            $day = strtotime($value['created_at']);
                            $day = date("d", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                } elseif ($_REQUEST['filter'] == 'day') {
                    $hours = range(0, 23);

                    for ($i = 0; $i <= 23; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $hour = strtotime($value['created_at']);
                            $hour = date("h", $hour);

                            if ($hour == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $value) {

                            $day = strtotime($value['created_at']);
                            $day = date("h", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                } elseif ($_REQUEST['filter'] == 'week') {
                    $hours = range(1, 7);

                    for ($i = 1; $i <= 7; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $date = strtotime($value['created_at']);

                            $f = date("N", $date);

                            if ($f == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $val) {

                            $day = strtotime($val['created_at']);
                            $day = date("N", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                } elseif ($_REQUEST['filter'] == 'year') {
                    $hours = range(1, 12);

                    for ($i = 1; $i <= 12; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $date = strtotime($value['created_at']);

                            $f = date("m", $date);

                            if ($f == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $val) {

                            $day = strtotime($val['created_at']);
                            $day = date("m", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                }
                $data['xAxis'] = $hours;
                $data['clients'] = $clients_for_time;
                $data['orders'] = $orders_for_time;
            }

            $sale_total = $this->getTotalSales();
            $data['total_sales'] = number_format($sale_total, 2, '.', '');
            //            var_export($sale_total);
            $sale_year_total = $this->getTotalSales(array('this_year' => true));
            $data['sale_year_total'] = number_format($sale_year_total, 2, '.', '');

            $orders_total = $this->getTotalOrders();
            $data['orders_total'] = $orders_total[0]['COUNT(*)'];
            $clients_total = $this->getTotalCustomers();
            $data['clients_total'] = $clients_total[0]['COUNT(*)'];
            $data['currency_code'] = $this->getDefaultCurrency();

            return $this->answer(json_encode([
                'version' => $this->API_VERSION,
                'response' => $data,
                'status' => true
            ]));
        } else {

            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);

            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /getOrders  getOrders
     * @apiVersion 0.1.0
     * @apiName GetOrders
     * @apiGroup Get orders info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page=0 number of the page.
     * @apiParam {Number} limit=9999 limit of the orders for the page.
     * @apiParam {String} [fio] full name of the client.
     * @apiParam {String} [order_status_id] unique id of the order.
     * @apiParam {Number} [min_price=1] min price of order.
     * @apiParam {Number} [max_price='max order price'] max price of order.
     * @apiParam {Date} [date_min] min date adding of the order.
     * @apiParam {Date} [date_max] max date adding of the order.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Array} orders  Array of the orders.
     * @apiSuccess {Array} statuses  Array of the order statuses.
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {String} order[currency_code] currency of the order.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     * @apiSuccess {Date} total_quantity  Total quantity of the orders.
     *
     *
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *      "orders":
     *      {
     *            {
     *             "order_id" : "1",
     *             "order_number" : "1",
     *             "fio" : "Anton Kiselev",
     *             "status" : "Complete",
     *             "total" : "106.00",
     *             "date_added" : "2016-12-09 16:17:02",
     *             "currency_code": "RUB"
     *             },
     *            {
     *             "order_id" : "2",
     *             "order_number" : "2",
     *             "fio" : "Vlad Kochergin",
     *             "status" : "Pending",
     *             "total" : "506.00",
     *             "date_added" : "2016-10-19 16:00:00",
     *             "currency_code": "RUB"
     *             }
     *       },
     *       "statuses" :
     *       {
     *             {
     *              "name": "Canceled",
     *              "order_status_id": "canceled"
     *              },
     *             {
     *              "name": "Complete",
     *              "order_status_id": "complete"
     *              },
     *              {
     *               "name": "Pending",
     *               "order_status_id": "pending"
     *               }
     *       },
     *       "currency_code": "RUB",
     *       "total_quantity": 50,
     *       "total_sum": "2026.00",
     *       "max_price": "1405.00"
     *   },
     *   "Status" : true,
     *   "version": 0.1.0
     * }
     * @apiErrorExample Error-Response:
     *
     * {
     *      "version": 0.1.0,
     *      "Status" : false
     *
     * }
     *
     *
     */

    public function getOrdersAction()
    {
        require_once("app/Mage.php");
        Mage::app();
        //        $params = $this->getRequest()->getParams();
        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] != 0) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 9999;
        }

        if (isset($_REQUEST['order_status_id']) || isset($_REQUEST['fio']) || isset($_REQUEST['min_price']) || isset($_REQUEST['max_price']) || isset($_REQUEST['date_min']) || isset($_REQUEST['date_max'])) {
            $filter = [];
            if (isset($_REQUEST['order_status_id']) && $_REQUEST['order_status_id'] !== '') {
                $filter['order_status_id'] = $_REQUEST['order_status_id'];
            }
            if (isset($_REQUEST['fio']) && $_REQUEST['fio'] !== '') {
                $filter['fio'] = $_REQUEST['fio'];
            }
            if (isset($_REQUEST['min_price']) && $_REQUEST['min_price'] !== 0 && $_REQUEST['min_price'] !== '') {
                $filter['min_price'] = $_REQUEST['min_price'];
            } else {
                $filter['min_price'] = 1;
            }
            if (isset($_REQUEST['max_price']) && isset($_REQUEST['max_price']) !== '') {
                $filter['max_price'] = $_REQUEST['max_price'];
            } else {
                $filter['max_price'] = $this->getMaxOrderPrice();
            }

            $filter['date_min'] = $_REQUEST['date_min'];
            $filter['date_max'] = $_REQUEST['date_max'];

            $orders = $this->getOrders(array('filter' => $filter, 'page' => $page, 'limit' => $limit));
        } else {
            $orders = $this->getOrders(array('page' => $page, 'limit' => $limit));
        }
        $response = [];
        $orders_to_response = [];

        foreach ($orders as $order) {
            if ($order['entity_id'] !== null) {
                $data['order_number'] = $order['entity_id'];
                $data['order_id'] = $order['entity_id'];
                $data['fio'] = $order['customer_firstname'] . ' ' . $order['customer_lastname'];
                $data['status'] = $order['label'];
                $data['total'] = number_format($order['grand_total'], 2, '.', '');
                $data['date_added'] = $order['created_at'];
                $data['currency_code'] = $order['order_currency_code'];
                $orders_to_response[] = $data;
            }
        }

        $response['total_quantity'] = $orders['quantity'];
        $response['currency_code'] = $this->getDefaultCurrency();
        $response['total_sum'] = number_format($orders['totalsumm'], 2, '.', '');
        $response['orders'] = $orders_to_response;
        $response['max_price'] = $this->getMaxOrderPrice();
        $statuses = $this->OrderStatusList();
        $response['statuses'] = $statuses;
        $response['api_version'] = $this->API_VERSION;

        $answer_json = json_encode(['version' => $this->API_VERSION, 'response' => $response, 'status' => true]);

        return $this->answer($answer_json);
    }

    /**
     * @api {get} /getOrderInfo  getOrderInfo
     * @apiVersion 0.1.0
     * @apiName getOrderInfo
     * @apiGroup Get orders info
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {String} email  Client's email.
     * @apiSuccess {Number} phone  Client's phone.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Date} date_added  Date added of the order.
     * @apiSuccess {Array} statuses  Statuses list for order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *      "response" :
     *          {
     *              "order_number" : "6",
     *              "currency_code": "RUB",
     *              "fio" : "Anton Kiselev",
     *              "email" : "client@mail.ru",
     *              "telephone" : "056 000-11-22",
     *              "date_added" : "2016-12-24 12:30:46",
     *              "total" : "1405.00",
     *              "status" : "Complete",
     *              "statuses" :
     *                  {
     *                         {
     *                             "name": "Canceled",
     *                             "order_status_id": "canceled"
     *                         },
     *                         {
     *                             "name": "Complete",
     *                             "order_status_id": "complete"
     *                          },
     *                          {
     *                              "name": "Pending",
     *                              "order_status_id": "pending"
     *                           }
     *                    }
     *          },
     *      "status" : true,
     *      "version": 1.0
     * }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error" : "Can not found order with id = 5",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     */

    public function getOrderInfoAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $order = $this->getOrderById($id);

            if (count($order) > 0) {
                $data['order_number'] = $order[0]['entity_id'];

                $data['fio'] = $order[0]['customer_firstname'] . ' ' . $order[0]['customer_lastname'];

                if (isset($order[0]['customer_email'])) {
                    $data['email'] = $order[0]['customer_email'];
                } else {
                    $data['email'] = '';
                }
                if (isset($order[0]['telephone'])) {
                    $data['telephone'] = $order[0]['telephone'];
                } else {
                    $data['telephone'] = '';
                }

                $data['date_added'] = $order[0]['created_at'];

                if (isset($order[0]['grand_total'])) {
                    $data['total'] = number_format($order[0]['grand_total'], 2, '.', '');;
                }
                if (isset($order[0]['label'])) {
                    $data['status'] = $order[0]['label'];
                } else {
                    $data['status'] = '';
                }
                $statuses = $this->OrderStatusList();
                $data['statuses'] = $statuses;
                $data['currency_code'] = $this->getDefaultCurrency();

                $answer_json = json_encode(['version' => $this->API_VERSION, 'response' => $data, 'status' => true], JSON_UNESCAPED_UNICODE);

                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'error' => 'Can not found order with id = ' . $id,
                    'status' => false
                ], JSON_UNESCAPED_UNICODE);

                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);

            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /getOrderPaymentAndDelivery  getOrderPaymentAndDelivery
     * @apiVersion 0.1.0
     * @apiName getOrderPaymentAndDelivery
     * @apiGroup Get orders info
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {String} payment_method     Payment method.
     * @apiSuccess {String} shipping_method  Shipping method.
     * @apiSuccess {String} shipping_address  Shipping address.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
     *      {
     *          "response":
     *              {
     *                  "payment_method" : "Оплата при доставке",
     *                  "shipping_method" : "Доставка с фиксированной стоимостью доставки",
     *                  "shipping_address" : "проспект Карла Маркса 1, Днепропетровск, Днепропетровская область, Украина."
     *              },
     *          "status": true,
     *          "version": 1.0
     *      }
     * @apiErrorExample Error-Response:
     *
     *    {
     *      "error": "Can not found order with id = 90",
     *      "version": 1.0,
     *      "Status" : false
     *   }
     *
     */

    public function getOrderPaymentAndDeliveryAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $order = $this->getPaymentAndShippingById($id);

            if ($order['error'] == null) {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'response' => $order['answer'],
                    'status' => true
                ], JSON_UNESCAPED_UNICODE);

                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'error' => 'Can not found order with id = ' . $id,
                    'status' => false
                ], JSON_UNESCAPED_UNICODE);

                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false], JSON_UNESCAPED_UNICODE);

            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /getOrderProducts  getOrderProducts
     * @apiVersion 0.1.0
     * @apiName getOrderProducts
     * @apiGroup Get orders info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {ID} order_id unique order id.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Url} image  Picture of the product.
     * @apiSuccess {Number} quantity  Quantity of the product.
     * @apiSuccess {String} name     Name of the product.
     * @apiSuccess {Number} Price  Price of the product.
     * @apiSuccess {Number} total_order_price  Total sum of the order.
     * @apiSuccess {Number} total_price  Sum of product's prices.
     * @apiSuccess {String} currency_code  currency of the order.
     * @apiSuccess {Number} shipping_price  Cost of the shipping.
     * @apiSuccess {Number} total  Total order sum.
     * @apiSuccess {Number} product_id  unique product id.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *      "response":
     *          {
     *              "products": [
     *              {
     *                  "image" : "http://magento_site_url/media/catalog/product/cache/1/thumbnail/200x200/9df78eab33525d08d6e5fb8d27136e95/w/p/wpd005t.jpg",
     *                  "name" : "DUMBO Boyfriend Jea",
     *                  "quantity" : 1,
     *                  "price" : 115.50,
     *                  "product_id" : 427
     *              },
     *              {
     *                  "image" : "http://magento_site_url/media/catalog/product/cache/1/thumbnail/200x200/9df78eab33525d08d6e5fb8d27136e95/h/d/hdd006_1.jpg",
     *                  "name" : "Geometric Candle Holders",
     *                  "quantity" : 3,
     *                  "price" : 45.00,
     *                  "product_id" : 391
     *               }
     *            ],
     *            "total_order_price":
     *              {
     *                   "total_discount": 0,
     *                   "total_price": 250.50,
     *                     "currency_code": "RUB",
     *                   "shipping_price": 36.75,
     *                   "total": 287.25
     *               }
     *
     *         },
     *      "status": true,
     *      "version": 1.0
     * }
     *
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *          "error": "Can not found any products in order with id = 10",
     *          "version": 1.0,
     *          "Status" : false
     *     }
     *
     */

    public function getOrderProductsAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];
            $order = Mage::getModel('sales/order')->load($id);
            $items = $order->getAllVisibleItems();
            $imageHelper = Mage::helper('catalog/image');
            $productIds = array();
            $dataAnswer = [];

            foreach ($items as $key => $item) {
                $productIds[] = $item->getProductId();
                $_product = Mage::getModel('catalog/product')
                    ->setStoreId($item->getOrder()->getStoreId())
                    ->load($item->getProductId());
                $thumbail_src = $imageHelper->init($_product, 'thumbnail')->resize(200, 200);
                $dataAnswer['products'][$key]['image'] = (string)$thumbail_src;
                $dataAnswer['products'][$key]['name'] = (string)$item->getName();
                $dataAnswer['products'][$key]['quantity'] = number_format($item->getQtyOrdered(), 2, '.', '');
                $dataAnswer['products'][$key]['price'] = number_format($item->getBasePriceInclTax(), 2, '.', '');
                $dataAnswer['products'][$key]['product_id'] = $item->getProductId();
            }
            $dataAnswer['total_order_price']['total_discount'] = number_format($order->getDiscountAmount(), 2, '.', '');
            $dataAnswer['total_order_price']['total_price'] = number_format($order->getSubtotalInclTax(), 2, '.', '');;
            $dataAnswer['total_order_price']['currency_code'] = $order->getOrderCurrencyCode();
            $dataAnswer['total_order_price']['shipping_price'] = number_format($order->getBaseShippingAmount(), 2, '.', '');
            $dataAnswer['total_order_price']['total'] = number_format($order->getGrandTotal(), 2, '.', '');
            $answer_json = json_encode([
                'version' => $this->API_VERSION,
                'response' => $dataAnswer,
                'status' => true
            ], JSON_UNESCAPED_UNICODE);

            return $this->answer($answer_json);
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);

            return $this->answer($answer_json);
        }

        return;
    }

    /**
     * @api {get} /getOrderHistory  getOrderHistory
     * @apiVersion 0.1.0
     * @apiName getOrderHistory
     * @apiGroup Get orders info
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {String} name     Status of the order.
     * @apiSuccess {String} order_status_id  ID of the status of the order.
     * @apiSuccess {Date} date_added  Date of adding status of the order.
     * @apiSuccess {String} comment  Some comment added from manager.
     * @apiSuccess {Array} statuses  Statuses list for order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *       {
     *           "response":
     *               {
     *                   "orders":
     *                      {
     *                          {
     *                              "name": "Complete",
     *                              "order_status_id": "complete",
     *                              "date_added": "2016-12-25 08:27:48.",
     *                              "comment": "Some text"
     *                          },
     *                          {
     *                              "name": "Processing",
     *                              "order_status_id": "processing",
     *                              "date_added": "2016-12-13 09:30:10.",
     *                              "comment": "Some text"
     *                          },
     *                          {
     *                              "name": "Pending",
     *                              "order_status_id": "pending",
     *                              "date_added": "2016-12-01 11:25:18.",
     *                              "comment": "Some text"
     *                           }
     *                       },
     *                    "statuses":
     *                        {
     *                             {
     *                                  "name": "Canceled",
     *                                  "order_status_id": "canceled"
     *                             },
     *                             {
     *                                  "name": "Complete",
     *                                  "order_status_id": "complete"
     *                              },
     *                              {
     *                                  "name": "Pending",
     *                                  "order_status_id": "pending"
     *                              }
     *                         }
     *               },
     *           "status": true,
     *           "version": 1.0
     *       }
     * @apiErrorExample Error-Response:
     *
     *     {
     *          "error": "Can not found any statuses for order with id = 5",
     *          "version": 1.0,
     *          "Status" : false
     *     }
     */

    public function getOrderHistoryAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $orderId = $_REQUEST['order_id'];

            $order = Mage::getModel('sales/order')->load($orderId);
            $history = $order->getStatusHistoryCollection()->getData();
            $dataAnswer = [];

            if (count($history) > 0) {
                foreach ($history as $key => $one_status_history) {
                    $orderStatusName = Mage::getModel('sales/order_status')
                        ->load($one_status_history['status'])
                        ->getData();

                    $dataAnswer['orders'][$key]['name'] = $orderStatusName['label'];
                    $dataAnswer['orders'][$key]['order_status_id'] = $one_status_history['status'];
                    $dataAnswer['orders'][$key]['date_added'] = $one_status_history['created_at'];
                    if ($one_status_history['comment'] == null) {
                        $dataAnswer['orders'][$key]['comment'] = '';
                    } else {
                        $dataAnswer['orders'][$key]['comment'] = $one_status_history['comment'];
                    }

                }
                $dataAnswer['statuses'] = $this->OrderStatusList();
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'response' => $dataAnswer,
                    'status' => true
                ], JSON_UNESCAPED_UNICODE);

                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'error' => 'Can not found any statuses for order with id = ' . $orderId,
                    'status' => false
                ]);

                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);

            return $this->answer($answer_json);
        }

        return;
    }

    /**
     * @api {get} /getClients  getClients
     * @apiVersion 0.1.0
     * @apiName GetClients
     * @apiGroup Get clients info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page number of the page.
     * @apiParam {Number} limit limit of the orders for the page.
     * @apiParam {String} fio full name of the client.
     * @apiParam {String} sort param for sorting clients(sum/quantity/date_added).
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} client_id  ID of the client.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {Number} total  Total sum of client's orders.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} quantity  Total quantity of client's orders.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *     "clients"
     *      {
     *          {
     *              "client_id" : "88",
     *              "fio" : "Anton Kiselev",
     *              "total" : "1006.00",
     *              "currency_code": "UAH",
     *              "quantity" : "5"
     *          },
     *          {
     *              "client_id" : "10",
     *              "fio" : "Vlad Kochergin",
     *              "currency_code": "UAH",
     *              "total" : "555.00",
     *              "quantity" : "1"
     *          }
     *      }
     *    },
     *    "Status" : true,
     *    "version": 1.0
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one client found",
     *      "version": 1.0,
     *      "Status" : false
     * }
     *
     *
     */

    public function getClientsAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && (int)$_REQUEST['limit'] != 0 && isset($_REQUEST['limit'])) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 20;
        }
        if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') {
            $order = $_REQUEST['sort'];
        } else {
            $order = 'date_added';
        }
        if (isset($_REQUEST['fio']) && $_REQUEST['fio'] != '') {
            $fio = $_REQUEST['fio'];
        } else {
            $fio = '';
        }

        $clients = $this->getClients(array('page' => $page, 'limit' => $limit, 'order' => $order, 'fio' => $fio));
        if (count($clients) > 0) {
            $answerToJson['clients'] = $clients;
        } else {
            $answerToJson['clients'] = [];
        }
        $answer_json = json_encode([
            'version' => $this->API_VERSION,
            'response' => $answerToJson,
            'status' => true
        ], JSON_UNESCAPED_UNICODE);

        return $this->answer($answer_json);
    }

    /**
     * @api {get} /getClientInfo  getClientInfo
     * @apiVersion 0.1.0
     * @apiName getClientInfo
     * @apiGroup Get clients info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} client_id unique client ID.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} client_id  ID of the client.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {Number} total  Total sum of client's orders.
     * @apiSuccess {Number} quantity  Total quantity of client's orders.
     * @apiSuccess {String} email  Client's email.
     * @apiSuccess {String} telephone  Client's telephone.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} cancelled  Total quantity of cancelled orders.
     * @apiSuccess {Number} completed  Total quantity of completed orders.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *         "client_id" : "88",
     *         "fio" : "Anton Kiselev",
     *         "total" : "1006.00",
     *         "quantity" : "5",
     *         "cancelled" : "1",
     *         "completed" : "2",
     *         "email" : "client@mail.ru",
     *         "currency_code": "UAH",
     *         "telephone" : "13456789"
     *   },
     *   "Status" : true,
     *   "version": 1.0
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one client found",
     *      "version": 1.0,
     *      "Status" : false
     * }
     *
     *
     */

    public function getClientInfoAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != '') {
            $client_id = $_REQUEST['client_id'];

            $clientInfo = $this->getClientInfo($client_id);

            $answer_json = json_encode([
                'version' => $this->API_VERSION,
                'response' => $clientInfo,
                'status' => true
            ], JSON_UNESCAPED_UNICODE);

            return $this->answer($answer_json);
        } else {

            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);

            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /getClientOrders  getClientOrders
     * @apiVersion 0.1.0
     * @apiName getClientOrders
     * @apiGroup Get clients info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} client_id unique client ID.
     * @apiParam {String} sort param for sorting orders(total/date_added/completed/cancelled).
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *       "orders":
     *          {
     *             "order_id" : "1",
     *             "order_number" : "1",
     *             "status" : "Complete",
     *             "currency_code": "UAH",
     *             "total" : "106.00",
     *             "date_added" : "2016-12-09 16:17:02"
     *          },
     *          {
     *             "order_id" : "2",
     *             "order_number" : "2",
     *             "currency_code": "UAH",
     *             "status" : "Canceled",
     *             "total" : "506.00",
     *             "date_added" : "2016-10-19 16:00:00"
     *          }
     *    },
     *    "Status" : true,
     *    "version": 1.0
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "You have not specified ID",
     *      "version": 1.0,
     *      "Status" : false
     * }
     *
     *
     */

    public function getClientOrdersAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != '') {
            $client_id = $_REQUEST['client_id'];

            if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') {
                switch ($_REQUEST['sort']) {
                    case 'date_added':
                        $sort = 'created_at'; //from db
                        break;
                    case 'total':
                        $sort = 'grand_total'; //from db
                        break;
                    case 'completed':
                        $sort = 'complete'; //from db
                        break;
                    case 'cancelled':
                        $sort = 'canceled';
                        break;
                    default:
                        $sort = 'created_at'; //from db
                }
            } else {
                $sort = 'created_at'; //from db
            }

            $orders['orders'] = $this->getClientOrders($client_id, $sort);

            if (count($orders['orders']) > 0) {

                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'response' => $orders,
                    'status' => true
                ], JSON_UNESCAPED_UNICODE);
                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'response' => ['orders' => []], 'status' => true]);
                return $this->answer($answer_json);
            }
        } else {

            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);
            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /getProductsList  getProductsList
     * @apiVersion 0.1.0
     * @apiName getProductsList
     * @apiGroup Get product info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page number of the page.
     * @apiParam {Number} limit limit of the orders for the page.
     * @apiParam {String} name name of the product for search.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {String} name  Name of the product.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} price  Price of the product.
     * @apiSuccess {Number} quantity  Actual quantity of the product.
     * @apiSuccess {Url} image  Url to the product image.
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response":
     *   {
     *      "products":
     *      {
     *           {
     *             "product_id" : "1",
     *             "name" : "HTC Touch HD",
     *             "price" : "100.00",
     *             "currency_code": "UAH",
     *             "quantity" : "83",
     *             "image" : "http://site-url/image/catalog/demo/htc_touch_hd_1.jpg"
     *           },
     *           {
     *             "product_id" : "2",
     *             "name" : "iPhone",
     *             "price" : "300.00",
     *             "currency_code": "UAH",
     *             "quantity" : "30",
     *             "image" : "http://site-url/image/catalog/demo/iphone_1.jpg"
     *           }
     *      }
     *   },
     *   "Status" : true,
     *   "version": 1.0
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one product not found",
     *      "version": 1.0,
     *      "Status" : false
     * }
     *
     *
     */

    public function getProductsListAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && (int)$_REQUEST['limit'] != 0 && isset($_REQUEST['limit'])) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 10;
        }
        if (isset($_REQUEST['name']) && $_REQUEST['name'] != '') {
            $name = $_REQUEST['name'];
        } else {
            $name = '';
        }

        $productsList['products'] = $this->getProductsList($page, $limit, $name);
        $answer_json = json_encode([
            'version' => $this->API_VERSION,
            'response' => $productsList,
            'status' => true
        ], JSON_UNESCAPED_UNICODE);
        return $this->answer($answer_json);

    }

    /**
     * @api {get} /getProductInfo  getProductInfo
     * @apiVersion 0.1.0
     * @apiName getProductInfo
     * @apiGroup Get product info
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} product_id unique product ID.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {String} name  Name of the product.
     * @apiSuccess {Number} price  Price of the product.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} quantity  Actual quantity of the product.
     * @apiSuccess {String} description     Detail description of the product.
     * @apiSuccess {Array} images  Array of the images of the product.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response":
     *   {
     *       "product_id" : "392",
     *       "name" : "Madison LX2200",
     *       "price" : "425.00",
     *       "currency_code": "UAH"
     *       "quantity" : "2",
     *       "description" : "10x Optical Zoom with 24mm Wide-angle and close up.10.7-megapixel backside illuminated CMOS sensor for low light shooting.  3" Multi-angle LCD. SD/SDXC slot. Full HD Video. High speed continuous shooting (up to 5 shots in approx one second) Built in GPS. Easy Panorama. Rechargable Li-ion battery. File formats: Still-JPEG, Audio- WAV, Movies-MOV. Image size: up to 4600x3400. Built in flash. 3.5" x 5" x 4". 20oz.",
     *       "images" :
     *       [
     *           "http://magento_site_url/media/catalog/product/cache/1/thumbnail/200x200/9df78eab33525d08d6e5fb8d27136e95/h/d/hde001a.jpg",
     *           "http://magento_site_url/media/catalog/product/cache/1/thumbnail/200x200/9df78eab33525d08d6e5fb8d27136e95/h/d/hde001b.jpg",
     *           "http://magento_site_url/media/catalog/product/cache/1/thumbnail/200x200/9df78eab33525d08d6e5fb8d27136e95/h/d/hde001t_2.jpg"
     *       ]
     *   },
     *   "Status" : true,
     *   "version": 1.0
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Can not found product with id = 10",
     *      "version": 1.0,
     *      "Status" : false
     * }
     *
     *
     */

    public function getProductInfoAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['product_id']) && (int)$_REQUEST['product_id'] != 0) {
            $product_id = $_REQUEST['product_id'];
            $productInfo = $this->getProductInfoById($product_id);
            if ($productInfo !== '') {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'response' => $productInfo,
                    'status' => true
                ], JSON_UNESCAPED_UNICODE);
                return $this->answer($answer_json);

            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Can not found order with id = ' . $_REQUEST['product_id'], 'status' => false]);
                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'You have not specified ID', 'status' => false]);
            return $this->answer($answer_json);
        }
    }

    /**
     * @api {get} /changeStatus  ChangeStatus
     * @apiVersion 0.1.0
     * @apiName ChangeStatus
     * @apiGroup Change
     *
     * @apiParam {String} comment New comment for order status.
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {String} status_id unique status ID.
     * @apiParam {Token} token your unique token.
     * @apiParam {Boolean} inform status of the informing client (true/false).
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {String} name Name of the new status.
     * @apiSuccess {String} date_added Date of adding status.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *          "response":
     *              {
     *                  "name" : "Complete",
     *                  "date_added" : "2016-12-27 12:01:51"
     *              },
     *          "status": true,
     *          "version": 1.0
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error" : "Missing some params",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function changeStatusAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['comment']) && isset($_REQUEST['status_id']) && $_REQUEST['status_id'] != '' && isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {

            $updateStatusAnswer = $this->updateOrderStatus($_REQUEST['order_id'], $_REQUEST['status_id'], $_REQUEST['comment'], (boolean)$_REQUEST['inform']);
            if (count($updateStatusAnswer)) {
                $answer_json = json_encode([
                    'version' => $this->API_VERSION,
                    'response' => $updateStatusAnswer,
                    'status' => true
                ], JSON_UNESCAPED_UNICODE);
                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);
                return $this->answer($answer_json);
            }

        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);
            return $this->answer($answer_json);
        }

    }

    /**
     * @api {get} /changeOrderDelivery  ChangeOrderDelivery
     * @apiVersion 0.1.0
     * @apiName ChangeOrderDelivery
     * @apiGroup Change
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {String} address New shipping address.
     * @apiParam {String} city New shipping city.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Boolean} response Status of change address.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "status": true,
     *         "version": 1.0
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Can not change address",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function changeOrderDeliveryAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        $error = $this->validToken();
        if ($error !== null) {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => $error, 'status' => false]);

            return $this->answer($answer_json);
        }

        if (isset($_REQUEST['address']) && $_REQUEST['address'] != '' && isset($_REQUEST['order_id'])) {
            $order = Mage::getModel('sales/order')->load($_REQUEST['order_id']);

            if (count($order->getData()) > 0) {
                $shippingAddressByOrder = Mage::getModel('sales/order_address')->load($order->getShippingAddress()->getId());
                $shippingAddressByOrder->setStreet($_REQUEST['address']);
                if (isset($_REQUEST['city']) && $_REQUEST['city'] !== '') {
                    $shippingAddressByOrder->setCity($_REQUEST['city']);
                }
                $shippingAddressByOrder->save();
                $answer_json = json_encode(['version' => $this->API_VERSION, 'status' => true]);
                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Can not change address', 'status' => false]);
                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);
            return $this->answer($answer_json);
        }
    }

    /**
     * @api {post} /updateDeviceToken  updateUserDeviceToken
     * @apiVersion 0.1.0
     * @apiName updateUserDeviceToken
     * @apiGroup Tokens
     *
     * @apiParam {String} new_token User's device's new token for firebase notifications.
     * @apiParam {String} old_token User's device's old token for firebase notifications.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Boolean} status  true.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "status": true,
     *          "version": 1.0
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Missing some params",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function updateDeviceTokenAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        if (isset($_REQUEST['old_token']) && isset($_REQUEST['new_token'])) {
            $updateToken = $this->updateUserDeviceToken($_REQUEST['old_token'], $_REQUEST['new_token']);

            if ($updateToken !== false) {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'status' => true]);
                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Can not find your token', 'status' => false]);
                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);
            return $this->answer($answer_json);
        }
    }

    /**
     * @api {post} /deleteDeviceToken  deleteUserDeviceToken
     * @apiVersion 0.1.0
     * @apiName deleteUserDeviceToken
     * @apiGroup Tokens
     *
     * @apiParam {String} old_token User's device's token for firebase notifications.
     *
     * @apiSuccess {Number} version  Current API version.
     * @apiSuccess {Boolean} status  true.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "status": true,
     *          "version": 1.0
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Missing some params",
     *       "version": 1.0,
     *       "Status" : false
     *     }
     *
     */

    public function deleteDeviceTokenAction()
    {
        require_once("app/Mage.php");
        Mage::app();

        if (isset($_REQUEST['old_token'])) {
            $deleteToken = $this->deleteUserDeviceToken($_REQUEST['old_token']);
            if ($deleteToken !== false) {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'status' => true]);
                return $this->answer($answer_json);
            } else {
                $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Can not find your token', 'status' => false]);
                return $this->answer($answer_json);
            }
        } else {
            $answer_json = json_encode(['version' => $this->API_VERSION, 'error' => 'Missing some params', 'status' => false]);
            return $this->answer($answer_json);
        }
    }

    /**
     *
     */

    public function indexAction()
    {
        $this->sendNotifications($_REQUEST['order_id']);

        echo '<h1>News</h1>';
    }

    public function answer($answer_json)
    {
        $answer = $this->getResponse()
            ->clearHeaders()
            ->setHeader('HTTP/1.0', 200, true)
            ->setHeader("Access-Control-Allow-Origin", '*')
            ->setHeader('Content-Type', 'application/json;charset=utf-8')// can be changed to json, xml...
            ->setBody($answer_json);

        return $answer;
    }

    public function setUserDeviceToken($user_id, $token, $os_type)
    {
        Mage::app();
        $userDatadeviseTokenToDB = Mage::getModel('pintamobileapi/userdevicestoken');
        $userDatadeviseTokenToDB->setUserId($user_id);
        $userDatadeviseTokenToDB->setToken($token);
        $userDatadeviseTokenToDB->setOsType($os_type);
        $userDatadeviseTokenToDB->save();

        return;
    }

    private function updateUserDeviceToken($old_token, $new_token)
    {
        $updateToken = Mage::getModel('pintamobileapi/userdevicestoken')
            ->load($old_token, 'token');

        $answer = false;
        if (count($updateToken->getData()) !== 0) {
            $updateToken->setToken($new_token);
            $updateToken->save();
            $answer = count($updateToken->getData());
        }

        return $answer;
    }

    private function deleteUserDeviceToken($old_token)
    {
        $deleteToken = Mage::getModel('pintamobileapi/userdevicestoken')
            ->load($old_token, 'token');

        $answer = false;
        if (count($deleteToken->getData()) !== 0) {
            $deleteToken->delete();
            $answer = count($deleteToken->getData());
        }
        return $answer;
    }

    public function getUserDevices()
    {
        Mage::app();
        $allUserAuthorizedDevices = Mage::getModel('pintamobileapi/userdevicestoken')
            ->getCollection();

        return $allUserAuthorizedDevices;
    }

    public function setUserToken($user_id, $token)
    {
        Mage::app();

        $userTokenToDB = Mage::getModel('pintamobileapi/mobileapi')

        ;
        $userTokenToDB->setUserId($user_id);
        $userTokenToDB->setToken($token);
        $userTokenToDB->save();

        return;
    }

    public function getUserToken($user_id)
    {
        Mage::app();
        $userToken = Mage::getModel('pintamobileapi/mobileapi')
            ->load($user_id, 'user_id');

        return $userToken->getToken();
    }

    public function getTokens()
    {
        Mage::app();
        $userTokens = Mage::getModel('pintamobileapi/mobileapi')->getCollection();

        return $userTokens;
    }

    private function validToken()
    {
        if (!isset($_REQUEST['token']) || $_REQUEST['token'] == '') {
            $error = 'You need to be logged!';
        } else {
            $tokens = $this->getTokens();
            if (count($tokens) > 0) {
                foreach ($tokens as $token) {
                    if ($_REQUEST['token'] == $token->getToken()) {
                        $error = null;
                    } else {
                        $error = 'Your token is no longer relevant!';
                    }
                }
            } else {
                $error = 'You need to be logged!';
            }
        }

        return $error;
    }

    public function getTotalCustomers($data = array())
    {
        Mage::app();
        $customers = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableName = $customers->getTableName('customer_entity');
        $query = 'SELECT * FROM ' . $tableName . ' ';
        if (isset($data['filter'])) {
            if ($data['filter'] == 'day') {
                $query .= 'WHERE DATE(created_at) = DATE(NOW())';
            } elseif ($data['filter'] == 'week') {
                $date_start = strtotime('-' . date('w') . ' days');
                $query .= "WHERE DATE(created_at) >= DATE('" . date('Y-m-d', $date_start) . "') ";
            } elseif ($data['filter'] == 'month') {
                $query .= "WHERE DATE(created_at) >= '" . date('Y') . '-' . date('m') . '-1' . "' ";
            } elseif ($data['filter'] == 'year') {
                $query .= "WHERE YEAR(created_at) = YEAR(NOW())";
            } else {
                return false;
            }
        } else {
            $query = "SELECT COUNT(*) FROM " . $tableName . " ";
        }
        $allCustomers = $customers->fetchAll($query);

        return $allCustomers;
    }

    public function getTotalOrders($data = array())
    {
        Mage::app();
        $orders = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableName = $orders->getTableName('sales_flat_order');
        $query = 'SELECT * FROM ' . $tableName . ' ';
        if (isset($data['filter'])) {
            if ($data['filter'] == 'day') {
                $query .= " WHERE DATE(created_at) = DATE(NOW())";
            } elseif ($data['filter'] == 'week') {
                $date_start = strtotime('-' . date('w') . ' days');
                $query .= " WHERE DATE(created_at) >= DATE('" . date('Y-m-d', $date_start) . "') ";

            } elseif ($data['filter'] == 'month') {
                $query .= " WHERE DATE(created_at) >= '" . date('Y') . '-' . date('m') . '-1' . "' ";

            } elseif ($data['filter'] == 'year') {
                $query .= " WHERE YEAR(created_at) = YEAR(NOW())";
            } else {
                return false;
            }
        } else {
            $query = "SELECT COUNT(*) FROM " . $tableName . " ";
        }
        $allOrders = $orders->fetchAll($query);

        return $allOrders;
    }

    public function getTotalSales($data = array())
    {
        Mage::app();
        $total = 0;
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('status', array('neq' => 'pending'));
        if (!empty($data['this_year'])) {
            $collection->addAttributeToFilter('created_at', array('gt' => date('Y-m-d H:i:s', strtotime(date('Y-01-01')))))
                ->addAttributeToFilter('created_at', array('lt' => date('Y-m-d H:i:s', mktime(date("H") + 3, date("i"), date("s"), date("m"), date("d"), date("Y")))));
        }
        foreach ($collection as $eachOrder) {
            $total += $eachOrder->getGrandTotal();
        }

        return $total;
    }

    public function getDefaultCurrency()
    {
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

        return $currency_code;
    }

    public function getOrders($data = array())
    {
        $orders = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableOrdersName = $orders->getTableName('sales_flat_order');
        $tableStatusesName = $orders->getTableName('sales_order_status');
        $query = "SELECT * FROM " . $tableOrdersName . " AS o LEFT JOIN " . $tableStatusesName . " AS s ON o.status = s.status  ";

        if (isset($data['filter'])) {
            if (isset($data['filter']['order_status_id']) && $data['filter']['order_status_id'] !== '' && $data['filter']['order_status_id'] !== 'null' && $data['filter']['order_status_id'] !== '0' && !ctype_digit($data['filter']['order_status_id']) && intval($data['filter']['order_status_id']) == 0) {
                $query .= " WHERE o.status = \"" . $data['filter']['order_status_id'] . "\"";
            } else {
                $query .= " WHERE o.status != \"pending\" ";
            }

            if (isset($data['filter']['fio']) && $data['filter']['fio'] !== '' && $data['filter']['fio'] !== 'null' && $data['filter']['fio'] !== '0' && !ctype_digit($data['filter']['fio']) && intval($data['filter']['fio']) == 0) {
                $params = [];
                $newparam = explode(' ', $data['filter']['fio']);

                foreach ($newparam as $key => $value) {
                    if ($value == '') {
                        unset($newparam[$key]);
                    } else {
                        $params[] = $value;
                    }
                }

                $query .= " AND ( o.customer_firstname LIKE \"%" . $params[0] . "%\" OR o.customer_lastname LIKE \"%" . $params[0] . "%\"";
                foreach ($params as $param) {
                    if ($param != $params[0]) {
                        $query .= " OR o.customer_firstname LIKE \"%" . $param . "%\" OR o.customer_lastname LIKE \"%" . $param . "%\"";
                    };
                }
                $query .= " ) ";
            }
            if ($data['filter']['min_price'] == 0) {
                $data['filter']['min_price'] = 1;
            }
            if (isset($data['filter']['min_price']) && isset($data['filter']['max_price']) && $data['filter']['max_price'] != '' && $data['filter']['max_price'] != 0 && $data['filter']['min_price'] !== 0 && $data['filter']['min_price'] !== '') {
                $query .= " AND o.grand_total > \"" . $data['filter']['min_price'] . "\" AND o.grand_total <= \"" . $data['filter']['max_price'] . "\"";
            }

            if (isset($data['filter']['date_min']) && $data['filter']['date_min'] != '') {
                $date_min = date('y-m-d', strtotime($data['filter']['date_min']));
                $query .= " AND DATE_FORMAT(o.created_at,\"%y-%m-%d\") > \"" . $date_min . "\"";
            }
            if (isset($data['filter']['date_max']) && $data['filter']['date_max'] != '') {
                $date_max = date('y-m-d', strtotime($data['filter']['date_max']));
                $query .= " AND DATE_FORMAT(o.created_at,\"%y-%m-%d\") < \"" . $date_max . "\"";
            }
        } else {
            $query .= " WHERE o.status != \"pending\" ";
        }
        $query .= " GROUP BY o.entity_id ORDER BY o.entity_id DESC";

        $total_sum = $orders->fetchAll($query);

        $sum = 0;
        $quantity = 0;
        foreach ($total_sum as $value) {
            $sum = $sum + $value['grand_total'];
            $quantity++;
        }

        $query .= " LIMIT " . (int)$data['limit'] . " OFFSET " . (int)$data['page'];

        $answer = $orders->fetchAll($query);

        $answer['totalsumm'] = $sum;
        $answer['quantity'] = $quantity;

        return $answer;
    }

    public function getMaxOrderPrice()
    {
        $orders = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableOrdersName = $orders->getTableName('sales_flat_order');
        $query = "SELECT MAX(grand_total) AS grand_total FROM " . $tableOrdersName . "  WHERE status != 'pending' ";
        $answer = $orders->fetchAll($query);

        return number_format($answer[0]['grand_total'], 2, '.', '');
    }

    public function OrderStatusList()
    {
        $orders = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableOrdersStatusState = $orders->getTableName('sales_order_status_state');

        $collection = Mage::getModel('sales/order_status')
            ->getCollection()//            ->getData()
        ;
        $collection->getSelect()->join(
            $tableOrdersStatusState,
            'main_table.status=' . $tableOrdersStatusState . '.status',
            array('state', 'is_default')
        )->where('is_default!=0');
        $statuses = [];
        $key = 0;
        foreach ($collection as $status) {
            $statuses[$key]['name'] = $status['label'];
            $statuses[$key]['order_status_id'] = $status['status'];
            $statuses[$key]['order_state'] = $status['state'];
            $key++;
        }

        return $statuses;
    }

    public function getOrderById($id)
    {
        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $orderCollection = Mage::getModel('sales/order')
            ->getCollection();

        $orderCollection->getSelect()->join(
            $tablesCollection->getTableName('sales_order_status'),
            'main_table.status=' . $tablesCollection->getTableName('sales_order_status') . '.status',
            array('*')
        );
        $checkShipppingCollection = Mage::getModel('sales/order_address')
            ->getCollection()
            ->addAttributeToFilter('parent_id', array('eq' => $id));
        if (count($checkShipppingCollection->getData()) == 1) {
            $orderCollection->getSelect()->join(
                $tablesCollection->getTableName('sales_flat_order_address'),
                'main_table.entity_id=' . $tablesCollection->getTableName('sales_flat_order_address') . '.parent_id',
                array('telephone', 'address_type')
            );
        } else {
            $orderCollection->getSelect()->join(
                $tablesCollection->getTableName('sales_flat_order_address'),
                'main_table.entity_id=' . $tablesCollection->getTableName('sales_flat_order_address') . '.parent_id',
                array('telephone', 'address_type')
            )->where("" . $tablesCollection->getTableName('sales_flat_order_address') . ".address_type = 'shipping'");
        }
        $orderCollection->addAttributeToFilter('entity_id', array('eq' => $id));

        return $orderCollection->getData();
    }

    public function getPaymentAndShippingById($id)
    {
        $order = Mage::getModel("sales/order")->load($id);

        $data = [];
        if (count($order->getData()) !== 0) {
            $data['error'] = null;

            $payment_method_title = $order->getPayment()->getMethodInstance()->getTitle(); //Fetch the payment method code from order

            if ($order->getShippingMethod() !== null) {
                $shipping_method_description = $order->getShippingDescription();
                $shipping_method_address = $order->getShippingAddress()->getData();

                $country = Mage::getModel('directory/country')->loadByCode($shipping_method_address['country_id']);

                $data['answer']['payment_method'] = $payment_method_title;
                $data['answer']['shipping_method'] = $shipping_method_description;
                if ($shipping_method_address['street'] !== null) {
                    $data['answer']['shipping_address'] = $shipping_method_address['street'];
                }
                if ($shipping_method_address['city'] !== null) {
                    $data['answer']['shipping_address'] .= ', ' . $shipping_method_address['city'];
                }
                if ($shipping_method_address['region'] !== null) {
                    $data['answer']['shipping_address'] .= ', ' . $shipping_method_address['region'];
                }
                $data['answer']['shipping_address'] .= ', ' . $country->getName();

            } else {
                $data['answer']['payment_method'] = $payment_method_title;
                $data['answer']['shipping_method'] = '';
                $data['answer']['shipping_address'] = '';

            }
        } else {
            $data['error'] = 'error';
        }

        return $data;
    }

    public function getClients($data = array())
    {
        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $collectionUsers = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('email');

        if (isset($data['fio']) && $data['fio'] != '') {
            $params = [];
            $newparam = explode(' ', $data['fio']);

            foreach ($newparam as $key => $value) {
                if ($value == '') {
                    unset($newparam[$key]);
                } else {
                    $params[] = $value;
                }
            }

            $tableCustomersName = $tablesCollection->getTableName('customer_entity_varchar');
            $query = "SELECT `entity_id` FROM " . $tableCustomersName . " WHERE ";
            $query .= "(value LIKE \"%" . $params[0] . "%\" ";
            foreach ($params as $param) {
                if ($param != $params[0]) {
                    $query .= "OR value LIKE \"%" . $param . "%\" ";
                };
            }
            $query .= ')';
            $usersIdsArray = $tablesCollection->fetchAll($query);
            $usersIdsUnitedArray = array();
            foreach ($usersIdsArray as $userId) {
                $usersIdsUnitedArray[] = intval($userId['entity_id']);
            }
            $usersIdsUnitedArray = array_unique($usersIdsUnitedArray);
            $collectionUsers->addFieldToFilter('entity_id', array('in' => $usersIdsUnitedArray));
        }
        $collectionUsers->getSelect()->limit((int)$data['limit'], (int)$data['page']);

        $dataFullAnswer = array();
        foreach ($collectionUsers as $user) {

            $collectionOrderByUser = Mage::getModel('sales/order')
                ->getCollection()
                ->addAttributeToSelect('grand_total')
                ->addAttributeToFilter('customer_id', array('eq' => $user['entity_id']));
            $ordersByUserTotalSum = 0;
            $dataAnswer['client_id'] = $user['entity_id'];
            $dataAnswer['date_added'] = $user['created_at'];
            if (isset($user['firstname']) && $user['firstname'] !== '') {
                $dataAnswer['fio'] = $user['firstname'];
            }
            if (isset($user['lastname']) && $user['lastname'] !== '') {
                $dataAnswer['fio'] .= ' ' . $user['lastname'];
            }
            if (!empty($collectionOrderByUser->getData())) {
                foreach ($collectionOrderByUser as $orderByUser) {
                    $ordersByUserTotalSum += $orderByUser['grand_total'];
                }

                $dataAnswer['total'] = number_format($ordersByUserTotalSum, 2, '.', '');
                $dataAnswer['currency_code'] = $this->getDefaultCurrency();
                $dataAnswer['quantity'] = count($collectionOrderByUser->getData());

            } else {
                $dataAnswer['total'] = '';
                $dataAnswer['currency_code'] = $this->getDefaultCurrency();
                $dataAnswer['quantity'] = '';
            }
            $dataFullAnswer[] = $dataAnswer;
        }

        if (isset($data['order']) && $data['order'] !== '') {
            switch ($data['order']) {
                case 'quantity':
                    usort($dataFullAnswer, function ($a, $b) {
                        return ($b['quantity'] - $a['quantity']);
                    });
                    break;
                case 'sum':
                    usort($dataFullAnswer, function ($a, $b) {
                        return ($b['total'] - $a['total']);
                    });
                    break;
                case 'date_added':
                    usort($dataFullAnswer, function ($a, $b) {
                        return ($b['date_added'] - $a['date_added']);
                    });
                    break;
            }
        }

        return $dataFullAnswer;
    }

    public function getClientInfo($cliend_id)
    {
        $userInfo = Mage::getModel('customer/customer')->load($cliend_id);


        $answerUserInfo = array();
        $answerUserInfo['client_id'] = $userInfo->getEntityId();
        if ($userInfo->getFirstname() !== '' && $userInfo->getFirstname() !== null) {
            $answerUserInfo['fio'] = $userInfo->getFirstname();
        }
        if ($userInfo->getLastname() !== '' && $userInfo->getLastname() !== null) {
            $answerUserInfo['fio'] .= ' ' . $userInfo->getLastname();
        }
        $infoAllOrderByUser = $this->getInfoAllOrdersByClient($cliend_id);
        $answerUserInfo['total'] = $infoAllOrderByUser['total_sum'];
        $answerUserInfo['quantity'] = $infoAllOrderByUser['quantity'];
        $answerUserInfo['cancelled'] = $infoAllOrderByUser['cancelled'];
        $answerUserInfo['completed'] = $infoAllOrderByUser['completed'];
        $answerUserInfo['currency_code'] = $this->getDefaultCurrency();
        $answerUserInfo['email'] = $userInfo->getEmail();
        if ($userInfo->getPrimaryShippingAddress()->getTelephone() !== '' && $userInfo->getPrimaryShippingAddress()->getTelephone() !== null) {
            $answerUserInfo['telephone'] = str_replace(' ', '-', $userInfo->getPrimaryShippingAddress()->getTelephone());
        } else {
            $answerUserInfo['telephone'] = str_replace(' ', '-', $userInfo->getPrimaryBillingAddress()->getTelephone());
        }

        return $answerUserInfo;
    }

    private function getInfoAllOrdersByClient($client_id)
    {
        $collectionOrderByUser = Mage::getModel('sales/order')
            ->getCollection();
        $ordersByUserTotalSumTmp = $collectionOrderByUser
            ->addAttributeToSelect('grand_total')
            ->addAttributeToFilter('customer_id', array('eq' => $client_id));
        $ordersByUserTotalSum = 0;
        if (!empty($ordersByUserTotalSumTmp->getData())) {
            foreach ($collectionOrderByUser as $orderByUser) {
                $ordersByUserTotalSum += $orderByUser['grand_total'];
            }
        }
        $answerInfoByUser['total_sum'] = number_format($ordersByUserTotalSum, 2, '.', '');
        $answerInfoByUser['quantity'] = number_format(count($collectionOrderByUser->getData()));
        $collectionOrderByUserSec = Mage::getModel('sales/order')
            ->getCollection();
        $ordersByUserCanceledCount = $collectionOrderByUserSec
            ->addAttributeToFilter('customer_id', array('eq' => $client_id))
            ->addAttributeToFilter('status', array('eq' => 'canceled'));
        $answerInfoByUser['cancelled'] = number_format(count($ordersByUserCanceledCount->getData()));
        $ordersByUserCompletedCount = $collectionOrderByUserSec
            ->addAttributeToFilter('customer_id', array('eq' => $client_id))
            ->addAttributeToFilter('status', array('eq' => 'complete'));
        $answerInfoByUser['completed'] = number_format(count($ordersByUserCompletedCount->getData()));

        return $answerInfoByUser;
    }

    private function getClientOrders($client_id, $sort)
    {
        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $collectionOrderByUser = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('customer_id', array('eq' => $client_id));
        $collectionOrderWithOtherStatusesByUser = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToFilter('customer_id', array('eq' => $client_id))
            ->setOrder('main_table.created_at', 'DESC');
        if ($sort !== 'canceled' && $sort !== 'complete') {
            $collectionOrderByUser
                ->setOrder('main_table.' . $sort, 'DESC');
        } elseif ($sort == 'complete') {
            $collectionOrderByUser
                ->setOrder('main_table.created_at', 'DESC')
                ->addAttributeToFilter('main_table.status', array('eq' => $sort));
            $collectionOrderWithOtherStatusesByUser
                ->addAttributeToFilter('main_table.status', array('neq' => $sort));
        } elseif ($sort == 'canceled') {
            $collectionOrderByUser
                ->setOrder('main_table.created_at', 'DESC')
                ->addAttributeToFilter('main_table.status', array('eq' => $sort));
            $collectionOrderWithOtherStatusesByUser
                ->addAttributeToFilter('main_table.status', array('neq' => $sort));
        } else {

        }
        $collectionOrderByUser->getSelect()->joinLeft(
            $tablesCollection->getTableName('sales_order_status'),
            'main_table.status=' . $tablesCollection->getTableName('sales_order_status') . '.status',
            array('label')
        );
        $collectionOrderWithOtherStatusesByUser->getSelect()->joinLeft(
            $tablesCollection->getTableName('sales_order_status'),
            'main_table.status=' . $tablesCollection->getTableName('sales_order_status') . '.status',
            array('label')
        );
        $collectionOrderByUser->getSelect()
            ->group('main_table.entity_id');
        $collectionOrderWithOtherStatusesByUser->getSelect()
            ->group('main_table.entity_id');
        $answer = array();
        $keyOrders = 0;
        $currency_code = $this->getDefaultCurrency();
        foreach ($collectionOrderByUser as $orderByUserWithStatus) {
            $answer[$keyOrders]['order_id'] = $orderByUserWithStatus->getEntityId();
            $answer[$keyOrders]['order_number'] = $orderByUserWithStatus->getEntityId();
            $answer[$keyOrders]['status'] = $orderByUserWithStatus->getLabel();
            $answer[$keyOrders]['currency_code'] = $currency_code;
            $answer[$keyOrders]['total'] = number_format($orderByUserWithStatus->getGrandTotal(), 2, '.', '');
            $answer[$keyOrders]['date_added'] = $orderByUserWithStatus->getCreatedAt();
            $keyOrders++;
        }
        if ($sort == 'canceled' && $sort == 'complete') {
            foreach ($collectionOrderWithOtherStatusesByUser as $orderByUserWithOtherStatuses) {
                $answer[$keyOrders]['order_id'] = $orderByUserWithOtherStatuses->getEntityId();
                $answer[$keyOrders]['order_number'] = $orderByUserWithOtherStatuses->getEntityId();
                $answer[$keyOrders]['status'] = $orderByUserWithOtherStatuses->getLabel();
                $answer[$keyOrders]['currency_code'] = $currency_code;
                $answer[$keyOrders]['total'] = number_format($orderByUserWithOtherStatuses->getGrandTotal(), 2, '.', '');
                $answer[$keyOrders]['date_added'] = $orderByUserWithOtherStatuses->getCreatedAt();
                $keyOrders++;
            }
        }

        return $answer;
    }

    private function getProductsList($page, $limit, $name = '')
    {
        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $allProducts = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect(array('name', 'price', 'thumbnail'))
            ->setPageSize($limit)// limit number of results returned
            ->setCurPage($page)
            ->joinField('qty',
                $tablesCollection->getTableName('cataloginventory/stock_item'),
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'); // set the offset
        if ($name != '') {
            $allProducts
                ->addAttributeToFilter('name', array('like' => '%' . $name . '%'));
        }
        $imageHelper = Mage::helper('catalog/image');

        $currencyCode = $this->getDefaultCurrency();
        $answer = array();
        if (count($allProducts->getData()) > 0) {
            foreach ($allProducts as $oneProduct) {

                $product['product_id'] = $oneProduct->getEntityId();
                $product['name'] = $oneProduct->getName();
                $product['price'] = number_format($oneProduct->getData('price'), 2, '.', '');
                $product['currency_code'] = $currencyCode;
                $product['quantity'] = number_format($oneProduct->getQty(), 0);
                $thumbail_src = $imageHelper->init($oneProduct, 'thumbnail')->resize(200, 200);
                $product['image'] = (string)$thumbail_src;
                $answer[] = $product;
            }
        }

        return $answer;
    }

    private function getProductInfoById($product_id)
    {
        $tablesCollection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $allProductInfo = Mage::getModel('catalog/product')->load($product_id);

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($allProductInfo->getEntityId());
        $imageHelper = Mage::helper('catalog/image');

        $images = $allProductInfo->getMediaGalleryImages();

        if (count($allProductInfo->getEntityId()) > 0) {
            $answer['product_id'] = $allProductInfo->getEntityId();
            $answer['name'] = $allProductInfo->getName();
            $answer['price'] = number_format($allProductInfo->getPrice(), 2, '.', '');
            $answer['currency_code'] = $this->getDefaultCurrency();
            $answer['quantity'] = number_format($stock->getQty(), 0);

            $answer['description'] = $allProductInfo->getDescription();
            $thumbail_src = $imageHelper->init($allProductInfo, 'image')->resize(200, 200);
            $answer['images'][0] = (string)$thumbail_src;

            $imagesByProduct = $allProductInfo->getData('media_gallery');

            if (count($imagesByProduct['images']) > 0) {
                foreach ($images as $image) {
                    $imgTmpArr[] = $image->getFile();
                }

                unset($imgTmpArr[array_search($allProductInfo->getImage(), $imgTmpArr)]);
                foreach ($imgTmpArr as $imgTmp) {
                    $thumbail_src = $imageHelper->init($allProductInfo, 'image', $imgTmp)->resize(200, 200);
                    $answer['images'][] = (string)$thumbail_src;
                }

            }
        } else {
            $answer = '';
        }

        return $answer;
    }

    private function updateOrderStatus($orderID, $statusID, $comment = '', $inform = false)
    {
        $order = Mage::getModel('sales/order')->load($orderID);
        $answer = array();
        if (count($order->getData()) > 0) {
            switch ($statusID) {
                case 'complete':
                    /**
                     * change order status to 'Completed'
                     */
                    var_dump($inform);
                    $order->setData('state', 'complete');
                    $order->setStatus('complete');

                    $history = $order->addStatusToHistory($statusID, $comment, $inform);

                    $order->save();

                    break;
                case 'pending':
                    /**
                     * change order status to 'Pending'
                     */

                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, $statusID, $comment, $inform)->save();

                    break;
                case 'pending_payment':
                    /**
                     * change order status to 'Pending'
                     */
                    $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $statusID, $comment, $inform)->save();

                    break;
                case 'processing':
                    /**
                     * change order status to 'Processing'
                     */
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $statusID, $comment, $inform)->save();

                    break;
                case 'closed':
                    /**
                     * change order status to 'Closed'
                     */
                    $order->setData('state', 'closed');
                    $order->setStatus('closed');

                    $order->addStatusToHistory($statusID, $comment, $inform);

                    $order->save();

                    break;
                case 'canceled':
                    /**
                     * change order status to 'Canceled'
                     */

                    $order->cancel();
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $statusID, $comment, $inform)->save();

                    break;
                case 'holded':
                    /**
                     * change order status to 'Holded'
                     */

                    $order->setState(Mage_Sales_Model_Order::STATE_HOLDED, $statusID, $comment, $inform)->save();

                    break;
            }

            $answer['name'] = $collection = Mage::getModel('sales/order_status')->load($statusID)->getData('label');
            $answer['date_added'] = $order->getStatusHistoryCollection()->getFirstItem()->getData('updated_at');
        }
        return $answer;
    }

    public function sendNotifications($order_id)
    {
        $devices = $this->getUserDevices();
        $ids = [];

        foreach ($devices->getData() as $device) {
            if (strtolower($device['os_type']) == 'ios') {
                $ids['ios'][] = $device['token'];
            } else {
                $ids['android'][] = $device['token'];
            }
        }

        $order = $this->getOrderByIdTmp($order_id);

        if (count($order)>0) {
            $msg = array(
                'body' => number_format($order['grand_total'], 2, '.', ''),
                'title' => "http://" . $_SERVER['HTTP_HOST'],
                'vibrate' => 1,
                'sound' => 1,
                'badge'      => 1,
                'priority' => 'high',
                'new_order' => [
                    'order_id' => $order_id,
                    'total' => number_format($order['grand_total'], 2, '.', ''),
                    'currency_code' => $order['base_currency_code'],
                    'site_url' => "http://" . $_SERVER['HTTP_HOST'],
                ],
                'event_type' => 'new_order'
            );

            $msg_android = array(

                'new_order' => [
                    'order_id' => $order_id,
                    'total' => number_format($order['grand_total'], 2, '.', ''),
                    'currency_code' => $order['base_currency_code'],
                    'site_url' => "http://" . $_SERVER['HTTP_HOST'],
                ],
                'event_type' => 'new_order'
            );

            foreach ($ids as $k => $mas):
                if ($k == 'ios') {
                    $fields = array
                    (
                        'registration_ids' => $ids[$k],
                        'notification' => $msg,
                    );
                } else {
                    $fields = array
                    (
                        'registration_ids' => $ids[$k],
                        'data' => $msg_android
                    );
                }
                $this->sendCurl($fields);

            endforeach;
        }
    }

    private function sendCurl($fields)
    {
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

    private function getOrderByIdTmp($order_id)
    {
        $orderCollection = Mage::getModel('sales/order')
            ->load($order_id);

        return $orderCollection->getData();
    }
}