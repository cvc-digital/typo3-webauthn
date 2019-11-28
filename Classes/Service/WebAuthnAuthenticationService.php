<?php

/*
 * WebAuthn extension for TYPO3 CMS
 * Copyright (C) 2019 CARL von CHIARI GmbH
 *
 * This file is part of the TYPO3 CMS project.
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Cvc\Typo3\CvcWebauthn\Service;

use Cvc\Typo3\CvcWebauthn\Http\WebAuthnSession;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class WebAuthnAuthenticationService extends AuthenticationService
{
    /**
     * @var BackendUserRepository
     */
    private $backendUserRepository;

    /**
     * @var WebAuthnService;
     */
    private $webAuthnService;

    /**
     * @var WebAuthnSession
     */
    private $webAuthnSession;

    public function __construct()
    {
        $this->backendUserRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(BackendUserRepository::class);
        $this->webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $this->webAuthnSession = GeneralUtility::makeInstance(WebAuthnSession::class);
    }

    /**
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function authUser(array $user): int
    {
        $username = (string) $user['username'];
        $data = base64_decode($this->login['uident']);

        $beUser = $this->backendUserRepository->findOneByUserName($username);

        //if backendUser does not exist authentication is failed
        if ($beUser === null) {
            return 0;
        }

        if (!$this->webAuthnSession->hasChallenge()) {
            return 100;
        }

        $challenge = $this->webAuthnSession->getChallenge();
        $this->webAuthnSession->purgeChallenge();
        $publicKeyCredentialRequestOptions = $this->webAuthnService->createCredentialsRequestOptions($beUser, $challenge);

        try {
            $this->webAuthnService->authenticate($publicKeyCredentialRequestOptions, $data, $beUser);
        } catch (\Exception $e) {
            // authenticate() only throws exceptions. If it throws one return 100 as we are not responsible anymore for authentication
            return 100;
        }
        // authentication is successful
        return 200;
    }
}
