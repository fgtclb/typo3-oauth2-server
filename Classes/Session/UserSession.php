<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Session;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Wrapper for the frontend user session
 */
final class UserSession
{
    public function __construct(
        private readonly FrontendUserAuthentication $frontendUser
    ) {}

    /**
     * Retrieve data from the user session
     */
    public function getData(string $key): mixed
    {
        return $this->frontendUser->getSessionData($key);
    }

    /**
     * Store data in the user session
     */
    public function setData(string $key, mixed $data): void
    {
        $this->frontendUser->setAndSaveSessionData($key, $data);
    }

    /**
     * Remove data from the user session
     */
    public function removeData(string $key): void
    {
        $this->frontendUser->setAndSaveSessionData($key, null);
    }
}
