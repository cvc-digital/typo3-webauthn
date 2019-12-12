<?php

namespace Cvc\Typo3\CvcWebauthn\Backend\Middleware;

use Cvc\Typo3\CvcWebauthn\Controller\WebAuthnLoginController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PublicBackendControllerMiddlewareTest extends TestCase
{
    public function testHandlerResponse()
    {
        $webAuthnLoginController = $this->prophesize(WebAuthnLoginController::class);
        GeneralUtility::setSingletonInstance(WebAuthnLoginController::class, $webAuthnLoginController->reveal());

        $handlerResponse = $this->prophesize(ResponseInterface::class)->reveal();
        $request = $this->prophesize(ServerRequestInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle($request)
            ->willReturn($handlerResponse);

        $middleware = new PublicBackendControllerMiddleware();
        $actualResponse = $middleware->process($request->reveal(), $handler->reveal());

        static::assertSame($handlerResponse, $actualResponse);
    }

    public function testBackendControllerResponse()
    {
        $webAuthnLoginController = $this->prophesize(WebAuthnLoginController::class);
        GeneralUtility::setSingletonInstance(WebAuthnLoginController::class, $webAuthnLoginController->reveal());

        $handlerResponse = $this->prophesize(ResponseInterface::class)->reveal();
        $controllerResponse = $this->prophesize(ResponseInterface::class)->reveal();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->withAttribute('routePath', '/ajax/login/webauthn');

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->willReturn($controllerResponse);

        $middleware = new PublicBackendControllerMiddleware();
        $actualResponse = $middleware->process($request->reveal(), $handler->reveal());

        static::assertSame($handlerResponse, $actualResponse);
    }
}
