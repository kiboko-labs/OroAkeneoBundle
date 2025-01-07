<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Processor;

use Oro\Bundle\AkeneoBundle\Tools\AttributeFamilyCodeGenerator;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareInterface;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareImportProcessor;

class AttributeFamilyImportProcessor extends StepExecutionAwareImportProcessor implements MemoryCacheProviderAwareInterface
{
    use MemoryCacheProviderAwareTrait;

    /** @var string */
    private $codePrefix;

    public function process($item)
    {
        if (!empty($item['code'])) {
            $code = AttributeFamilyCodeGenerator::generate($item['code'], $this->codePrefix);
            $this->memoryCacheProvider->get(
                'attribute_family_' . $code,
                function () use ($code) {
                    return $code;
                }
            );
        }

        return parent::process($item);
    }

    public function setCodePrefix(string $codePrefix): void
    {
        $this->codePrefix = $codePrefix;
    }
}
