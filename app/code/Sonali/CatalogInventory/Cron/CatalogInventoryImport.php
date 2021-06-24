<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sonali\CatalogInventory\Cron;

use Sonali\CatalogInventory\Model\CatalogInventoryImport as CatalogInventoryImportModel;
use Sonali\CatalogInventory\Helper\Data as ConfigHelper;

/**
 * class CatalogInventoryImport
 * Cron Import with frequency
 */
class CatalogInventoryImport
{
    /**
     * @var CatalogInventoryImportModel
     */
    protected $_catalogInventoryImportModel;
    /**
     * @var ConfigHelper
     */
    protected $_configHelper;
    /**
     * @const IMPORT_ENABLE
     */
    const IMPORT_ENABLE = 'cataloginventory/import/enable';

    /**
     * @param CatalogInventoryImportModel $catalogInventoryImportModel
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        CatalogInventoryImportModel $catalogInventoryImportModel,
        ConfigHelper $configHelper
    ) {
        $this->_catalogInventoryImportModel = $catalogInventoryImportModel;
        $this->_configHelper = $configHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        try {

            $isCronEnabled = $this->_configHelper
                ->getConfigValue(self::IMPORT_ENABLE);
            // Check if cron enabled
            if (!$isCronEnabled) {
                $this->_configHelper->writeLog('Import Cron will not executed due to disable status.');
                return $this;
            }
            // call model function to import csv for update qty
            $this->_configHelper->writeLog('-------------------');
            $this->_configHelper->writeLog('Import Cron started to execute.');
            $this->_catalogInventoryImportModel->importCatalogInventory();
            $this->_configHelper->writeLog('Import Cron has been successfully executed.');

            return $this;
        } catch (\Exception $e) {
            $this->_configHelper->writeLog($e->getMessage());
            return $this;
        }
    }
}
