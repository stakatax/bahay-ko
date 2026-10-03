<?php

$passwordFlash =
    $_SESSION['required_password_flash']
    ?? [];

unset(
    $_SESSION['required_password_flash']
);

$errorMessage =
    trim(
        (string) (
            $passwordFlash['error']
            ?? ''
        )
    );

$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

$displayName =
    trim(
        (string) (
            $_SESSION['name']
            ?? 'Account holder'
        )
    );

$email =
    trim(
        (string) (
            $_SESSION['email']
            ?? ''
        )
    );

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="password-change-shell">

    <aside class="password-change-rail">

        <a
            href="index.php?page=required_password_change"
            class="password-change-brand">

            <img
                src="Assets/Images/ulsco.png"
                alt="OLSHCO logo">

            <span>

                <strong>OLSHCO</strong>

                <small>Digital Hub</small>

            </span>

        </a>

        <div class="password-change-rail-copy">

            <span class="password-change-eyebrow">
                First Login Security
            </span>

            <h1>
                Secure your<br>
                account.
            </h1>

            <p>
                Replace the temporary password before
                accessing the Digital Hub.
            </p>

            <ul>

                <li>
                    <i class="fa-solid fa-shield-halved"></i>
                    Your permanent password stays private.
                </li>

                <li>
                    <i class="fa-solid fa-key"></i>
                    The temporary password cannot be reused.
                </li>

                <li>
                    <i class="fa-solid fa-lock"></i>
                    Other application pages remain protected.
                </li>

            </ul>

        </div>

        <form
            action="index.php?page=logout"
            method="post">

            <?= csrfInput() ?>

            <button type="submit">

                <i class="fa-solid fa-right-from-bracket"></i>

                Sign out instead

            </button>

        </form>

    </aside>

    <main class="password-change-workspace">

        <section class="password-change-card">

            <header>

                <span class="password-change-icon">

                    <i class="fa-solid fa-user-lock"></i>

                </span>

                <div>

                    <span class="password-change-eyebrow">
                        Required Action
                    </span>

                    <h2>Create a private password</h2>

                    <p>
                        Complete this step to continue to
                        the OLSHCO Digital Hub.
                    </p>

                </div>

            </header>

            <div class="password-change-account">

                <span>

                    <?= $escape(
                        strtoupper(
                            mb_substr(
                                $displayName !== ''
                                    ? $displayName
                                    : 'F',
                                0,
                                1
                            )
                        )
                    ) ?>

                </span>

                <div>

                    <strong>
                        <?= $escape(
                            $displayName
                        ) ?>
                    </strong>

                    <small>
                        <?= $escape(
                            $email
                        ) ?>
                    </small>

                </div>

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <?php if ($errorMessage !== ''): ?>

                <div class="password-change-alert">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= $escape(
                            $errorMessage
                        ) ?>
                    </span>

                </div>

            <?php endif; ?>

            <form
                id="requiredPasswordForm"
                action="index.php?page=required_password_change_action"
                method="post"
                class="password-change-form">

                <?= csrfInput() ?>

                <label>

                    <span>
                        New Password
                    </span>

                    <div class="password-change-control">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            id="requiredNewPassword"
                            name="new_password"
                            autocomplete="new-password"
                            placeholder="Create a strong password"
                            required>

                        <button
                            type="button"
                            data-password-toggle="requiredNewPassword"
                            aria-label="Show new password">

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                </label>

                <label>

                    <span>
                        Confirm New Password
                    </span>

                    <div class="password-change-control">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            id="requiredPasswordConfirmation"
                            name="new_password_confirmation"
                            autocomplete="new-password"
                            placeholder="Repeat new password"
                            required>

                        <button
                            type="button"
                            data-password-toggle="requiredPasswordConfirmation"
                            aria-label="Show password confirmation">

                            <i class="fa-regular fa-eye"></i>

                        </button>

                    </div>

                    <small
                        id="requiredPasswordMatch"
                        class="password-match-message">
                        Passwords must match.
                    </small>

                </label>

                <div
                    id="requiredPasswordRules"
                    class="password-change-rules">

                    <span data-rule="length">
                        <i class="fa-solid fa-circle"></i>
                        At least 8 characters
                    </span>

                    <span data-rule="uppercase">
                        <i class="fa-solid fa-circle"></i>
                        One uppercase letter
                    </span>

                    <span data-rule="lowercase">
                        <i class="fa-solid fa-circle"></i>
                        One lowercase letter
                    </span>

                    <span data-rule="number">
                        <i class="fa-solid fa-circle"></i>
                        One number
                    </span>

                    <span data-rule="symbol">
                        <i class="fa-solid fa-circle"></i>
                        One special character
                    </span>

                </div>

                <button
                    type="submit"
                    id="requiredPasswordSubmit"
                    class="password-change-submit">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>Secure Account</span>

                </button>

            </form>

            <div class="password-change-notice">

                <i class="fa-solid fa-circle-info"></i>

                <p>
                    OLSHCO personnel will never ask for your
                    permanent password.
                </p>

            </div>

        </section>

    </main>

</section>