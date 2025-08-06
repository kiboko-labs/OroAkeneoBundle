<?php

namespace Oro\Bundle\AkeneoBundle\ImportExport\Reader;

use Oro\Bundle\AkeneoBundle\ImportExport\AkeneoIntegrationTrait;
use Oro\Bundle\AkeneoBundle\Integration\AkeneoFileManager;
use Oro\Bundle\AkeneoBundle\Tools\CacheProviderTrait;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class ProductImageReader extends IteratorBasedReader
{
    use AkeneoIntegrationTrait;
    use CacheProviderTrait;

    /** @var array */
    private $attributesImageFilter = [];

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ContextInterface */
    protected $context;

    /** @var AkeneoFileManager */
    private $akeneoFileManager;

    public function setAkeneoFileManager(AkeneoFileManager $akeneoFileManager): void
    {
        $this->akeneoFileManager = $akeneoFileManager;
    }

    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    protected function initializeFromContext(ContextInterface $context)
    {
        $this->setImportExportContext($context);
        parent::initializeFromContext($context);

        $this->initAttributesImageList();

        $items = $this->cacheProvider->fetch('akeneo')['items'] ?? [];

        if (!empty($items)) {
            $this->processImagesDownload($items, $context);
        }

        $images = [];
        foreach ($items as $item) {
            foreach ($item['values'] as $code => $values) {
                if (empty($this->attributesImageFilter) || in_array($code, $this->attributesImageFilter)) {
                    foreach ($values as $value) {
                        if (empty($value['data'])) {
                            continue;
                        }

                        $imageAttributes = [
                            'pim_catalog_image',
                            'pim_assets_collection',
                            'pim_catalog_asset_collection',
                        ];
                        if (!in_array($value['type'], $imageAttributes)) {
                            continue;
                        }

                        foreach ((array) $value['data'] as $path) {
                            $sku = $item['sku'];

                            if (is_string($path)) {
                                $images[$sku][$path] = [
                                    'SKU' => $sku,
                                    'Name' => $path,
                                ];
                            } else {
                                $images[$sku][$path['data']] = [
                                    'SKU' => $sku,
                                    'Name' => $path['data'],
                                    'Order' => $path['order'] ?? null,
                                ];
                            }

                            if ($this->getTransport()->isAkeneoMergeImageToParent() && !empty($item['parent'])) {
                                $sku = $item['parent'];
                                if (is_string($sku)) {
                                    $images[$sku][$path] = [
                                        'SKU' => $sku,
                                        'Name' => $path,
                                    ];
                                } else {
                                    $images[$sku][$path]['data'] = [
                                        'SKU' => $sku,
                                        'Name' => $path['data'],
                                        'Order' => $path['order'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($images as &$sku) {
            $sku = array_values($sku);

            foreach ($sku as $i => &$img) {
                $img['_original_index'] = $i;
            }
            unset($img);

            usort($sku, function($a, $b) {
                $aOrder = $a['Order'] ?? PHP_INT_MAX;
                $bOrder = $b['Order'] ?? PHP_INT_MAX;

                if ($aOrder !== $bOrder) {
                    return $aOrder - $bOrder;
                }

                return $b['_original_index'] - $a['_original_index'];
            });

            foreach ($sku as &$img) {
                unset($img['_original_index']);
            }
            unset($img);
        }
        unset($sku);

        $this->stepExecution->setReadCount(0);

        $this->setSourceIterator(new \ArrayIterator($images));
    }

    protected function initAttributesImageList()
    {
        $this->attributesImageFilter = [];
        $list = $this->getTransport()->getAkeneoAttributesImageList();
        if (!empty($list)) {
            $this->attributesImageFilter = explode(';', $list);
        }
    }

    protected function processImagesDownload(array $items, ContextInterface $context)
    {
        $this->akeneoFileManager->initTransport($context);

        foreach ($items as $item) {
            foreach ($item['values'] as $code => $values) {
                if (empty($this->attributesImageFilter) || in_array($code, $this->attributesImageFilter)) {
                    foreach ($values as $value) {
                        if (empty($value['data'])) {
                            continue;
                        }

                        if (in_array($value['type'], ['pim_catalog_image'])) {
                            $this->akeneoFileManager->registerMediaFile($value['data']);
                        }

                        if (in_array($value['type'], ['pim_catalog_asset_collection'])) {
                            foreach ($value['data'] as $data) {
                                $this->akeneoFileManager->registerAssetMediaFile($data['data']);
                            }
                        }

                        if (in_array($value['type'], ['pim_assets_collection'])) {
                            if (!is_array($value['data'])) {
                                continue;
                            }

                            foreach ($value['data'] as $code => $file) {
                                $this->akeneoFileManager->registerAsset($code, $file);
                            }
                        }
                    }
                }
            }
        }
    }
}
