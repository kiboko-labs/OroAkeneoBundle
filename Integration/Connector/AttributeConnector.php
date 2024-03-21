<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Integration\AkeneoTransportInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

/**
 * @property AkeneoTransportInterface $transport
 */
class AttributeConnector extends AbstractConnector
{
    const IMPORT_JOB_NAME = 'akeneo_attribute_import';
    const PAGE_SIZE = 25;

    public function getLabel(): string
    {
        return 'oro.akeneo.connector.attribute.label';
    }

    public function getImportEntityFQCN()
    {
        return FieldConfigModel::class;
    }

    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    public function getType()
    {
        return 'attribute';
    }

    protected function getConnectorSource()
    {
        return $this->transport->getAttributes(self::PAGE_SIZE);
    }
}
