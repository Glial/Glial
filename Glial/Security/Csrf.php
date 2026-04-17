<?php
namespace Glial\Security;

/**
 * CSRF token helper — session-scoped, rotated after verification.
 *
 * Usage in a form:
 *   echo Csrf::field();
 *
 * Usage when handling POST:
 *   if (!Csrf::verify()) { http_response_code(400); exit; }
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Return the current session token, generating one on first call.
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Hidden input tag containing the current token.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    /**
     * Verify $_POST['_csrf_token'] matches the session token.
     * On success, rotate the token to prevent replay.
     */
    public static function verify(): bool
    {
        $posted = $_POST['_csrf_token'] ?? '';
        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        if ($stored === '' || !hash_equals($stored, $posted)) {
            return false;
        }
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return true;
    }
}
