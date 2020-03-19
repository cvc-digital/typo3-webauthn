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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnAuthenticationServiceTest extends TestCase
{
    /**
     * @dataProvider userProvider
     *
     * @param $user
     * @param $expected
     */
    public function testAuthUser(int $expected, string $user, bool $userHasAuthenticator, bool $isAuthenticatorValid, bool $secondFactorLogin)
    {
        $backendUserRepository = $this->prophesize(TestBackendUserRepository::class);
        $webAuthnService = $this->prophesize(WebAuthnService::class);
        $publicKeyCredentialSourceRepositoy = $this->prophesize(PublicKeyCredentialSourceRepository::class);
        $publicKeyCredentialUserEntity = $this->prophesize(PublicKeyCredentialUserEntity::class);
        $publicKeyCredentialSource = $this->prophesize(PublicKeyCredentialSource::class);
        $publicKeyCredentialRequestOptions = $this->prophesize(PublicKeyCredentialRequestOptions::class);
        $backendUser = new BackendUser();
        $session = new WebAuthnSession();
        $session->initialize('TestChallenge');
        $backendExtensionConfiguration = [
            'secondFactorLogin' => $secondFactorLogin,
        ];
        $backendUserRepository->findOneByUserName(Argument::exact('LukasTest'))->willReturn($backendUser);
        $backendUserRepository->findOneByUserName(Argument::exact('userNotFound'))->willReturn(null);
        $webAuthnService->createUserEntity(Argument::exact($backendUser))->willReturn($publicKeyCredentialUserEntity->reveal());

        if ($userHasAuthenticator) {
            $publicKeyCredentialSourceRepositoy->findAllForUserEntity(Argument::exact($publicKeyCredentialUserEntity->reveal()))->willReturn([$publicKeyCredentialSource->reveal()]);
        } else {
            $publicKeyCredentialSourceRepositoy->findAllForUserEntity(Argument::exact($publicKeyCredentialUserEntity->reveal()))->willReturn([]);
        }

        $webAuthnService->createCredentialsRequestOptions(Argument::any(), Argument::any())->willReturn($publicKeyCredentialRequestOptions->reveal());
        if ($isAuthenticatorValid) {
            $webAuthnService->authenticate(Argument::exact($publicKeyCredentialRequestOptions->reveal()), Argument::any(), Argument::exact($backendUser))->willReturn();
        } else {
            $webAuthnService->authenticate(Argument::exact($publicKeyCredentialRequestOptions->reveal()), Argument::any(), Argument::exact($backendUser))->willThrow(new WebAuthnException());
        }

        $userToAuthenticate = [
            'username' => $user,
            'uident' => 'admin',
            'uident_text' => 'admin',
            'challenge' => $session->getChallenge(),
        ];

        $authenticationService = new WebAuthnAuthenticationService(
            $backendUserRepository->reveal(),
            $webAuthnService->reveal(),
            $session,
            $backendExtensionConfiguration,
            $publicKeyCredentialSourceRepositoy->reveal()
        );
        $authenticationService->initAuth('testMode', ['webauthn_uident' => 'test'], ['testAuthInfo' => 'test'], $authenticationService);
        $actual = $authenticationService->authUser($userToAuthenticate);

        static::assertSame($expected, $actual);
    }

    public function userProvider()
    {
        return [
            'username doesnt exist' => [0, 'userNotFound', false, true, false],
            'user has no authenticator' => [100, 'LukasTest', false, true, false],
            'user has valid authenticator' => [200, 'LukasTest', true, true, false],
            'user has invalid authenticator' => [0, 'LukasTest', true, false, false],
            'user is using second factor login' => [1, 'LukasTest', true, true, true],
        ];
    }
}

/**
 * @method BackendUser findOneByUserName()
 */
class TestBackendUserRepository extends BackendUserRepository
{
}
