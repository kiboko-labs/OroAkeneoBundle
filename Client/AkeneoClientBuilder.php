<?php

namespace Oro\Bundle\AkeneoBundle\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Security\Authentication;
use Oro\Bundle\AkeneoBundle\Client\Api\ApiAwareInterface;

class AkeneoClientBuilder extends AkeneoPimClientBuilder
{
    /**
     * @var ApiAwareInterface[]
     */
    protected array $apiRegistry = [];

    public function __construct(?ApiAwareInterface ...$apis)
    {
        foreach ($apis as $api) {
            $this->addApi($api);
        }
    }

    /**
     * @return $this
     */
    public function setBaseUri(string $baseUri): AkeneoClientBuilder
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    public function addApi(ApiAwareInterface $api): void
    {
        $shortClass = (new \ReflectionClass($api))->getShortName();
        $this->apiRegistry[$shortClass] = $api;
    }

    protected function buildAuthenticatedClient(Authentication $authentication): AkeneoPimClientInterface
    {
        list($resourceClient, $pageFactory, $cursorFactory, $fileSystem) = parent::setUp($authentication);

        $client = new AkeneoClient(
            parent::buildAuthenticatedClient($authentication)
        );
        foreach ($this->apiRegistry as $key => $api) {
            $api->setResourceClient($resourceClient)
                ->setPageFactory($pageFactory)
                ->setCursorFactory($cursorFactory)
                ->setFileSystem($fileSystem)
            ;
            $client->addApi($key, $api);
        }

        return $client;
    }
}
