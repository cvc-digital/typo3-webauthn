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

namespace Cvc\Typo3\CvcWebauthn\Http;

use PHPUnit\Framework\TestCase;

class WebAuthnSessionTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot access WebAuthn session before it is initialized
     */
    public function testSetChallengeThrowsException()
    {
        $session = new WebAuthnSession();
        $session->setChallenge('TestChallenge');
    }

    public function testChallenge()
    {
        $session = new WebAuthnSession();
        $session->initialize(null);
        static::assertFalse($session->isChanged());
        $session->setChallenge('Test');
        static::assertTrue($session->isChanged());
        static::assertSame('Test', $session->getChallenge());
    }
}
