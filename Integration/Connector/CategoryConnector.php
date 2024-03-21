<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Integration\AkeneoTransportInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

/**
 * @property AkeneoTransportInterface $transport
 */
class CategoryConnector extends AbstractConnector
{
    const IMPORT_JOB_NAME = 'akeneo_category_import';
    const PAGE_SIZE = 25;

    public function getLabel(): string
    {
        return 'oro.akeneo.connector.category.label';
    }

    public function getImportEntityFQCN()
    {
        return Category::class;
    }

    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    public function getType()
    {
        return 'category';
    }

    protected function getConnectorSource()
    {
        return $this->transport->getCategories(self::PAGE_SIZE);
    }
}
