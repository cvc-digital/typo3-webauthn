define(['jquery', 'TYPO3/CMS/Backend/Notification'], function ($, Notification) {
    var publicKey = window.tx_cvcwebauthn_publickey;

    var arrayToBase64String = function (a) {
        return btoa(String.fromCharCode(...a));
    };

    function base64url2base64(input) {
        input = input
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        const pad = input.length % 4;
        if (pad) {
            if (pad === 1) {
                throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
            }
            input += new Array(5 - pad).join('=');
        }
        return input;
    }

    publicKey.challenge = Uint8Array.from(window.atob(base64url2base64(publicKey.challenge)), c => c.charCodeAt(0));
    publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), c => c.charCodeAt(0));
    if (publicKey.excludeCredentials) {
        publicKey.excludeCredentials = publicKey.excludeCredentials.map(function (data) {
            data.id = Uint8Array.from(window.atob(base64url2base64(data.id)), c => c.charCodeAt(0));
            return data;
        });
    }

    $(function () {
        var $button = $('#register');
        $button.on('click', function (event) {
            event.preventDefault();
            $button.button('loading');
            navigator.credentials.create({'publicKey': publicKey})
                .then(data => {
                    const publicKeyCredential = {
                        id: data.id,
                        type: data.type,
                        rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                        response: {
                            clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                            attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject))
                        }
                    };

                    $('#tx_webauthn_form_publickey').val(btoa(JSON.stringify(publicKeyCredential)));
                    $('#tx_webauthn_form_key-description').val($('#tx_webauthn_key-description').val());
                    $('#tx_webauthn_form').submit();
                }, error => {
                    if (error.code === 11) {
                        Notification.error(
                            'Error',
                            TYPO3.lang.js_error_key_registered
                        );
                        $button.button('reset');
                    } else {
                        Notification.error(
                            'Error',
                            TYPO3.lang.js_error_unspecified
                        );
                        $button.button('reset');
                    }
                });
        });

        $('[data-delete-url]').on('click', function (element) {
            top.TYPO3.Modal.confirm(TYPO3.lang.js_confirm_question, TYPO3.lang.js_confirm_dialog, top.TYPO3.Severity.warning)
                .on('confirm.button.ok', function () {
                    window.location.href = $(element.currentTarget).data('deleteUrl');
                    top.TYPO3.Modal.currentModal.trigger('modal-dismiss');
                })
                .on('confirm.button.cancel', function () {
                    top.TYPO3.Modal.currentModal.trigger('modal-dismiss');
                })
        });
    });
});
