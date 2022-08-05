<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductVariantProcessor implements ProcessorInterface, StepExecutionAwareInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var ImportStrategyHelper */
    private $strategyHelper;

    /** @var StepExecution */
    private $stepExecution;

    /** @var ContextRegistry */
    private $contextRegistry;

    /** @var TranslatorInterface */
    private $translator;

    /** @var int */
    private $organizationId;

    /** @var ObjectRepository */
    private $productRepository;

    public function __construct(
        ManagerRegistry $registry,
        ImportStrategyHelper $strategyHelper,
        ContextRegistry $contextRegistry,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->strategyHelper = $strategyHelper;
        $this->contextRegistry = $contextRegistry;
        $this->translator = $translator;
        $this->productRepository = $this->registry
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param mixed $items
     */
    public function process($items)
    {
        $parentSkus = array_column($items, 'parent');
        $variantSkus = array_values(array_column($items, 'variant'));

        $parentSku = reset($parentSkus);

        $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
        $context->setValue('rawItemData', ['configurable' => $parentSku, 'variants' => $variantSkus]);
        $context->setValue('itemData', ['configurable' => $parentSku, 'variants' => $variantSkus]);

        $objectManager = $this->registry->getManagerForClass(Product::class);

        $parentProduct = $this->findProductBySku($parentSku);
        if (!$parentProduct instanceof Product) {
            $context->incrementErrorEntriesCount();
            $errorMessages = [
                $this->translator->trans(
                    'oro.akeneo.validator.product_by_sku.not_found',
                    ['%sku%' => $parentSku],
                    'validators'
                ),
            ];
            $this->strategyHelper->addValidationErrors($errorMessages, $context);

            return null;
        }

        $variantSkusUppercase = array_map(
            function ($variantSku) {
                return mb_strtoupper($variantSku);
            },
            $variantSkus
        );

        $variantSkusUppercase = array_combine($variantSkusUppercase, $variantSkusUppercase);
        foreach ($parentProduct->getVariantLinks() as $variantLink) {
            $variantProduct = $variantLink->getProduct();
            if (!$variantSkusUppercase) {
                $parentProduct->removeVariantLink($variantLink);
                $variantProduct->setStatus(Product::STATUS_DISABLED);
                $objectManager->remove($variantLink);
                $context->incrementDeleteCount();

                continue;
            }

            if (!array_key_exists($variantProduct->getSkuUppercase(), $variantSkusUppercase)) {
                $parentProduct->removeVariantLink($variantLink);
                $variantProduct->setStatus(Product::STATUS_DISABLED);
                $objectManager->remove($variantLink);
                $context->incrementDeleteCount();

                continue;
            }

//            $variantProduct->setStatus(Product::STATUS_ENABLED);

            unset($variantSkusUppercase[$variantProduct->getSkuUppercase()]);
        }

        foreach ($variantSkusUppercase as $variantSku) {
            $variantProduct = $this->findProductBySku($variantSku);
            if (!$variantProduct instanceof Product) {
                $context->incrementErrorEntriesCount();

                $errorMessages = [
                    $this->translator->trans(
                        'oro.akeneo.validator.product_by_sku.not_found',
                        ['%sku%' => $variantSku],
                        'validators'
                    ),
                ];
                $this->strategyHelper->addValidationErrors($errorMessages, $context);

                continue;
            }

            $variantLink = new ProductVariantLink();
            $variantLink->setProduct($variantProduct);
            $variantLink->setParentProduct($parentProduct);

            $variantProduct->addParentVariantLink($variantLink);
            $parentProduct->addVariantLink($variantLink);

//            $variantProduct->setStatus(Product::STATUS_ENABLED);

            $context->incrementAddCount();

            $objectManager->persist($variantLink);
        }

        $validationErrors = $this->strategyHelper->validateEntity($parentProduct);
        if ($validationErrors) {
            $context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $context);

//            $objectManager->clear();

            $parentProduct = $this->findProductBySku($parentSku);
            if (!$parentProduct instanceof Product) {
                return null;
            }

            $parentProduct->setStatus(Product::STATUS_DISABLED);

            return $parentProduct;
        }

        if ($parentProduct->getVariantLinks()->isEmpty()) {
            $parentProduct->setStatus(Product::STATUS_DISABLED);

            $context->incrementErrorEntriesCount();
            $errorMessages = [
                $this->translator->trans(
                    'oro.akeneo.validator.product_variants.empty',
                    ['%sku%' => $parentSku],
                    'validators'
                ),
            ];
            $this->strategyHelper->addValidationErrors($errorMessages, $context);
        }

        $context->incrementUpdateCount();

        return $parentProduct;
    }

    private function findProductBySku(string $parentSku)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->productRepository->getBySkuQueryBuilder($parentSku);
        $qb->andWhere($qb->expr()->eq('product.organization', ':organization'))
            ->setParameter('organization', $this->getOrganizationId())
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function getOrganizationId(): ?int
    {
        if (!$this->organizationId) {
            $channelId = $this->stepExecution->getJobExecution()->getExecutionContext()->get('channel');
            if (!$channelId) {
                return null;
            }

            /** @var Channel $channel */
            $channel = $this->registry->getRepository(Channel::class)->find($channelId);
            if (!$channel) {
                return null;
            }

            $this->organizationId = $channel->getOrganization()->getId();
        }

        return $this->organizationId;
    }
}
