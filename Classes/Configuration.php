<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server;

use DateInterval;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration for OAuth2 processing
 */
final class Configuration
{
    protected string $privateKeyFile;

    protected string $publicKeyFile;

    /**
     * UID of the login page
     */
    protected int $loginPage;

    protected string $authEndpoint = '/oauth/authorize';
    protected string $tokenEndpoint = '/oauth/token';
    protected string $resourceEndpoint = '/oauth/identity';

    protected DateInterval $accessTokenLifetime;

    protected DateInterval $refreshTokenLifetime;

    protected DateInterval $authorizationCodeLifetime;

    /**
     * @throws \InvalidArgumentException if the extension configuration is invalid/incomplete
     */
    public function __construct()
    {
        /**
         * @var array{
         *     privateKeyFile: string,
         *     publicKeyFile: string,
         *     loginPage: string,
         *     authEndpoint?: string,
         *     tokenEndpoint?: string,
         *     resourceEndpoint?: string,
         *     accessTokenLifetime?: string,
         *     refreshTokenLifetime?: string,
         *     authorizationCodeLifetime?: string
         * } $configuration
         */
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
        $this->authEndpoint = $configuration['authEndpoint'] ?: $this->authEndpoint;
        $this->tokenEndpoint = $configuration['tokenEndpoint'] ?: $this->tokenEndpoint;
        $this->resourceEndpoint = $configuration['resourceEndpoint'] ?: $this->resourceEndpoint;
        $this->accessTokenLifetime = DateInterval::createFromDateString($configuration['accessTokenLifetime'] ?: '1 hour');
        $this->refreshTokenLifetime = DateInterval::createFromDateString($configuration['refreshTokenLifetime'] ?: '1 month');
        $this->authorizationCodeLifetime = DateInterval::createFromDateString($configuration['authorizationCodeLifetime'] ?: '10 minutes');
    }

    /**
     * Get the path to the private RSA key
     *
     * @return string
     */
    public function getPrivateKeyFile(): string
    {
        return GeneralUtility::getFileAbsFileName($this->privateKeyFile);
    }

    /**
     * Get the path to the public RSA key
     *
     * @return string
     */
    public function getPublicKeyFile(): string
    {
        return GeneralUtility::getFileAbsFileName($this->publicKeyFile);
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
     * @return DateInterval
     */
    public function getAccessTokenLifetime(): DateInterval
    {
        return $this->accessTokenLifetime;
    }

    /**
     * Get the lifetime of refresh tokens
     *
     * @return DateInterval
     */
    public function getRefreshTokenLifetime(): DateInterval
    {
        return $this->refreshTokenLifetime;
    }

    /**
     * Get the lifetime of authorization codes
     *
     * @return DateInterval
     */
    public function getAuthorizationCodeLifetime(): DateInterval
    {
        return $this->authorizationCodeLifetime;
    }

    /**
     * Get the authorization endpoint
     *
     * @return string
     */
    public function getAuthEndpoint(): string
    {
        return $this->authEndpoint;
    }

    /**
     * Get the token endpoint
     *
     * @return string
     */
    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    /**
     * Get the resource endpoint
     *
     * @return string
     */
    public function getResourceEndpoint(): string
    {
        return $this->resourceEndpoint;
    }
}
