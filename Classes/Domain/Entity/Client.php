<?php
declare(strict_types = 1);

namespace FGTCLB\OAuth2Server\Domain\Entity;

use FGTCLB\OAuth2Server\Domain\Repository\ClientRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * OAuth2 client
 *
 * @phpstan-import-type TClientRow from ClientRepository
 */
final class Client implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    private string $secret;

    private function __construct(string $identifier, string $name, string $redirectUri, string $secret, bool $isConfidential)
    {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUri = GeneralUtility::trimExplode("\n", $redirectUri);
        $this->secret = $secret;
        $this->isConfidential = $isConfidential;
    }

    /**
     * @param TClientRow $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['identifier'],
            $row['name'],
            $row['redirect_uris'],
            $row['secret'],
            // TODO make this configurable once we support OAuth2 e.g. in a browser, where the client secret cannot be kept secret
            true
        );
    }

    public function validateSecret(string $secret, PasswordHashFactory $hashFactory): bool
    {
        return $hashFactory
            ->get($this->secret, 'FE')
            ->checkPassword($secret, $this->secret);
    }
}
