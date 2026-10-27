<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Exception;

/**
 * A required option of the extension configuration is missing or empty.
 *
 * Extends `\InvalidArgumentException`, so code catching that still catches this one.
 */
final class MissingConfigurationException extends \InvalidArgumentException {}
