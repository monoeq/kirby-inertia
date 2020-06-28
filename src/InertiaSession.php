<?php

namespace Monoeq\KirbyInertia;

/**
 * Thin wrapper around $kirby->session() 
 * for passing data between Inertia requests
 */
class InertiaSession {
  /**
   * Wrapper around $session->set()
   */
  public static function set ($key, $value) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    kirby()->session()->set($key, $value);
  }

  /**
   * Append value to a session (or sets session)
   */
  public static function append ($key, $newValue) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    $value = kirby()->session()->get($key);
    if (is_array($value)) {
      $value[] = $newValue;
    } else if ($value) {
      $value = [ $value, $newValue ];
    } else {
      $value = [ $newValue ];
    }
    kirby()->session()->set($key, $value);
  }

  /**
   * Merges value into a session (or sets session)
   */
  public static function merge ($key, $newValue) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    if (is_array($newValue)) {
      $value = kirby()->session()->get($key);
      if (is_array($value)) {
        $value = array_merge($value, $newValue);
      } else if ($value) {
        $value = array_merge([ $value ], $newValue);
      } else {
        $value = $newValue;
      }
      kirby()->session()->set($key, $value);
    } else {
      self::append($key, $newValue);
    }
  }

  /**
   * Wrapper around $session->get()
   */
  public static function get ($key) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    return kirby()->session()->get($key);
  }

  /**
   * Wrapper around $session->pull()
   */
  public static function pull ($key) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    return kirby()->session()->pull($key);
  }

  /**
   * Wrapper around $session->remove()
   */
  public static function remove ($key) {
    $prefix = option('monoeq.inertia.session');
    $key = "{$prefix}.{$key}";
    return kirby()->session()->remove($key);
  }
}