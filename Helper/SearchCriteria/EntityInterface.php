<?php

namespace Omnisend\Omnisend\Helper\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;

interface EntityInterface
{
    /**
     * @param $isImported
     * @param $storeId
     * @return SearchCriteriaBuilder
     */
    public function getEntityInStoreByImportStatusSearchCriteria($isImported, $storeId): SearchCriteriaBuilder;
}
