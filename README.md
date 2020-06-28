# Kirby Inertia

[Inertia.js](https://inertiajs.com/) adapter for Kirby 3.

Inertia allows you to build your front end using Javascript (React, Vue and Svelte), while letting the server (in this case, Kirby) take care of routing and shaping data. Think of it as building a normal Kirby site, but the front end is rendered with Javascript.

### How it works

The basic idea is when loading your site in the browser, Kirby serves up an HTML page with the page data inlined as JSON so your Javascript application can render the page. As you navigate to different pages, requests are sent to Kirby with an `X-Inertia` header, informing Kirby to return only the JSON for the page, rather than a full HTML response. Your Javascript application picks up on this data and renders each new page. You can read a bit more about [how it works on the Inertia site](https://inertiajs.com/how-it-works).

**📌 This readme does not cover how Inertia works, or how to build your front-end. It just outlines the core features implemented to use Kirby as an Inertia backend.**

## Setup

After [installation](#installation), the bare minimum you need to do is define a `default.php` template:

```php
<!DOCTYPE html>
<html>
<head>
  <title><?= $site->title() ?></title>
  <?= css('assets/css/site.css'); /* Your site’s css */ ?>
</head>
<body>
<?php snippet('inertia') ?>
<?= js('assets/js/site.js'); /* Your site’s js */ ?>
</body>
</html>
```

The included [`inertia` snippet](https://github.com/monoeq/kirby-inertia/blob/master/snippets/inertia.php) simply renders an application shell with the current page data encoded as JSON:

```html
<div id="app" data-page="{}"></div>
```

At this point you can go ahead and build your [Inertia front end](https://inertiajs.com/client-side-setup). If you visit your site in the browser (and view source), you’ll see the application shell with page data inlined. If you were to request that same url with an `X-Inertia` header (test using wget/curl or [Postman](https://www.postman.com/)), a JSON response will be returned instead.

## Creating Responses

[Inertia responses](https://inertiajs.com/responses) in Kirby are created with Kirby [Controllers](https://getkirby.com/docs/guide/templates/controllers). The only difference with a typical Kirby controller is you return an `Inertia::render` function, instead of an array. This plugin assigns a [`default.php` controller](https://github.com/monoeq/kirby-inertia/blob/master/controllers/default.php) for you:

```php
return function ($page, $site, $kirby) {
  return Inertia::render(
    $page->intendedTemplate(), 
    $page->toArray()
  );
};
```

The first argument specifies the name of the view your Javascript application should render, the second argument is the page data. In this case we’re using the `$page->intendedTemplate()` as the view name, and the `$page->toArray()` method to pass the page data.

Here's an example Controller with more specific control:

```php
return function ($page, $site, $kirby) {
  return Inertia::render('TemplateName', [
    'title' => $page->title()->value(),
    'date' => $page->date()->value(),
    'thumbnail' => $page->thumbnail()->toFile()->url(),
    'content' => $page->text()->kirbytext()
  ]);
};
```

## Inertia Features

The following sections outline how to use some of Inertia's features in Kirby since they differ slightly with the Laravel adapter.

---

### Lazy evaluation

[Lazy evaluation](https://inertiajs.com/responses#lazy-evaluation) and partial data works as expected, just wrap your data in closures:

```php
return function ($page, $site, $kirby) {
  return Inertia::render('TemplateName', [
    'title' => $page->title()->value(),
    'lazyProp' => function () use ($page) {
      return $page->children()->toArray();
    }
  ]);
};
```

---

### Shared Data

Unlike the Laravel adapter, [Shared Data](https://inertiajs.com/shared-data) in Kirby is defined in your [`config.php`](#config). You can define shared data as an array (values can be closures) or as a closure which returns an array:

```php
'monoeq.inertia.shared' => [
  "prop" => "value",
  "beep" => function () {
    return "boop";
  }
]
```

```php
'monoeq.inertia.shared' => function () {
  return [
    "prop" => "value"
  ];
}
```

---

### Form Handling

Handle forms in your Kirby controller like you usually would. Just make sure you [redirect to a view](https://inertiajs.com/forms) as explained in the Inertia docs:

```php
return function ($page, $site, $kirby) {
  
  // Form Handling
  if (kirby()->request()->method() === 'POST') {
    $kirby->impersonate('kirby');
    $page->changeTitle(get('title', ''));
    go($page); // <- Redirect back to GET request for Inertia
  }

  return Inertia::render(
    $page->intendedTemplate(), 
    $page->toArray()
  );
};
```

#### [Error Handling](#session-data)

You'll want to handle errors in the controller as well. An [`InertiaSession`](#inertiasession) helper is included for passing data to the Kirby session, that you can then pick up on in your shared data. [See an example here.](#session-data)

---

### Root template data

You can access data in your Kirby templates with the via the `$inertia` variable. Example: `$inertia['prop']`

#### With View Data

Kirby Inertia does not have a `withViewData` method, instead, you can optionally pass a 3rd param into `Inertia::render`. This just passes data into the kirby template like the normal controller behavior.

```php
return function ($page, $site, $kirby) {
  return Inertia::render(
    $page->intendedTemplate(), 
    $page->toArray(),
    [ 'meta' => 'hello' ]
  );
};

// In your template:
<?= $meta ?>
```

---

## Session Data

An [`InertiaSession`](#inertiasession) helper is included for passing session data to your Inertia views. This is really helpful for [form error handling](https://inertiajs.com/forms#server-side-validation) or [flash messages](https://inertiajs.com/shared-data#flash-messages), and is similar to Laravel's `Inertia::share`. You can handle your Inertia session data when you set up your shared data in `config.php`:

```php
return [
  'monoeq.inertia.shared' => [
    'messages' => function () {
      // pull() fetches any session data stored under messages, and then wipes it
      return InertiaSession::pull('messages');
    },
    'errors' => function () {
      // pull() fetches any session data stored under errors, and then wipes it
      return InertiaSession::pull('errors');
    }
  ]
];
```

So you can imagine in a form controller, using this helper like so:

```php
return function ($page, $site, $kirby) {
  
  // Form Handling
  if (kirby()->request()->method() === 'POST') {
    try {
      $kirby->impersonate('kirby');
      $page->changeTitle(get('title', ''));
      InertiaSession::append('messages', 'Thank You!');
    } catch (Exception $e) {
      InertiaSession::append('errors', $e->getMessage());
      // or if you want to have named errors
      // InertiaSession::merge('errors', [ 'title' => $e->getMessage() ]);  
    }
    go($page); // <- Redirect back to GET request for Inertia
  }

  return Inertia::render(
    $page->intendedTemplate(), 
    $page->toArray()
  );
};
```

From there, in your Javascript views you can pick up on this data

```html
<div v-if="$page.messages">{{ $page.messages }}</div>
<div v-if="$page.errors">{{ $page.errors }}</div>
```

## Config

Setup [versioning](https://inertiajs.com/asset-versioning) and [shared data](https://inertiajs.com/shared-data) in your `config.php`.

```php
return [
  'monoeq.inertia.version' => '1.0',
  'monoeq.inertia.shared' => [
    'site' => function () {
      return [
        'title' => site()->title()->value()
      ];
    }
  ]
];
```

## Classes API

### `Inertia`

#### `Inertia::render($name, $data, $viewData)`

Return from your Kirby Controllers to render an Inertia response.

### `InertiaSession`

Wrapper class around `kirby()->session()` for passing data to your Inertia views. Data is namespaced under the hood with `inertia` to avoid conflict with other Kirby session data.

#### `InertiaSession::set($key, $value)`

Wrapper around [`$session->set()`](https://getkirby.com/docs/reference/objects/session/set)

#### `InertiaSession::append($key, $value)`

Appends (or sets if not yet defined) value to the desired key.

```php
InertiaSession::append('messages', 'beep');
InertiaSession::append('messages', 'boop');
InertiaSession::get('messages'); // => ['beep', 'boop']
```

#### `InertiaSession::merge($key, $value)`

Merges (or sets if not yet defined) value to the desired key.

```php
InertiaSession::merge('messages', [ 'beep' => 'boop' ]);
InertiaSession::merge('messages', [ 'bleep' => 'bloop' ]);
InertiaSession::get('messages'); // => [ 'beep' => 'boop', 'bleep' => 'bloop' ]
```

#### `InertiaSession::get($key)`

Wrapper around [`$session->get()`](https://getkirby.com/docs/reference/objects/session/get)

#### `InertiaSession::pull($key)`

Wrapper around [`$session->pull()`](https://getkirby.com/docs/reference/objects/session/pull)

#### `InertiaSession::remove($key)`

Wrapper around [`$session->remove()`](https://getkirby.com/docs/reference/objects/session/remove)

## Installation

### Download

Download and copy this repository to `/site/plugins/kirby-inertia`.

### Git submodule

```
git submodule add https://github.com/monoeq/kirby-inertia.git site/plugins/kirby-inertia
```

### Composer

```
composer require monoeq/kirby-inertia
```

## Other Notes

### Auto Templates

Typically in Kirby you need to create an actual template file for a controller to be called. But when using Inertia you usually only need the `default.php` template. As a helper, this plugin automatically assigns any controller file which does not have a matching template file to `default.php`, allowing you to just create controllers without worrying about creating templates.

## Example Front End

Refer to the [Inertia.js](https://inertiajs.com/client-side-setup) docs for how to build an Inertia front end, but here's a bare minimum example using Vue:

### `app.js`

<details>
  <summary>
    <strong>See code</strong>
  </summary>

```js
import { InertiaApp } from '@inertiajs/inertia-vue'
import Vue from 'vue'
import 'nprogress/nprogress.css'

Vue.use(InertiaApp)

const app = document.getElementById('app')

// Include templates here
const templates = {
  'default': require('./templates/default').default
}

new Vue({
  render: h => h(InertiaApp, {
    props: {
      initialPage: JSON.parse(app.dataset.page),
      // Falls back to default template, Kirby-style
      resolveComponent: name => templates[name] || templates['default']
    },
  }),
}).$mount(app)
```
</details>

### `templates/default.vue`

<details>
  <summary>
    <strong>See code</strong>
  </summary>

```html
<template>
  <div>{{ content.title }}</div>
</template>

<script>
  export default {
    props: {
      content: Object
    }
  }
</script>
```
</details>

## Todo

- [ ] Caching
  - Enabling the Kirby cache will currently break Inertia functionality.


## Credits

- [Jon Gacnik](https://github.com/jongacnik)
