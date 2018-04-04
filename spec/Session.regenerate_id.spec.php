<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;
use Ellipse\Session\SessionIdManager;

describe('Session::regenerate_id()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->manager = mock(SessionIdManager::class);

        allow(SessionIdManager::class)->toBe($this->manager->get());

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when the session is not active', function () {

        it('should throw a SessionRegenerateIdException', function () {

            $test = [$this->session, 'regenerate_id'];

            expect($test)->toThrow();

        });

    });

    context('when the session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->id('session_id');

            $this->session->start();

        });

        context('when the delete old session parameter is set to true', function () {

            it('should call the session handler destroy method the current session id', function () {

                $this->session->id('new_session_id');

                $this->session->regenerate_id(true);

                $this->handler->destroy->calledWith('new_session_id');

            });

            context('when the session handler destroy method return false', function () {

                it('should return false', function () {

                    $this->handler->destroy->returns(false);

                    $test = $this->session->regenerate_id(true);

                    expect($test)->toBeFalsy();

                });

            });

            context('when the session handler destroy method return true', function () {

                it('should return true', function () {

                    $this->handler->destroy->returns(true);

                    $test = $this->session->regenerate_id(true);

                    expect($test)->toBeTruthy();

                });

            });

        });

        context('when the delete old session parameter is set to false', function () {

            it('should return true', function () {

                $test = $this->session->regenerate_id();

                expect($test)->toBeTruthy();

            });

            it('should not call the session handler destroy method', function () {

                $this->session->regenerate_id();

                $this->handler->destroy->never()->called();

            });

            it('should update the current session id by using the session id manager generateWithNoCollision', function () {

                $this->manager->generateWithNoCollision->with($this->handler, '')->returns('new_session_id');

                $this->session->regenerate_id();

                $test = $this->session->id();

                expect($test)->toEqual('new_session_id');

            });

        });

    });

});
