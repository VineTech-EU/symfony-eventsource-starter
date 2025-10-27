<?php

declare(strict_types=1);

namespace App;

use App\SharedKernel\Adapters\DependencyInjection\RegisterEventTypesCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        // Auto-register all DomainEvent classes in EventTypeRegistry
        $container->addCompilerPass(new RegisterEventTypesCompilerPass());
    }
}
