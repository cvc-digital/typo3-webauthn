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
use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
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

    /**
     * @var array
     */
    private $backendExtensionConfiguration;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSourceRepository;

    public function __construct()
    {
        $this->backendUserRepository = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(BackendUserRepository::class);
        $this->webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $this->webAuthnSession = GeneralUtility::makeInstance(WebAuthnSession::class);
        $this->backendExtensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cvc_webauthn');
        $this->publicKeyCredentialSourceRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(PublicKeyCredentialSourceRepository::class);
    }

    public function processLoginData(array &$loginData, $passwordTransmissionStrategy)
    {
        $isProcessed = false;
        if ($passwordTransmissionStrategy === 'normal') {
            $loginData['webauthn-uident'] = GeneralUtility::_GP('webauthn-userident');
            if ($loginData['uident'] == '') {
                $loginData['uident'] = $loginData['webauthn-uident'];
            } else {
                $loginData['uident-text'] = $loginData['uident'];
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
        $beUser = $this->backendUserRepository->findOneByUserName((string) $user['username']);

        //if backendUser does not exist authentication is failed
        if ($beUser === null) {
            return 0;
        }

        $beUserEntity = $this->webAuthnService->createUserEntity($beUser);
        $authenticators = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($beUserEntity);

        if (empty($authenticators)) {
            return parent::authUser($user);
        }

        if ($this->backendExtensionConfiguration['secondFactorLogin'] == '0') {
            return $this->verifyAuthenticatorForUser($beUser);
        }

        $serviceChainValue = parent::authUser($user);

        if ($serviceChainValue == 200) {
            $serviceChainValue = $this->verifyAuthenticatorForUser($beUser);
        }

        return $serviceChainValue;
    }

    private function verifyAuthenticatorForUser(BackendUser $beUser): int
    {
        if (!$this->webAuthnSession->hasChallenge()) {
            return 0;
        }

        $data = base64_decode($this->login['webauthn-uident']);
        $challenge = $this->webAuthnSession->getChallenge();
        $this->webAuthnSession->purgeChallenge();
        $publicKeyCredentialRequestOptions = $this->webAuthnService->createCredentialsRequestOptions($beUser, $challenge);

        try {
            $this->webAuthnService->authenticate($publicKeyCredentialRequestOptions, $data, $beUser);

            return 200;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
