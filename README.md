# Psr-15 session middleware

This package provides a [Psr-15 middleware](https://www.php-fig.org/psr/psr-15/) allowing to use session with Psr-7 request and response.

**Require** php >= 7.0

**Installation** `composer require ellipse/session`

**Run tests** `./vendor/bin/kahlan`

- [Using the session middleware](#using-the-session-middleware)
- [Setting the session cookie name](#setting-the-session-cookie-name)
- [Setting the session cookie options](#setting-the-session-cookie-options)
- [Setting the session handler](#setting-the-session-handler)

## Using the session middleware

The `Ellipse\Session\SessionMiddleware` class is a Psr-15 middleware adapting the default php session mechanism to the Psr-7 request and response flow. The default php session cookie is disabled and the session id is manually retrieved from the Psr-7 request and attached to the Psr-7 response.

By default this middleware uses the return value of `session_name()` for the session cookie name, the return values of `session_get_cookie_params()` for the session cookie options and the default session handler. In other words using this middleware without parameter emulate the default php session behaviour.

```php
<?php

namespace App;

use Ellipse\Session\SessionMiddleware;

// All middleware deeper than this one will have acces to the $_SESSION data.
// The default php session behaviour is emulated.
$middleware = new SessionMiddleware;
```

## Setting the session cookie name

The first constructor parameter of `SessionMiddleware` class is a string used as session cookie name.

```php
<?php

namespace App;

use Ellipse\Session\SessionMiddleware;

// The session cookie will be named 'my_session_cookie'.
$middleware = new SessionMiddleware('my_session_cookie');

// It can also be set with the ->withCookieName() method.
$middleware = (new SessionMiddleware)->withCookieName('my_session_cookie');
```

## Setting the session cookie options

The second constructor parameter of `SessionMiddleware` class is an array which is merged with the one returned by `session_get_cookie_params()`. It allows to override the default php session cookie options. It can contain the following keys:

- (string) **path**: the session cookie path
- (string) **domain**: the session cookie domain
- (int) **lifetime**: the session cookie lifetime in second
- (bool) **secure**: whether the session cookie should only be sent over secure connections
- (bool) **httponly**: whether the session cookie can only be accessed through the HTTP protocol

```php
<?php

namespace App;

use Ellipse\Session\SessionMiddleware;

// The session cookie will use '/my_cookie_path' as path.
$middleware = new SessionMiddleware('my_session_cookie', ['path' => '/my_cookie_path']);

// It can also be set with the ->withCookieOptions() method.
$middleware = (new SessionMiddleware)->withCookieOptions(['path' => '/my_cookie_path']);
```

## Setting the session handler

Finally the third constructor parameter of `SessionMiddleware` class is an implementation of `SessionHandlerInterface` to use as session handler.

```php
<?php

namespace App;

use Ellipse\Session\SessionMiddleware;

// The given instance of MySessionHandler will be used as session handler.
$middleware = new SessionMiddleware('my_session_cookie', [], new MySessionHandler);

// It can also be set with the ->withSessionHandler() method.
$middleware = (new SessionMiddleware)->withSessionHandler(new MySessionHandler);
```
