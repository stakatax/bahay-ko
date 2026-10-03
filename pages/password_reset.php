<?php

$resetFlash =
    $_SESSION['password_reset_flash']
    ?? [];

unset(
    $_SESSION['password_reset_flash']
);

$validToken =
    !empty($viewData['valid_token']);

$rawToken =
    trim(
        (string) (
            $viewData['token']
            ?? ''
        )
    );

$errorMessage =
    trim(
        (string) (
            $resetFlash['error']
            ?? $viewData['error']
            ?? ''
        )
    );

$expiresAt =
    $viewData['expires_at']
    ?? null;

?>

<section class="auth-shell password-recovery-shell">

    <?php
    require __DIR__
        . '/../include/auth-recovery-rail.php';
    ?>

    <main class="auth-workspace password-recovery-workspace">

        <section class="auth-card password-recovery-card">

            <?php if ($validToken): ?>

                <header class="auth-card-header">

                    <span class="auth-card-icon">

                        <i
                            class="fa-solid fa-lock"
                            aria-hidden="true"></i>

                    </span>

                    <div>

                        <span class="auth-eyebrow">
                            Secure Password Reset
                        </span>

                        <h2>
                            Create a new password
                        </h2>

                        <p>
                            Choose a strong password that you
                            have not used for this account.
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

                <form
                    method="post"
                    action="index.php?page=password_reset_action"
                    class="auth-form password-recovery-form"
                    data-password-reset-form>

                    <?= csrfInput() ?>

                    <input
                        type="hidden"
                        name="token"
                        value="<?= htmlspecialchars(
                                    $rawToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                    <div class="auth-field">

                        <label for="resetPassword">
                            New Password
                        </label>

                        <div class="auth-control">

                            <i
                                class="fa-solid fa-lock"
                                aria-hidden="true"></i>

                            <input
                                type="password"
                                id="resetPassword"
                                name="password"
                                placeholder="Enter a new password"
                                minlength="8"
                                maxlength="72"
                                autocomplete="new-password"
                                required
                                data-password-input>

                            <button
                                type="button"
                                class="auth-password-toggle"
                                data-password-toggle
                                aria-label="Show new password"
                                aria-pressed="false">

                                <i
                                    class="fa-regular fa-eye"
                                    aria-hidden="true"></i>

                            </button>

                        </div>

                    </div>

                    <div class="auth-field">

                        <label for="resetPasswordConfirmation">
                            Confirm New Password
                        </label>

                        <div class="auth-control">

                            <i
                                class="fa-solid fa-lock"
                                aria-hidden="true"></i>

                            <input
                                type="password"
                                id="resetPasswordConfirmation"
                                name="password_confirmation"
                                placeholder="Enter the password again"
                                minlength="8"
                                maxlength="72"
                                autocomplete="new-password"
                                required
                                data-password-confirmation>

                            <button
                                type="button"
                                class="auth-password-toggle"
                                data-password-toggle
                                aria-label="Show password confirmation"
                                aria-pressed="false">

                                <i
                                    class="fa-regular fa-eye"
                                    aria-hidden="true"></i>

                            </button>

                        </div>

                    </div>

                    <div
                        class="password-requirements"
                        aria-label="Password requirements">

                        <strong>
                            Your password must contain:
                        </strong>

                        <ul>

                            <li data-password-rule="length">
                                At least 8 characters
                            </li>

                            <li data-password-rule="uppercase">
                                One uppercase letter
                            </li>

                            <li data-password-rule="lowercase">
                                One lowercase letter
                            </li>

                            <li data-password-rule="number">
                                One number
                            </li>

                            <li data-password-rule="match">
                                Matching confirmation
                            </li>

                        </ul>

                    </div>

                    <?php if (
                        !empty($expiresAt)
                    ): ?>

                        <p class="password-reset-expiry">

                            <i
                                class="fa-regular fa-clock"
                                aria-hidden="true"></i>

                            This link remains valid until

                            <strong>
                                <?= htmlspecialchars(
                                    date(
                                        'F j, Y g:i A',
                                        strtotime(
                                            (string) $expiresAt
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </p>

                    <?php endif; ?>

                    <button
                        type="submit"
                        class="auth-submit-button">

                        Reset Password

                        <i
                            class="fa-solid fa-shield-halved"
                            aria-hidden="true"></i>

                    </button>

                </form>

            <?php else: ?>

                <div class="password-reset-invalid">

                    <span>

                        <i
                            class="fa-solid fa-link-slash"
                            aria-hidden="true"></i>

                    </span>

                    <span class="auth-eyebrow">
                        Link Unavailable
                    </span>

                    <h2>
                        This reset link cannot be used
                    </h2>

                    <p>
                        The link may be invalid, expired,
                        already used, or replaced by a newer
                        password-reset request.
                    </p>

                    <a
                        href="index.php?page=forgot_password"
                        class="auth-submit-button">

                        Request Another Link

                        <i
                            class="fa-solid fa-arrow-right"
                            aria-hidden="true"></i>

                    </a>

                    <a
                        href="index.php?page=login"
                        class="password-reset-secondary-link">

                        Return to sign in

                    </a>

                </div>

            <?php endif; ?>

        </section>

    </main>

</section>