<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::name()', function () {

    beforeEach(function () {

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

        $this->session->name('session_name');

    });

    context('when no session name is given', function () {

        it('should return the current session name', function () {

            $test = $this->session->name();

            expect($test)->toEqual('session_name');

        });

        it('should not update the current session name', function () {

            $this->session->name();

            $test = $this->session->name();

            expect($test)->toEqual('session_name');

        });

    });

    context('when a session name is given', function () {

        it('should return the current session name', function () {

            $test = $this->session->name('new_session_name');

            expect($test)->toEqual('session_name');

        });

        it('should update the current session name', function () {

            $this->session->name('new_session_name');

            $test = $this->session->name();

            expect($test)->toEqual('new_session_name');

        });

    });

});
