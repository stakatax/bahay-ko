<?php

$recoveryFlash =
    $_SESSION['password_recovery_flash']
    ?? [];

unset(
    $_SESSION['password_recovery_flash']
);

$errorMessage =
    trim(
        (string) (
            $recoveryFlash['error']
            ?? ''
        )
    );

$successMessage =
    trim(
        (string) (
            $recoveryFlash['success']
            ?? ''
        )
    );

$oldIdentifier =
    trim(
        (string) (
            $recoveryFlash['old_identifier']
            ?? ''
        )
    );

?>

<section class="auth-shell password-recovery-shell">

    <?php
    require __DIR__
        . '/../include/auth-recovery-rail.php';
    ?>

    <main class="auth-workspace password-recovery-workspace">

        <section class="auth-card password-recovery-card">

            <header class="auth-card-header">

                <span class="auth-card-icon">

                    <i
                        class="fa-solid fa-key"
                        aria-hidden="true"></i>

                </span>

                <div>

                    <span class="auth-eyebrow">
                        Account Recovery
                    </span>

                    <h2>
                        Forgot your password?
                    </h2>

                    <p>
                        Enter your account ID or registered
                        email address. If the account is eligible,
                        we will send a secure reset link.
                    </p>

                </div>

            </header>

            <?php if (
                $errorMessage !== ''
            ): ?>

                <div
                    class="password-recovery-status is-error"
                    role="alert">

                    <i
                        class="fa-solid fa-circle-exclamation"
                        aria-hidden="true"></i>

                    <span>
                        <?= htmlspecialchars(
                            $errorMessage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </div>

            <?php endif; ?>

            <?php if (
                $successMessage !== ''
            ): ?>

                <div
                    class="password-recovery-status is-success"
                    role="status">

                    <i
                        class="fa-solid fa-circle-check"
                        aria-hidden="true"></i>

                    <span>
                        <?= htmlspecialchars(
                            $successMessage,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                </div>

            <?php endif; ?>

            <form
                method="post"
                action="index.php?page=password_reset_request"
                class="auth-form password-recovery-form">

                <?= csrfInput() ?>

                <div class="auth-field">

                    <label for="recoveryIdentifier">
                        Account ID or Email
                    </label>

                    <div class="auth-control">

                        <i
                            class="fa-regular fa-user"
                            aria-hidden="true"></i>

                        <input
                            type="text"
                            id="recoveryIdentifier"
                            name="identifier"
                            value="<?= htmlspecialchars(
                                        $oldIdentifier,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            placeholder="Enter account ID or email"
                            maxlength="190"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required>

                    </div>

                    <small class="auth-field-foot">
                        Parent accounts should use their
                        registered email address.
                    </small>

                </div>

                <button
                    type="submit"
                    class="auth-submit-button">

                    Send Reset Link

                    <i
                        class="fa-solid fa-arrow-right"
                        aria-hidden="true"></i>

                </button>

            </form>

            <footer class="auth-card-footer">

                <span>
                    Remembered your password?
                </span>

                <a href="index.php?page=login">
                    Return to sign in
                </a>

            </footer>

            <div class="auth-notice">

                <i
                    class="fa-solid fa-shield-halved"
                    aria-hidden="true"></i>

                <p>
                    For privacy, the system always displays the
                    same confirmation whether or not an account
                    matches the information entered.
                </p>

            </div>

        </section>

    </main>

</section>