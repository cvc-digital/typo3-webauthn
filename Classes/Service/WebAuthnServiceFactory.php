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

use Cvc\Typo3\CvcWebauthn\WebAuthn\PublicKeyCredentialSourceRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WebAuthnServiceFactory
{
    public static function fromGlobals(): WebAuthnService
    {
        return new WebAuthnService('TYPO3 Backend', $_SERVER['HTTP_HOST'], GeneralUtility::makeInstance(PublicKeyCredentialSourceRepository::class));
    }
}
