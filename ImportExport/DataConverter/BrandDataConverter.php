<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\DataConverter;

use Oro\Bundle\AkeneoBundle\Entity\AkeneoSettings;
use Oro\Bundle\AkeneoBundle\ImportExport\AkeneoIntegrationTrait;
use Oro\Bundle\AkeneoBundle\Tools\Generator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Brand;

class BrandDataConverter implements DataConverterInterface, ContextAwareInterface
{
    use AkeneoIntegrationTrait;
    use LocalizationAwareTrait;

    private $mappedAttributes = [];

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var ConfigManager */
    private $configManager;

    /** @var EntityConfigManager */
    private $entityConfigManager;

    /** @var ContextInterface */
    protected $context;

    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FieldHelper $fieldHelper,
        EntityConfigManager $entityConfigManager,
        ConfigManager $configManager,
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->fieldHelper = $fieldHelper;
        $this->entityConfigManager = $entityConfigManager;
        $this->configManager = $configManager;
    }

    public function convertToExportFormat(array $exportedRecord, $skipNullValues = true)
    {
        return [];
    }

    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $record = [];
        $fields = $this->fieldHelper->getEntityFields(Brand::class, EntityFieldProvider::OPTION_WITH_RELATIONS);
        $fieldsByName = [];
        foreach ($fields as $field) {
            $fieldsByName[$field['name']] = $field;
        }

        $mappedAttributes = $this->getMappedAttributes();
        foreach ($mappedAttributes as $systemAttribute => $akeneoAttribute) {
            if (empty($importedRecord['values'][$akeneoAttribute])) {
                continue;
            }

            if (!array_key_exists($systemAttribute, $fieldsByName)) {
                continue;
            }

            $field = $fieldsByName[$systemAttribute];

            $value = $importedRecord['values'][$akeneoAttribute];

            $this->processValue($record, $field, $value);
        }

        $this->setNames($record, $importedRecord);

        $record['channel'] = ['id' => $this->context->getOption('channel')];
        $record['akeneo_code'] = $importedRecord['code'];

        return $record;
    }

    private function setNames(array &$record, array &$importedRecord): void
    {
        if (empty($record['names']['default'])) {
            $record['names']['default'] = [
                'fallback' => null,
                'string' => $importedRecord['code'],
            ];
        }
    }

    private function processValue(array &$importedRecord, array $field, array $value)
    {
        $importExportProvider = $this->entityConfigManager->getProvider('importexport');
        $importExportConfig = $importExportProvider->getConfig(Brand::class, $field['name']);

        $isLocalizable = in_array($field['type'], [RelationType::MANY_TO_MANY, RelationType::TO_MANY])
            && is_a($field['related_entity_name'], AbstractLocalizedFallbackValue::class, true);

        if ($isLocalizable) {
            $importedRecord[$field['name']] = $this->processRelationType(
                $value,
                $importExportConfig->get('fallback_field', false, 'text'),
                $this->getDefaultLocalization(),
                $this->getTransport()
            );

            return;
        }

        switch ($field['type']) {
            case 'enum':
                $importedRecord[$field['name']] = $this->processEnumType($value);
                break;
            case 'multiEnum':
                $importedRecord[$field['name']] = $this->processMultiEnumType($value);
                break;
            case 'file':
                $importedRecord[$field['name']] = $this->processFileType($value);
                break;
            case 'image':
                $importedRecord[$field['name']] = $this->processFileType($value);
                break;
            case 'multiFile':
                $importedRecord[$field['name']] = $this->processFileTypes($value);
                break;
            case 'multiImage':
                $importedRecord[$field['name']] = $this->processFileTypes($value);
                break;
            default:
                $importedRecord[$field['name']] = $this->processBasicType($value);
                break;
        }
    }

    private function processRelationType(
        array $value,
        string $fallbackField,
        Localization $defaultLocalization,
        AkeneoSettings $transport,
    ): array {
        $result = [];

        foreach ($value as $item) {
            if (null === $item['locale']) {
                $result['default'] = [
                    'fallback' => null,
                    $fallbackField => html_entity_decode($item['data']),
                ];

                continue;
            }

            foreach ($transport->getAkeneoLocales() as $akeneoLocale) {
                if ($akeneoLocale->getCode() !== $item['locale']) {
                    continue;
                }

                foreach ($this->getLocalizations($akeneoLocale->getLocale()) as $localization) {
                    $result[$localization->getName()] = [
                        'fallback' => null,
                        $fallbackField => html_entity_decode($item['data']),
                    ];
                }
            }
        }

        if (false === isset($result['default'])) {
            if (isset($result[$defaultLocalization->getName()])) {
                $result['default'] = $result[$defaultLocalization->getName()];
            } elseif (count($result) > 1) {
                $result['default'] = array_values($result)[0];
            }
        }

        return $result;
    }

    private function processEnumType(array $value): array
    {
        $item = array_shift($value);

        return [
            'id' => $this->prepareEnumId($item['data']),
        ];
    }

    private function processMultiEnumType(array $value): array
    {
        $ids = [];
        $result = [];

        foreach ($value as $item) {
            $ids = array_merge($ids, $item['data']);
        }

        foreach (array_unique($ids) as $data) {
            $result[] = [
                'id' => $this->prepareEnumId($data),
            ];
        }

        return $result;
    }

    private function prepareEnumId(?string $id): ?string
    {
        return $id !== null ? Generator::generateLabel($id) : null;
    }

    private function processFileType(array $value): array
    {
        $item = array_shift($value);

        return ['uri' => $this->getAttachmentPath($item['data'])];
    }

    private function processFileTypes(array $value): array
    {
        $items = array_shift($value);

        $paths = [];
        foreach ($items['data'] as $item) {
            $paths[] = ['uri' => $this->getAttachmentPath($item)];
        }

        return $paths;
    }

    protected function getAttachmentPath(string $code): string
    {
        return $code;
    }

    private function processBasicType(array $value)
    {
        $item = array_shift($value);

        return $item['data'];
    }

    private function getMappedAttributes(): array
    {
        if ($this->mappedAttributes) {
            return $this->mappedAttributes;
        }

        $brandMappings = trim(
            $this->getTransport()->getAkeneoBrandMapping() ?? AkeneoSettings::DEFAULT_BRAND_MAPPING,
            ';:'
        );

        if (!empty($brandMappings)) {
            $attributesMapping = explode(';', $brandMappings);
            foreach ($attributesMapping as $attributeMapping) {
                list($akeneoAttribute, $systemAttribute) = explode(':', $attributeMapping);
                if (!isset($akeneoAttribute, $systemAttribute)) {
                    continue;
                }

                $this->mappedAttributes[$systemAttribute] = $akeneoAttribute;
            }
        }

        return $this->mappedAttributes;
    }
}
