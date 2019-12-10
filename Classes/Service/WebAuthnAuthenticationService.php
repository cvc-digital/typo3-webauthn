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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class WebAuthnAuthenticationService extends AuthenticationService
{
    /**
     * @var int
     * 0 for authentication failed, 100 for not responsible, 200 for authentication success
     */
    protected $serviceChainValue = 0;
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

    /**
     * @var array
     */
    private $backendExtensionConfiguration;

    public function __construct()
    {
        $this->backendUserRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(BackendUserRepository::class);
        $this->webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $this->webAuthnSession = GeneralUtility::makeInstance(WebAuthnSession::class);
        $this->backendExtensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cvc_webauthn');
    }

    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {
        $isProcessed = false;
        if ($passwordTransmissionStrategy === 'normal') {
            $loginData['webauthn-uident'] = GeneralUtility::_GP('webauthn-userident');
            if ($loginData['uident'] == '') {
                $loginData['uident'] = $loginData['webauthn-uident'];
            }
            $isProcessed = true;
        }

        return $isProcessed;
    }

    /**
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function authUser(array $user): int
    {
        if ($this->backendExtensionConfiguration['secondFactorLogin'] == '1') {
            $this->serviceChainValue = parent::authUser($user);

            if ($this->serviceChainValue == 200) {
                $this->serviceChainValue = $this->authenticateUser((string) $user['username']);
            }
        } else {
            $this->serviceChainValue = $this->authenticateUser((string) $user['username']);
        }

        return $this->serviceChainValue;
    }

    private function authenticateUser($username): int
    {
        $serviceChainValue = 0;
        $data = base64_decode($this->login['webauthn-uident']);
        $beUser = $this->backendUserRepository->findOneByUserName($username);
        //if backendUser does not exist authentication is failed
        if ($beUser === null) {
            $serviceChainValue = 0;
        }

        if (!$this->webAuthnSession->hasChallenge()) {
            $serviceChainValue = 100;
        }

        $challenge = $this->webAuthnSession->getChallenge();
        $this->webAuthnSession->purgeChallenge();
        $publicKeyCredentialRequestOptions = $this->webAuthnService->createCredentialsRequestOptions($beUser, $challenge);

        try {
            $this->webAuthnService->authenticate($publicKeyCredentialRequestOptions, $data, $beUser);
            $serviceChainValue = 200;
        } catch (\Exception $e) {
            // authenticate() only throws exceptions. If it throws one return 0 because authentication has failed.
            $serviceChainValue = 0;
        }

        return $serviceChainValue;
    }
}
