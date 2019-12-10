define(['jquery',], function ($) {

    function arrayToBase64String(a) {
        return btoa(String.fromCharCode(...a));
    }

    $(function () {
        const $loginForm = $('#typo3-login-form');
        const $nextButton = $('#t3-webauthn-next');
        let authenticated = false;
        $loginForm.on('submit', function (event) {
            if (authenticated) {
                return;
            }
            event.preventDefault();
            $nextButton.button('loading');
            let username = $('#t3-username').val();
            $.ajax({
                method: 'POST',
                url: TYPO3.settings.ajaxUrls['login_webauthn'],
                data: {
                    username: username,
                }
            }).done(function (publicKey) {
                publicKey.challenge = Uint8Array.from(window.atob(publicKey.challenge), c => c.charCodeAt(0));
                publicKey.allowCredentials = publicKey.allowCredentials.map(function (data) {
                    var id = data.id
                        .replace(/-/g, '+')
                        .replace(/_/g, '/');
                    return {
                        ...data,
                        'id': Uint8Array.from(atob(id), c => c.charCodeAt(0))
                    };
                });

                navigator.credentials.get({publicKey})
                    .then(data => {
                        let publicKeyCredential = {
                            id: data.id,
                            type: data.type,
                            rawId: arrayToBase64String(new Uint8Array(data.rawId)),
                            response: {
                                clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                                authenticatorData: arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
                                signature: arrayToBase64String(new Uint8Array(data.response.signature)),
                                userHandle: data.response.userHandle ? arrayToBase64String(new Uint8Array(data.response.userHandle)) : null
                            }
                        };
                        authenticated = true;
                        $('#t3-webauthn').val(btoa(JSON.stringify(publicKeyCredential)));
                        $('#t3-field-userident').val($('#t3-password').val());
                        $('#typo3-login-form').submit();
                    }, error => {
                        $('#t3-login-error').show();
                        $nextButton.button('reset');
                    });
            }).fail(function () {
                $('#t3-login-error').show();
                $nextButton.button('reset');
            });
        });
    });
});
