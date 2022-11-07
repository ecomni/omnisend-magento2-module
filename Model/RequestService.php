<?php

namespace Omnisend\Omnisend\Model;

use Exception;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Manager;
use Omnisend\Omnisend\Api\Data\OmnisendRequestInterface;
use Omnisend\Omnisend\Api\Data\OmnisendRequestInterfaceFactory;
use Omnisend\Omnisend\Api\OmnisendRequestRepositoryInterface;
use Psr\Log\LoggerInterface;

class RequestService implements RequestServiceInterface
{
    const HTTP_RESPONSE_NOT_FOUND = '404';
    const HTTP_RESPONSE_BAD_REQUEST = '400';
    const HTTP_RESPONSE_UNAUTHORIZED = '401';
    const HTTP_RESPONSE_FORBIDDEN = '403';
    const HTTP_RESPONSE_NOT_ACCEPTABLE = '406';
    const HTTP_RESPONSE_TIMEOUT = '408';
    const HTTP_RESPONSE_UNPROCESSABLE_ENTITY = '422';
    const HTTP_RESPONSE_TOO_MANY_REQUESTS = '429';
    const HTTP_RESPONSE_INTERNAL_SERVER_ERROR = '503';

    const FAILED_RESPONSE_CODES = [
        self::HTTP_RESPONSE_NOT_FOUND,
        self::HTTP_RESPONSE_BAD_REQUEST,
        self::HTTP_RESPONSE_UNAUTHORIZED,
        self::HTTP_RESPONSE_FORBIDDEN,
        self::HTTP_RESPONSE_NOT_ACCEPTABLE,
        self::HTTP_RESPONSE_TIMEOUT,
        self::HTTP_RESPONSE_TOO_MANY_REQUESTS,
        self::HTTP_RESPONSE_UNPROCESSABLE_ENTITY,
        self::HTTP_RESPONSE_INTERNAL_SERVER_ERROR
    ];

    /**
     * @var string
     */
    private $eventPrefix = 'omnisend_request_service';

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseRateManagerInterface
     */
    private $responseRateManager;

    /**
     * @var OmnisendRequestRepositoryInterface
     */
    private $omnisendRequestRepository;

    /**
     * @var OmnisendRequestInterfaceFactory
     */
    private $omnisendRequestFactory;

    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @param ResponseFactory $responseFactory
     * @param LoggerInterface $logger
     * @param ResponseRateManagerInterface $responseRateManager
     * @param OmnisendRequestRepositoryInterface $omnisendRequestRepository
     * @param OmnisendRequestInterfaceFactory $omnisendRequestFactory
     * @param Manager $eventManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        ResponseFactory $responseFactory,
        LoggerInterface $logger,
        ResponseRateManagerInterface $responseRateManager,
        OmnisendRequestRepositoryInterface $omnisendRequestRepository,
        OmnisendRequestInterfaceFactory $omnisendRequestFactory,
        Manager $eventManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->responseRateManager = $responseRateManager;
        $this->omnisendRequestRepository = $omnisendRequestRepository;
        $this->omnisendRequestFactory = $omnisendRequestFactory;
        $this->eventManager = $eventManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param RequestDataInterface $requestData
     * @param string $entity
     * @return null|string
     */
    public function call(RequestDataInterface $requestData, $entity = 'all')
    {
        if ($this->isDuplicateRequest($requestData)) {
            $this->logger->debug(
                self::class . ":: Duplicate request: ",
                [
                    $requestData->getType(),
                    $requestData->getUrl(),
                    $requestData->getBody()
                ]
            );
            return null;
        }

        $curl = curl_init();
        $response = $this->responseFactory->create();

        curl_setopt_array($curl, [
            CURLOPT_URL => $requestData->getUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER => 1,
            CURLOPT_CUSTOMREQUEST => $requestData->getType(),
            CURLOPT_POSTFIELDS => $requestData->getBody(),
            CURLOPT_HTTPHEADER => $requestData->getHeader()
        ]);

        try {
            $responseData = curl_exec($curl);
            $this->logger->debug(self::class . " - Request Headers: ", $requestData->getHeader());
            $this->logger->debug(self::class . " - Request Url: ", [$requestData->getUrl()]);
            $this->logger->debug(self::class . " - Request Type: ", [$requestData->getType()]);
            $this->logger->debug(self::class . " - Request Body: ", [$requestData->getBody()]);

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            [$responseHeader, $responseBody] = explode("\r\n\r\n", $responseData, 2);
            $this->logger->debug(self::class . " - Response Header: ", [$responseHeader]);
            $this->logger->debug(self::class . " - Response Body: ", [$responseBody]);

            if (strpos($responseHeader, "100 Continue") !== false) {
                [$responseHeader, $responseBody] = explode("\r\n\r\n", $responseBody, 2);
            }

            $this->responseRateManager->update($responseHeader, $requestData->getStoreId());
            $response->setResponse($responseBody, $httpCode);
            $this->eventManager->dispatch(
                $this->eventPrefix . "_" . $entity . "_response",
                [
                    "header" => $responseHeader,
                    "body" => $responseBody
                ]
            );

            /** @var OmnisendRequestInterface $requestRecord */
            $requestRecord = $this->omnisendRequestFactory->create();
            $requestRecord
                ->setRequestUrl($requestData->getUrl())
                ->setRequestMethod($requestData->getType())
                ->setRequestBody($requestData->getBody())
                ->setResponseCode($httpCode)
                ->setStoreId($requestData->getStoreId())
                ->setHash($this->calculateRequestHashForRequestData($requestData))
                ->setResponseBody($responseBody);
            $this->omnisendRequestRepository->save($requestRecord);

            if (in_array($response->getResponseCode(), self::FAILED_RESPONSE_CODES)) {
                return $response->getResponseCode();
            }

            if ($response->hasError()) {
                return $response->getError();
            }

            return $response->getData();
        } catch (Exception $exception) {
            $this->logger->critical(
                $requestData->getUrl() . ' ' .
                $requestData->getType() . ' ' .
                $requestData->getBody() . ' ' .
                $exception->getMessage()
            );
        }

        return null;
    }

    /**
     * Calculate a hash for request data, used to find duplicate requests
     *
     * @param RequestDataInterface $requestData
     * @return string
     */
    public function calculateRequestHashForRequestData(RequestDataInterface $requestData): string
    {
        return substr(sha1(json_encode([
            (string)$requestData->getUrl(),
            (string)$requestData->getBody(),
            (string)$requestData->getType(),
            (int)$requestData->getStoreId()
        ])), 0, 16);
    }

    public function isDuplicateRequest(RequestDataInterface $requestData): bool
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OmnisendRequestInterface::HASH, $this->calculateRequestHashForRequestData($requestData))
                // Be a little more sure as we 'only' compared a hash of 16 chars. It doesn't affect the execution time.
                ->addFilter(OmnisendRequestInterface::REQUEST_BODY, $requestData->getBody())
                ->addFilter(OmnisendRequestInterface::REQUEST_URL, $requestData->getUrl())
                ->addFilter(OmnisendRequestInterface::REQUEST_METHOD, $requestData->getType())
                ->setPageSize(1)
                ->create();

            $count = $this->omnisendRequestRepository->getList($searchCriteria)->getTotalCount();
            return $count > 0;
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        }
    }
}
