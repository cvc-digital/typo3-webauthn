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

use TYPO3\CMS\Core\SingletonInterface;

class WebAuthnSession implements SingletonInterface
{
    /**
     * @var string|null
     */
    private $challenge;

    /**
     * @var bool
     */
    private $changed = false;

    /**
     * @var bool
     */
    private $initialized = false;

    public function initialize(?string $challenge): void
    {
        if ($this->initialized) {
            throw new \RuntimeException('The WebAuthn session was already initialized.');
        }

        $this->challenge = $challenge;
        $this->changed = false;
        $this->initialized = true;
    }

    public function setChallenge(string $challenge): void
    {
        $this->assertInitialized();
        $this->challenge = $challenge;
        $this->changed = true;
    }

    public function hasChallenge(): bool
    {
        $this->assertInitialized();

        return $this->challenge !== null;
    }

    public function getChallenge(): string
    {
        $this->assertInitialized();

        return $this->challenge;
    }

    public function purgeChallenge(): void
    {
        $this->assertInitialized();
        $this->challenge = null;
        $this->changed = true;
    }

    public function isChanged(): bool
    {
        $this->assertInitialized();

        return $this->changed;
    }

    private function assertInitialized(): void
    {
        if (!$this->initialized) {
            throw new \RuntimeException('Cannot access WebAuthn session before it is initialized');
        }
    }
}
