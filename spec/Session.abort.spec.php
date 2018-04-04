<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::abort()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when session is not active', function () {

        it('should return false', function () {

            $test = $this->session->abort();

            expect($test)->toBeFalsy();

        });

    });

    context('when session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->start();

        });

        context('when the session handler close method returns false', function () {

            beforeEach(function () {

                $this->handler->close->returns(false);

            });

            it('should return false', function () {

                $test = $this->session->abort();

                expect($test)->toBeFalsy();

            });

            it('should let the session status to PHP_SESSION_ACTIVE', function () {

                $this->session->abort();

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_ACTIVE);

            });

        });

        context('when the session handler close method returns true', function () {

            beforeEach(function () {

                $this->handler->close->returns(true);

            });

            it('should return true', function () {

                $test = $this->session->abort();

                expect($test)->toBeTruthy();

            });

            it('should let the session status to PHP_SESSION_NONE', function () {

                $this->session->abort();

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_NONE);

            });

        });

    });

});
