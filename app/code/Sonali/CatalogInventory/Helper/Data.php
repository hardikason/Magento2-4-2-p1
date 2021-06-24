<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sonali\CatalogInventory\Helper;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Data
 * Data helper class for config module
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var DirectoryList
     */
    protected $_dirList;
    /**
     * @var File
     */
    protected $_file;
    /**
     * @var SerializerInterface
     */
    protected $_serializerInterface;
    /**
     * default store code
     */
    const STORE_CODE = "default";
    /**
     * default website code
     */
    const WEBSITE_CODE = "base";
    /**
     * @const LOG_FILENAME
     */
    const LOG_FILENAME = 'cataloginventory/import/filename';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param DirectoryList $dirList
     * @param File $file
     * @param SerializerInterface $serializerInterface
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        DirectoryList $dirList,
        File $file,
        SerializerInterface $serializerInterface
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_dirList = $dirList;
        $this->_file = $file;
        $this->_serializerInterface = $serializerInterface;
    }

    /**
     * Get configuration status for AU only
     * @param String $path
     * @param String $scopeType
     * @param String $code
     * @return String
     */
    public function getConfigValue($path, $scopeType = '', $code = '')
    {
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $scopeCode = self::STORE_CODE;
        if ($scopeType == \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE && $code == self::WEBSITE_CODE) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            $scopeCode = self::WEBSITE_CODE;
        }
        return $this->_scopeConfig->getValue($path, $scope, $scopeCode);
    }

    /**
     * Write log to specific log file
     * @param String $message
     * @return void
     */
    public function writeLog($message)
    {
        $log_filename = $this->getConfigValue(self::LOG_FILENAME);
        $path = $this->_dirList->getPath('var') .DIRECTORY_SEPARATOR. $log_filename . '.log';
        $this->_file->filePutContents($path, date('Y-m-d H:i:s').': '.$message . "\r\n", FILE_APPEND);
    }

    /**
     * Serialize data
     * @param $data
     * @return mixed
     */
    public function serialize($data)
    {
        return $this->_serializerInterface->serialize($data);
    }
}
