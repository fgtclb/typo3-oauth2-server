<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Exception;

/**
 * A lifetime of the extension configuration is no valid relative date format.
 *
 * Extends `\InvalidArgumentException`, so code catching that still catches this one.
 */
final class InvalidLifetimeException extends \InvalidArgumentException {}
