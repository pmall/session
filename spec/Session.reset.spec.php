<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::reset()', function () {

    beforeEach(function () {

        ini_set('session.name', 'session_name');
        ini_set('session.save_path', 'session_save_path');
        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when session is not active', function () {

        it('should return true', function () {

            $test = $this->session->reset();

            expect($test)->toBeTruthy();

        });

    });

    context('when session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->with('session_id')->returns(serialize(['key' => 'value']));

            $this->session->id('session_id');

            $this->session->start();

        });

        it('should call the session handler open method a second time with the session name and save path', function () {

            $this->session->reset();

            $test = $this->handler->open->with('session_save_path', 'session_name')
                ->called()
                ->callCount();

            expect($test)->toEqual(2);

        });

        context('when the session handler open method returns false', function () {

            beforeEach(function () {

                $this->handler->open->returns(false);

            });

            it('should return false', function () {

                $test = $this->session->reset();

                expect($test)->toBeFalsy();

            });

            it('should not call the session handler read method a second time', function () {

                $this->session->reset();

                $test = $this->handler->read->called()->callCount();

                expect($test)->toEqual(1);

            });

        });

        context('when the session handler open method returns true', function () {

            it('should return true', function () {

                $test = $this->session->reset();

                expect($test)->toBeTruthy();

            });

            it('should call the session handler read method a second time with the current session id', function () {

                $this->handler->read->with('new_session_id')->returns('');

                $this->session->id('new_session_id');

                $this->session->reset();

                $test = $this->handler->read->called()->callCount();

                expect($test)->toEqual(2);

            });

            it('should update the session data', function () {

                $this->handler->read->returns(serialize(['new_key' => 'new_value']));

                $this->session->reset();

                $test = $this->session->data()->toArray();

                expect($test)->toEqual(['new_key' => 'new_value']);

            });

        });

    });

});
