<?php

declare(strict_types=1);

namespace Dmind\BucketPagination;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;

/**
 * Paginator where items stored in the cache and retrieved based on the passed bucket ID.
 *
 * This helps to keep filter objects from POST applied. Additionally, the filter can be saved in the additionalContent field.
 */
final class BucketPaginator extends AbstractPaginator
{
    const CACHE_IDENTIFIER = 'bucket_pagination';

    /**
     * @var string
     */
    private string $bucketId;

    /**
     * @var array
     */
    private array $items;

    /**
     * @var array
     */
    private array $additionalContent;

    /**
     * @var array
     */
    private array $paginatedItems = [];

    /**
     * @param array|null $items
     * @param array $additionalContent
     * @param string $bucketId
     * @param int $currentPageNumber
     * @param int $itemsPerPage
     * @throws InvalidArgumentForHashGenerationException
     * @throws NoSuchCacheException
     */
    public function __construct(
        ?array $items,
        array  $additionalContent = [],
        string $bucketId = '',
        int    $currentPageNumber = 1,
        int    $itemsPerPage = 10
    )
    {
        $variableCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_IDENTIFIER);

        if ($bucketId) {
            if (empty($items)) {
                // retrieve items by bucket ID since no items got passed
                if ($variableCache->has($bucketId)) {
                    $existingBucket = $variableCache->get($bucketId);
                    $items = $existingBucket['bucket_content'];
                    $additionalContent = $existingBucket['bucket_additional_content'];
                } else {
                    $items = [];
                }
            } else {
                // if no cache entry exists, but the cache key is explicitly defined, use the passed cache key
                if (!$variableCache->has($bucketId)) {
                    $variableCache->set($bucketId, [
                        'bucket_content' => $items,
                        'bucket_additional_content' => $additionalContent,
                    ]);
                } else {
                    // check bucket for existence and for changed content, update if necessary
                    $generatedBucketId = $this->generateBucketId($items, $additionalContent);
                    if ($generatedBucketId != $bucketId) {
                        $variableCache->set($bucketId, [
                            'bucket_content' => $items,
                            'bucket_additional_content' => $additionalContent,
                        ]);
                    }
                }
            }
        }

        if (is_array($items) && !$bucketId) {
            $bucketId = $this->generateBucketId($items, $additionalContent);

            // add new bucket if we have no existing bucket
            if (!$variableCache->has($bucketId)) {
                $variableCache->set($bucketId, [
                    'bucket_content' => $items,
                    'bucket_additional_content' => $additionalContent,
                ]);
            }
        }

        // don't break the pagination if f.e. no items got passed but the passed bucket didn't exist either
        if (!is_array($items)) {
            $items = [];
        }

        // don't break if structure changed and content couldn't be serialized anymore
        if (!is_array($additionalContent)) {
            $additionalContent = [];
        }

        $this->bucketId = $bucketId;
        $this->items = $items;
        $this->additionalContent = $additionalContent;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @param array $items
     * @param array $additionalContent
     * @return string
     * @throws InvalidArgumentForHashGenerationException
     */
    private function generateBucketId(array $items, array $additionalContent = []): string
    {
        $hashService = GeneralUtility::makeInstance(HashService::class);
        $serializedBucketContent = serialize($items);
        $serializedBucketAdditionalContent = serialize($additionalContent);

        return $hashService->generateHmac($serializedBucketContent . $serializedBucketAdditionalContent);
    }

    /**
     * check if bucket exists
     *
     * @param string $bucketId
     * @return bool
     * @throws NoSuchCacheException
     */
    public static function hasBucket(string $bucketId): bool
    {
        $variableCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_IDENTIFIER);
        return $variableCache->has($bucketId);
    }

    /**
     * option to retrieve the items of a saved bucket without generating the paginator first
     *
     * @param string $bucketId
     * @return array
     * @throws NoSuchCacheException
     */
    public static function getBucketContentsById(string $bucketId): array
    {
        $variableCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_IDENTIFIER);
        return $variableCache->has($bucketId) ? $variableCache->get($bucketId)['bucket_content'] : [];
    }

    /**
     * option to retrieve the additional content of a saved bucket without generating the paginator first
     *
     * @param string $bucketId
     * @return array
     * @throws NoSuchCacheException
     */
    public static function getBucketAdditionalContentsById(string $bucketId): array
    {
        $variableCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_IDENTIFIER);
        return $variableCache->has($bucketId) ? $variableCache->get($bucketId)['bucket_additional_content'] : [];
    }

    /**
     * @return string
     */
    public function getBucketId(): string
    {
        return $this->bucketId;
    }

    /**
     * @return array
     */
    public function getAdditionalContent(): array
    {
        return $this->additionalContent;
    }

    /**
     * @return iterable|array
     */
    public function getPaginatedItems(): iterable
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = array_slice($this->items, $offset, $itemsPerPage);
    }

    protected function getTotalAmountOfItems(): int
    {
        return count($this->items);
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
