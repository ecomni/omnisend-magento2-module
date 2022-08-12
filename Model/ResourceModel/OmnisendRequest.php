<?php

namespace Omnisend\Omnisend\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OmnisendRequest extends AbstractDb
{
    const TABLE_NAME = 'omnisend_request';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            $this->getConnection()->getTableName(self::TABLE_NAME),
            'record_id'
        );
    }

    /**
     * Clean requests older than x days
     *
     * @param int $days
     * @return int  The number of removed requests
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function clean(int $days): int
    {
        if ($days < 0) {
            return 0;
        }
        $minDate = new \DateTime(sprintf('-%d days', $days));
        return (int)$this->getConnection()->delete(
            $this->getMainTable(),
            ['created_at < ?' => $minDate]
        );
    }
}
