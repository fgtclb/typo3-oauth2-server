<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Server;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Configuration for OAuth2 servers
 */
final class ServerConfiguration
{
    /**
     * @var string Path to a private RSA key
     */
    protected string $privateKeyFile;

    /**
     * @var string Path to a public RSA key
     */
    protected string $publicKeyFile;

    /**
     * @param ExtensionConfiguration $extensionConfiguration
     * @throws \InvalidArgumentException if the extension configuration is invalid/incomplete
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $configuration = $extensionConfiguration->get('oauth2_server');

        if (empty($configuration['privateKeyFile']) || !is_string($configuration['privateKeyFile'])) {
            throw new \InvalidArgumentException('Missing "privateKeyFile" in OAuth2 server extension configuration', 1559054947);
        }

        if (empty($configuration['publicKeyFile']) || !is_string($configuration['publicKeyFile'])) {
            throw new \InvalidArgumentException('Missing "publicKeyFile" in OAuth2 server extension configuration', 1559054961);
        }

        $this->privateKeyFile = $configuration['privateKeyFile'];
        $this->publicKeyFile = $configuration['publicKeyFile'];
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
}
