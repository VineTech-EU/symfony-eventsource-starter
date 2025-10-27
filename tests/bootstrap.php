<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

/*
 * Performance optimizations for tests
 *
 * 1. Disable garbage collector during tests (10-15% speed improvement)
 *    The GC will still run when memory is full, but won't run on every allocation.
 *    Re-enabled after each test via PHPUnit's tearDown if needed.
 *
 * 2. Increase realpath cache for faster file operations
 *    Tests involve lots of file I/O (autoloading, config, etc.)
 */
gc_disable();

// Increase realpath cache for better performance with many files
ini_set('realpath_cache_size', '4096K');
ini_set('realpath_cache_ttl', '600');

// Optional: Disable opcache validation in tests for speed
// (Only enable if your tests don't modify PHP files during execution)
if (function_exists('opcache_get_status')) {
    ini_set('opcache.validate_timestamps', '0');
    ini_set('opcache.revalidate_freq', '0');
}
