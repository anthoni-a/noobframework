<?php

namespace Core;

class Cookie
{
    /** @var string name prefix indicating that the cookie must be from a secure origin (i.e. HTTPS) and the 'secure' attribute must be set */
    const string PREFIX_SECURE = '__Secure-';
    /** @var string name prefix indicating that the 'domain' attribute must *not* be set, the 'path' attribute must be '/' and the effects of {@see PREFIX_SECURE} apply as well */
    const string PREFIX_HOST = '__Host-';
    const string HEADER_PREFIX = 'Set-Cookie: ';
    const string SAME_SITE_RESTRICTION_NONE = 'None';
    const string SAME_SITE_RESTRICTION_LAX = 'Lax';
    const string SAME_SITE_RESTRICTION_STRICT = 'Strict';

    /** The name of the cookie which is also the key for future accesses via `$_COOKIE[...]` */
    private string $name;
    /** @var mixed|null the value of the cookie that will be stored on the client's machine */
    private mixed $value;
    /** The Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds` */
    private int $expiryTime;
    /** The path on the server that the cookie will be valid for (including all subdirectories), e.g. an empty string for the current directory or `/` for the root directory */
    private string $path;
    /** The domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains) */
    private ?string $domain;
    /** @var bool indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages */
    private bool $httpOnly;
    /** Indicates that the cookie should be sent back by the client over secure HTTPS connections only */
    private bool $secureOnly;
    /** Indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`) */
    private string|null $sameSiteRestriction;

    /**
     * Prepares a new cookie
     *
     * @param string $name the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     */
    public function __construct(string $name) {
        $this->name = $name;
        $this->value = null;
        $this->expiryTime = 0;
        $this->path = '/';
        $this->domain = null;
        $this->httpOnly = true;
        $this->secureOnly = false;
        $this->sameSiteRestriction = self::SAME_SITE_RESTRICTION_LAX;
    }

