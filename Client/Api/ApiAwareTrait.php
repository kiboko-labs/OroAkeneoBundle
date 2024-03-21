<?php

namespace Oro\Bundle\AkeneoBundle\Client\Api;

use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\FileSystem\FileSystemInterface;
use Akeneo\Pim\ApiClient\Pagination\PageFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorFactoryInterface;

trait ApiAwareTrait
{
    /** @var ResourceClientInterface */
    protected $resourceClient;

    /** @var PageFactoryInterface */
    protected $pageFactory;

    /** @var ResourceCursorFactoryInterface */
    protected $cursorFactory;

    /** @var FileSystemInterface */
    protected $fileSystem;

    public function setResourceClient(ResourceClientInterface $resourceClient): ApiAwareInterface
    {
        $this->resourceClient = $resourceClient;

        return $this;
    }

    public function setPageFactory(PageFactoryInterface $pageFactory): ApiAwareInterface
    {
        $this->pageFactory = $pageFactory;

        return $this;
    }

    public function setCursorFactory(ResourceCursorFactoryInterface $cursorFactory): ApiAwareInterface
    {
        $this->cursorFactory = $cursorFactory;

        return $this;
    }

    public function setFileSystem(FileSystemInterface $fileSystem): ApiAwareInterface
    {
        $this->fileSystem = $fileSystem;

        return $this;
    }
}
