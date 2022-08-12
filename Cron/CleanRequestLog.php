<?php

namespace Omnisend\Omnisend\Cron;

use Magento\Cron\Model\Schedule;

class CleanRequestLog
{
    const XML_PATH_CLEANUP_DAYS = 'omnisend_config/general/request_log_cleanup_days';

    /**
     * @var \Omnisend\Omnisend\Model\ResourceModel\OmnisendRequest
     */
    protected $resource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Omnisend\Omnisend\Model\ResourceModel\OmnisendRequest $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(?Schedule $schedule = null)
    {
        $days = $this->getConfiguredDays();
        if ($days > 0) {
            $affectedRows = $this->resource->clean($days);
            if ($schedule) {
                $schedule->setMessages(sprintf('Removed %d requests older than %d days', $affectedRows, $days));
            }
        }
    }

    public function getConfiguredDays(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_CLEANUP_DAYS);
    }
}
