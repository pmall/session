<?php declare(strict_types=1);

namespace Ellipse\Session;

class SessionIdManager
{
    /**
     * Return a session id. Use a default algorithm when the given handler is
     * not an implementation of SessionIdInterface.
     *
     * @param \SessionHandlerInterface  $handler
     * @param string                    $prefix
     * @return string
     */
    public function generate(\SessionHandlerInterface $handler, string $prefix = ''): string
    {
        return $prefix . ($handler instanceof \SessionIdInterface
            ? $handler->create_sid()
            : $this->generateDefault());
    }

    /**
     * Return a session id which does not collide with another one already used.
     *
     * @param \SessionHandlerInterface  $handler
     * @param string                    $prefix
     * @return string
     */
    public function generateWithNoCollision(\SessionHandlerInterface $handler, string $prefix = ''): string
    {
        $session_id = $this->generate($handler, $prefix);

        return $this->isValid($handler, $session_id)
            ? $this->generateWithNoCollision($handler, $prefix)
            : $session_id;
    }

    /**
     * Return whether the given session id is currently used. Use the session
     * handler validateId when it is an implementation of SessionUpdateTimestampHandlerInterface.
     *
     * @param \SessionHandlerInterface  $handler
     * @param string                    $session_id
     * @return bool
     */
    public function isValid(\SessionHandlerInterface $handler, string $session_id): bool
    {
        return $handler instanceof \SessionUpdateTimestampHandlerInterface
            ? $handler->validateId($session_id)
            : $handler->read($session_id) !== '';
    }

    /**
     * Try to emulate the default php session id generation. Shamelessly adapted
     * from one php manual comment.
     *
     * @see http://php.net/manual/en/function.session-create-id.php#121945
     *
     * @return string
     */
    private function generateDefault(): string
    {
        $desired_output_length = ini_get('session.sid_length');
        $bits_per_character = ini_get('session.sid_bits_per_character');

        $bytes_needed = (int) ceil($desired_output_length * $bits_per_character / 8);
        $random_input_bytes = random_bytes($bytes_needed);

        // The below is translated from function bin_to_readable in the PHP source (ext/session/session.c)
        $hexconvtab = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,-';

        $out = '';

        $p = 0;
        $q = strlen($random_input_bytes);
        $w = 0;
        $have = 0;

        $mask = (1 << $bits_per_character) - 1;

        $chars_remaining = $desired_output_length;
        while ($chars_remaining--) {
            if ($have < $bits_per_character) {
                if ($p < $q) {
                    $byte = ord( $random_input_bytes[$p++] );
                    $w |= ($byte << $have);
                    $have += 8;
                } else {
                    // Should never happen. Input must be large enough.
                    break;
                }
            }

            // consume $bits_per_character bits
            $out .= $hexconvtab[$w & $mask];
            $w >>= $bits_per_character;
            $have -= $bits_per_character;
        }

        return $out;
    }
}
