<?php

namespace Omnisend\Omnisend\Helper\SearchCriteria;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Omnisend\Omnisend\Model\Config\GeneralConfig;
use Omnisend\Omnisend\Setup\InstallData;

class Entity implements EntityInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var GeneralConfig
     */
    private $generalConfig;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GeneralConfig $generalConfig,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->generalConfig = $generalConfig;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityInStoreByImportStatusSearchCriteria($isImported, $storeId): SearchCriteriaBuilder
    {
        return $this->searchCriteriaBuilder
            ->addFilter(InstallData::IS_IMPORTED, $isImported)
            ->addFilter('store_id', $storeId)
            ->addSortOrder(
                $this->sortOrderBuilder
                    ->setField('entity_id')
                    ->setDescendingDirection()
                    ->create()
            )
            ->setPageSize($this->generalConfig->getMaximumEntitiesPerCron());
    }
}
