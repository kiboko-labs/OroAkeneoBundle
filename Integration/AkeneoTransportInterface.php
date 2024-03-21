<?php

namespace Oro\Bundle\AkeneoBundle\Integration;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

interface AkeneoTransportInterface extends TransportInterface
{
    /**
     * @return array
     */
    public function getCurrencies();

    /**
     * @return array
     */
    public function getMergedCurrencies();

    public function setConfigProvider(CurrencyProviderInterface $configProvider);

    /**
     * @return array
     */
    public function getLocales();

    /**
     * @return array
     */
    public function getChannels();

    /**
     * @return \Iterator
     */
    public function getCategories(int $pageSize);

    /**
     * @return \Iterator
     */
    public function getAttributeFamilies();

    /**
     * @return \Iterator
     */
    public function getProducts(int $pageSize, ?\DateTime $updatedAt = null);

    /**
     * @return \Iterator
     */
    public function getProductsForVariants(int $pageSize, ?\DateTime $updatedAt = null);

    /**
     * @return \Iterator
     */
    public function getProductModels(int $pageSize, ?\DateTime $updatedAt = null);

    public function getProductsList(int $pageSize, ?int $sinceLastNDays = null, ?\DateTime $updatedAt = null): iterable;

    public function getProductModelsList(int $pageSize, ?int $sinceLastNDays = null, ?\DateTime $updatedAt = null): iterable;

    /**
     * @return \Iterator
     */
    public function getAttributes(int $pageSize);

    public function getBrands(): \Traversable;

    public function downloadAndSaveMediaFile(string $code): void;

    public function downloadAndSaveAsset(string $code, string $file): void;

    public function downloadAndSaveReferenceEntityMediaFile(string $code): void;

    public function downloadAndSaveAssetMediaFile(string $code): void;

    public function getAlternativeIdentifier(): ?string;
}
