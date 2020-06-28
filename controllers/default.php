<?php

return function ($page, $site, $kirby) {
  return Inertia::render(
    $page->intendedTemplate(), 
    $page->toArray()
  );
};