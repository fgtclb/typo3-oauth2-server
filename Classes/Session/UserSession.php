<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Session;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Wrapper for the frontend user session
 */
final class UserSession
{
    /**
     * @var FrontendUserAuthentication
     */
    protected $frontendUser;

    public function __construct(FrontendUserAuthentication $frontendUser)
    {
        $this->frontendUser = $frontendUser;
    }

    /**
     * Retrieve data from the user session
     *
     * @param string $key
     * @return mixed
     */
    public function getData(string $key)
    {
        return $this->frontendUser->getSessionData($key);
    }

    /**
     * Store data in the user session
     *
     * @param string $key
     * @param mixed $data
     */
    public function setData(string $key, $data): void
    {
        $this->frontendUser->setAndSaveSessionData($key, $data);
    }

    /**
     * Store data in the user session
     *
     * @param string $key
     */
    public function removeData(string $key): void
    {
        $this->frontendUser->setAndSaveSessionData($key, null);
    }
}
