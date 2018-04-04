<?php declare(strict_types=1);

namespace Ellipse;

class SessionData implements \ArrayAccess
{
    /**
     * The session data.
     *
     * @var array
     */
    private $data;

    /**
     * Set up session data with the given array.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Return the value associated to the given offset or the given default.
     *
     * @param string    $offset
     * @param mixed     $default
     * @return mixed
     */
    public function get(string $offset, $default = null)
    {
        return $this->offsetExists($offset)
            ? $this->offsetGet($offset)
            : $default;
    }

    /**
     * Return whether the given offset exists or not.
     *
     * @param string $offset
     * @return bool
     */
    public function has(string $offset): bool
    {
        return $this->offsetExists($offset);
    }

    /**
     * Set the given value at the given offset.
     *
     * @param string    $offset
     * @param mixed     $value
     * @return void
     */
    public function set(string $offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * Unset the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function unset(string $offset)
    {
        $this->offsetUnset($offset);
    }

    /**
     * Return all the session data as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
