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

use Cose\Algorithms;
use Cvc\Typo3\CvcWebauthn\Factory\Factory;
use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnService
{
    public const TIMEOUT = 60000;
    private const CHALLENGE_LENGTH = 32;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    private $rpEntity;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $publicKeyCredentialSourceRepository;

    /**
     * @var Factory
     */
    private $factory;

    public function __construct(string $name, string $id, PublicKeyCredentialSourceRepository $publicKeyCredentialSourceRepository)
    {
        $this->rpEntity = new PublicKeyCredentialRpEntity($name, $id);
        $this->publicKeyCredentialSourceRepository = $publicKeyCredentialSourceRepository;
        $this->factory = Factory::createFactory($this->publicKeyCredentialSourceRepository);
    }

    public function createCredentialCreationOptions(BackendUser $backendUser, ?string $challenge = null): PublicKeyCredentialCreationOptions
    {
        $publicKeyCredentialParameters = [
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
        ];

        $userEntity = $this->createUserEntity($backendUser);

        $publicKeySources = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $excludedCredentialDescriptors = array_map([$this, 'returnPublicKeyCredentialDescriptor'], $publicKeySources);

        return new PublicKeyCredentialCreationOptions(
            $this->rpEntity,
            $userEntity,
            $challenge ?? $this->createChellenge(),
            $publicKeyCredentialParameters,
            static::TIMEOUT,
            $excludedCredentialDescriptors,
            new AuthenticatorSelectionCriteria(),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            new AuthenticationExtensionsClientInputs()
        );
    }

    public function createCredentialsRequestOptions(BackendUser $backendUser, string $challenge = null): PublicKeyCredentialRequestOptions
    {
        $userEntity = $this->createUserEntity($backendUser);
        $publicKeySources = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $registeredPublicKeyCredentialDescriptors = array_map([$this, 'returnPublicKeyCredentialDescriptor'], $publicKeySources);

        $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
            $challenge ?? $this->createChellenge(),
            self::TIMEOUT,
            $this->rpEntity->getId(),
            $registeredPublicKeyCredentialDescriptors,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED
        );

        return $publicKeyCredentialRequestOptions;
    }

    public function register(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data, string $keyDescription)
    {
        $publicKeyCredentialLoader = $this->factory->getPublicKeyCredentialLoader();
        $authenticatorAttestationResponseValidator = $this->factory->getAuthenticatorAttestationResponseValidator();

        // init PSR7 request
        try {
            $psr7Request = new ServerRequest(new Uri('https://'.$this->rpEntity->getId()));

            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $response = $publicKeyCredential->getResponse();

            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new \RuntimeException('Not an authenticator attestation response');
            }

            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $psr7Request);

            $this->publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
            $this->publicKeyCredentialSourceRepository->updateKeyDescription($publicKeyCredentialSource, $keyDescription);
        } catch (\Throwable $exception) {
            throw new WebAuthnException($exception->getMessage(), 1569585845, $exception);
        }
    }

    public function authenticate(PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data, BackendUser $beUser)
    {
        $user = $this->createUserEntity($beUser);
        $publicKeyCredentialLoader = $this->factory->getPublicKeyCredentialLoader();
        $authenticatorAssertionResponseValidator = $this->factory->getAuthenticatorAssertionResponseValidator();

        // init PSR7 request
        try {
            $psr7Request = new ServerRequest(new Uri('https://'.$this->rpEntity->getId()));

            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $assertionResponse = $publicKeyCredential->getResponse();

            if (!$assertionResponse instanceof AuthenticatorAssertionResponse) {
                throw new \RuntimeException('Not an authenticator assertion response');
            }

            $authenticatorAssertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $assertionResponse,
                $publicKeyCredentialRequestOptions,
                $psr7Request,
                $user->getId()
            );
        } catch (\Throwable $exception) {
            throw new WebAuthnException($exception->getMessage(), 1569585846, $exception);
        }
    }

    public function createUserEntity(BackendUser $backendUser): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $backendUser->getUserName(),
            (string) $backendUser->getUid(),
            $backendUser->getRealName() ?: $backendUser->getUserName()
        );
    }

    private function createChellenge(): string
    {
        return bin2hex(random_bytes(static::CHALLENGE_LENGTH));
    }

    private function returnPublicKeyCredentialDescriptor(PublicKeyCredentialSource $publicKeyCredentialSource): PublicKeyCredentialDescriptor
    {
        return $publicKeyCredentialSource->getPublicKeyCredentialDescriptor();
    }
}
