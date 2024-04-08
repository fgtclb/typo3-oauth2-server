<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Compiler;

use FGTCLB\OAuth2Server\Service\ResourceHandlingFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ResourceHandlerPass implements CompilerPassInterface
{

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ResourceHandlingFactory::class)) {
            return;
        }

        $definition = $container->findDefinition(ResourceHandlingFactory::class);

        $taqgedServices = $container->findTaggedServiceIds('oauth.resource_handler');

        foreach ($taqgedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall(
                    'addResourceHandler',
                    [
                        new Reference($id),
                        $attributes['clientId'],
                    ]
                );
            }
        }
    }
}
