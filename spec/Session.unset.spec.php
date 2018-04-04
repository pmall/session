<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::unset()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when the session is not active', function () {

        it('should return false', function () {

            $test = $this->session->unset();

            expect($test)->toBeFalsy();

        });

    });

    context('when the session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->with('session_id')->returns(serialize(['key' => 'value']));

            $this->session->id('session_id');

            $this->session->start();

        });

        it('should return true', function () {

            $test = $this->session->unset();

            expect($test)->toBeTruthy();

        });

        it('should set the data as empty array', function () {

            $this->session->unset();

            $test = $this->session->data()->toArray();

            expect($test)->toEqual([]);

        });

    });

});
