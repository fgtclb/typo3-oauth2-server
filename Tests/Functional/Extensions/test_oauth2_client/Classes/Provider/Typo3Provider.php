<?php

declare(strict_types=1);

namespace FGTCLB\TestOauth2Client\Provider;

use FGTCLB\TestOauth2Client\ResourceOwner\BasicResourceOwner;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

final class Typo3Provider extends AbstractProvider
{
    protected $guarded = [];

    private static array $requiredOptions = [

    ];

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
    }

    /**
     * @inheritDoc
     */
    public function getBaseAuthorizationUrl(): string
    {
        return 'https://localhost/oauth/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://localhost/oauth/token';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://localhost/oauth/identity';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultScopes(): array
    {
        return [ 'name' ];
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (empty($data['error'])) {
            return;
        }

        $message = $data['error']['type'] . ': ' . $data['error']['message'];
        throw new IdentityProviderException($message, $data['error']['code'], $data);
    }

    /**
     * @inheritDoc
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new BasicResourceOwner($response);
    }
}
