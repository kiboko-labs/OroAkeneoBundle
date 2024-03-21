<?php

namespace Oro\Bundle\AkeneoBundle\Client\Api;

use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\FileSystem\FileSystemInterface;
use Akeneo\Pim\ApiClient\Pagination\PageFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorFactoryInterface;

interface ApiAwareInterface
{
    public function setResourceClient(ResourceClientInterface $resourceClient): ApiAwareInterface;

    public function setPageFactory(PageFactoryInterface $pageFactory): ApiAwareInterface;

    public function setCursorFactory(ResourceCursorFactoryInterface $cursorFactory): ApiAwareInterface;

    public function setFileSystem(FileSystemInterface $fileSystem): ApiAwareInterface;
}
