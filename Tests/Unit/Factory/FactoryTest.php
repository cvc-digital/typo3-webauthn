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

namespace Cvc\Typo3\CvcWebAuthn\Tests\Unit\Factory;

use Cvc\Typo3\CvcWebauthn\Factory\Factory;
use PHPUnit\Framework\TestCase;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSourceRepository;

class FactoryTest extends TestCase
{
    /**
     * This tests covers the differentiation of the two supported web-auth/webauthn-lib versions.
     */
    public function testCreateAuthenticationAssertionResponseValidator(): void
    {
        $publicKeyCredentialSourceRepository = $this->prophesize(PublicKeyCredentialSourceRepository::class)->reveal();
        $factory = Factory::createFactory($publicKeyCredentialSourceRepository);
        $result = $factory->getAuthenticatorAssertionResponseValidator();

        static::assertInstanceOf(AuthenticatorAssertionResponseValidator::class, $result);
    }
}
