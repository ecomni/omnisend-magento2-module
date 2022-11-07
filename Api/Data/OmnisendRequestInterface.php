<?php

namespace Omnisend\Omnisend\Api\Data;

interface OmnisendRequestInterface
{

    const RECORD_ID = 'record_id';
    const REQUEST_URL = 'request_url';
    const REQUEST_METHOD = 'request_method';
    const REQUEST_BODY = 'request_body';
    const STORE_ID = 'store_id';
    const RESPONSE_CODE = 'response_code';
    const RESPONSE_BODY = 'response_body';
    const CREATED_AT = 'created_at';
    const HASH = 'hash';
    const EXECUTION_TIME = 'execution_time';

    /**
     * Get entity_id
     * @return string|null
     */
    public function getId();

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setId($entityId);

    /**
     * Get
     * @return string|null
     */
    public function getRequestUrl();

    /**
     * Set
     * @param string $url
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setRequestUrl($url);

    /**
     * Get
     * @return string|null
     */
    public function getRequestMethod();

    /**
     * Set
     * @param string $method
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setRequestMethod($method);

    /**
     * Get
     * @return string|null
     */
    public function getRequestBody();

    /**
     * Set
     * @param string $body
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setRequestBody($body);

    /**
     * Get
     * @return int|null
     */
    public function getStoreId();

    /**
     * Set
     * @param int $id
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setStoreId($id);

    /**
     * Get
     * @return string|null
     */
    public function getResponseCode();

    /**
     * Set
     * @param string $code
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setResponseCode($code);

    /**
     * Get
     * @return string|null
     */
    public function getResponseBody();

    /**
     * Set
     * @param string $body
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setResponseBody($body);

    /**
     * Get created at
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get hash
     * @return string|null
     */
    public function getHash(): ?string;

    /**
     * Set hash
     * @param string $hash
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setHash(?string $hash): OmnisendRequestInterface;

    /**
     * Get execution time
     * @return int|null
     */
    public function getExecutionTime(): ?int;

    /**
     * Set execution time
     * @param int|null $executionTime
     * @return \Omnisend\Omnisend\Api\Data\OmnisendRequestInterface
     */
    public function setExecutiontime(?int $executionTime): OmnisendRequestInterface;
}
