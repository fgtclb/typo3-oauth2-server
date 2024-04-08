<?php

declare(strict_types=1);
namespace FGTCLB\OAuth2Server\Middleware;

use FGTCLB\OAuth2Server\Configuration;
use FGTCLB\OAuth2Server\Server\ServerFactory;
use FGTCLB\OAuth2Server\Service\ResourceHandlingFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;

/**
 * Handler for OAuth2 identity requests
 *
 * @see https://oauth2.thephpleague.com/resource-server/securing-your-api/
 */
final class OAuth2Identity implements MiddlewareInterface
{
    private Configuration $configuration;
    private ServerFactory $authServerFactory;
    private ResourceHandlingFactory $resourceServerFactory;

    public function __construct(
        Configuration $configuration,
        ResourceHandlingFactory $resourceServerFactory,
        ServerFactory $authServerFactory
    ) {
        $this->configuration = $configuration;
        $this->resourceServerFactory = $resourceServerFactory;
        $this->authServerFactory = $authServerFactory;
    }
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== $this->configuration->getResourceEndpoint()) {
            return $handler->handle($request);
        }
        $server = $this->authServerFactory->buildResourceServer();

        try {
            // OAuth Auth Server validates the request
            $request = $server->validateAuthenticatedRequest($request);
            $clientId = $request->getAttribute('oauth_client_id') ?? '';

            // Internal resource handler accepts the request
            // and generates a response
            //
            // we return the resourceHandler response here, as we don't want to have the path
            // moved to TYPO3 handling. In most cases, the route won't be able inside the TYPO3,
            // so the handler has to decide whether to redirect or return a response.
            $resourceHandler = $this->resourceServerFactory->getResourceHandler($clientId);
            return $resourceHandler->handleAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse(new Response());
        }
    }
}
