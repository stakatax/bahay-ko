document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        /* ======================================
           EXISTING NOTIFICATION REFRESH
        ====================================== */

        document
            .querySelectorAll(
                '.notification-item-link'
            )
            .forEach((link) => {
                link.addEventListener(
                    'click',
                    () => {
                        sessionStorage.setItem(
                            'notificationsRequireRefresh',
                            '1'
                        );
                    }
                );
            });

        document.querySelectorAll(
            '.notifications-toggle input[type="checkbox"]'
        ).forEach((input) => {
            const label = input.closest('.notifications-toggle')
                ?.querySelector('.notifications-toggle-copy strong');
            const updateLabel = () => {
                if (label) label.textContent = input.checked ? 'Enabled' : 'Disabled';
            };
            input.addEventListener('change', updateLabel);
            updateLabel();
        });

        setupBrowserPush();
    }
);

/* ==========================================
   BROWSER PUSH SETUP
========================================== */

async function setupBrowserPush() {
    const configurationElement =
        document.getElementById(
            'browserPushConfiguration'
        );

    const toggleButton =
        document.getElementById(
            'browserPushToggleButton'
        );

    const statusElement =
        document.getElementById(
            'browserPushStatus'
        );

    const statusTitle =
        document.getElementById(
            'browserPushStatusTitle'
        );

    const statusMessage =
        document.getElementById(
            'browserPushStatusMessage'
        );

    if (
        !configurationElement ||
        !toggleButton ||
        !statusElement ||
        !statusTitle ||
        !statusMessage
    ) {
        return;
    }

    const buttonLabel =
        toggleButton.querySelector(
            'span'
        );

    const buttonIcon =
        toggleButton.querySelector(
            'i'
        );

    const csrfToken =
        configurationElement.dataset
            .csrfToken || '';

    const configurationUrl =
        configurationElement.dataset
            .configurationUrl || '';

    const subscribeUrl =
        configurationElement.dataset
            .subscribeUrl || '';

    const unsubscribeUrl =
        configurationElement.dataset
            .unsubscribeUrl || '';

    const serviceWorkerUrl =
        configurationElement.dataset
            .serviceWorkerUrl || '';

    let registration = null;
    let currentSubscription = null;
    let publicKey = '';
    let serverEnabled =
        toggleButton.dataset
            .serverEnabled === '1';

    function setStatus(
        state,
        title,
        message
    ) {
        statusElement.classList.remove(
            'is-enabled',
            'is-disabled',
            'is-blocked',
            'is-error',
            'is-busy'
        );

        statusElement.classList.add(
            state
        );

        statusTitle.textContent =
            title;

        statusMessage.textContent =
            message;
    }

    function setButton(
        label,
        iconClass,
        disabled = false
    ) {
        if (buttonLabel) {
            buttonLabel.textContent =
                label;
        }

        if (buttonIcon) {
            buttonIcon.className =
                iconClass;
        }

        toggleButton.disabled =
            disabled;
    }

    function setBusy(
        message
    ) {
        setStatus(
            'is-busy',
            'Updating browser notifications',
            message
        );

        setButton(
            'Please wait',
            'fa-solid fa-spinner fa-spin',
            true
        );
    }

    function updateDisplay() {
        if (
            Notification.permission ===
            'denied'
        ) {
            setStatus(
                'is-blocked',
                'Blocked by this browser',
                'Open the browser site settings to allow notifications.'
            );

            setButton(
                'Blocked',
                'fa-solid fa-ban',
                true
            );

            return;
        }

        if (currentSubscription) {
            setStatus(
                'is-enabled',
                'Enabled on this browser',
                'Targeted school updates can appear outside the Digital Hub.'
            );

            setButton(
                'Disable on this browser',
                'fa-regular fa-bell-slash'
            );

            return;
        }

        if (serverEnabled) {
            setStatus(
                'is-disabled',
                'Not enabled on this browser',
                'Your account may still be enabled on another browser or device.'
            );

            setButton(
                'Enable this browser',
                'fa-regular fa-bell'
            );

            return;
        }

        setStatus(
            'is-disabled',
            'Browser notifications are off',
            'Enable them to receive targeted updates while the Hub tab is closed.'
        );

        setButton(
            'Enable browser notifications',
            'fa-regular fa-bell'
        );
    }

    async function requestJson(
        url,
        options = {}
    ) {
        const response =
            await fetch(
                url,
                {
                    credentials:
                        'same-origin',

                    ...options,

                    headers: {
                        'X-CSRF-Token':
                            csrfToken,

                        ...(
                            options.headers ||
                            {}
                        )
                    }
                }
            );

        let payload = null;

        try {
            payload =
                await response.json();
        } catch (error) {
            throw new Error(
                'The server returned an invalid response.'
            );
        }

        if (
            !response.ok ||
            !payload?.success
        ) {
            throw new Error(
                payload?.message ||
                'The request could not be completed.'
            );
        }

        return payload.data || {};
    }

    function convertPublicKey(
        base64String
    ) {
        const padding =
            '='.repeat(
                (
                    4 -
                    (
                        base64String.length %
                        4
                    )
                ) % 4
            );

        const normalized =
            (
                base64String +
                padding
            )
                .replace(
                    /-/g,
                    '+'
                )
                .replace(
                    /_/g,
                    '/'
                );

        const rawData =
            window.atob(
                normalized
            );

        return Uint8Array.from(
            rawData,
            (character) =>
                character.charCodeAt(
                    0
                )
        );
    }

    async function enablePush() {
        const permission =
            await Notification
                .requestPermission();

        if (
            permission !== 'granted'
        ) {
            updateDisplay();
            return;
        }

        const applicationServerKey =
            convertPublicKey(
                publicKey
            );

        const subscription =
            await registration
                .pushManager
                .subscribe({
                    userVisibleOnly:
                        true,

                    applicationServerKey
                });

        const contentEncoding =
            Array.isArray(
                PushManager
                    .supportedContentEncodings
            ) &&
            PushManager
                .supportedContentEncodings
                .length > 0
                ? PushManager
                    .supportedContentEncodings[0]
                : 'aes128gcm';

        const deviceLabel =
            navigator.userAgentData
                ?.platform ||
            navigator.platform ||
            'This browser';

        try {
            const data =
                await requestJson(
                    subscribeUrl,
                    {
                        method:
                            'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body:
                            JSON.stringify({
                                subscription:
                                    subscription
                                    .toJSON(),

                                content_encoding:
                                    contentEncoding,

                                device_label:
                                    deviceLabel
                            })
                    }
                );

            currentSubscription =
                subscription;

            serverEnabled =
                Boolean(
                    data.browser_push_enabled
                );

            updateDisplay();
        } catch (error) {
            await subscription
                .unsubscribe();

            throw error;
        }
    }

    async function disablePush() {
        if (!currentSubscription) {
            updateDisplay();
            return;
        }

        const endpoint =
            currentSubscription
                .endpoint;

        const data =
            await requestJson(
                unsubscribeUrl,
                {
                    method:
                        'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body:
                        JSON.stringify({
                            endpoint
                        })
                }
            );

        await currentSubscription
            .unsubscribe();

        currentSubscription =
            null;

        serverEnabled =
            Boolean(
                data.browser_push_enabled
            );

        updateDisplay();
    }

    /* ======================================
       FEATURE AND SERVER INITIALIZATION
    ====================================== */

    if (
        !window.isSecureContext ||
        !(
            'serviceWorker'
            in navigator
        ) ||
        !(
            'PushManager'
            in window
        ) ||
        !(
            'Notification'
            in window
        )
    ) {
        setStatus(
            'is-error',
            'Browser notifications unavailable',
            'This browser or connection does not support secure Web Push.'
        );

        setButton(
            'Unavailable',
            'fa-solid fa-circle-exclamation',
            true
        );

        return;
    }

    try {
        registration =
            await navigator
                .serviceWorker
                .register(
                    serviceWorkerUrl
                );

        await navigator
            .serviceWorker
            .ready;

        const serverConfiguration =
            await requestJson(
                configurationUrl,
                {
                    method:
                        'POST'
                }
            );

        publicKey =
            String(
                serverConfiguration
                    .public_key || ''
            );

        serverEnabled =
            Boolean(
                serverConfiguration
                    .browser_push_enabled
            );

        if (publicKey === '') {
            throw new Error(
                'The browser notification public key is unavailable.'
            );
        }

        currentSubscription =
            await registration
                .pushManager
                .getSubscription();

        updateDisplay();
    } catch (error) {
        console.error(
            'Browser push initialization failed:',
            error
        );

        setStatus(
            'is-error',
            'Browser notifications unavailable',
            error.message ||
            'Unable to initialize browser notifications.'
        );

        setButton(
            'Try again after refreshing',
            'fa-solid fa-rotate-right',
            true
        );

        return;
    }

    toggleButton.addEventListener(
        'click',
        async () => {
            setBusy(
                currentSubscription
                    ? 'Removing this browser subscription.'
                    : 'Waiting for browser permission.'
            );

            try {
                if (currentSubscription) {
                    await disablePush();
                } else {
                    await enablePush();
                }
            } catch (error) {
                console.error(
                    'Browser push update failed:',
                    error
                );

                setStatus(
                    'is-error',
                    'Unable to update browser notifications',
                    error.message ||
                    'Please refresh the page and try again.'
                );

                setButton(
                    currentSubscription
                        ? 'Try disabling again'
                        : 'Try enabling again',
                    'fa-solid fa-rotate-right'
                );
            }
        }
    );
}

/* ==========================================
   EXISTING PAGE RESTORE HANDLING
========================================== */

window.addEventListener(
    'pageshow',
    (event) => {
        const requiresRefresh =
            sessionStorage.getItem(
                'notificationsRequireRefresh'
            ) === '1';

        if (
            event.persisted &&
            requiresRefresh
        ) {
            sessionStorage.removeItem(
                'notificationsRequireRefresh'
            );

            window.location.reload();
            return;
        }

        if (!event.persisted) {
            sessionStorage.removeItem(
                'notificationsRequireRefresh'
            );
        }
    }
);