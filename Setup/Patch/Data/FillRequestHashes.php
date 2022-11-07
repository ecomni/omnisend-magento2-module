<?php

namespace Omnisend\Omnisend\Setup\Patch\Data;

use Omnisend\Omnisend\Api\Data\OmnisendRequestInterface;

class FillRequestHashes implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    const FILL_LAST_REQUESTS = 2000;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var \Omnisend\Omnisend\Api\OmnisendRequestRepositoryInterface
     */
    protected $omnisendRequestRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var \Omnisend\Omnisend\Model\RequestService
     */
    protected $requestService;

    /**
     * @var \Omnisend\Omnisend\Model\RequestDataInterfaceFactory
     */
    protected $requestDataFactory;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Omnisend\Omnisend\Api\OmnisendRequestRepositoryInterface $omnisendRequestRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Omnisend\Omnisend\Model\RequestService $requestService,
        \Omnisend\Omnisend\Model\RequestDataInterfaceFactory $requestDataFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->omnisendRequestRepository = $omnisendRequestRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->requestService = $requestService;
        $this->requestDataFactory = $requestDataFactory;
    }

    /**
     * Fill the request hashes for the last 2000 requests
     *
     * @return FillRequestHashes
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OmnisendRequestInterface::HASH, true, 'null')
            ->addSortOrder(
                $this->sortOrderBuilder
                    ->setField(OmnisendRequestInterface::RECORD_ID)
                    ->setDescendingDirection()
                    ->create()
            )
            ->setPageSize(self::FILL_LAST_REQUESTS)
            ->create();

        $requests = $this->omnisendRequestRepository->getList($searchCriteria)
            ->getItems();

        foreach ($requests as $request) {
            $request->setHash($this->calculateRequestDataHash($request));
            $this->omnisendRequestRepository->save($request);
        }
        return $this;
    }

    protected function calculateRequestDataHash(OmnisendRequestInterface $request)
    {
        /** @var \Omnisend\Omnisend\Model\RequestDataInterface $requestData */
        $requestData = $this->requestDataFactory->create();
        $requestData->setUrl($request->getRequestUrl());
        $requestData->setBody($request->getRequestBody());
        $requestData->setStoreId($request->getStoreId());
        $requestData->setType($request->getRequestMethod());

        return $this->requestService->calculateRequestHashForRequestData($requestData);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
