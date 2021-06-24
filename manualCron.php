<?php

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';
$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('frontend');

$object_manager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
$resource = $object_manager->get('Magento\Framework\App\ResourceConnection');
$fslau_connection = $resource->getConnection();
$dailyCron = $object_manager->get('Sonali\CatalogInventory\Cron\CatalogInventoryImport')->execute();
?>
