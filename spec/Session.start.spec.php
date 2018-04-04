<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;
use Ellipse\Session\SessionIdManager;

describe('Session::start()', function () {

    beforeEach(function () {

        ini_set('session.name', 'session_name');
        ini_set('session.save_path', 'session_save_path');
        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->manager = mock(SessionIdManager::class);

        allow(SessionIdManager::class)->toBe($this->manager->get());

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when session is active', function () {

        it('should throw a SessionStartException', function () {

            $this->handler->open->returns(true);
            $this->handler->read->returns('');

            $this->session->start();

            $test = [$this->session, 'start'];

            expect($test)->toThrow();

        });

    });

    context('when session is not active', function () {

        it('should call the session handler open method with the session name and save path configuration values', function () {

            $this->session->start();

            $this->handler->open->calledWith('session_save_path', 'session_name');

        });

        context('when the session handler open method retruns false', function () {

            beforeEach(function () {

                $this->handler->open->returns(false);

            });

            it('should return false', function () {

                $test = $this->session->start();

                expect($test)->toBeFalsy();

            });

            it('should not change the session status', function () {

                $this->session->start();

                $test = $this->session->status();

                expect($test)->toEqual(PHP_SESSION_NONE);

            });

            it('should not update the current session id', function () {

                $this->session->id('session_id');

                $this->session->start();

                $test = $this->session->id();

                expect($test)->toEqual('session_id');

            });

            it('should not call the session handler read method', function () {

                $this->session->start();

                $this->handler->read->never()->called();

            });

            it('should not call the session handler gc method', function () {

                ini_set('session.gc_probability', 1);
                ini_set('session.gc_divisor', 1);

                $this->session->start();

                $this->handler->gc->never()->called();

            });

        });

        context('when the session handler open method retruns true', function () {

            beforeEach(function () {

                $this->handler->open->returns(true);
                $this->handler->read->returns(serialize(['key' => 'value']));

            });

            it('should populate the session data', function () {

                $this->session->start();

                $test = $this->session->data()->toArray();

                expect($test)->toEqual(['key' => 'value']);

            });

            context('when the garbage collection should not be run', function () {

                it('should not call the session handler gc method', function () {

                    $this->session->start();

                    $this->handler->gc->never()->called();

                });

            });

            context('when the garbage collection should be run', function () {

                it('should call the session handler gc method with the gc_maxlifetime configuration value', function () {

                    ini_set('session.gc_probability', 1);
                    ini_set('session.gc_divisor', 1);
                    ini_set('session.gc_maxlifetime', 3600);

                    $this->handler->gc->returns(1);

                    $this->session->start();

                    $this->handler->gc->calledWith(3600);

                });

            });

            context('when no id is set', function () {

                beforeEach(function () {

                    $this->manager->generateWithNoCollision->with($this->handler, '')->returns('session_id');

                });

                it('should update the session id by using the session id manager generateWithNoCollision method', function () {

                    $this->session->start();

                    $test = $this->session->id();

                    expect($test)->toEqual('session_id');

                });

                it('should call the session handler read method with the generated session id', function () {

                    $this->session->start();

                    $this->handler->read->calledWith('session_id');

                });

            });

            context('when a session id is set', function () {

                beforeEach(function () {

                    $this->session->id('session_id');

                });

                context('when the use_strict_mode configuration value is equal to 0', function () {

                    beforeEach(function () {

                        ini_set('session.use_strict_mode', 0);

                    });

                    it('should call the session handler read method with the current session id', function () {

                        $this->session->start();

                        $this->handler->read->calledWith('session_id');

                    });

                });

                context('when the use_strict_mode configuration value is equal to 1', function () {

                    beforeEach(function () {

                        ini_set('session.use_strict_mode', 1);

                    });

                    context('when the session id is valid', function () {

                        beforeEach(function () {

                            $this->manager->isValid->with($this->handler, 'session_id')->returns(true);

                        });

                        it('should call the session handler read method with the current session id', function () {

                            $this->session->start();

                            $this->handler->read->calledWith('session_id');

                        });

                    });

                    context('when the session id is not valid', function () {

                        beforeEach(function () {

                            $this->manager->isValid->with($this->handler, 'session_id')->returns(false);

                            $this->manager->generateWithNoCollision->with($this->handler, '')->returns('new_session_id');

                        });

                        it('should update the session id by using the session id manager generateWithNoCollision method', function () {

                            $this->session->start();

                            $test = $this->session->id();

                            expect($test)->toEqual('new_session_id');

                        });

                        it('should call the session handler read method with the generated session id', function () {

                            $this->session->start();

                            $this->handler->read->calledWith('new_session_id');

                        });

                    });

                });

            });

        });

    });

});
