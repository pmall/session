# Session middleware

This package provides a [Psr-15 middleware](https://www.php-fig.org/psr/psr-15/) allowing to use session with Psr-7 request and response.

**Require** php >= 7.0

**Installation** `composer require ellipse/session`

**Run tests** `./vendor/bin/kahlan`

- [Using the session middleware](#using-the-session-middleware)

# Using the session middleware

This middleware use the default php session mechanism adapted to Psr-7 request and response flow. The default php session cookie is disabled and the session id is manually stored in a cookie retrieved from the Psr-7 request and attached to the Psr-7 response.

By default values returned by `session_name()` and `session_get_cookie_params` are used to build the session cookie. An optional array of options can be given to the middleware in order to overwrite those default values:

- (string) **path**: the session cookie path
- (string) **domain**: the session cookie domain
- (int) **lifetime**: the session cookie lifetime in second
- (bool) **secure**: whether the session cookie should only be sent over secure connections
- (bool) **httponly**: whether the session cookie can only be accessed through the HTTP protocol

```php
<?php

namespace App;

use Ellipse\Session\SessionMiddleware;

// All middleware processed after this one will have acces to the $_SESSION data.
// The session cookie name will by 'my_session_cookie'. See above for other options.
$middleware = new SessionMiddleware('my_session_cookie');
```
