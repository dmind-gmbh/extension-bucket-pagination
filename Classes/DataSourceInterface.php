<?php

declare(strict_types=1);

namespace Dmind\BucketPagination;

interface DataSourceInterface
{
    /**
     * retrieve items from a data source
     * which will return the items used in the pagination based on the passed bucket content (f.e. filter values)
     *
     * @param array $bucketContent
     * @return array
     */
    public function getItemsFromDataSource(array $bucketContent): array;
}
