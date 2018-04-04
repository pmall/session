<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;
use Ellipse\Session\SessionIdManager;

describe('Session::create_id()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->manager = mock(SessionIdManager::class);

        allow(SessionIdManager::class)->toBe($this->manager->get());

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when the session is not active', function () {

        context('when no prefix is given', function () {

            it('should call the session id manager generate method with an empty prefix', function () {

                $this->manager->generate->with($this->handler, '')->returns('session_id');

                $test = $this->session->create_id();

                expect($test)->toEqual('session_id');

            });

        });

        context('when a prefix is given', function () {

            context('when the given prefix is valid', function () {

                it('should call the session id manager generate method with the given prefix', function () {

                    $this->manager->generate->with($this->handler, 'prefix0,9-')->returns('session_id');

                    $test = $this->session->create_id('prefix0,9-');

                    expect($test)->toEqual('session_id');

                });

            });

            context('when the given prefix is not valid', function () {

                it('throw a SessionIdPrefixException', function () {

                    $test = function () {

                        $this->session->create_id('prefix#');

                    };

                    expect($test)->toThrow();

                });

            });

        });

    });

    context('when the session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->start();

        });

        context('when no prefix is given', function () {

            it('should call the session id manager generateWithNoCollision method with an empty prefix', function () {

                $this->manager->generateWithNoCollision->with($this->handler, '')->returns('session_id');

                $test = $this->session->create_id();

                expect($test)->toEqual('session_id');

            });

        });

        context('when a prefix is given', function () {

            context('when the given prefix is valid', function () {

                it('should call the session id manager generateWithNoCollision method with the given prefix', function () {

                    $this->manager->generateWithNoCollision->with($this->handler, 'prefix0,9-')->returns('session_id');

                    $test = $this->session->create_id('prefix0,9-');

                    expect($test)->toEqual('session_id');

                });

            });

            context('when the given prefix is not valid', function () {

                it('throw a SessionIdPrefixException', function () {

                    $test = function () {

                        $this->session->create_id('prefix#');

                    };

                    expect($test)->toThrow();

                });

            });

        });

    });

});
