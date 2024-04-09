<?php

declare(strict_types=1);

use FGTCLB\OAuth2Server\DependencyInjection\IdentityHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder) {
    $containerBuilder->addCompilerPass(new IdentityHandlerPass());
};
