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
            uasort($sku, static function ($a, $b) {
                $aOrder = $a['Order'] ?? null;
                $bOrder = $b['Order'] ?? null;

                if ($aOrder === null && $bOrder === null) {
                    return 0;
                }

                if ($aOrder === null) {
                    return 1;
                }
                if ($bOrder === null) {
                    return -1;
                }

                return $aOrder <=> $bOrder;
            });
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
