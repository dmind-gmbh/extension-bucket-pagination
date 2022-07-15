<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\Dmind\BucketPagination\BucketPaginator::CACHE_IDENTIFIER] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'options' => [
                // cache the bucket by default for 1 week
                'defaultLifetime' => 7 * 24 * 60 * 60,
            ]
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][\Dmind\BucketPagination\DataSourcePaginator::CACHE_IDENTIFIER] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'options' => [
                // cache the bucket by default for 1 week
                'defaultLifetime' => 7 * 24 * 60 * 60,
            ]
        ];
    }
);