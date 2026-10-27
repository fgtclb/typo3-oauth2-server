<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Server;

use FGTCLB\OAuth2Server\Exception\MissingConfigurationException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Configuration for OAuth2 servers
 */
final class ServerConfiguration
{
    /**
     * Path to a private RSA key
     */
    private readonly string $privateKeyFile;

    /**
     * Path to a public RSA key
     */
    private readonly string $publicKeyFile;

    /**
     * @throws MissingConfigurationException
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $configuration = $extensionConfiguration->get('oauth2_server');

        if (empty($configuration['privateKeyFile']) || !is_string($configuration['privateKeyFile'])) {
            throw new MissingConfigurationException('Missing "privateKeyFile" in OAuth2 server extension configuration', 1559054947);
        }

        if (empty($configuration['publicKeyFile']) || !is_string($configuration['publicKeyFile'])) {
            throw new MissingConfigurationException('Missing "publicKeyFile" in OAuth2 server extension configuration', 1559054961);
        }

        $this->privateKeyFile = $configuration['privateKeyFile'];
        $this->publicKeyFile = $configuration['publicKeyFile'];
    }

    /**
     * Get the path to the private RSA key
     */
    public function getPrivateKeyFile(): string
    {
        return $this->privateKeyFile;
    }

    /**
     * Get the path to the public RSA key
     */
    public function getPublicKeyFile(): string
    {
        return $this->publicKeyFile;
    }
}
