<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::set_save_handler()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler1 = mock(SessionHandlerInterface::class);
        $this->handler2 = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler1->get());

    });

    context('when the session is not active', function () {

        it('should return true', function () {

            $test = $this->session->set_save_handler($this->handler2->get());

            expect($test)->toBeTruthy();

        });

        it('should update the session handler', function () {

            $this->session->set_save_handler($this->handler2->get());

            $this->handler2->open->returns(false);

            $this->session->start();

            $this->handler2->open->called();

        });

    });

    context('when the session is active', function () {

        beforeEach(function () {

            $this->handler1->open->returns(true);
            $this->handler1->read->returns('');

            $this->session->start();

        });

        it('should return false', function () {

            $test = $this->session->set_save_handler($this->handler2->get());

            expect($test)->toBeFalsy();

        });

        it('should not update the session handler', function () {

            $this->handler1->write->returns(true);
            $this->handler1->close->returns(true);

            $this->session->set_save_handler($this->handler2->get());

            $this->session->commit();

            $this->handler1->close->called();

        });

    });

});
