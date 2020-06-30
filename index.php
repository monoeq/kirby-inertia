<?php

/**
 * Autoloader for all Kirby Inertia classes
 */
load([
  'monoeq\\kirbyinertia\\inertia' => 'src/Inertia.php',
  'monoeq\\kirbyinertia\\inertiasession' => 'src/InertiaSession.php'
], __DIR__);

/**
 * Creates class aliases for the Inertia and InertiaSession classes
 */
class_alias('Monoeq\\KirbyInertia\\Inertia', 'Inertia');
class_alias('Monoeq\\KirbyInertia\\InertiaSession', 'InertiaSession');

/**
 * Plugin Definition
 */
Kirby::plugin('monoeq/inertia', [
  'options' => [
    'version' => false,
    'shared' => false,
    'session' => 'inertia'
  ],
  'snippets' => [
    'inertia' => __DIR__ . '/snippets/inertia.php'
  ],
  'controllers' => [
    'default' => require __DIR__ . '/controllers/default.php'
  ],
  'templates' => Inertia::assignTemplates(),
  'pageMethods' => [
    'inertiaUri' => function () {
      return $this->isHomePage() ? '/' : '/' . $this->uri();
    }
  ]
]);