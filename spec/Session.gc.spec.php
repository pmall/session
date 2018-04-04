<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::gc()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);
        ini_set('session.gc_maxlifetime', 3600);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when the session is not active', function () {

        it('should throw a SessionGcException', function () {

            $test = [$this->session, 'gc'];

            expect($test)->toThrow();

        });

    });

    context('when the session is active', function () {

        it('should call the session handler gc method with the gc_maxlifetime configuration value', function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->start();

            $this->handler->gc->with(3600)->returns(10);

            $test = $this->session->gc();

            expect($test)->toEqual(10);

        });

    });

});
