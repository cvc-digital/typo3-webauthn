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

namespace Cvc\Typo3\CvcWebauthn\Controller;

use Cvc\Typo3\CvcWebauthn\Http\WebAuthnSession;
use Cvc\Typo3\CvcWebauthn\Service\WebAuthnService;
use Cvc\Typo3\CvcWebauthn\Service\WebAuthnServiceFactory;
use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class WebAuthnLoginController implements SingletonInterface
{
    /**
     * @var BackendUserRepository;
     */
    private $backendUserRepository;

    /**
     * @var WebAuthnService
     */
    private $webAuthnService;

    /**
     * @var BackendUserAuthentication
     */
    private $backendAuthentication;

    /**
     * @var WebAuthnSession
     */
    private $webAuthnSession;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSourceRepository;

    public function __construct()
    {
        $this->backendUserRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(BackendUserRepository::class);
        $this->webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $this->backendAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $this->webAuthnSession = GeneralUtility::makeInstance(WebAuthnSession::class);
        $this->publicKeyCredentialSourceRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(PublicKeyCredentialSourceRepository::class);
    }

    public function requestChallenge(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse([
                'error' => 'Invalid HTTP method.',
            ], 405);
        }

        /**
         * @var array
         */
        $postArguments = $request->getParsedBody();

        if (!isset($postArguments['username']) || $postArguments['username'] === '') {
            return new JsonResponse([
                'error' => 'Username is missing.',
            ], 400);
        }

        $username = (string) $postArguments['username'];
        $beUser = $this->backendUserRepository->findOneByUserName($username);

        if ($beUser === null) {
            return new JsonResponse([
                'error' => 'User not found.',
            ], 404);
        }

        $publicKeyCredentialRequestOptions = $this->webAuthnService->createCredentialsRequestOptions($beUser);
        if (count($publicKeyCredentialRequestOptions->getAllowCredentials()) == 0) {
            return new JsonResponse([
                'error' => 'No security keys registered',
            ], 404);
        }
        $this->webAuthnSession->setChallenge($publicKeyCredentialRequestOptions->getChallenge());

        return new JsonResponse($publicKeyCredentialRequestOptions->jsonSerialize());
    }
}
