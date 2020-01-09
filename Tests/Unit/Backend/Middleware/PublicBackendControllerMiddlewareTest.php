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

namespace Cvc\Typo3\CvcWebauthn\Backend\Middleware;

use Cvc\Typo3\CvcWebauthn\Controller\WebAuthnLoginController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Webauthn\Server;

class PublicBackendControllerMiddlewareTest extends TestCase
{
    public function testHandlerResponse()
    {
        $webAuthnLoginController = $this->prophesize(WebAuthnLoginController::class);
        GeneralUtility::setSingletonInstance(WebAuthnLoginController::class, $webAuthnLoginController->reveal());

        $handlerResponse = $this->prophesize(ResponseInterface::class)->reveal();
        $request = new ServerRequest();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle($request)
            ->willReturn($handlerResponse);

        $middleware = new PublicBackendControllerMiddleware();
        $actualResponse = $middleware->process($request, $handler->reveal());

        static::assertSame($handlerResponse, $actualResponse);
    }

    public function testBackendControllerResponse()
    {
        $webAuthnLoginController = $this->prophesize(WebAuthnLoginController::class);

        GeneralUtility::setSingletonInstance(WebAuthnLoginController::class, $webAuthnLoginController->reveal());

        $controllerResponse = $this->prophesize(ResponseInterface::class)->reveal();
        $request = new ServerRequest();
        $request = $request->withAttribute('routePath', '/ajax/login/webauthn');

        $controller = $this->prophesize(RequestHandlerInterface::class);
        $controller->requestChallenge()->willReturn($controllerResponse);

        $middleware = new PublicBackendControllerMiddleware();
        $actualResponse = $middleware->process($request, $controller->reveal());

        static::assertSame($controllerResponse, $actualResponse);
    }
}
