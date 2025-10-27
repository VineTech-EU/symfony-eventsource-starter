<?php

declare(strict_types=1);

use Artprima\PrometheusMetricsBundle\ArtprimaPrometheusMetricsBundle;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use League\FlysystemBundle\FlysystemBundle;
use Liip\MonitorBundle\LiipMonitorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
    FrameworkBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    DAMADoctrineTestBundle::class => ['test' => true],
    TwigBundle::class => ['all' => true],
    TwigExtraBundle::class => ['all' => true],
    ArtprimaPrometheusMetricsBundle::class => ['all' => true],
    LiipMonitorBundle::class => ['all' => true],
    FlysystemBundle::class => ['all' => true],
];
