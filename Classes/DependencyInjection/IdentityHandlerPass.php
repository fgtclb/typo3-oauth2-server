<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\DependencyInjection;

use FGTCLB\OAuth2Server\Service\IdentityHandlingFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class IdentityHandlerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(IdentityHandlingFactory::class)) {
            return;
        }
        $definition = $container->findDefinition(IdentityHandlingFactory::class);
        $taqgedServices = $container->findTaggedServiceIds('oauth.identity_handler');
        foreach ($taqgedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addIdentityHandler',
                    [
                        new Reference($id),
                        $attributes['clientId'],
                    ]
                );
            }
        }
    }
}
