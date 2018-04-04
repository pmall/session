<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::save_path()', function () {

    beforeEach(function () {

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

        $this->session->save_path('session_save_path');

    });

    context('when no session save_path is given', function () {

        it('should return the current session save_path', function () {

            $test = $this->session->save_path();

            expect($test)->toEqual('session_save_path');

        });

        it('should not update the current session save_path', function () {

            $this->session->save_path();

            $test = $this->session->save_path();

            expect($test)->toEqual('session_save_path');

        });

    });

    context('when a session save_path is given', function () {

        it('should return the current session save_path', function () {

            $test = $this->session->save_path('new_session_save_path');

            expect($test)->toEqual('session_save_path');

        });

        it('should update the current session save_path', function () {

            $this->session->save_path('new_session_save_path');

            $test = $this->session->save_path();

            expect($test)->toEqual('new_session_save_path');

        });

    });

});
