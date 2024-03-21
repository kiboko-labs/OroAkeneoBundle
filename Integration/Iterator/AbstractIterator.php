<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Iterator;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractIterator implements \Iterator
{
    use LoggerAwareTrait;

    const PAGE_SIZE = 100;

    /**
     * @var ResourceCursorInterface
     */
    protected $resourceCursor;

    /**
     * @var AkeneoPimClientInterface
     */
    protected $client;

    /**
     * AttributeIterator constructor.
     */
    public function __construct(
        ResourceCursorInterface $resourceCursor,
        AkeneoPimClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->resourceCursor = $resourceCursor;
        $this->client = $client;

        $this->setLogger($logger);
    }

    final public function current()
    {
        return $this->doCurrent();
    }

    /**
     * current() login.
     */
    abstract public function doCurrent();

    public function next()
    {
        $this->resourceCursor->next();
    }

    public function key()
    {
        return $this->resourceCursor->key();
    }

    public function valid()
    {
        return $this->resourceCursor->valid();
    }

    public function rewind()
    {
        $this->resourceCursor->rewind();
    }
}
