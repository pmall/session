<?php

use function Eloquent\Phony\Kahlan\mock;

use Ellipse\Session;

describe('Session::commit()', function () {

    beforeEach(function () {

        ini_set('session.use_strict_mode', 0);
        ini_set('session.gc_probability', 0);

        $this->handler = mock(SessionHandlerInterface::class);

        $this->session = new Session($this->handler->get());

    });

    context('when the session is not active', function () {

        it('should do nothing', function () {

            $this->session->commit();

            $this->handler->write->never()->called();
            $this->handler->close->never()->called();

        });

    });

    context('when the session is active', function () {

        beforeEach(function () {

            $this->handler->open->returns(true);
            $this->handler->read->with('session_id')->returns(serialize(['key' => 'value']));

            $this->session->id('session_id');

            $this->session->start();

            $this->handler->write->returns(true);
            $this->handler->close->returns(true);

        });

        it('should call the session handler write method with the current session id and data', function () {

            $this->session->id('new_session_id');
            $this->session->data()->set('new_key', 'new_value');

            $this->session->commit();

            $this->handler->write->calledWith('new_session_id', serialize([
                'key' => 'value',
                'new_key' => 'new_value',
            ]));

        });

        it('should call the session handler close method', function () {

            $this->session->data()->set('new_key', 'new_value');

            $this->session->commit();

            $this->handler->close->called();

        });

    });

});
