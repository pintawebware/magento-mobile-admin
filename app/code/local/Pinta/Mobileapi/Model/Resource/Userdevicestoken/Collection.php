<?php

class Pinta_Mobileapi_Model_Resource_Userdevicestoken_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('pintamobileapi/userdevicestoken');
    }

}