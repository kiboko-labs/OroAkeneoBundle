<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Processor;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

class BuildVariantCacheProcessor implements ProcessorInterface
{
    use MemoryCacheProviderAwareTrait;

    public function process($item)
    {
        $this->updateVariants($item);

        return $item;
    }

    private function updateVariants(array &$item): void
    {
        $sku = $item['sku'];

        $variants = $this->memoryCacheProvider->get('product_variants') ?? [];
        if (!empty($item['family_variant'])) {
            if (isset($item['parent'], $variants[$sku])) {
                $parent = $item['parent'];
                foreach (array_keys($variants[$sku]) as $sku) {
                    $variants[$parent][$sku] = ['parent' => $parent, 'variant' => $sku];
                }
            }

            return;
        }

        if (empty($item['parent'])) {
            return;
        }

        $parent = $item['parent'];

        $variants[$parent][$sku] = ['parent' => $parent, 'variant' => $sku];
    }
}
