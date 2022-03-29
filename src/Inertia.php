<?php

namespace Monoeq\KirbyInertia;

use Kirby\Http\Response;

class Inertia {
  public static function render ($template = 'app', $props = [], $viewData = []) {
    $request = kirby()->request();

    $inertia = [
      'component' => is_a($template, 'Kirby\Cms\Template') 
        ? $template->name() 
        : $template,
      'props' => $props,
      'url' => $request->url()->toString()
    ];

    // Set Partial
    $only = array_filter(explode(',', $request->header('X-Inertia-Partial-Data') ?? ""));
    $inertia['props'] = ($only && $request->header('X-Inertia-Partial-Component') === $inertia['component'])
      ? self::getColumns($inertia['props'], $only)
      : $inertia['props'];

    // Call Lazy Props
    array_walk_recursive($inertia['props'], function (&$prop) {
      if ($prop instanceof \Closure) {
        $prop = $prop();
      }
    });

    // Set Version
    if ($version = kirby()->option('monoeq.inertia.version')) {
      $inertia['version'] = $version;
    }

    // Set Shared
    if ($shared = kirby()->option('monoeq.inertia.shared')) {
      if ($shared instanceof \Closure) {
        $shared = $shared();
      }

      if (is_array($shared)) {
        array_walk_recursive($shared, function (&$prop) {
          if ($prop instanceof \Closure) {
            $prop = $prop();
          }
        });

        $inertia['props'] = array_merge($inertia['props'], $shared);
      }
    }

    if ($request->method() === 'GET' && $request->header('X-Inertia')) {
      die(Response::json($inertia, null, null, [
        'Vary' => 'Accept',
        'X-Inertia' => 'true'
      ]));
    } else {
      if (is_array($viewData) && count($viewData)) {
        return array_merge([
          'inertia' => $inertia
        ], $viewData);
      } else {
        return compact('inertia');
      }
    }
  }

  /**
   * Assigns defualt template to controllers with
   * no matching template defined.
   * 
   * Kirby only runs controllers when the matching 
   * template is rendered. When using Inertia, we only 
   * care about controllers, as the template is just
   * our application shell. By assigning the default
   * template to controllers without a matching template,
   * we can simply define controllers.
   */
  public static function assignTemplates () {
    $controllers = array_map(['F', 'name'], glob(kirby()->root('controllers') . '/*.php'));
    $templates = array_map(['F', 'name'], glob(kirby()->root('templates') . '/*.php'));

    return array_reduce($controllers, function ($arr, $controller) use ($templates) {
      if (!in_array($controller, $templates)) {
        $arr[$controller] = kirby()->root('templates') . '/default.php';
      }
      return $arr;
    }, []);
  }

  /**
   * Returns a new array with only the specified columns
   *
   * @param array $array
   * @param array $columns
   * @return array
   */
  private static function getColumns ($array = [], $columns = []) {
    $filtered = [];
    foreach ($array as $key => $value) {
      if (in_array($key, $columns)) {
        $filtered[$key] = $value;
      }
    }
    return $filtered;
  }
}
