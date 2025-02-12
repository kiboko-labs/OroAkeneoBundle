<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Processor;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

class BuildVariantCacheProcessor implements ProcessorInterface
{
    use MemoryCacheProviderAwareTrait;

    /** @var array */
    private array $variants = [];

    public function process($item)
    {
        $this->updateVariants($item);

        return $item;
    }

    private function updateVariants(array $item): void
    {
        $sku = $item['sku'];

        if (!empty($item['family_variant'])) {
            if (isset($item['parent'], $this->variants[$sku])) {
                $parent = $item['parent'];
                foreach (array_keys($this->variants[$sku]) as $sku) {
                    $this->variants[$parent][$sku] = ['parent' => $parent, 'variant' => $sku];
                }
            }

            return;
        }

        if (empty($item['parent'])) {
            return;
        }

        $parent = $item['parent'];

        $this->variants[$parent][$sku] = ['parent' => $parent, 'variant' => $sku];
    }

    public function initialize(): void
    {
        $this->variants = [];
        $this->memoryCacheProvider->reset();
    }

    public function flush(): void
    {
        $this->memoryCacheProvider->get(
            'akeneo_variants',
            function () {
                return $this->variants;
            }
        );
        $this->variants = [];
    }
}
