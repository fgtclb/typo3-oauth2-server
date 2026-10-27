<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server;

use FGTCLB\OAuth2Server\Exception\InvalidLifetimeException;
use FGTCLB\OAuth2Server\Exception\MissingConfigurationException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration for OAuth2 processing
 */
final class Configuration
{
    private readonly string $privateKeyFile;

    private readonly string $publicKeyFile;

    /**
     * UID of the login page
     */
    private readonly int $loginPage;

    private readonly string $authEndpoint;
    private readonly string $tokenEndpoint;
    private readonly string $resourceEndpoint;

    private readonly \DateInterval $accessTokenLifetime;

    private readonly \DateInterval $refreshTokenLifetime;

    private readonly \DateInterval $authorizationCodeLifetime;

    /**
     * @throws MissingConfigurationException
     * @throws InvalidLifetimeException
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $configuration = $extensionConfiguration->get('oauth2_server');

        if (empty($configuration['privateKeyFile'])) {
            throw new MissingConfigurationException('Missing "privateKeyFile" in OAuth2 server extension configuration', 1539686145);
        }

        if (empty($configuration['publicKeyFile'])) {
            throw new MissingConfigurationException('Missing "publicKeyFile" in OAuth2 server extension configuration', 1539686197);
        }

        if ((int)($configuration['loginPage'] ?? 0) < 1) {
            throw new MissingConfigurationException('Missing/invalid "loginPage" in OAuth2 server extension configuration', 1539693234);
        }

        $this->privateKeyFile = $configuration['privateKeyFile'];
        $this->publicKeyFile = $configuration['publicKeyFile'];
        $this->loginPage = (int)$configuration['loginPage'];
        $this->authEndpoint = $configuration['authEndpoint'] ?? '/oauth/authorize';
        $this->tokenEndpoint = $configuration['tokenEndpoint'] ?? '/oauth/token';
        $this->resourceEndpoint = $configuration['resourceEndpoint'] ?? '/oauth/identity';

        $accessInterval = false;
        if (isset($configuration['accessTokenLifetime'])) {
            $accessInterval = \DateInterval::createFromDateString($configuration['accessTokenLifetime']);
            if ($accessInterval === false) {
                throw new InvalidLifetimeException(
                    'Invalid Access Token lifetime. See https://www.php.net/manual/de/datetime.formats.php#datetime.formats.relative for valid formats',
                    1712606345346
                );
            }
        }
        $this->accessTokenLifetime = $accessInterval ?: \DateInterval::createFromDateString('1 hour');
        $refreshInterval = false;
        if (isset($configuration['refreshTokenLifetime'])) {
            $refreshInterval = \DateInterval::createFromDateString($configuration['refreshTokenLifetime']);
            if ($refreshInterval === false) {
                throw new InvalidLifetimeException(
                    'Invalid Refresh Token lifetime. See https://www.php.net/manual/de/datetime.formats.php#datetime.formats.relative for valid formats',
                    1712606543156
                );
            }
        }
        $this->refreshTokenLifetime = $refreshInterval ?: \DateInterval::createFromDateString('1 month');

        $authorizationCodeInterval = false;
        if (isset($configuration['authorizationCodeLifetime'])) {
            $authorizationCodeInterval = \DateInterval::createFromDateString($configuration['authorizationCodeLifetime']);
            if ($authorizationCodeInterval === false) {
                throw new InvalidLifetimeException(
                    'Invalid Authorization Code lifetime. See https://www.php.net/manual/de/datetime.formats.php#datetime.formats.relative for valid formats',
                    1712606546362
                );
            }
        }
        $this->authorizationCodeLifetime = $authorizationCodeInterval ?: \DateInterval::createFromDateString('10 minutes');
    }

    /**
     * Get the path to the private RSA key
     */
    public function getPrivateKeyFile(): string
    {
        return GeneralUtility::getFileAbsFileName($this->privateKeyFile);
    }

    /**
     * Get the path to the public RSA key
     */
    public function getPublicKeyFile(): string
    {
        return GeneralUtility::getFileAbsFileName($this->publicKeyFile);
    }

    /**
     * Get the UID of the login page
     */
    public function getLoginPage(): int
    {
        return $this->loginPage;
    }

    /**
     * Get the lifetime of access tokens
     */
    public function getAccessTokenLifetime(): \DateInterval
    {
        return $this->accessTokenLifetime;
    }

    /**
     * Get the lifetime of refresh tokens
     */
    public function getRefreshTokenLifetime(): \DateInterval
    {
        return $this->refreshTokenLifetime;
    }

    /**
     * Get the lifetime of authorization codes
     */
    public function getAuthorizationCodeLifetime(): \DateInterval
    {
        return $this->authorizationCodeLifetime;
    }

    /**
     * Get the authorization endpoint
     */
    public function getAuthEndpoint(): string
    {
        return $this->authEndpoint;
    }

    /**
     * Get the token endpoint
     */
    public function getTokenEndpoint(): string
    {
        return $this->tokenEndpoint;
    }

    /**
     * Get the resource endpoint
     */
    public function getResourceEndpoint(): string
    {
        return $this->resourceEndpoint;
    }
}
