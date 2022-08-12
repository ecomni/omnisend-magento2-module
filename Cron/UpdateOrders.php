<?php

namespace Omnisend\Omnisend\Cron;

use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Omnisend\Omnisend\Helper\SearchCriteria\EntityInterface as EntitySearchCriteriaInterface;
use Omnisend\Omnisend\Model\Attribute\IsImported\AttributeUpdaterInterface;
use Omnisend\Omnisend\Model\Attribute\IsImported\ImportStatus;
use Omnisend\Omnisend\Model\Config\GeneralConfig;
use Omnisend\Omnisend\Model\EntityDataSender\Order as OrderDataSender;
use Omnisend\Omnisend\Model\ResponseRateManagerInterface;

class UpdateOrders
{
    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GeneralConfig
     */
    private $generalConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EntitySearchCriteriaInterface
     */
    private $entitySearchCriteria;

    /**
     * @var ResponseRateManagerInterface
     */
    private $responseRateManager;

    /**
     * @var OrderDataSender
     */
    private $orderDataSender;

    /**
     * @var AttributeUpdaterInterface
     */
    private $orderAttributeUpdater;

    /**
     * @var ImportStatus
     */
    private $importStatus;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        GeneralConfig $generalConfig,
        StoreManagerInterface $storeManager,
        EntitySearchCriteriaInterface $entitySearchCriteria,
        ResponseRateManagerInterface $responseRateManager,
        OrderDataSender $orderDataSender,
        AttributeUpdaterInterface $orderAttributeUpdater,
        ImportStatus $importStatus,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->generalConfig = $generalConfig;
        $this->storeManager = $storeManager;
        $this->entitySearchCriteria = $entitySearchCriteria;
        $this->responseRateManager = $responseRateManager;
        $this->orderDataSender = $orderDataSender;
        $this->orderAttributeUpdater = $orderAttributeUpdater;
        $this->importStatus = $importStatus;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    public function execute(?\Magento\Cron\Model\Schedule $schedule = null)
    {
        if (!$this->generalConfig->getIsCronSynchronizationEnabled()) {
            return;
        }

        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $isImported = 0;

            $searchCriteria = $this->entitySearchCriteria->getEntityInStoreByImportStatusSearchCriteria(
                $isImported,
                $storeId
            )->create();

            $orders = $this->orderRepository
                ->getList($searchCriteria)
                ->getItems();

            if ($schedule) {
                $schedule->setMessages(
                    $schedule->getMessages()
                    . sprintf('- Found %d orders for store %d', count($orders), $store->getId())
                    . "\n"
                );
            }

            if (!$this->sendOrders($orders, $storeId)) {
                if ($schedule) {
                    $schedule->setMessages($schedule->getMessages() . '- Rate limit hit' . "\n");
                }
                return;
            }
        }

        if ($schedule && !empty($orders)) {
            $schedule->setMessages($schedule->getMessages() . '- Done' . "\n");
        }
    }

    /**
     * @param OrderInterface[] $orders
     * @param $storeId
     * @return bool
     */
    public function sendOrders($orders, $storeId)
    {
        foreach ($orders as $order) {
            if (!$this->responseRateManager->check($storeId)) {
                return false;
            }

            $this->processOrder($order);
        }

        return true;
    }

    /**
     * @param OrderInterface $order
     */
    public function processOrder(OrderInterface $order)
    {
        $response = $this->orderDataSender->send($order);
        $isImported = $this->importStatus->getImportStatus($response);
        $this->orderAttributeUpdater->setIsImported($order->getEntityId(), $isImported);

        if (!$order->getOmnisendPostStatus() && $isImported) {
            $this->orderAttributeUpdater->setPostStatus($order->getEntityId(), 1);
        }
    }
}
