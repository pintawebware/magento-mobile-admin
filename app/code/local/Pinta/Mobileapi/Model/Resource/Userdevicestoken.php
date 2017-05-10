<?php
class Pinta_Mobileapi_Model_Resource_Userdevicestoken extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('pintamobileapi/table_mobileapiuserdevices', 'id');
    }

}