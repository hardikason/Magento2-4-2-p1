<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sonali\CatalogInventory\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Sonali\CatalogInventory\Helper\Data as ConfigHelper;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File as IoFile;
use Magento\Framework\File\Csv;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\Framework\App\CacheInterface;
use Magento\CatalogInventory\Model\Indexer\Stock as StockIndexer;

/**
 * Class CatalogInventoryImport
 * Update product quantity
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogInventoryImport
{
    /**
     * @var ConfigHelper
     */
    protected $_configHelper;
    /**
     * @var Filesystem
     */
    protected $_filesystem;
    /**
     * @var DriverFile
     */
    protected $_driverFile;
    /**
     * @var IoFile
     */
    protected $_ioFile;
    /**
     * @var Csv
     */
    protected $_csv;
    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistryInterface;
    /**
     * @var CacheTypeListInterface
     */
    protected $_cacheTypeListInterface;
    /**
     * @var CacheInterface
     */
    protected $_cacheInterface;
    /**
     * @var StockIndexer
     */
    protected $_stockIndexer;
    /**
     * @const FILE_EXTENSION
     */
    const FILE_EXTENSION = 'csv';
    /**
     * @const IMPORT_FOLDER
     */
    const IMPORT_FOLDER = 'cataloginventory/import/import_folder';
    /**
     * @const IMPORT_ARCHIVE_FOLDER
     */
    const IMPORT_ARCHIVE_FOLDER = 'cataloginventory/import/import_archive_folder';

    protected $_skuUpdated = [];

    /**
     * CatalogInventoryImport constructor.
     * @param ConfigHelper $configHelper
     * @param Filesystem $filesystem
     * @param DriverFile $driverFile
     * @param IoFile $ioFile
     * @param Csv $csv
     * @param StockRegistryInterface $stockRegistryInterface
     * @param CacheTypeListInterface $cacheTypeListInterface
     * @param CacheInterface $cacheInterface
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        ConfigHelper $configHelper,
        Filesystem $filesystem,
        DriverFile $driverFile,
        IoFile $ioFile,
        Csv $csv,
        StockRegistryInterface $stockRegistryInterface,
        CacheTypeListInterface $cacheTypeListInterface,
        CacheInterface $cacheInterface,
        StockIndexer $stockIndexer
    ) {
        $this->_configHelper = $configHelper;
        $this->_filesystem = $filesystem;
        $this->_driverFile = $driverFile;
        $this->_ioFile = $ioFile;
        $this->_csv = $csv;
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->_cacheTypeListInterface = $cacheTypeListInterface;
        $this->_cacheInterface = $cacheInterface;
        $this->_stockIndexer = $stockIndexer;
    }

    /**
     * Import csv and update sku's quantity
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function importCatalogInventory()
    {
        try {

            $import_folder = $this->_configHelper->getConfigValue(self::IMPORT_FOLDER);

            $destinationFolder = $this->_filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath()
                    . $import_folder;
            if (!$this->_driverFile->isDirectory($destinationFolder)) {
                $this->_configHelper->writeLog("Directory '" . $import_folder . "' not found.");
                return;
            }

            //read the directory
            $files = $this->_driverFile->readDirectory($destinationFolder);
            foreach ($files as $file) {
                if ($this->_driverFile->isDirectory($file)) {
                    continue;
                }

                //check  for .csv extension
                $fileExt = explode(".", $file);
                $fileExt = end($fileExt);
                if ($fileExt != self::FILE_EXTENSION) {
                    continue;
                }

                $fileName = explode('/', $file);
                $fileName = end($fileName);
                $this->_configHelper->writeLog($fileName.' is started for processing.');
                //get data from csv
                $csvData = $this->_csv->getData($file);
                foreach ($csvData as $row => $data) {
                    if ($row == 0) {
                        if (trim($data[0]) != 'sku' || trim($data[1]) != 'qty') {
                            $this->_configHelper->writeLog($fileName.' File should be with column sku and qty. '
                            .$fileName.' is not processed.');
                            break;
                        }
                    } elseif ($row > 0 && !empty($data) && count($data) == 2) {
                        $sku = trim($data[0]);
                        $qty = trim($data[1]);
                        // call for updating qty's
                        $this->updateCatalogInventory($sku, $qty, $fileName);
                    }
                }
                if (!empty($this->_skuUpdated[$fileName])) {
                    //Move proccessed files in archive folder
                    $archiveFolder = $this->_configHelper->getConfigValue(self::IMPORT_ARCHIVE_FOLDER);
                    $archiveFolderPath = $this->_filesystem->getDirectoryRead(DirectoryList::VAR_DIR)
                            ->getAbsolutePath() . $archiveFolder;

                    // Check if archive folder already present else create
                    $this->_ioFile->checkAndCreateFolder($archiveFolderPath);

                    /// To avoid replacing file if already exist in archive folder
                    $archivedFile = $archiveFolderPath . DIRECTORY_SEPARATOR . $fileName . '_' . date('YmdHis');
                    $this->_ioFile->mv($file, $archivedFile);
                    $this->_configHelper->writeLog($fileName.' has moved to archive.');
                }
            }

            //clear cache and indexing
            $this->clearCacheAndIndexing();

        } catch (\Exception $ex) {
            $this->_configHelper->writeLog("Exception from importCatalogInventory  for File '".$fileName."' : ".
                $ex->getMessage());
        }
    }

    /**
     * @param null $sku
     * @param null $qty
     * @param null $fileName
     */
    protected function updateCatalogInventory($sku = null, $qty = null, $fileName = null)
    {
        try {
            if ($sku) {
                $stockItem = $this->_stockRegistryInterface->getStockItemBySku($sku);
                if ($stockItem->getProductId()) {
                    $stockItem->setQty($qty);
                    $stockItem->setIsInStock((bool)$qty);
                    $stockItem->setManageStock(1);
                    $this->_stockRegistryInterface->updateStockItemBySku($sku, $stockItem);
                    $this->_skuUpdated[$fileName][$sku] = $qty;
                }
            }
        } catch (\Exception $ex) {
            $this->_configHelper->writeLog("Exception from updateCatalogInventory for File '".$fileName."' : ".
                $ex->getMessage());
        }
    }

    /**
     * clear cache and indexing
     * @return void
     */
    public function clearCacheAndIndexing()
    {
        try {
            if (!empty($this->_skuUpdated)) {
                $this->reIndexStock();
                $this->clearCache();
                $this->_configHelper->writeLog('Products updated : '.
                    $this->_configHelper->serialize($this->_skuUpdated));
                $this->_skuUpdated = [];
            } else {
                $this->_configHelper->writeLog('0 Products updated.');
            }
        } catch (\Exception $ex) {
            $this->_configHelper->writeLog("Exception from clearCacheAndIndexing ".$ex->getMessage());
        }
    }

    /**
     * Reindex Product Stock
     * @return void
     */
    protected function reIndexStock()
    {
        try {
            $this->_stockIndexer->executeFull();
            $this->_configHelper->writeLog('Indexing done.');
        } catch (\Exception $ex) {
            $this->_configHelper->writeLog('Indexer Exception : '. $ex->getMessage());
        }
    }

    /**
     * Clear Magento Cache for Category and Product Tags, Collections and Full Page Cache Types
     * @return void
     */
    protected function clearCache()
    {
        try {
            $this->_cacheInterface->clean([\Magento\Catalog\Model\Category::CACHE_TAG,
                                            \Magento\Catalog\Model\Product::CACHE_TAG]);
            $this->_cacheTypeListInterface->cleanType(\Magento\Framework\App\Cache\Type\Collection::TYPE_IDENTIFIER);
            $this->_cacheTypeListInterface->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
            $this->_configHelper->writeLog('Cache cleared.');
        } catch (\Exception $ex) {
            $this->_configHelper->writeLog('Cache Exception : '. $ex->getMessage());
        }
    }
}
