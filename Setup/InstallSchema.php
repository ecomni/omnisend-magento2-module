<?php

namespace Omnisend\Omnisend\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Omnisend\Omnisend\Api\Data\OmnisendContactInterface;
use Omnisend\Omnisend\Api\Data\OmnisendGuestSubscriberInterface;
use Omnisend\Omnisend\Api\Data\OmnisendOrderStatusInterface;
use Omnisend\Omnisend\Api\Data\OmnisendRateLimitInterface;
use Zend_Db_Exception;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->addAttributeToQuote($setup);
        $this->addAttributeToNewsletterSubscriber($setup);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addAttributeToQuote(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('quote'),
            'is_imported',
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => 'Is Imported',
                'required' => false,
                'default' => '0'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addAttributeToNewsletterSubscriber(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('newsletter_subscriber'),
            InstallData::IS_IMPORTED,
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => InstallData::IS_IMPORTED,
                'required' => false,
                'default' => '0'
            ]
        );
    }
}
