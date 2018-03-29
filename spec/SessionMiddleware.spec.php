<?php

use function Eloquent\Phony\Kahlan\mock;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Zend\Diactoros\Response\TextResponse;

use Ellipse\Session\SessionMiddleware;
use Ellipse\Session\Exceptions\SessionStartException;
use Ellipse\Session\Exceptions\SessionDisabledException;
use Ellipse\Session\Exceptions\SessionAlreadyStartedException;
use Ellipse\Session\Exceptions\SessionAlreadyClosedException;

describe('SessionMiddleware', function () {

    beforeEach(function () {

        $this->middleware = new SessionMiddleware;

    });

    it('should implement MiddlewareInterface', function () {

        expect($this->middleware)->toBeAnInstanceOf(MiddlewareInterface::class);

    });

    describe('->withCookieName()', function () {

        it('should return a new SessionMiddleware using the given name', function () {

            $test = $this->middleware->withCookieName('test');

            $middleware = new SessionMiddleware('test');

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withCookieOptions()', function () {

        it('should return a new SessionMiddleware using the given options', function () {

            $test = $this->middleware->withCookieOptions(['key' => 'value']);

            $middleware = new SessionMiddleware(null, ['key' => 'value']);

            expect($test)->toEqual($middleware);

        });

    });

    describe('->withSessionHandler()', function () {

        it('should return a new SessionMiddleware using the given session handler', function () {

            $handler = mock(SessionHandlerInterface::class)->get();

            $test = $this->middleware->withSessionHandler($handler);

            $middleware = new SessionMiddleware(null, [], $handler);

            expect($test)->toEqual($middleware);

        });

    });

    describe('->process()', function () {

        beforeEach(function () {

            $this->request = mock(ServerRequestInterface::class);
            $this->handler = mock(RequestHandlerInterface::class);

        });

        context('when session is disabled', function () {

            it('should throw a SessionDisabledException', function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_DISABLED);

                $test = function () {

                    $this->middleware->process($this->request->get(), $this->handler->get());

                };

                expect($test)->toThrow(new SessionDisabledException);

            });

        });

        context('when session has already been started', function () {

            it('should throw a SessionAlreadyStartedException', function () {

                allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_ACTIVE);

                $test = function () {

                    $this->middleware->process($this->request->get(), $this->handler->get());

                };

                expect($test)->toThrow(new SessionAlreadyStartedException);

            });

        });

        context('when session are enabled and not already started', function () {

            context('when session_start() returns false', function () {

                it('should throw a SessionStartException', function () {

                    allow('session_start')->toBeCalled()
                        ->with(SessionMiddleware::SESSION_OPTIONS)
                        ->andReturn(false);

                    $test = function () {

                        $this->middleware->process($this->request->get(), $this->handler->get());

                    };

                    expect($test)->toThrow(new SessionStartException);

                });

            });

            context('when session_start() returns true', function () {

                beforeEach(function () {

                    allow('session_start')->toBeCalled()
                        ->with(SessionMiddleware::SESSION_OPTIONS)
                        ->andReturn(true);

                    $this->response = new TextResponse('body', 404, ['set-cookie' => 'test=value']);

                    $this->handler->handle->returns($this->response);

                });

                context('when the session is prematurely closed', function () {

                    it('should throw a SessionAlreadyClosedException', function () {

                        allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_NONE, PHP_SESSION_NONE, PHP_SESSION_NONE);

                        $test = function () {

                            $this->middleware->process($this->request->get(), $this->handler->get());

                        };

                        expect($test)->toThrow(new SessionAlreadyClosedException);

                    });

                });

                context('when the session is not prematurely closed', function () {

                    beforeEach(function () {

                        allow('session_status')->toBeCalled()->andReturn(PHP_SESSION_NONE, PHP_SESSION_NONE, PHP_SESSION_ACTIVE);

                    });

                    it('should not update body, status code and headers of the response returned by the request handler', function () {

                        $response = $this->middleware->process($this->request->get(), $this->handler->get());

                        $body = $response->getBody()->getContents();
                        $code = $response->getStatusCode();
                        $content = $response->getHeaderLine('content-type');
                        $cookie = $response->getHeaderLine('set-cookie');

                        expect($body)->toEqual('body');
                        expect($code)->toEqual(404);
                        expect($content)->toContain('text/plain');
                        expect($cookie)->toContain('test=value');

                    });

                    it('should call session_write_close', function () {

                        $this->called = false;

                        allow('session_write_close')->toBeCalled()->andRun(function () {

                            $this->called = true;

                        });

                        $this->middleware->process($this->request->get(), $this->handler->get());

                        expect($this->called)->toBeTruthy();

                    });

                    context('when no session cookie name is given', function () {

                        beforeEach(function () {

                            allow('session_name')->toBeCalled()->andReturn('default_session_name');

                        });

                        context('when the request do not have a cookie with the default name', function () {

                            it('should attach a cookie with the default name and a new session id to the response', function () {

                                $this->request->getCookieParams->returns([]);

                                allow('session_id')->toBeCalled()->andReturn('newsessionid');

                                $response = $this->middleware->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('default_session_name=newsessionid');

                            });

                        });

                        context('when the request has a cookie with the default name', function () {

                            it('should attach a cookie with the default name and the incoming session id to the response', function () {

                                $this->request->getCookieParams->returns([
                                    'default_session_name' => 'incomingsessionid',
                                ]);

                                $response = $this->middleware->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('default_session_name=incomingsessionid');

                            });

                        });

                    });

                    context('when a session cookie name is given', function () {

                        context('when the request do not have a cookie with this name', function () {

                            it('should attach a cookie with this name and a new session id to the response', function () {

                                $this->request->getCookieParams->returns([]);

                                allow('session_id')->toBeCalled()->andReturn('newsessionid');

                                $response = $this->middleware->withCookieName('newname')
                                    ->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('newname=newsessionid');

                            });

                        });

                        context('when the request has a cookie with this name', function () {

                            it('should attach a cookie with this name and the incoming session id to the response', function () {

                                $this->request->getCookieParams->returns([
                                    'newname' => 'newsessionid',
                                ]);

                                $response = $this->middleware->withCookieName('newname')
                                    ->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('newname=newsessionid');

                            });

                        });

                    });

                    context('when no session cookie option are given', function () {

                        it('should attach a cookie with the default session cookie options', function () {

                            allow('session_get_cookie_params')->toBeCalled()->andReturn([
                                'path' => '/default/path',
                                'domain' => 'default.domain.com',
                                'lifetime' => 3600,
                                'secure' => false,
                                'httponly' => false,
                            ]);

                            $response = $this->middleware->process($this->request->get(), $this->handler->get());

                            $test = $response->getHeaderLine('set-cookie');

                            $maxage = 3600;
                            $expires = gmdate('D, d M Y H:i:s T', time() + $maxage);

                            expect($test)->toContain('Path=/default/path');
                            expect($test)->toContain('Domain=default.domain.com');
                            expect($test)->toContain('Expires=' . $expires);
                            expect($test)->toContain('Max-Age=' . $maxage);
                            expect($test)->not->toContain('HttpOnly');
                            expect($test)->not->toContain('Secure');

                        });

                    });

                    context('when session cookie option are given', function () {

                        context('when the array keys are lowercased', function () {

                            it('should attach a cookie with those session cookie options', function () {

                                $options = [
                                    'path' => '/new/path',
                                    'domain' => 'new.path.com',
                                    'lifetime' => 0,
                                    'secure' => true,
                                    'httponly' => true,
                                ];

                                $response = $this->middleware->withCookieOptions($options)
                                    ->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('Path=/new/path');
                                expect($test)->toContain('Domain=new.path.com');
                                expect($test)->not->toContain('Expires');
                                expect($test)->not->toContain('Max-Age');
                                expect($test)->toContain('HttpOnly');
                                expect($test)->toContain('Secure');

                            });

                        });

                        context('when the array keys are uppercased', function () {

                            it('should attach a cookie with those session cookie options', function () {

                                $options = [
                                    'PATH' => '/new/path',
                                    'DOMAIN' => 'new.path.com',
                                    'LIFETIME' => 0,
                                    'SECURE' => true,
                                    'HTTPONLY' => true,
                                ];

                                $response = $this->middleware->withCookieOptions($options)
                                    ->process($this->request->get(), $this->handler->get());

                                $test = $response->getHeaderLine('set-cookie');

                                expect($test)->toContain('Path=/new/path');
                                expect($test)->toContain('Domain=new.path.com');
                                expect($test)->not->toContain('Expires');
                                expect($test)->not->toContain('Max-Age');
                                expect($test)->toContain('HttpOnly');
                                expect($test)->toContain('Secure');

                            });

                        });

                    });

                    context('when no session handler is given', function () {

                        it('should not call session_set_save_handler', function () {

                            $this->called = false;

                            allow('session_set_save_handler')->toBeCalled()->andRun(function () {

                                $this->called = true;

                            });

                            $this->middleware->process($this->request->get(), $this->handler->get());

                            expect($this->called)->toBeFalsy();

                        });

                    });

                    context('when a session handler is given', function () {

                        it('should call session_set_save_handler with this session handler', function () {

                            $handler = mock(SessionHandlerInterface::class)->get();

                            $this->called = false;

                            allow('session_set_save_handler')->toBeCalled()->with($handler)->andRun(function () {

                                $this->called = true;

                            });

                            $this->middleware->withSessionHandler($handler)
                                ->process($this->request->get(), $this->handler->get());

                            expect($this->called)->toBeTruthy();

                        });

                    });

                });

            });

        });

    });

});
