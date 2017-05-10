<?php

$installer = $this;
$tableMobileApi = $installer->getTable('pintamobileapi/table_mobileapi');
$tableMobileApiUserDevices = $installer->getTable('pintamobileapi/table_mobileapiuserdevices');
//die($tableMobileApi);

$installer->startSetup();

$installer->getConnection()->dropTable($tableMobileApi);
$table = $installer->getConnection()
    ->newTable($tableMobileApi)
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, '11', array(
        'identity'  => true,
        'nullable'  => false,
        'primary'   => true,
    ))
    ->addColumn('user_id', Varien_Db_Ddl_Table::TYPE_INTEGER, '11', array(
        'nullable'  => false,
    ))
    ->addColumn('token', Varien_Db_Ddl_Table::TYPE_TEXT, '255', array(
        'nullable'  => false,
    ));
$installer->getConnection()->createTable($table);


$installer->getConnection()->dropTable($tableMobileApiUserDevices);
$table = $installer->getConnection()
    ->newTable($tableMobileApiUserDevices)
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, '11', array(
        'identity'  => true,
        'nullable'  => false,
        'primary'   => true,
    ))
    ->addColumn('user_id', Varien_Db_Ddl_Table::TYPE_INTEGER, '11', array(
        'nullable'  => false,
    ))
    ->addColumn('token', Varien_Db_Ddl_Table::TYPE_TEXT, '255', array(
        'nullable'  => false,
    ))
    ->addColumn('os_type', Varien_Db_Ddl_Table::TYPE_TEXT, '10', array(
        'nullable'  => false,
    ));
$installer->getConnection()->createTable($table);
$installer->endSetup();
