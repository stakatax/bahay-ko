document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const config =
            document.getElementById(
                'sessionTimeoutConfig'
            );

        if (!config) {
            return;
        }

        const timeoutSeconds =
            Number(
                config.dataset
                    .timeoutSeconds ||
                1800
            );

        const warningSeconds =
            Number(
                config.dataset
                    .warningSeconds ||
                300
            );

        const csrfToken =
            config.dataset
                .csrfToken ||
            '';

        const keepAliveUrl =
            config.dataset
                .keepAliveUrl ||
            '';



        const loginUrl =
            config.dataset
                .loginUrl ||
            'index.php?page=login';

        let expiresAt =
            Date.now() +
            (
                timeoutSeconds *
                1000
            );

        let timerId = null;
        let modal = null;
        let countdown = null;
        let stayButton = null;

        function formatRemaining(
            seconds
        ) {
            const minutes =
                Math.floor(
                    seconds / 60
                );

            const remainingSeconds =
                seconds % 60;

            return `${minutes}:${String(
                remainingSeconds
            ).padStart(2, '0')}`;
        }

        function createModal() {
            if (modal) {
                return;
            }

            modal =
                document.createElement(
                    'div'
                );

            modal.className =
                'session-timeout-modal';

            modal.hidden = true;

            modal.innerHTML = `
                <div class="session-timeout-backdrop"></div>

                <section
                    class="session-timeout-dialog"
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="sessionTimeoutTitle"
                    aria-describedby="sessionTimeoutMessage">

                    <span class="session-timeout-icon">
                        <i class="fa-regular fa-clock"></i>
                    </span>

                    <span class="page-eyebrow">
                        Session Security
                    </span>

                    <h2 id="sessionTimeoutTitle">
                        Are you still there?
                    </h2>

                    <p id="sessionTimeoutMessage">
                        Your session will expire after
                        five minutes of inactivity.
                    </p>

                    <strong
                        class="session-timeout-countdown"
                        aria-live="polite">
                        5:00
                    </strong>

                    <p class="session-timeout-note">
                        Unsaved information may be lost
                        when the session expires.
                    </p>

                    <div class="session-timeout-actions">

                        <button
                            type="button"
                            class="app-button secondary"
                            data-session-logout>
                            Log Out
                        </button>

                        <button
                            type="button"
                            class="app-button primary"
                            data-session-stay>
                            Stay Signed In
                        </button>

                    </div>

                </section>
            `;

            document.body.appendChild(
                modal
            );

            countdown =
                modal.querySelector(
                    '.session-timeout-countdown'
                );

            stayButton =
                modal.querySelector(
                    '[data-session-stay]'
                );

            stayButton?.addEventListener(
                'click',
                keepSessionActive
            );

            modal
                .querySelector(
                    '[data-session-logout]'
                )
                ?.addEventListener(
                    'click',
                    () => {
                        const logoutButton =
                            document.querySelector(
                                '.sidebar-logout-form button[type="submit"]'
                            );

                        logoutButton?.click();
                    }
                );
        }

        function showModal() {
            createModal();

            if (!modal || !modal.hidden) {
                return;
            }

            modal.hidden = false;

            document.body.classList.add(
                'session-warning-open'
            );

            stayButton?.focus();
        }

        function hideModal() {
            if (!modal) {
                return;
            }

            modal.hidden = true;

            document.body.classList.remove(
                'session-warning-open'
            );
        }

        async function keepSessionActive() {
            if (
                !keepAliveUrl ||
                !csrfToken ||
                !stayButton
            ) {
                return;
            }

            stayButton.disabled = true;
            stayButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Staying Signed In...';

            const formData =
                new FormData();

            formData.set(
                'csrf_token',
                csrfToken
            );

            try {
                const response =
                    await fetch(
                        keepAliveUrl,
                        {
                            method: 'POST',
                            body: formData,
                            credentials:
                                'same-origin',
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                                'Accept':
                                    'application/json'
                            }
                        }
                    );

                const result =
                    await response.json();

                if (
                    !response.ok ||
                    !result.success
                ) {
                    throw new Error(
                        result.message ||
                        'The session could not be renewed.'
                    );
                }

                expiresAt =
                    Date.now() +
                    (
                        timeoutSeconds *
                        1000
                    );

                hideModal();
            } catch (error) {
                window.location.href =
                    loginUrl +
                    '&error=' +
                    encodeURIComponent(
                        error.message ||
                        'Your session expired. Please sign in again.'
                    );
            } finally {
                stayButton.disabled =
                    false;

                stayButton.innerHTML =
                    'Stay Signed In';
            }
        }

        function expireSession() {
    window.clearInterval(
        timerId
    );

    const logoutForm =
        document.querySelector(
            '.sidebar-logout-form'
        );

    if (!logoutForm) {
        window.location.href =
            loginUrl;

        return;
    }

    let expirationInput =
        logoutForm.querySelector(
            '[name="session_expired"]'
        );

    if (!expirationInput) {
        expirationInput =
            document.createElement(
                'input'
            );

        expirationInput.type =
            'hidden';

        expirationInput.name =
            'session_expired';

        logoutForm.appendChild(
            expirationInput
        );
    }

    expirationInput.value =
        '1';

    logoutForm.submit();
}
        function updateTimer() {
            const remaining =
                Math.max(
                    0,
                    Math.ceil(
                        (
                            expiresAt -
                            Date.now()
                        ) / 1000
                    )
                );

            if (
                remaining <=
                warningSeconds
            ) {
                showModal();

                if (countdown) {
                    countdown.textContent =
                        formatRemaining(
                            remaining
                        );
                }
            }

           if (remaining <= 0) {
    expireSession();

    return;
}
        }

        createModal();

        timerId =
            window.setInterval(
                updateTimer,
                1000
            );
    }
);
