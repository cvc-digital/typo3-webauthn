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

namespace Cvc\Typo3\CvcWebauthn\Backend\Middleware;

use Cvc\Typo3\CvcWebauthn\Http\WebAuthnSession;
use Cvc\Typo3\CvcWebauthn\Service\WebAuthnService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Initializes the WebAuthn session.
 *
 * The session is responsible to store the challenges that are used during the authentication process.
 * The challenges are stored in the TYPO3 cache, so that they are invalidated after the timeout.
 *
 * A separate session is needed because TYPO3 initializes the session only after authentication.
 */
class WebAuthnSessionMiddleware implements MiddlewareInterface
{
    /**
     * @var WebAuthnSession
     */
    private $session;

    /**
     * @var FrontendInterface
     */
    private $cache;

    /**
     * @var int
     */
    private $currentTimestamp;

    public function __construct()
    {
        $this->session = GeneralUtility::makeInstance(WebAuthnSession::class);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('webauthn_challenges');
        $dateAspect = GeneralUtility::makeInstance(Context::class)->getAspect('date');
        assert($dateAspect instanceof DateTimeAspect);
        $this->currentTimestamp = $dateAspect->getDateTime()->getTimestamp();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $sessionId = $request->getCookieParams()['webauthn_session_id'];
        $this->initializeSession($sessionId);

        $response = $handler->handle($request);
        $response = $this->updateSession($response, $sessionId);

        return $response;
    }

    private function updateSession(ResponseInterface $response, ?string $sessionId): ResponseInterface
    {
        if (!$this->session->isChanged()) {
            return $response;
        }

        if (!$this->session->hasChallenge()) {
            if ($sessionId) {
                $this->cache->remove($sessionId);
            }

            return $this->responseWithCookie($response, '', 0);
        }

        if (!$sessionId) {
            $sessionId = bin2hex(random_bytes(32));
        }

        $this->cache->set($sessionId, $this->session->getChallenge(), [], WebAuthnService::TIMEOUT / 1000);

        return $this->responseWithCookie($response, $sessionId, (WebAuthnService::TIMEOUT / 1000));
    }

    private function responseWithCookie(ResponseInterface $response, string $sessionId, int $expires): ResponseInterface
    {
        $cookieHeaderValue = 'webauthn_session_id='.$sessionId;
        $cookieHeaderValue .= '; Secure; HttpOnly; Path=/typo3';
        $cookieHeaderValue .= '; Expires='.date(DATE_RFC7231, $this->currentTimestamp + $expires);

        return $response->withAddedHeader('Set-Cookie', $cookieHeaderValue);
    }

    private function initializeSession(?string $sessionId): void
    {
        if (!$sessionId) {
            $this->session->initialize(null);

            return;
        }

        $challenge = $this->cache->get($sessionId) ?: null;
        assert(is_string($challenge) || is_null($challenge));
        $this->session->initialize($challenge);
    }
}
