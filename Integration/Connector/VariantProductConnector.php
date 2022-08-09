<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Placeholder\SchemaUpdateFilter;
use Oro\Bundle\AkeneoBundle\Settings\DataProvider\SyncProductsDataProvider;
use Oro\Bundle\AkeneoBundle\Tools\CacheProviderTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\OrderedConnectorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Integration product connector.
 */
class VariantProductConnector extends AbstractOroAkeneoConnector implements AllowedConnectorInterface, OrderedConnectorInterface
{
    use CacheProviderTrait;

    const IMPORT_JOB_NAME = 'akeneo_variant_product_import';
    const PAGE_SIZE = 100;
    const TYPE = 'variant';

    /**
     * @var SchemaUpdateFilter
     */
    protected $schemaUpdateFilter;

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.akeneo.connector.product_variant.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return Product::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses): bool
    {
        return !$this->needToUpdateSchema($integration) && $integration->getTransport()->getSyncProducts() === SyncProductsDataProvider::PUBLISHED;
    }

    public function setSchemaUpdateFilter(SchemaUpdateFilter $schemaUpdateFilter): void
    {
        $this->schemaUpdateFilter = $schemaUpdateFilter;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $iterator = new \AppendIterator();
        $iterator->append($this->transport->getProductsForVariants(self::PAGE_SIZE, $this->getLastSyncDate()));

        return $iterator;
    }

    /**
     * Checks if schema is changed and need to update it.
     */
    private function needToUpdateSchema(Channel $integration): bool
    {
        return $this->schemaUpdateFilter->isApplicable($integration, Product::class);
    }

    public function getOrder()
    {
        return 7;
    }
}
