<?php

namespace AppBundle\Integration;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use DateTimeInterface;
use Iterator;
use Oro\Bundle\AkeneoBundle\Client\AkeneoClientFactory;
use Oro\Bundle\AkeneoBundle\Entity\AkeneoSettings;
use Oro\Bundle\AkeneoBundle\Integration\AkeneoSearchBuilder;
use Oro\Bundle\AkeneoBundle\Integration\AkeneoTransportInterface;
use Oro\Bundle\AkeneoBundle\Integration\Iterator\ProductIterator;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Psr\Log\LoggerInterface;

final class AkeneoTransport implements AkeneoTransportInterface
{
    /**
     * @var int
     */
    private const PAGE_SIZE = 100;

    private AkeneoTransportInterface $decorated;
    private AkeneoPimClientInterface $client;
    private AkeneoSettings $transportEntity;
    private AkeneoClientFactory $clientFactory;
    private AkeneoSearchBuilder $akeneoSearchBuilder;
    private array $referenceDataClassMapping = [];
    private array $additionalAttributes = [];
    private LoggerInterface $logger;
    private array $attributesWithNorms = [];
    private array $familyVariants = [];
    /**
     * @var string
     */
    private const CODE = 'code';

    /**
     * @param mixed[] $referenceDataClassMapping
     * @param mixed[] $additionalAttributes
     */
    public function __construct(
        AkeneoTransportInterface $decorated,
        AkeneoClientFactory $akeneoClientFactory,
        AkeneoSearchBuilder $akeneoSearchBuilder,
        array $referenceDataClassMapping,
        array $additionalAttributes,
        LoggerInterface $logger
    ) {
        $this->decorated = $decorated;
        $this->clientFactory = $akeneoClientFactory;
        $this->akeneoSearchBuilder = $akeneoSearchBuilder;
        $this->referenceDataClassMapping = $referenceDataClassMapping;
        $this->additionalAttributes = $additionalAttributes;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param \DateTime|\DateTimeImmutable|null $updatedAt
     */
    public function getProducts(int $pageSize, ?DateTimeInterface $updatedAt = null): ProductIterator
    {
        $this->initAttributesList();

        $searchFilters = $this->akeneoSearchBuilder->getFilters($this->transportEntity->getProductFilter());

        if ($this->transportEntity->getSyncProducts() === 'published') {
            $api = $this->client->getPublishedProductApi();
        } else {
            $api = $this->client->getProductApi();
        }

        return new ProductIterator(
            $api->all($pageSize,
                ['search' => $searchFilters, 'scope' => $this->transportEntity->getAkeneoActiveChannel()]),
            $this->client,
            $this->logger,
            $this->attributesWithNorms,
            [],
            [],
            [],
            $this->transportEntity->getAlternativeIdentifier()
        );
    }

    /**
     * @return mixed[]
     */
    private function arrayFromMappingSetting(): array
    {
        $configuration = $this->transportEntity->getChannel()->getMappingSettings()->offsetGet('norms');
        if (empty($configuration)) {
            return [];
        }

        return explode(';', $configuration);
    }

    /**
     * @return mixed[]
     */
    private function arrayFromAkeneoAttributesList(): array
    {
        $configuration = $this->transportEntity->getAkeneoAttributesList();
        if (empty($configuration)) {
            return [];
        }

        return explode(';', $configuration);
    }

    /**
     * @return mixed[]
     */
    private function arrayFromAkeneoAttributesImageList(): array
    {
        $configuration = $this->transportEntity->getAkeneoAttributesImageList();
        if (empty($configuration)) {
            return [];
        }

        return explode(';', $configuration);
    }

    private function initAttributesList(): void
    {
        if ($this->attributesWithNorms !== []) {
            return;
        }

        $attributeCodes = array_merge(
            [],
            $this->arrayFromMappingSetting(),
            $this->arrayFromAkeneoAttributesList(),
            $this->arrayFromAkeneoAttributesImageList(),
            $this->additionalAttributes,
            array_keys($this->referenceDataClassMapping)
        );
        foreach ($this->client->getAttributeApi()->all(self::PAGE_SIZE) as $attribute) {
            if (!in_array($attribute[self::CODE], $attributeCodes, true)) {
                continue;
            }

            $this->attributesWithNorms[$attribute[self::CODE]] = $attribute;
        }
    }

    protected function initFamilyVariants(): void
    {
        if (count($this->familyVariants) <= 0) {
            foreach ($this->client->getFamilyApi()->all(self::PAGE_SIZE) as $family) {
                foreach ($this->client->getFamilyVariantApi()->all($family[self::CODE], self::PAGE_SIZE) as $variant) {
                    $variant['family'] = $family[self::CODE];
                    $this->familyVariants[$variant[self::CODE]] = $variant;
                }
            }
        }
    }

    public function init(Transport $transportEntity, $tokensEnabled = true): void
    {
        $this->decorated->init($transportEntity, $tokensEnabled);
        $this->client = $this->clientFactory->getInstance($transportEntity, $tokensEnabled);
        $this->transportEntity = $transportEntity;
    }

    /**
     * @return mixed[]
     */
    public function getCurrencies(): array
    {
        return $this->decorated->getCurrencies();
    }

    /**
     * @return mixed[]
     */
    public function getMergedCurrencies(): array
    {
        return $this->decorated->getMergedCurrencies();
    }

    public function setConfigProvider(CurrencyProviderInterface $configProvider)
    {
        return $this->decorated->setConfigProvider($configProvider);
    }

    /**
     * @return mixed[]
     */
    public function getLocales(): array
    {
        return $this->decorated->getLocales();
    }

    /**
     * @return mixed[]
     */
    public function getChannels(): array
    {
        return $this->decorated->getChannels();
    }

    public function getCategories(int $pageSize): Iterator
    {
        return $this->decorated->getCategories($pageSize);
    }

    public function getAttributeFamilies(): Iterator
    {
        return $this->decorated->getAttributeFamilies();
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $updatedAt
     */
    public function getProductModels(int $pageSize, ?DateTimeInterface $updatedAt = null): ProductIterator
    {
        $this->initAttributesList();
        $this->initFamilyVariants();

        $searchFilters = $this->akeneoSearchBuilder->getFilters($this->transportEntity->getProductFilter());
        if (isset($searchFilters['completeness'])) {
            unset($searchFilters['completeness']);
        }

        return new ProductIterator(
            $this->client->getProductModelApi()->all(
                $pageSize,
                ['search' => $searchFilters, 'scope' => $this->transportEntity->getAkeneoActiveChannel()]
            ),
            $this->client,
            $this->logger,
            $this->attributesWithNorms,
            $this->familyVariants
        );
    }

    public function getAttributes(int $pageSize): Iterator
    {
        return $this->decorated->getAttributes($pageSize);
    }

    public function getLabel(): string
    {
        return $this->decorated->getLabel();
    }

    public function getSettingsFormType(): string
    {
        return $this->decorated->getSettingsFormType();
    }

    public function getSettingsEntityFQCN(): string
    {
        return $this->decorated->getSettingsEntityFQCN();
    }

    public function downloadAndSaveMediaFile($code): void
    {
        $this->decorated->downloadAndSaveMediaFile($code);
    }

    public function getBrands(): \Traversable
    {
        return $this->decorated->getBrands();
    }

    public function downloadAndSaveAsset(string $code, string $file): void
    {
        $this->decorated->downloadAndSaveAsset($code, $file);
    }

    public function downloadAndSaveReferenceEntityMediaFile(string $code): void
    {
        $this->decorated->downloadAndSaveMediaFile($code);
    }

    public function downloadAndSaveAssetMediaFile(string $code): void
    {
        $this->decorated->downloadAndSaveAssetMediaFile($code);
    }

    public function getProductsList(int $pageSize): iterable
    {
        return $this->decorated->getProductsList($pageSize);
    }

    public function getProductModelsList(int $pageSize): iterable
    {
        return $this->decorated->getProductModelsList($pageSize);
    }
}
