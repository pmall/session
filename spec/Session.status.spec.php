<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::status()', function () {

    beforeEach(function () {

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('the session has not been started', function () {

        it('should return PHP_SESSION_NONE', function () {

            $test = $this->session->status();

            expect($test)->toEqual(PHP_SESSION_NONE);

        });

    });

    context('the session has been started', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->start();

        });

        context('when the session has not been closed', function () {

            it('should return PHP_SESSION_ACTIVE', function () {

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_ACTIVE);

            });

        });

        context('when the session has been closed', function () {

            it('should return PHP_SESSION_NONE', function () {

                $this->handler->write->returns(true);
                $this->handler->close->returns(true);

                $this->session->commit();

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_NONE);

            });

        });

    });

});
