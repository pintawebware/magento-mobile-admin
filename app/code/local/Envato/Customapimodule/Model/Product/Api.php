<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 03.04.17
 * Time: 14:23
 */
// app/code/local/Envato/Customapimodule/Model/Product/Api.php
class Envato_Customapimodule_Model_Product_Api extends Mage_Api_Model_Resource_Abstract
{
    public function items()
    {
        $arr_products=array();
        $products=Mage::getModel("catalog/product")
            ->getCollection()
            ->addAttributeToSelect('*')
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(5);

        foreach ($products as $product) {
            $arr_products[] = $product->toArray(array('entity_id', 'name'));
        }

        return $arr_products;
    }
}