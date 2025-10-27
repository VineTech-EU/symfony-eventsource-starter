<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$env = $_SERVER['APP_ENV'] ?? 'test';
assert(is_string($env));

$debug = $_SERVER['APP_DEBUG'] ?? false;
assert(is_string($debug) || is_bool($debug));

$kernel = new Kernel($env, (bool) $debug);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

// Create test database schema if needed
$application->run(new ArrayInput([
    'command' => 'doctrine:database:create',
    '--if-not-exists' => true,
    '--env' => 'test',
    '--quiet' => true,
]), new NullOutput());

$application->run(new ArrayInput([
    'command' => 'doctrine:schema:update',
    '--force' => true,
    '--env' => 'test',
    '--quiet' => true,
]), new NullOutput());

return $kernel->getContainer()->get('doctrine')->getManager();
