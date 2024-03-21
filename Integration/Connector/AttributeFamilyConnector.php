<?php

namespace Oro\Bundle\AkeneoBundle\Integration\Connector;

use Oro\Bundle\AkeneoBundle\Integration\AkeneoTransportInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;

/**
 * @property AkeneoTransportInterface $transport
 */
class AttributeFamilyConnector extends AbstractConnector
{
    const IMPORT_JOB_NAME = 'akeneo_attribute_family_import';

    public function getLabel(): string
    {
        return 'oro.akeneo.connector.attribute_family.label';
    }

    public function getImportEntityFQCN()
    {
        return AttributeFamily::class;
    }

    public function getImportJobName()
    {
        return self::IMPORT_JOB_NAME;
    }

    public function getType()
    {
        return 'attribute_family';
    }

    protected function getConnectorSource()
    {
        return $this->transport->getAttributeFamilies();
    }
}
