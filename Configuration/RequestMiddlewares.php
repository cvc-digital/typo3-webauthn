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

return [
    'backend' => [
        'cvc/typo3-webauthn/webauthn-session' => [
            'target' => \Cvc\Typo3\CvcWebauthn\Backend\Middleware\WebAuthnSessionMiddleware::class,
            'before' => [
                'typo3/cms-backend/authentication',
            ],
        ],
        'cvc/typo3-webauthn/public-backend-controller' => [
            'target' => \Cvc\Typo3\CvcWebauthn\Backend\Middleware\PublicBackendControllerMiddleware::class,
            'before' => [
                'typo3/cms-backend/authentication',
            ],
            'after' => [
                'typo3/cms-backend/backend-routing',
                'cvc/typo3-webauthn/webauthn-session',
            ],
        ],
    ],
];
