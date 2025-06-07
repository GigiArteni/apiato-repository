<?php
// Robustly resolve the canonical config for testbench compatibility
// Works for both local and testbench environments

// Try to resolve from project root relative to this file
$rootConfig = dirname(__DIR__, 4) . '/config/repository.php';
if (!file_exists($rootConfig)) {
    // Try one level up (for testbench or other setups)
    $rootConfig = dirname(__DIR__, 5) . '/config/repository.php';
}
if (!file_exists($rootConfig)) {
    throw new \RuntimeException('Could not locate config/repository.php for Apiato Repository package. Tried: ' . $rootConfig);
}
return require $rootConfig;
