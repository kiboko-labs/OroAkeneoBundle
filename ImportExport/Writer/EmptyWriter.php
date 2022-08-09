<?php

declare(strict_types=1);

namespace Oro\Bundle\AkeneoBundle\ImportExport\Writer;

use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;

class EmptyWriter implements ItemWriterInterface
{
    public function write(array $items)
    {
        //we are building variants cache, so there is nothing to save
    }
}
