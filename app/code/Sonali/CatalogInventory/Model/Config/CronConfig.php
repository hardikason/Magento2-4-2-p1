<?php

/**
 * Copyright  Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Sonali\CatalogInventory\Model\Config;

use Magento\Framework\Message\Manager;

/**
 * Class CronConfig
 * Cron Expression settings
 */
class CronConfig extends \Magento\Framework\App\Config\Value
{

    /** @const CRON string path */
    const CRON_IMPORT_STRING_PATH = 'crontab/default/jobs/cataloginventory_import/schedule/cron_expr';
    /** @const CRON model path */
    const CRON_IMPORT_MODEL_PATH = 'crontab/default/jobs/cataloginventory_import/run/model';
    /**
     * @const IMPORT_EXPRESSION
     */
    const IMPORT_EXPRESSION = 'groups/import/fields/import_cronexpr/value';
    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;
    /**
     * @var string
     */
    protected $_runModelPath = '';
    /**
     * @var Manager
     */
    protected $_messageManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param string $runModelPath
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Manager $messageManager,
        $runModelPath = '',
        array $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        $this->_messageManager = $messageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     * @throws \Exception
     */
    public function afterSave()
    {

        $importCronExpr = $this->getData(self::IMPORT_EXPRESSION);

        if (empty($importCronExpr)) {
            $this->_messageManager->addError('Invalid Format for Import Cron Expression');
            return parent::afterSave();
        }

        if ((substr_count($importCronExpr, ' ') == 4)) {

            try {
                // Set Cron Expr for Import
                $this->_configValueFactory->create()->load(
                    self::CRON_IMPORT_STRING_PATH,
                    'path'
                )->setValue(
                    $importCronExpr
                )->setPath(
                    self::CRON_IMPORT_STRING_PATH
                )->save();
                $this->_configValueFactory->create()->load(
                    self::CRON_IMPORT_MODEL_PATH,
                    'path'
                )->setValue(
                    $this->_runModelPath
                )->setPath(
                    self::CRON_IMPORT_MODEL_PATH
                )->save();

            } catch (\Exception $e) {
                $this->_messageManager->addError(__('We can\'t save the import cron expression.' . $e->getMessage()));
            }
        } else {
            $this->_messageManager->addError('Invalid Format for Cron Expression');
        }
        return parent::afterSave();
    }
}
