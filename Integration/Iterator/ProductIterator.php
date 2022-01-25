<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Iterator;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Oro\Bundle\AkeneoBundle\Integration\AkeneoPimExtendableClientInterface;
use Psr\Log\LoggerInterface;

class ProductIterator extends AbstractIterator
{
    private $attributes = [];

    private $familyVariants = [];

    private $measureFamilies = [];

    private $attributeMapping = [];

    /**
     * @var string|null
     */
    private $alternativeAttribute;

    private $assets = [];

    public function __construct(
        ResourceCursorInterface $resourceCursor,
        AkeneoPimExtendableClientInterface $client,
        LoggerInterface $logger,
        array $attributes = [],
        array $familyVariants = [],
        array $measureFamilies = [],
        array $attributeMapping = [],
        ?string $alternativeAttribute = null
    ) {
        parent::__construct($resourceCursor, $client, $logger);

        $this->attributes = $attributes;
        $this->familyVariants = $familyVariants;
        $this->measureFamilies = $measureFamilies;
        $this->attributeMapping = $attributeMapping;
        $this->alternativeAttribute = $alternativeAttribute;
    }

    /**
     * {@inheritdoc}
     */
    public function doCurrent()
    {
        $product = $this->resourceCursor->current();

        $this->setAlternativeIdentifier($product);
        $this->setSku($product);
        $this->setValueAttributeTypes($product);
        $this->setFamilyVariant($product);
        $this->setAssetCode($product);

        return $product;
    }

    /**
     * Switch the product code (intern identifier in Akeneo) value
     * with an other attribute to allow to map it differently
     */
    protected function setAlternativeIdentifier(array &$product)
    {
        if (null === $this->alternativeAttribute) return;

        @list($altAttribute, $identifier) = explode(':', $this->alternativeAttribute);

        if (!empty($altAttribute)
            && isset($product['values'][$altAttribute])
            && isset($product['identifier'])
        ) {

            if (isset($product['values'][$altAttribute][0]['data'])) {
                if (null !== $identifier) {
                    $product[$identifier] = $product['identifier'];
                }

                $product['identifier'] = $product['values'][$altAttribute][0]['data'];
            }
        }
    }

    /**
     * Set attribute types for product values.
     */
    protected function setValueAttributeTypes(array &$product)
    {
        foreach ($product['values'] as $code => $values) {
            if (isset($this->attributes[$code])) {
                foreach ($values as $key => $value) {
                    $product['values'][$code][$key]['type'] = $this->attributes[$code]['type'];

                    if (!isset($value['data']['unit'])) {
                        continue;
                    }

                    if (array_key_exists($value['data']['unit'], $this->measureFamilies)) {
                        $symbol = $this->measureFamilies[$value['data']['unit']];

                        $product['values'][$code][$key]['data']['symbol'] = $symbol;
                    }
                }
            } else {
                unset($product['values'][$code]);
            }
        }
    }

    /**
     * Set family variant from API.
     */
    private function setFamilyVariant(array &$model)
    {
        if (empty($model['family_variant'])) {
            return;
        }

        if (isset($this->familyVariants[$model['family_variant']])) {
            $model['family_variant'] = $this->familyVariants[$model['family_variant']];
        }
    }

    private function setAssetCode(array &$product): void
    {
        foreach ($product['values'] as $code => &$values) {
            foreach ($values as $key => &$value) {
                if ($value['type'] !== 'pim_assets_collection') {
                    continue;
                }

                $codes = [];
                foreach ((array)$value['data'] as &$code) {
                    if (array_key_exists($code, $this->assets)) {
                        $codes[$code] = $this->assets[$code];

                        continue;
                    }

                    $asset = $this->client->getAssetApi()->get($code);
                    if (!empty($asset['reference_files'][0]['code'])) {
                        $this->assets[$code] = $asset['reference_files'][0]['code'];

                        $codes[$code] = $this->assets[$code];
                    }
                }
                $value['data'] = $codes;
            }
        }
    }

    private function setSku(array &$product): void
    {
        $sku = $product['identifier'] ?? $product['code'];

        if (array_key_exists('sku', $this->attributeMapping)) {
            if (!empty($product['values'][$this->attributeMapping['sku']][0]['data'])) {
                $sku = $product['values'][$this->attributeMapping['sku']][0]['data'];
            }
        }

        $product['sku'] = (string)$sku;
    }
}
