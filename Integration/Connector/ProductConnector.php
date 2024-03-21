<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Placeholder\SchemaUpdateFilter;
use Oro\Bundle\AkeneoBundle\Tools\CacheProviderTrait;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Log\LoggerAwareInterface;

/**
 * Integration product connector.
 */
class ProductConnector extends AbstractOroAkeneoConnector implements AllowedConnectorInterface
{
    use CacheProviderTrait;

    const IMPORT_JOB_NAME = 'akeneo_product_import';
    const PAGE_SIZE = 100;
    const TYPE = 'product';

    /**
     * @var SchemaUpdateFilter
     */
    protected $schemaUpdateFilter;

    public function getLabel(): string
    {
        return 'oro.akeneo.connector.product.label';
    }

    public function getImportEntityFQCN()
    {
        return Product::class;
    }

    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    public function getType()
    {
        return self::TYPE;
    }

    public function isAllowed(Channel $integration, array $processedConnectorsStatuses): bool
    {
        return !$this->needToUpdateSchema($integration);
    }

    public function setSchemaUpdateFilter(SchemaUpdateFilter $schemaUpdateFilter): void
    {
        $this->schemaUpdateFilter = $schemaUpdateFilter;
    }

    protected function getConnectorSource()
    {
        $items = $this->cacheProvider->fetch('akeneo')['items'] ?? [];
        if ($items) {
            return new \ArrayIterator();
        }

        $iterator = new \AppendIterator();
        $iterator->append($this->transport->getProducts(self::PAGE_SIZE, $this->getLastSyncDate()));
        $iterator->append($this->transport->getProductModels(self::PAGE_SIZE, $this->getLastSyncDate()));

        return $iterator;
    }

    /**
     * Checks if schema is changed and need to update it.
     */
    private function needToUpdateSchema(Channel $integration): bool
    {
        return $this->schemaUpdateFilter->isApplicable($integration, Product::class);
    }

    protected function initializeFromContext(ContextInterface $context)
    {
        $this->transport = $this->contextMediator->getTransport($context, true);
        $this->channel = $this->contextMediator->getChannel($context);

        $status = $this->getLastCompletedIntegrationStatus($this->channel, $this->getType());
        if ($status !== null) {
            $this->addStatusData(self::LAST_SYNC_KEY, $status->getData()[self::LAST_SYNC_KEY] ?? null);
        } else {
            $this->addStatusData(self::LAST_SYNC_KEY, null);
        }

        $this->validateConfiguration();
        $this->transport->init($this->channel->getTransport());
        $this->setSourceIterator($this->getConnectorSource());

        if ($this->getSourceIterator() instanceof LoggerAwareInterface) {
            $this->getSourceIterator()->setLogger($this->logger);
        }
    }

    public function supportsForceSync()
    {
        return true;
    }
}
