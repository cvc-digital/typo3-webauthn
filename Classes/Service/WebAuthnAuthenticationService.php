<?php

/*
 * WebAuthn extension for TYPO3 CMS
 * Copyright (C) 2020 CARL von CHIARI GmbH
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

    public function __construct(
        ?BackendUserRepository $backendUserRepository = null,
        ?WebAuthnService $webAuthnService = null,
        ?WebAuthnSession $webAuthnSession = null,
        array $backendExtensionConfiguration = [],
        ?PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository = null
    ) {
        $this->backendUserRepository = $backendUserRepository ?? GeneralUtility::makeInstance(ObjectManager::class)->get(BackendUserRepository::class);
        $this->webAuthnService = $webAuthnService ?? WebAuthnServiceFactory::fromGlobals();
        $this->webAuthnSession = $webAuthnSession ?? GeneralUtility::makeInstance(WebAuthnSession::class);
        $this->backendExtensionConfiguration = $backendExtensionConfiguration ?? GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cvc_webauthn');
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository ?? GeneralUtility::makeInstance(ObjectManager::class)->get(PublicKeyCredentialSourceRepository::class);
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

        if ($beUser === null) {
            return 0;
        }

        $beUserEntity = $this->webAuthnService->createUserEntity($beUser);
        $authenticators = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($beUserEntity);

        if (empty($authenticators)) {
            return 100;
        }

        if (!$this->verifyAuthenticatorForUser($beUser)) {
            return 0;
        }

        if ($this->backendExtensionConfiguration['secondFactorLogin'] == '0') {
            return 200;
        }

        return 1;
    }

    private function verifyAuthenticatorForUser(BackendUser $beUser): bool
    {
        if (!$this->webAuthnSession->hasChallenge()) {
            return false;
        }

        $data = base64_decode($this->login['webauthn-uident']);
        $challenge = $this->webAuthnSession->getChallenge();
        $this->webAuthnSession->purgeChallenge();
        $publicKeyCredentialRequestOptions = $this->webAuthnService->createCredentialsRequestOptions($beUser, $challenge);

        try {
            $this->webAuthnService->authenticate($publicKeyCredentialRequestOptions, $data, $beUser);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
