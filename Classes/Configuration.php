<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration for OAuth2 processing
 */
final class Configuration
{
    /**
     * @var string Path to a private RSA key
     */
    protected $privateKeyFile;

    /**
     * @var string Path to a public RSA key
     */
    protected $publicKeyFile;

    /**
     * @var int UID of the login page
     */
    protected $loginPage;

    /**
     * @var \DateInterval
     */
    protected $accessTokenLifetime;

    /**
     * @var \DateInterval
     */
    protected $refreshTokenLifetime;

    /**
     * @var \DateInterval
     */
    protected $authorizationCodeLifetime;

    /**
     * @throws \InvalidArgumentException if the extension configuration is invalid/incomplete
     */
    public function __construct()
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('oauth2_server');

        if (empty($configuration['privateKeyFile'])) {
            throw new \InvalidArgumentException('Missing "privateKeyFile" in OAuth2 server extension configuration', 1539686145);
        }

        if (empty($configuration['publicKeyFile'])) {
            throw new \InvalidArgumentException('Missing "publicKeyFile" in OAuth2 server extension configuration', 1539686197);
        }

        if ((int)($configuration['loginPage'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Missing/invalid "loginPage" in OAuth2 server extension configuration', 1539693234);
        }

        $this->privateKeyFile = $configuration['privateKeyFile'];
        $this->publicKeyFile = $configuration['publicKeyFile'];
        $this->loginPage = (int)$configuration['loginPage'];
        $this->accessTokenLifetime = \DateInterval::createFromDateString('1 hour');
        $this->refreshTokenLifetime = \DateInterval::createFromDateString('1 month');
        $this->authorizationCodeLifetime = \DateInterval::createFromDateString('10 minutes');
    }

    /**
     * Get the path to the private RSA key
     *
     * @return string
     */
    public function getPrivateKeyFile(): string
    {
        return $this->privateKeyFile;
    }

    /**
     * Get the path to the public RSA key
     *
     * @return string
     */
    public function getPublicKeyFile(): string
    {
        return $this->publicKeyFile;
    }

    /**
     * Get the UID of the login page
     *
     * @return int
     */
    public function getLoginPage(): int
    {
        return $this->loginPage;
    }

    /**
     * Get the lifetime of access tokens
     *
     * @return \DateInterval
     */
    public function getAccessTokenLifetime(): \DateInterval
    {
        return $this->accessTokenLifetime;
    }

    /**
     * Get the lifetime of refresh tokens
     *
     * @return \DateInterval
     */
    public function getRefreshTokenLifetime(): \DateInterval
    {
        return $this->refreshTokenLifetime;
    }

    /**
     * Get the lifetime of authorization codes
     *
     * @return \DateInterval
     */
    public function getAuthorizationCodeLifetime(): \DateInterval
    {
        return $this->authorizationCodeLifetime;
    }
}
