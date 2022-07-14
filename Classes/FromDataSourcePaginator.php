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
 * Paginator to generate items in pagination by calling the passed data source with the bucket content as argument.
 *
 * This helps trim down the used storage if a bucket is taking huge amounts of storage or large amounts of items are involved.
 * Another advantage is, that we always have current data since the result items are not cached, only the filter
 */
final class FromDataSourcePaginator extends AbstractPaginator
{
    const CACHE_IDENTIFIER = 'bucket_data_source_pagination';

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
    private array $paginatedItems = [];

    /**
     * @param DataSourceInterface $dataSource
     * @param array $bucketContent
     * @param string $bucketId
     * @param int $currentPageNumber
     * @param int $itemsPerPage
     * @throws NoSuchCacheException
     * @throws InvalidArgumentForHashGenerationException
     */
    public function __construct(
        DataSourceInterface $dataSource,
        array               $bucketContent = [],
        string              $bucketId = '',
        int                 $currentPageNumber = 1,
        int                 $itemsPerPage = 10
    )
    {
        $variableCache = GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_IDENTIFIER);

        if ($bucketId) {
            if (empty($bucketContent)) {
                // if no bucket content got passed, and we have a cache entry, retrieve from cache
                if ($variableCache->has($bucketId)) {
                    $bucketContent = $variableCache->get($bucketId);
                }
            } else {
                if (!$variableCache->has($bucketId)) {
                    // if no cache entry exists, but the cache key is explicitly defined, use the passed cache key
                    $variableCache->set($bucketId, $bucketContent);
                } else {
                    // check bucket for existence and for changed content, update if necessary
                    $generatedBucketId = $this->generateBucketId($bucketContent);
                    if ($generatedBucketId != $bucketId) {
                        $variableCache->set($bucketId, $bucketContent);
                    }
                }
            }
        } else {
            $bucketId = $this->generateBucketId($bucketContent);

            // add new bucket if we have no existing bucket
            if (!$variableCache->has($bucketId)) {
                $variableCache->set($bucketId, $bucketContent);
            }
        }

        $items = $dataSource->getItemsFromDataSource($bucketContent);

        $this->bucketId = $bucketId;
        $this->items = $items;
        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    /**
     * @param array $bucketContent
     * @return string
     * @throws InvalidArgumentForHashGenerationException
     */
    private function generateBucketId(array $bucketContent): string
    {
        $hashService = GeneralUtility::makeInstance(HashService::class);
        $serializedBucketContent = serialize($bucketContent);

        return $hashService->generateHmac($serializedBucketContent);
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
        return $variableCache->has($bucketId) ? $variableCache->get($bucketId) : [];
    }

    /**
     * @return string
     */
    public function getBucketId(): string
    {
        return $this->bucketId;
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