    /**
     * Returns the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the value of the cookie
     *
     * @return mixed|null the value of the cookie that will be stored on the client's machine
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sets the value for the cookie
     *
     * @param mixed|null $value the value of the cookie that will be stored on the client's machine
     */
    public function setValue(mixed $value): self {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns the expiry time of the cookie, the Unix timestamp indicating the time that the cookie will expire at,
     * i.e. usually `time() + $seconds`
     */
    public function getExpiryTime(): int
    {
        return $this->expiryTime;
    }

    /**
     * Sets the expiry time for the cookie
     *
     * @param int $expiryTime the Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds`
     * @return self this instance for chaining
     */
    public function setExpiryTime(int $expiryTime): self
    {
        $this->expiryTime = $expiryTime;

        return $this;
    }

    /**
     * Returns the maximum age of the cookie (i.e. the remaining lifetime)
     */
    public function getMaxAge(): int
    {
        return $this->expiryTime - \time();
    }

    /**
     * Sets the expiry time for the cookie based on the specified maximum age (i.e. the remaining lifetime)
     *
     * @param int $maxAge the maximum age for the cookie in seconds
     * @return self this instance for chaining
     */
    public function setMaxAge(int $maxAge): self
    {
        $this->expiryTime = \time() + $maxAge;

        return $this;
    }

    /**
     * Returns the path of the cookie
     *
     * @return string the path on the server that the cookie will be valid for (including all subdirectories), e.g. an empty string for the current directory or `/` for the root directory
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets the path for the cookie
     *
     * @param string $path the path on the server that the cookie will be valid for (including all subdirectories), e.g. an empty string for the current directory or `/` for the root directory
     * @return self this instance for chaining
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Returns the domain of the cookie
     *
     * @return string|null the domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Sets the domain for the cookie
     *
     * @param string|null $domain the domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
     * @return self this instance for chaining
     */
    public function setDomain(string $domain = null): self
    {
        $this->domain = self::normalizeDomain($domain);

        return $this;
    }

    /**
     * Returns whether the cookie should be accessible through HTTP only
     *
     * @return bool whether the cookie should be accessible through the HTTP protocol only and not through scripting languages
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Sets whether the cookie should be accessible through HTTP only
     *
     * @param bool $httpOnly indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages
     * @return self this instance for chaining
     */
    public function setHttpOnly(bool $httpOnly): self
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    /**
     * Returns whether the cookie should be sent over HTTPS only
     *
     * @return bool whether the cookie should be sent back by the client over secure HTTPS connections only
     */
    public function isSecureOnly(): bool
    {
        return $this->secureOnly;
    }

    /**
     * Sets whether the cookie should be sent over HTTPS only
     *
     * @param bool $secureOnly indicates that the cookie should be sent back by the client over secure HTTPS connections only
     * @return self this instance for chaining
     */
    public function setSecureOnly(bool $secureOnly): self
    {
        $this->secureOnly = $secureOnly;

        return $this;
    }

    /**
     * Returns the same-site restriction of the cookie
     *
     * @return string|null whether the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     */
    public function getSameSiteRestriction(): ?string
    {
        return $this->sameSiteRestriction;
    }

    /**
     * Sets the same-site restriction for the cookie
     *
     * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     * @return self this instance for chaining
     */
    public function setSameSiteRestriction(?string $sameSiteRestriction): self
    {
        $this->sameSiteRestriction = $sameSiteRestriction;

        return $this;
    }

    /**
     * Saves the cookie
     *
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to set the cookie)
     */
    public function save(): bool
    {
        return self::addHttpHeader((string) $this);
    }

    /**
     * Saves the cookie and immediately creates the corresponding variable in the superglobal `$_COOKIE` array
     *
     * The variable would otherwise only be available starting from the next HTTP request
     *
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to set the cookie)
     */
    public function saveAndSet(): bool
    {
        $_COOKIE[$this->name] = $this->value;

        return $this->save();
    }

    /**
     * Deletes the cookie
     *
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to delete the cookie)
     */
    public function delete(): bool
    {
        // create a temporary copy of this cookie so that it isn't corrupted
        $copiedCookie = clone $this;
        // set the copied cookie's value to an empty string which internally sets the required options for a deletion
        $copiedCookie->setValue('');
        // set the copied cookie's value to 1 hour ago to clear the cookie on the client side
        $copiedCookie->setExpiryTime(time() - 3600);

        // save the copied "deletion" cookie
        return $copiedCookie->save();
    }

    /**
     * Deletes the cookie and immediately removes the corresponding variable from the superglobal `$_COOKIE` array
     *
     * The variable would otherwise only be deleted at the start of the next HTTP request
     *
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to delete the cookie)
     */
    public function deleteAndUnset(): bool
    {
        unset($_COOKIE[$this->name]);

        return $this->delete();
    }

    public function __toString() {
        return self::buildCookieHeader($this->name, $this->value, $this->expiryTime, $this->path, $this->domain, $this->secureOnly, $this->httpOnly, $this->sameSiteRestriction);
    }

    /**
     * Sets a new cookie in a way compatible to PHP's `setcookie(...)` function
     *
     * @param string $name the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     * @param mixed|null $value the value of the cookie that will be stored on the client's machine
     * @param int $expiryTime the Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds`
     * @param string|null $path the path on the server that the cookie will be valid for (including all subdirectories), e.g. an empty string for the current directory or `/` for the root directory
     * @param string|null $domain the domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
     * @param bool $secureOnly indicates that the cookie should be sent back by the client over secure HTTPS connections only
     * @param bool $httpOnly indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages
     * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     * @return bool whether the cookie header has successfully been sent (and will *probably* cause the client to set the cookie)
     */
    public static function setcookie(string $name, mixed $value = null, int $expiryTime = 0, ?string $path = null, ?string $domain = null, bool $secureOnly = false, bool $httpOnly = false, ?string $sameSiteRestriction = null): bool
    {
        return self::addHttpHeader(
            self::buildCookieHeader($name, $value, $expiryTime, $path, $domain, $secureOnly, $httpOnly, $sameSiteRestriction)
        );
    }

    /**
     * Builds the HTTP header that can be used to set a cookie with the specified options
     *
     * @param string $name the name of the cookie which is also the key for future accesses via `$_COOKIE[...]`
     * @param mixed|null $value the value of the cookie that will be stored on the client's machine
     * @param int $expiryTime the Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds`
     * @param string|null $path the path on the server that the cookie will be valid for (including all sub-directories), e.g. an empty string for the current directory or `/` for the root directory
     * @param string|null $domain the domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
     * @param bool $secureOnly indicates that the cookie should be sent back by the client over secure HTTPS connections only
     * @param bool $httpOnly indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages
     * @param string|null $sameSiteRestriction indicates that the cookie should not be sent along with cross-site requests (either `null`, `None`, `Lax` or `Strict`)
     * @return ?string the HTTP header
     */
    public static function buildCookieHeader(string $name, mixed $value = null, int $expiryTime = 0, ?string $path = null, ?string $domain = null, bool $secureOnly = false, bool $httpOnly = false, ?string $sameSiteRestriction = null): ?string
    {
        if (!self::isNameValid($name)) {
            return null;
        }

        if (!self::isExpiryTimeValid($expiryTime)) {
            return null;
        }

        $forceShowExpiry = false;

        if (\is_null($value) || $value === false || $value === '') {
            $value = 'deleted';
            $expiryTime = 0;
            $forceShowExpiry = true;
        }

        $maxAgeStr = self::formatMaxAge($expiryTime, $forceShowExpiry);
        $expiryTimeStr = self::formatExpiryTime($expiryTime, $forceShowExpiry);

        $headerStr = self::HEADER_PREFIX . $name . '=' . \urlencode($value);

        if (!\is_null($expiryTimeStr)) {
            $headerStr .= '; expires=' . $expiryTimeStr;
        }

        // The `Max-Age` property is supported on PHP 5.5+ only (https://bugs.php.net/bug.php?id=23955).
        if (\PHP_VERSION_ID >= 50500) {
            if (!\is_null($maxAgeStr)) {
                $headerStr .= '; Max-Age=' . $maxAgeStr;
            }
        }

        if (!empty($path)) {
            $headerStr .= '; path=' . $path;
        }

        if (!empty($domain)) {
            $headerStr .= '; domain=' . $domain;
        }

        if ($secureOnly) {
            $headerStr .= '; secure';
        }

        if ($httpOnly) {
            $headerStr .= '; httponly';
        }

        if ($sameSiteRestriction === self::SAME_SITE_RESTRICTION_NONE) {
            // if the 'secure' attribute is missing
            if (!$secureOnly) {
                \trigger_error('When the \'SameSite\' attribute is set to \'None\', the \'secure\' attribute should be set as well', \E_USER_WARNING);
            }

            $headerStr .= '; SameSite=None';
        }
        elseif ($sameSiteRestriction === self::SAME_SITE_RESTRICTION_LAX) {
            $headerStr .= '; SameSite=Lax';
        }
        elseif ($sameSiteRestriction === self::SAME_SITE_RESTRICTION_STRICT) {
            $headerStr .= '; SameSite=Strict';
        }

        return $headerStr;
    }

    /**
     * Parses the given cookie header and returns an equivalent cookie instance
     *
     * @param string $cookieHeader the cookie header to parse
     * @return Cookie|null the cookie instance or `null`
     */
    public static function parse(string $cookieHeader): ?Cookie
    {
        if (empty($cookieHeader)) {
            return null;
        }

        if (\preg_match('/^' . self::HEADER_PREFIX . '(.*?)=(.*?)(?:; (.*?))?$/i', $cookieHeader, $matches)) {
            $cookie = new self($matches[1]);
            $cookie->setPath(null);
            $cookie->setHttpOnly(false);
            $cookie->setValue(
                \urldecode($matches[2])
            );
            $cookie->setSameSiteRestriction(null);

            if (\count($matches) >= 4) {
                $attributes = \explode('; ', $matches[3]);

                foreach ($attributes as $attribute) {
                    if (\strcasecmp($attribute, 'HttpOnly') === 0) {
                        $cookie->setHttpOnly(true);
                    }
                    elseif (\strcasecmp($attribute, 'Secure') === 0) {
                        $cookie->setSecureOnly(true);
                    }
                    elseif (\stripos($attribute, 'Expires=') === 0) {
                        $cookie->setExpiryTime((int) \strtotime(\substr($attribute, 8)));
                    }
                    elseif (\stripos($attribute, 'Domain=') === 0) {
                        $cookie->setDomain(\substr($attribute, 7));
                    }
                    elseif (\stripos($attribute, 'Path=') === 0) {
                        $cookie->setPath(\substr($attribute, 5));
                    }
                    elseif (\stripos($attribute, 'SameSite=') === 0) {
                        $cookie->setSameSiteRestriction(\substr($attribute, 9));
                    }
                }
            }

            return $cookie;
        }
        else {
            return null;
        }
    }

    /**
     * Checks whether a cookie with the specified name exists
     *
     * @param string $name the name of the cookie to check
     * @return bool whether there is a cookie with the specified name
     */
    public static function exists(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Returns the value from the requested cookie or, if not found, the specified default value
     *
     * @param string $name the name of the cookie to retrieve the value from
     * @param mixed|null $defaultValue the default value to return if the requested cookie cannot be found
     * @return mixed the value from the requested cookie or the default value
     */
    public static function get(string $name, mixed $defaultValue = null): mixed
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        else {
            return $defaultValue;
        }
    }

    private static function isNameValid(string $name): bool
    {
        // The name of a cookie must not be empty on PHP 7+ (https://bugs.php.net/bug.php?id=69523).
        if ($name !== '' || \PHP_VERSION_ID < 70000) {
            if (!\preg_match('/[=,; \\t\\r\\n\\013\\014]/', $name)) {
                return true;
            }
        }

        return false;
    }

    private static function isExpiryTimeValid($expiryTime): bool
    {
        return \is_numeric($expiryTime) || \is_null($expiryTime) || \is_bool($expiryTime);
    }

    private static function calculateMaxAge($expiryTime): int
    {
        if ($expiryTime === 0) {
            return 0;
        }
        else {
            $maxAge = $expiryTime - \time();

            // The value of the `Max-Age` property must not be negative on PHP 7.0.19+ (< 7.1) and
            // PHP 7.1.5+ (https://bugs.php.net/bug.php?id=72071).
            if ((\PHP_VERSION_ID >= 70019 && \PHP_VERSION_ID < 70100) || \PHP_VERSION_ID >= 70105) {
                if ($maxAge < 0) {
                    $maxAge = 0;
                }
            }

            return $maxAge;
        }
    }

    private static function formatExpiryTime($expiryTime, $forceShow = false): ?string
    {
        if ($expiryTime > 0 || $forceShow) {
            if ($forceShow) {
                $expiryTime = 1;
            }

            return \gmdate('D, d-M-Y H:i:s T', $expiryTime);
        }
        else {
            return null;
        }
    }

    private static function formatMaxAge($expiryTime, $forceShow = false): ?string
    {
        if ($expiryTime > 0 || $forceShow) {
            return (string) self::calculateMaxAge($expiryTime);
        }
        else {
            return null;
        }
    }

    private static function normalizeDomain($domain = null): ?string
    {
        // make sure that the domain is a string
        $domain = (string) $domain;

        // if the cookie should be valid for the current host only
        if ($domain === '') {
            // no need for further normalization
            return null;
        }

        // if the provided domain is actually an IP address
        if (\filter_var($domain, \FILTER_VALIDATE_IP) !== false) {
            // let the cookie be valid for the current host
            return null;
        }

        // for local hostnames (which either have no dot at all or a leading dot only)
        if (!str_contains($domain, '.') || \strrpos($domain, '.') === 0) {
            // let the cookie be valid for the current host while ensuring maximum compatibility
            return null;
        }

        // unless the domain already starts with a dot
        if ($domain[0] !== '.') {
            // prepend a dot for maximum compatibility (e.g. with RFC 2109)
            $domain = '.' . $domain;
        }

        // return the normalized domain
        return $domain;
    }

    private static function addHttpHeader($header): bool
    {
        if (!\headers_sent()) {
            if (!empty($header)) {
                \header($header, false);

                return true;
            }
        }

        return false;
    }
}