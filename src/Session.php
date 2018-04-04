<?php declare(strict_types=1);

namespace Ellipse;

use Ellipse\Session\SessionIdManager;

class Session
{
    /**
     * The current session state.
     *
     * @var int
     */
    private $status = \PHP_SESSION_NONE;

    /**
     * The current session id.
     *
     * @var string
     */
    private $session_id = '';

    /**
     * The current session handler.
     *
     * @var \SessionHandlerInterface
     */
    private $handler;

    /**
     * The session id manager.
     *
     * @var \Ellipse\Session\SessionIdManager|null
     */
    private $manager = null;

    /**
     * The session data.
     *
     * @var \Ellipse\SessionData
     */
    private $data = null;

    /**
     * Set up a session with the given session handler.
     *
     * @param \SessionHandlerInterface $handler
     */
    public function __construct(\SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Return the data.
     *
     * @return \Ellipse\SessionData
     */
    public function data(): SessionData
    {
        return $this->data;
    }

    /**
     * Emulate the session_status() function.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Emulate the session_id() function.
     *
     * @param string $new
     * @return string
     */
    public function id(string $new = ''): string
    {
        $id = $this->session_id;

        if ($new != '') { $this->session_id = $new; }

        return $id;
    }

    /**
     * Emulate the session_name() function.
     *
     * @param string $new
     * @return string
     */
    public function name(string $new = ''): string
    {
        $name = ini_get('session.name');

        if ($new != '') { ini_set('session.name', $new); }

        return $name;
    }

    /**
     * Emulate the session_save_path() function.
     *
     * @param string $new
     * @return string
     */
    public function save_path(string $new = ''): string
    {
        $save_path = ini_get('session.save_path');

        if ($new != '') { ini_set('session.save_path', $new); }

        return $save_path;
    }

    /**
     * Emulate the session_set_save_handler() function.
     *
     * @param \SessionHandlerInterface $handler
     * @return bool
     */
    public function set_save_handler(\SessionHandlerInterface $handler): bool
    {
        if ($this->status === \PHP_SESSION_NONE) {

            $this->handler = $handler;

            return true;

        }

        return false;
    }

    /**
     * Emulate the session_start() function.
     *
     * @return bool
     */
    public function start(): bool
    {
        if ($this->status === \PHP_SESSION_NONE) {

            if ($this->open()) {

                // set a new session id when not already set or when the current
                // session id is available with strict mode enabled.
                $unset = $this->session_id === '';
                $strict_mode = (int) ini_get('session.use_strict_mode') === 1;

                if ($unset || ($strict_mode && ! $this->isSessionIdValid($this->session_id))) {

                    $this->session_id = $this->newSessionIdWithNoCollision();

                }

                // read the data.
                $this->read();

                // randomly run gc
                $percent = 100 * (int) ini_get('session.gc_probability') / (int) ini_get('session.gc_divisor');

                if (rand(1, 100) < $percent) { $this->gc(); }

                return true;

            }

            return false;

        }

        throw new \Exception('A session had already been started - ignoring Ellipse\Session::start()');
    }

    /**
     * Emulate the create_id() function.
     *
     * @param string $prefix
     * @return string
     */
    public function create_id(string $prefix = ''): string
    {
        if ($prefix === '' || preg_match('/^[-,a-zA-Z0-9]+$/', $prefix)) {

            return $this->status === \PHP_SESSION_NONE
                ? $this->newSessionId($prefix)
                : $this->newSessionIdWithNoCollision($prefix);

        }

        throw new \Exception('Ellipse\Session::create_id(): Prefix cannot contain special characters. Only aphanumeric, ',', '-' are allowed');
    }

    /**
     * Emulate the session_regenerate_id() function.
     *
     * @param bool $delete_old_session
     * @return bool
     */
    public function regenerate_id(bool $delete_old_session = false): bool
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            if ($delete_old_session && ! $this->handler->destroy($this->session_id)) {

                return false;

            }

            $this->session_id = $this->newSessionIdWithNoCollision();

            return true;

        }

        throw new \Exception('Ellipse\Session::regenerate_id(): Cannot regenerate session id - session is not active');
    }

    /**
     * Emulate the session_unset() function.
     *
     * @return bool
     */
    public function unset(): bool
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            $this->data = new SessionData([]);

            return true;

        }

        return false;
    }

    /**
     * Emulate the session_abort() function.
     *
     * @return bool
     */
    public function abort(): bool
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            return $this->close();

        }

        return false;
    }

    /**
     * Emulate the session_reset() function.
     *
     * @return bool
     */
    public function reset(): bool
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            if ($this->open()) {

                $this->read();

                return true;

            }

            return false;

        }

        return true;
    }

    /**
     * Emulate the session_destroy() function.
     *
     * @return bool
     */
    public function destroy(): bool
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            if ($this->handler->destroy($this->session_id)) {

                return $this->close();

            }

            return false;

        }

        throw new \Exception('Ellipse\Session::destroy(): Trying to destroy uninitialized session');
    }

    /**
     * Emulate the session_gc() function.
     *
     * @return int
     */
    public function gc(): int
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            $maxlifetime = ini_get('session.gc_maxlifetime');

            return $this->handler->gc((int) $maxlifetime);

        }

        throw new \Exception('Session::gc(): Session is not active');
    }

    /**
     * Emulate the session_commit() function.
     *
     * @return void
     */
    public function commit()
    {
        $this->write_close();
    }

    /**
     * Emulate the session_write_close() function.
     *
     * @return void
     */
    public function write_close()
    {
        if ($this->status === \PHP_SESSION_ACTIVE) {

            $this->write();
            $this->close();

        }
    }

    /**
     * Return a new session id with the given prefix
     *
     * @param string $prefix
     * @return string
     */
    private function newSessionId(string $prefix = ''): string
    {
        if (! $this->manager) $this->manager = new SessionIdManager;

        return $this->manager->generate($this->handler, $prefix);
    }

    /**
     * Return a new session id with the given prefix. Ensure there is no
     * colision.
     *
     * @param string $prefix
     * @return string
     */
    private function newSessionIdWithNoCollision(string $prefix = ''): string
    {
        if (! $this->manager) $this->manager = new SessionIdManager;

        return $this->manager->generateWithNoCollision($this->handler, $prefix);
    }

    /**
     * Return whether the given session id is valid.
     *
     * @param string $session_id
     * @return bool
     */
    private function isSessionIdValid(string $session_id): bool
    {
        if (! $this->manager) $this->manager = new SessionIdManager;

        return $this->manager->isValid($this->handler, $session_id);
    }

    /**
     * Open the session handler and set the session state as active.
     *
     * @return bool
     */
    private function open(): bool
    {
        $name = $this->name();
        $save_path = $this->save_path();

        if ($this->handler->open($save_path, $name)) {

            $this->status = \PHP_SESSION_ACTIVE;

            return true;

        }

        return false;
    }

    /**
     * Close the session handler and set the session state as not active.
     *
     * @return bool
     */
    private function close(): bool
    {
        if ($this->handler->close()) {

            $this->status = \PHP_SESSION_NONE;

            return true;

        }

        return false;
    }

    /**
     * Read the serialized session data using the session handler.
     *
     * @return void
     */
    private function read()
    {
        $serialized = $this->handler->read($this->session_id);

        $data = @unserialize($serialized);

        $this->data = new SessionData($data !== false ? $data : []);
    }

    /**
     * Write the unserialized session data using the session handler.
     *
     * @return bool
     */
    private function write(): bool
    {
        $serialized = serialize($this->data->toArray());

        return $this->handler->write($this->session_id, $serialized);
    }
}
