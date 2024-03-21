<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Reader;

use Oro\Bundle\AkeneoBundle\Tools\CacheProviderTrait;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class ProductVariantReader extends IteratorBasedReader
{
    use CacheProviderTrait;

    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);

        $cache = $this->cacheProvider->fetch('product_variants');
        $variants = $cache !== false ? $cache : [];

        $this->stepExecution->setReadCount(count($variants));

        $this->setSourceIterator(new \ArrayIterator($variants));
    }
}
