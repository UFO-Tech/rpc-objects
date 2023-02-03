<?php

namespace Ufo\RpcObject;


use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcJsonParseException;

class RpcBatchRequest
{
    /**
     * @var RpcRequest[]
     */
    private array $requestCollection = [];
    /**
     * @var RpcRequest[]
     */
    private array $readyToHandle = [];
    /**
     * @var RpcRequest[]
     */
    private array $waitForOtherResponse = [];

    private array $results = [];

    /**
     * @var RpcRequest[]
     */
    protected array $foreAsync = [];

    public static function fromJson(string $json): static
    {
        try {
            $data = json_decode($json, true);
            $collection = new static();
            foreach ($data as $requestArray) {
                $collection->addRequestObject(RpcRequest::fromArray($requestArray));
            }
            return $collection;

        } catch (\TypeError $e) {
            throw new RpcJsonParseException('Invalid json data', previous: $e);
        }
    }

    /**
     * @return RpcRequest[]
     */
    public function getCollection(): array
    {
        return $this->requestCollection;
    }

    /**
     * @return RpcRequest[]
     */
    public function &getReadyToHandle(): array
    {
        return $this->readyToHandle;
    }

    /**
     * @param RpcRequest $requestObject
     * @return $this
     */
    public function addRequestObject(RpcRequest $requestObject): static
    {
        $this->requestCollection[$requestObject->getId()] = $requestObject;

        $this->addToQueue($requestObject);
        return $this;
    }

    protected function addToQueue(RpcRequest $requestObject): static
    {
        $collectionName = $requestObject->hasRequire() ? 'waitForOtherResponse' : 'readyToHandle';
        $this->{$collectionName}[$requestObject->getId()] = $requestObject;
        
        if ($requestObject) {
            $this->foreAsync[$requestObject->getId()] = $requestObject;
        }
        
        return $this;
    }

    protected function changeQueue(RpcRequest $requestObject): static
    {
        unset($this->readyToHandle[$requestObject->getId()]);
        unset($this->waitForOtherResponse[$requestObject->getId()]);
        $this->addToQueue($requestObject);
        return $this;
    }

    public function addResult(array $result): static
    {
        $this->results[$result['id']] = $result;
        $this->refreshQueue($result['id']);
        return $this;
    }

    protected function refreshQueue(string|int $id)
    {
        foreach ($this->waitForOtherResponse as $queueId => $requestObject) {
            if ($requestObject->checkRequireId($id)) {
                foreach ($requestObject->getRequire() as $paramName => $rrrpfr) {
                    try {
                        if (!isset($this->results[$id]['result'])) {
                            throw new RpcBadRequestException(
                                sprintf(
                                    'The parent\'s request "%s" returned the error. I can\'t substitute values in the current request.',
                                    $id
                                )
                            );
                        }
                        if (!isset($this->results[$id]['result'][$rrrpfr->getResponseFieldName()])) {
                            throw new RpcBadRequestException(
                                sprintf(
                                    'The parent request "%s" does not have a "%s" field in the response. I can\'t substitute value "%s" in the current request.',
                                    $id, $rrrpfr->getResponseFieldName(), $paramName
                                )
                            );
                        }
                        if ($rrrpfr->getResponseId() != $id) {
                            continue;
                        }
                        
                        $newValue = $this->results[$id]['result'][$rrrpfr->getResponseFieldName()];
                        $requestObject->replaceRequestParam($paramName, $newValue);
                        $this->changeQueue($requestObject);
                    } catch (\Throwable $e) {
                        $requestObject->setError($e);
                    }
                }
            }
        }
    }

    /**
     * @param bool $withKeys
     * @return array
     */
    public function getResults(bool $withKeys = true): array
    {
        return $withKeys ? $this->results : array_values($this->results);
    }

    public function getUnprocessedRequests(): array
    {
        return $this->waitForOtherResponse;
    }

    /**
     * @return RpcRequest[]
     */
    public function provideUnprocessedRequests(): array
    {
        if (count($this->waitForOtherResponse) > 0) {
            array_walk($this->waitForOtherResponse, function ($unprocessedRequest, $key) {
                /**
                 * @var RpcRequest $unprocessedRequest
                 */
                if (!$unprocessedRequest->hasError()) {
                    $unprocessedRequest->setError(
                        new RpcBadRequestException(
                            sprintf(
                                'The parent\'s request "%s" is not found. I can\'t substitute values in the current request.',
                                $unprocessedRequest->getCurrentRequireId()
                            )
                        )
                    );
                }
            });
        }
        return $this->waitForOtherResponse;
    }

}
