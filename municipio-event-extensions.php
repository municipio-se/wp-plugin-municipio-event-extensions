<?php

declare(strict_types=1);

/**
 * Plugin Name: Municipio Event Extensions
 * Description: Server-rendered event presentation for Municipio 6.
 * Version: 0.1.0
 * Author: Whitespace
 * Requires PHP: 8.2
 * License: MIT
 * Text Domain: municipio-event-extensions
 */

if (!defined('ABSPATH')) {
    exit();
}

require_once __DIR__ . '/source/EventSource.php';
require_once __DIR__ . '/source/EventManagerSource.php';
require_once __DIR__ . '/source/Cards.php';
require_once __DIR__ . '/source/CardView.php';
require_once __DIR__ . '/source/CardSupport.php';
require_once __DIR__ . '/source/EventManagerOccurrence.php';

add_action('after_setup_theme', static function (): void {
    $version = class_exists(\Composer\InstalledVersions::class)
    && \Composer\InstalledVersions::isInstalled('helsingborg-stad/municipio')
        ? \Composer\InstalledVersions::getPrettyVersion('helsingborg-stad/municipio')
        : null;
    if (
        $version
        && version_compare($version, '6.0', '>=')
        && version_compare($version, '7.0', '<')
        && class_exists(\EventManagerIntegration\Helper\QueryEvents::class)
    ) {
        (new \MunicipioEventExtensions\Cards(__DIR__))->register();
    }
});
