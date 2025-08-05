<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\IntegrationBundle\ImportExport\Processor\StepExecutionAwareImportProcessor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

class ProductImageImportProcessor extends StepExecutionAwareImportProcessor implements ClosableInterface
{
    public function close()
    {
        if ($this->strategy instanceof ClosableInterface) {
            $this->strategy->close();
        }

        if ($this->dataConverter instanceof ClosableInterface) {
            $this->dataConverter->close();
        }
    }

    public function process($items)
    {
        $images = [];
        $product = null;
        foreach ($items as $image) {
            $this->context->setValue('rawItemData', $image);

            if ($this->dataConverter) {
                $image = $this->dataConverter->convertToImportFormat($image, false);
            }

            $this->context->setValue('itemData', $image);

            /** @var ProductImage $object */
            $object = $this->serializer->denormalize(
                $image,
                $this->getEntityName(),
                '',
                array_merge(
                    $this->context->getConfiguration(),
                    [
                        'entityName' => $this->getEntityName(),
                    ]
                )
            );

            if ($this->strategy) {
                $object = $this->strategy->process($object);
                if ($object) {
                    $product = $object->getProduct();
                    $images[$object->getImage()->getOriginalFilename()] = $object;
                }
            }
        }

        if (!$product) {
            return null;
        }

        return $this->mergeImages($product, $images);
    }

    /**
     * @param ProductImage[] $images
     */
    private function mergeImages(Product $product, array $images): Product
    {
        /** @var string|null $firstFilename */
        $firstFilename = $images[0]['filename'] ?? null;

        $incoming = [];
        foreach ($images as $data) {
            $incoming[$data->getImage()->getOriginalFilename()] = $data;
        }

        foreach ($product->getImages() as $image) {
            $file = $image->getImage();

            if (!$file || !is_a($file->getParentEntityClass(), ProductImage::class, true)) {
                $product->removeImage($image);
                continue;
            }

            $filename = $file->getOriginalFilename();

            if (!isset($incoming[$filename])) {
                $product->removeImage($image);
                continue;
            }

            $isFirst = ($filename === $firstFilename);

            if (!$isFirst && $this->hasType($image, ProductImageType::TYPE_MAIN)) {
                $image->removeType(ProductImageType::TYPE_MAIN);
            }
            if (!$isFirst && $this->hasType($image, ProductImageType::TYPE_LISTING)) {
                $image->removeType(ProductImageType::TYPE_LISTING);
            }

            if ($isFirst) {
                if (!$this->hasType($image, ProductImageType::TYPE_MAIN)) {
                    $image->addType(ProductImageType::TYPE_MAIN);
                }
                if (!$this->hasType($image, ProductImageType::TYPE_LISTING)) {
                    $image->addType(ProductImageType::TYPE_LISTING);
                }
            }

            unset($incoming[$filename]);
        }

        foreach ($incoming as $filename => $image) {
            $product->addImage($image);

            if ($filename === $firstFilename) {
                $image->addType(ProductImageType::TYPE_MAIN);
                $image->addType(ProductImageType::TYPE_LISTING);
            }
        }

        if ($firstFilename === null && $product->getImages()->first()
            && !$this->hasType($product->getImages()->first(), ProductImageType::TYPE_LISTING)) {
            $product->getImages()->first()->addType(ProductImageType::TYPE_LISTING);
        }

        return $product;
    }

    private function hasType(ProductImage $image, string $type): bool
    {
        foreach ($image->getTypes() as $imageType) {
            if ($imageType->getType() === $type) {
                return true;
            }
        }

        return false;
    }
}
