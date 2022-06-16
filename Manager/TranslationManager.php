<?php

declare(strict_types=1);

namespace Oro\Bundle\AkeneoBundle\Manager;

use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager as BaseTranslationManager;

class TranslationManager extends BaseTranslationManager
{

    /**
     * Update existing translation value or create new one if it does not exist
     *
     * @param string $key
     * @param string $value
     * @param string $locale
     * @param string $domain
     * @param int $scope
     *
     * @return Translation|null
     */
    public function saveOrUpdateTranslation(
        $key,
        $value,
        $locale,
        $domain = self::DEFAULT_DOMAIN,
        $scope = Translation::SCOPE_SYSTEM
    ) {
        /** @var TranslationRepository $repo */
        $repo = $this->getEntityRepository(Translation::class);

        $this->findTranslationKey($key, $domain);

        $translation = $repo->findTranslation($key, $locale, $domain);
        if (!$this->canUpdateTranslation($scope, $translation)) {
            return null;
        }

        $cacheKey = $this->getCacheKey($locale, $domain, $key);

        if ((null === $value && null !== $translation) || (null !== $value && null !== $translation)) {
            $translation->setValue($value);
            $this->translations[$cacheKey] = $translation;
            return null;
        }

        if (null !== $value && null === $translation) {
            $translation = array_key_exists($cacheKey, $this->translations)
                ? $this->translations[$cacheKey]
                : $this->createTranslation($key, $value, $locale, $domain);
        }

        if (null !== $translation) {
            $this->translations[$cacheKey] = $translation->setValue($value)->setScope($scope);
        }

        return $translation;
    }

    /**
     * @param string $locale
     * @param string $domain
     * @param string $key
     * @return string
     */
    private function getCacheKey($locale, $domain, $key)
    {
        return sprintf('%s-%s-%s', $locale, $domain, $key);
    }
}
