# Bucket Paginator
![GitHub](https://img.shields.io/github/license/dmind-gmbh/extension-bucket-pagination)

Paginator using the Bucket Pattern to allow paginating over POST form results

## Installation
It is recommended to install the extension using composer with `composer require dmind/bucket-pagination`

## Recommended
The [NumberedPaginator](https://github.com/georgringer/numbered_pagination) is recommended for easier pagination over the bucket paginator.

## Usage
Below are 2 example usages of each paginator in combination with the NumberedPaginator.

### BucketPaginator
You can pass items to save into a bucket, which can be retrieved again with the generated bucket ID.
It is also possible to update the items in the bucket, so you can either don't pass items if a bucket is passed to the request or pass items if there is a need to update the bucket (if f.e. changes happened to the results).  

An example usage:
```php
$itemsToBePaginated = ['apple', 'banana', 'strawberry', 'raspberry', 'pineapple'];
$filter = (new Filter())->setTitle('apple');
$itemsPerPage = 10;
$maximumLinks = 15;

$currentPage = $this->request->hasArgument('currentPage') ? (int)$this->request->getArgument('currentPage') : 1;
$currentBucketId = $this->request->hasArgument('bucketId') ? $this->request->getArgument('bucketId') : '';

$paginator = new BucketPaginator(
    // if you pass new/changed items while also passing a bucket ID
    // it'll update the items in the bucket
    $currentBucketId ? null : $itemsToBePaginated,
    // optional elements to store into the bucket (f.e. filter objects)
    // the currently passed elements will be ignored if no items are passed
    // and the optional arguments from the existing bucket will be used
    ['filter' => $filter],
    // either the current bucket ID or an empty string
    // it'll generate its own bucket ID if not set based on the items and optional elements
    $currentBucketId,
    // current page to display
    $currentPage,
    // amount of items per page
    $itemsPerPage
);

$pagination = new NumberedPagination($paginator, $maximumLinks);
```

You can retrieve the bucket contents and additional items freely during the template as well:
```html
<f:if condition="{pagination.paginator.additionalContent.filter}">
    You filtered for {pagination.paginator.additionalContent.filter.title}
    <br/>
    (Bucket ID: {pagination.paginator.bucketId})
    <br/>
</f:if>
<hr/>

<f:for each="{pagination.paginator.paginatedItems}" as="item">
    {item.title}
    <br/>
</f:for>
```

### DataSourcePaginator
Another option (f.e. due to storage reasons or to refresh results) would be to use the `DataSourcePaginator`
to generate the items we are paginating over from the bucket contents.
The advantages are smaller bucket sizes and automatically current results.

```php
$productRepository = GeneralUtility::makeInstance(ProductRepository::class);
$filter = (new Filter())->setTitle('apple');
$itemsPerPage = 10;
$maximumLinks = 15;

$currentPage = $this->request->hasArgument('currentPage') ? (int)$this->request->getArgument('currentPage') : 1;
$currentBucketId = $this->request->hasArgument('bucketId') ? $this->request->getArgument('bucketId') : '';

$paginator = new DataSourcePaginator(
    // the data source which implements the \Dmind\BucketPagination\DataSourceInterface
    // to retrieve the items from based on the bucket contents
    $productRepository,
    // bucket content which will be passed to the DataSource
    // if you pass new/changed arguments while also passing a bucket ID
    // it'll update the items in the bucket
    $currentBucketId ? []: ['filter' => $filter],
    // either the current bucket ID or an empty string
    // it'll generate its own bucket ID if not set based on the items and optional elements
    $currentBucketId,
    // current page to display
    $currentPage,
    // amount of items per page
    $itemsPerPage
);

$pagination = new NumberedPagination($paginator, $maximumLinks);
```

Here you can also retrieve the bucket content as well:
```html
<f:if condition="{pagination.paginator.bucketContent.filter}">
    You filtered for {pagination.paginator.bucketContent.filter.title}
    <br/>
    (Bucket ID: {pagination.paginator.bucketId})
    <br/>
</f:if>
<hr/>

<f:for each="{pagination.paginator.paginatedItems}" as="item">
    {item.title}
    <br/>
</f:for>
```

## License
This project is licensed under the GPL v3.0 - see the [LICENSE](LICENSE) file for details
