<?php

namespace Oro\Bundle\AkeneoBundle\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AssetApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetCategoryApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetApiInterface as AssetManagerApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetAttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetAttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetManager\AssetMediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetReferenceFileApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetTagApiInterface;
use Akeneo\Pim\ApiClient\Api\AssetVariationFileApiInterface;
use Akeneo\Pim\ApiClient\Api\AssociationTypeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeGroupApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use Akeneo\Pim\ApiClient\Api\ChannelApiInterface;
use Akeneo\Pim\ApiClient\Api\CurrencyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\LocaleApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasureFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MeasurementFamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\MediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelDraftApiInterface;
use Akeneo\Pim\ApiClient\Api\PublishedProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityAttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityMediaFileApiInterface;
use Akeneo\Pim\ApiClient\Api\ReferenceEntityRecordApiInterface;
use Oro\Bundle\AkeneoBundle\Client\Api\ApiAwareInterface;

class AkeneoClient implements AkeneoPimClientInterface
{
    protected AkeneoPimClientInterface $decoratedClient;

    /** @var ApiAwareInterface[] */
    protected array $apiRegistry = [];

    public function __construct(
        AkeneoPimClientInterface $decoratedClient
    ) {
        $this->decoratedClient = $decoratedClient;
    }

    public function addApi(string $key, ApiAwareInterface $api): void
    {
        $this->apiRegistry[$key] = $api;
    }

    public function get(string $name): ?ApiAwareInterface
    {
        return $this->apiRegistry[$name] ?? null;
    }

    public function __call($name, $arguments)
    {
        $property = lcfirst(substr($name, 3));
        if ('get' === substr($name, 0, 3) && isset($this->apiRegistry[$property])) {
            return $this->apiRegistry[$property];
        }

        return $this->decoratedClient->{$name}($arguments);
    }

    public function getToken(): ?string
    {
        return $this->decoratedClient->getToken();
    }

    public function getRefreshToken(): ?string
    {
        return $this->decoratedClient->getRefreshToken();
    }

    public function getProductApi(): ProductApiInterface
    {
        return $this->decoratedClient->getProductApi();
    }

    public function getCategoryApi(): CategoryApiInterface
    {
        return $this->decoratedClient->getCategoryApi();
    }

    public function getAttributeApi(): AttributeApiInterface
    {
        return $this->decoratedClient->getAttributeApi();
    }

    public function getAttributeOptionApi(): AttributeOptionApiInterface
    {
        return $this->decoratedClient->getAttributeOptionApi();
    }

    public function getAttributeGroupApi(): AttributeGroupApiInterface
    {
        return $this->decoratedClient->getAttributeGroupApi();
    }

    public function getFamilyApi(): FamilyApiInterface
    {
        return $this->decoratedClient->getFamilyApi();
    }

    public function getProductMediaFileApi(): MediaFileApiInterface
    {
        return $this->decoratedClient->getProductMediaFileApi();
    }

    public function getLocaleApi(): LocaleApiInterface
    {
        return $this->decoratedClient->getLocaleApi();
    }

    public function getChannelApi(): ChannelApiInterface
    {
        return $this->decoratedClient->getChannelApi();
    }

    public function getCurrencyApi(): CurrencyApiInterface
    {
        return $this->decoratedClient->getCurrencyApi();
    }

    public function getMeasureFamilyApi(): MeasureFamilyApiInterface
    {
        return $this->decoratedClient->getMeasureFamilyApi();
    }

    public function getAssociationTypeApi(): AssociationTypeApiInterface
    {
        return $this->decoratedClient->getAssociationTypeApi();
    }

    public function getFamilyVariantApi(): FamilyVariantApiInterface
    {
        return $this->decoratedClient->getFamilyVariantApi();
    }

    public function getProductModelApi(): ProductModelApiInterface
    {
        return $this->decoratedClient->getProductModelApi();
    }

    public function getPublishedProductApi(): PublishedProductApiInterface
    {
        return $this->decoratedClient->getPublishedProductApi();
    }

    public function getProductModelDraftApi(): ProductModelDraftApiInterface
    {
        return $this->decoratedClient->getProductModelDraftApi();
    }

    public function getProductDraftApi(): ProductDraftApiInterface
    {
        return $this->decoratedClient->getProductDraftApi();
    }

    public function getAssetApi(): AssetApiInterface
    {
        return $this->decoratedClient->getAssetApi();
    }

    public function getAssetCategoryApi(): AssetCategoryApiInterface
    {
        return $this->decoratedClient->getAssetCategoryApi();
    }

    public function getAssetTagApi(): AssetTagApiInterface
    {
        return $this->decoratedClient->getAssetTagApi();
    }

    public function getAssetReferenceFileApi(): AssetReferenceFileApiInterface
    {
        return $this->decoratedClient->getAssetReferenceFileApi();
    }

    public function getAssetVariationFileApi(): AssetVariationFileApiInterface
    {
        return $this->decoratedClient->getAssetVariationFileApi();
    }

    public function getReferenceEntityRecordApi(): ReferenceEntityRecordApiInterface
    {
        return $this->decoratedClient->getReferenceEntityRecordApi();
    }

    public function getReferenceEntityMediaFileApi(): ReferenceEntityMediaFileApiInterface
    {
        return $this->decoratedClient->getReferenceEntityMediaFileApi();
    }

    public function getReferenceEntityAttributeApi(): ReferenceEntityAttributeApiInterface
    {
        return $this->decoratedClient->getReferenceEntityAttributeApi();
    }

    public function getReferenceEntityAttributeOptionApi(): ReferenceEntityAttributeOptionApiInterface
    {
        return $this->decoratedClient->getReferenceEntityAttributeOptionApi();
    }

    public function getReferenceEntityApi(): ReferenceEntityApiInterface
    {
        return $this->decoratedClient->getReferenceEntityApi();
    }

    public function getAssetManagerApi(): AssetManagerApiInterface
    {
        return $this->decoratedClient->getAssetManagerApi();
    }

    public function getAssetFamilyApi(): AssetFamilyApiInterface
    {
        return $this->decoratedClient->getAssetFamilyApi();
    }

    public function getAssetAttributeApi(): AssetAttributeApiInterface
    {
        return $this->decoratedClient->getAssetAttributeApi();
    }

    public function getAssetAttributeOptionApi(): AssetAttributeOptionApiInterface
    {
        return $this->decoratedClient->getAssetAttributeOptionApi();
    }

    public function getAssetMediaFileApi(): AssetMediaFileApiInterface
    {
        return $this->decoratedClient->getAssetMediaFileApi();
    }

    public function getMeasurementFamilyApi(): MeasurementFamilyApiInterface
    {
        return $this->decoratedClient->getMeasurementFamilyApi();
    }
}
