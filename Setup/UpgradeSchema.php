<?php

namespace Omnisend\Omnisend\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Omnisend\Omnisend\Api\Data\OmnisendRequestInterface;
use Omnisend\Omnisend\Model\ResourceModel\OmnisendRequest;
use Omnisend\Omnisend\Model\ResourceModel\Subscriber;

class UpgradeSchema implements UpgradeSchemaInterface
{
    const OMNISEND_POST_STATUS = 'omnisend_post_status';
    const OMNISEND_POST_STATUS_LABEL = 'Is entity currently on Omnisend';
    const OMNISEND_PREVIOUS_SUBSCRIBER_STATUS = 'A flag used to keep state of the previous subscriber status';

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $this->addOmnisendPostStatusAttributeToQuote($setup);
            $this->addOmnisendPostStatusAttributeToOrder($setup);
            $this->addOmnisendSubscriberStatusAttributeToNewsletterSubscriber($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    protected function addOmnisendPostStatusAttributeToQuote(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('quote'),
            self::OMNISEND_POST_STATUS,
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => self::OMNISEND_POST_STATUS_LABEL,
                'required' => false,
                'default' => '0'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    protected function addOmnisendPostStatusAttributeToOrder(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            self::OMNISEND_POST_STATUS,
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => self::OMNISEND_POST_STATUS_LABEL,
                'required' => false,
                'default' => '0'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    protected function addOmnisendSubscriberStatusAttributeToNewsletterSubscriber(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('newsletter_subscriber'),
            Subscriber::OMNISEND_PREVIOUS_SUBSCRIBER_STATUS,
            [
                'type' => Table::TYPE_INTEGER,
                'comment' => self::OMNISEND_PREVIOUS_SUBSCRIBER_STATUS,
                'required' => false,
                'default' => null
            ]
        );
    }
}
