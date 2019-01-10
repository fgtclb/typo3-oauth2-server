<?php declare(strict_types = 1);
namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Server\ServerFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handler for OAuth2 identity requests
 *
 * @see https://oauth2.thephpleague.com/resource-server/securing-your-api/
 */
final class OAuth2Identity implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/oauth/identity') {
            return $handler->handle($request);
        }

        $factory = new ServerFactory();
        $server = $factory->buildResourceServer();

        try {
            $request = $server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }

        $userId = $request->getAttribute('oauth_user_id');
        $userData = $this->getUserData((int)$userId);

        return new JsonResponse([
            'id' => (string)$userData['tx_users_fgtclbuserid'], // required
            'username' => $userData['tx_profiles_nickname'],
        ]);
    }

    /**
     * Get data for a given user ID
     *
     * @param int $userId
     * @return array
     */
    protected function getUserData(int $userId): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');
        $userData = $connection->select(
            [
                'tx_users_fgtclbuserid',
                'tx_profiles_nickname',
            ],
            'fe_users',
            ['uid' => $userId]
        )->fetch();

        return $userData;
    }
}
