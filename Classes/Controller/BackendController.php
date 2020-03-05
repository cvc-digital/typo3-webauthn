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

namespace Cvc\Typo3\CvcWebauthn\Controller;

use Cvc\Typo3\CvcWebauthn\Domain\Model\Key;
use Cvc\Typo3\CvcWebauthn\Domain\Repository\KeyRepository;
use Cvc\Typo3\CvcWebauthn\Service\WebAuthnServiceFactory;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendController extends ActionController
{
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var KeyRepository
     */
    private $keyRepository;

    /**
     * @var BackendUserRepository
     */
    private $backendUserRepository;

    public function __construct(KeyRepository $keyRepository, BackendUserRepository $backendUserRepository)
    {
        $versionNumber = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        if ($versionNumber < 10) {
            parent::__construct();
        }

        $this->keyRepository = $keyRepository;
        $this->backendUserRepository = $backendUserRepository;
    }

    public function indexAction(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $backendUser = $this->getBackendUser();

        $keysForBeUser = $this->keyRepository->findKeyOwner($backendUser->getUid());
        $creationOptions = $webAuthnService->createCredentialCreationOptions($backendUser);

        if ($this->view instanceof BackendTemplateView) {
            $jsonEncodedPublicKey = json_encode($creationOptions);
            $javaScriptCode = <<<JS
var tx_cvcwebauthn_publickey = {$jsonEncodedPublicKey};
JS;
            $this->view->getModuleTemplate()->addJavaScriptCode('cvc_webauthn Credential Creation Options', $javaScriptCode);
        }

        $extName = 'cvc_webauthn';
        $labelsToTranslateInJS = ['js_error_key_registered', 'js_error_unspecified', 'js_confirm_question', 'js_confirm_dialog', 'js_error_no_https'];

        foreach ($labelsToTranslateInJS as $value) {
            $pageRenderer->addInlineLanguageLabel($value, LocalizationUtility::translate($value, $extName));
        }

        $this->view->assign('challenge', $creationOptions->getChallenge());
        $this->view->assign('keys', $keysForBeUser);
    }

    public function createAction(): void
    {
        $backendUser = $this->getBackendUser();
        $webAuthnService = WebAuthnServiceFactory::fromGlobals();
        $data = base64_decode($this->request->getArgument('publicKey'));
        $challenge = $this->request->getArgument('challenge');
        $keyDescription = $this->request->getArgument('key-description');

        $publicKeyCredentialCreationOptions = $webAuthnService->createCredentialCreationOptions($backendUser, $challenge);

        $webAuthnService->register($publicKeyCredentialCreationOptions, $data, $keyDescription);

        $this->addFlashMessage(LocalizationUtility::translate('security_key_registered_success', 'cvc_webauthn'), '', FlashMessage::OK);

        $this->redirect('index');
    }

    public function deleteAction(Key $key): void
    {
        $this->keyRepository->remove($key);

        $this->addFlashMessage(LocalizationUtility::translate('security_key_success_delete', 'cvc_webauthn'), '', FlashMessage::OK);

        $this->redirect('index', 'Backend', 'cvc_webauthn');
    }

    private function getBackendUser(): BackendUser
    {
        $beUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('backend.user');
        assert($beUserAspect instanceof UserAspect);
        $beUserId = $beUserAspect->get('id');
        $backendUser = $this->backendUserRepository->findByUid($beUserId);

        return $backendUser;
    }
}
