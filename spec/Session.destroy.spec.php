<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::destroy()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when session is not active', function () {

        it('should throw a SessionDestroyException', function () {

            $test = [$this->session, 'destroy'];

            expect($test)->toThrow();

        });

    });

    context('when session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->id('session_id');

            $this->session->start();

        });

        it('should call the session handler destroy method one time with the current session id', function () {

            $this->session->id('new_session_id');

            $this->session->destroy();

            $test = $this->handler->destroy->calledWith('new_session_id')->callCount();

            expect($test)->toEqual(1);

        });

        context('when the session handler destroy method returns false', function () {

            beforeEach(function () {

                $this->handler->destroy->returns(false);

            });

            it('should return false', function () {

                $test = $this->session->destroy();

                expect($test)->toBeFalsy();

            });

            it('should not change the session status', function () {

                $this->session->destroy();

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_ACTIVE);

            });

        });

        context('when the session handler destroy method returns true', function () {

            beforeEach(function () {

                $this->handler->destroy->returns(true);

            });

            context('when the session handler close method returns false', function () {

                beforeEach(function () {

                    $this->handler->close->returns(false);

                });

                it('should return false', function () {

                    $test = $this->session->destroy();

                    expect($test)->toBeFalsy();

                });

                it('should not change the session status', function () {

                    $this->session->destroy();

                    $test = $this->session->status();

                    expect($test)->toEqual(PHP_SESSION_ACTIVE);

                });

            });

            context('when the session handler close method returns true', function () {

                beforeEach(function () {

                    $this->handler->close->returns(true);

                });

                it('should return true', function () {

                    $test = $this->session->destroy();

                    expect($test)->toBeTruthy();

                });

                it('should set the session status to inactive', function () {

                    $this->session->destroy();

                    $test = $this->session->status();

                    expect($test)->toEqual(PHP_SESSION_NONE);

                });

            });

        });

    });

});
