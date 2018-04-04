<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::id()', function () {

    beforeEach(function () {

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

        $this->session->id('session_id');

    });

    context('when no session id is given', function () {

        it('should return the current session id', function () {

            $test = $this->session->id();

            expect($test)->toEqual('session_id');

        });

        it('should not update the current session id', function () {

            $this->session->id();

            $test = $this->session->id();

            expect($test)->toEqual('session_id');

        });

    });

    context('when a session id is given', function () {

        it('should return the current session id', function () {

            $test = $this->session->id('new_session_id');

            expect($test)->toEqual('session_id');

        });

        it('should update the current session id', function () {

            $this->session->id('new_session_id');

            $test = $this->session->id();

            expect($test)->toEqual('new_session_id');

        });

    });

});
