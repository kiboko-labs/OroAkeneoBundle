<?php

namespace Oro\Bundle\AkeneoBundle\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Oro\Bundle\AkeneoBundle\Encoder\Crypter;
use Oro\Bundle\AkeneoBundle\Entity\AkeneoSettings;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Factory to create Akeneo PIM client instance.
 */
class AkeneoClientFactory
{
    /** @var Crypter */
    private $crypter;

    /** @var string */
    private $akeneoUrl;

    /** @var string */
    private $clientId;

    /** @var string */
    private $secret;

    /** @var string */
    private $userName;

    /** @var string */
    private $password;

    /** @var string */
    private $token;

    /** @var string */
    private $refreshToken;

    /** @var AkeneoPimClientInterface */
    private $client;

    /** @var AkeneoSettings */
    private $akeneoSettings;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @var AkeneoClientBuilder
     */
    private $clientBuilder;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        Crypter $crypter,
        AkeneoClientBuilder $clientBuilder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->crypter = $crypter;
        $this->clientBuilder = $clientBuilder;
    }

    public function getInstance(AkeneoSettings $akeneoSettings, bool $tokensEnabled = true): AkeneoPimClientInterface
    {
        $this->initProperties($akeneoSettings);

        if (
            $tokensEnabled
            && $akeneoSettings->getToken()
            && $akeneoSettings->getTokenExpiryDateTime()
            && $akeneoSettings->getTokenExpiryDateTime() > new \DateTime('now', new \DateTimeZone('UTC'))
        ) {
            $this->createClientByToken();
        } else {
            $this->createClient();
        }

        return $this->client;
    }

    /**
     * Set properties from AkeneoSettings entity.
     */
    private function initProperties(AkeneoSettings $akeneoSettings): void
    {
        $this->akeneoSettings = $akeneoSettings;
        $this->akeneoUrl = $akeneoSettings->getUrl();
        $this->clientId = $akeneoSettings->getClientId();
        $this->secret = $akeneoSettings->getSecret();
        $this->userName = $akeneoSettings->getUsername();
        $this->password = $this->crypter->getDecryptData($akeneoSettings->getPassword());
        $this->token = $akeneoSettings->getToken();
        $this->refreshToken = $akeneoSettings->getRefreshToken();
    }

    private function createClientByToken()
    {
        $this->clientBuilder->setBaseUri($this->akeneoUrl);
        $this->client = $this->clientBuilder->buildAuthenticatedByToken(
            $this->clientId,
            $this->secret,
            $this->token,
            $this->refreshToken
        );

        return $this->client;
    }

    /**
     * Build token by username and password.
     *
     * @return AkeneoPimClientInterface
     */
    private function createClient()
    {
        $this->clientBuilder->setBaseUri($this->akeneoUrl);
        $this->client = $this->clientBuilder->buildAuthenticatedByPassword(
            $this->clientId,
            $this->secret,
            $this->userName,
            $this->password
        );

        if ($this->akeneoSettings->getId()) {
            $this->persistTokens();
        }

        return $this->client;
    }

    /**
     * Persist authentication tokens.
     * Sends request to get currencies. It's needed to fetch token.
     */
    private function persistTokens(): void
    {
        $this->client->getCurrencyApi()->all();
        $em = $this->doctrineHelper->getEntityManager($this->akeneoSettings);
        $em->getUnitOfWork()->removeFromIdentityMap($this->akeneoSettings);
        $em->refresh($this->akeneoSettings);

        $this->akeneoSettings->setToken($this->client->getToken());
        $this->akeneoSettings->setRefreshToken($this->client->getRefreshToken());
        $this->akeneoSettings->setTokenExpiryDateTime(new \DateTime('now +3500 seconds'));

        $em->flush($this->akeneoSettings);
        $em->getUnitOfWork()->markReadOnly($this->akeneoSettings);
    }
}
