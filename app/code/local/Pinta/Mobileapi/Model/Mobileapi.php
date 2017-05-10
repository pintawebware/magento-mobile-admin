<?php

class Pinta_Mobileapi_Model_Mobileapi extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('pintamobileapi/mobileapi');
    }

}