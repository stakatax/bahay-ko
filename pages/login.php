<?php

$loginFlash =
    $_SESSION['login_flash']
    ?? [];

unset(
    $_SESSION['login_flash']
);

$passwordResetSuccess =
    trim(
        (string) (
            $_SESSION['password_reset_success']
            ?? ''
        )
    );

unset(
    $_SESSION['password_reset_success']
);

$errorMessage =
    trim(
        (string) (
            $loginFlash['error']
            ?? $_GET['error']
            ?? ''
        )
    );

$errorField =
    trim(
        (string) (
            $loginFlash['error_field']
            ?? ''
        )
    );

$oldIdentifier =
    trim(
        (string) (
            $loginFlash['old_identifier']
            ?? ''
        )
    );

$rememberChecked =
    !empty($loginFlash['remember']);

$successMessage =
    $passwordResetSuccess !== ''
    ? $passwordResetSuccess
    : trim(
        (string) (
            $_GET['success']
            ?? ''
        )
    );

$successTitle =
    $passwordResetSuccess !== ''
    ? 'Password Reset Complete'
    : 'Registration Submitted';

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($errorMessage !== ''): ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: <?= json_encode($errorMessage) ?>,
                confirmButtonColor: '#7f1d1d'
            });
        });
    </script>

<?php endif; ?>

<?php if ($successMessage !== ''): ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'success',
                title: <?= json_encode($successTitle) ?>,
                text: <?= json_encode($successMessage) ?>,
                confirmButtonColor: '#7f1d1d'
            });
        });
    </script>

<?php endif; ?>

<section class="auth-shell">

    <aside class="auth-rail">

        <a
            href="index.php?page=home"
            class="auth-logo">

            <img
                src="Assets/Images/ulsco.png"
                alt="OLSHCO logo">

            <span>

                <strong>OLSHCO</strong>

                <small>Digital Hub</small>

            </span>

        </a>

        <div class="auth-rail-content">

            <span class="auth-eyebrow">
                Official School Platform
            </span>

            <h1>
                One school.<br>
                One trusted hub.
            </h1>

            <p>
                Access verified announcements, events,
                shared documents, surveys, and official
                school information.
            </p>

            <div class="auth-feature-list">

                <span>
                    <i class="fa-solid fa-shield-halved"></i>
                    Secure role-based access
                </span>

                <span>
                    <i class="fa-solid fa-bullhorn"></i>
                    Verified school updates
                </span>

                <span>
                    <i class="fa-solid fa-users"></i>
                    Student and Parent support
                </span>

            </div>

        </div>

        <nav class="auth-rail-navigation">

            <a href="index.php?page=home">
                Home
            </a>

            <a href="index.php?page=about">
                About
            </a>

            <a href="index.php?page=contact">
                Contact
            </a>

        </nav>

        <p class="auth-rail-footer">
            Rooted in Faith, Grounded in Excellence
        </p>

    </aside>

    <main class="auth-workspace">

        <section class="auth-card">

            <header class="auth-card-header">

                <span class="auth-card-icon">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </span>

                <div>

                    <span class="auth-eyebrow">
                        Account Access
                    </span>

                    <h2>Welcome back</h2>

                    <p>
                        Enter your account credentials to continue.
                    </p>

                </div>

            </header>

            <?php if ($errorMessage !== ''): ?>

                <div
                    id="loginErrorCard"
                    class="auth-error-card"
                    role="alert"
                    aria-live="assertive"
                    tabindex="-1"
                    data-error-field="<?= htmlspecialchars(
                                            $errorField,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                    <span class="auth-error-icon">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </span>

                    <div>

                        <strong>
                            Unable to sign in
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $errorMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                    </div>

                    <button
                        type="button"
                        class="auth-error-dismiss"
                        data-dismiss-login-error
                        aria-label="Dismiss error message">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>

            <?php endif; ?>

            <form
                id="loginForm"
                action="index.php?page=login_action"
                method="POST"
                class="auth-form">

                <?= csrfInput() ?>

                <div class="auth-field<?= $errorField === 'identifier'
                                            ? ' has-error'
                                            : '' ?>">

                    <label for="loginIdentifier">
                        Account ID or Email
                    </label>

                    <div class="auth-control">

                        <i class="fa-regular fa-user"></i>

                        <input
                            type="text"
                            id="loginIdentifier"
                            name="identifier"
                            maxlength="100"
                            placeholder="Enter account ID or email"
                            autocomplete="username"
                            value="<?= htmlspecialchars(
                                        $oldIdentifier,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"

                            <?= $errorField === 'identifier'
                                ? 'aria-invalid="true"'
                                : ''
                            ?>
                            required>

                    </div>

                    <small>
                        Parent accounts should use their email address.
                    </small>

                </div>

                <div class="auth-field<?= $errorField === 'password'
                                            ? ' has-error'
                                            : '' ?>">

                    <div class="auth-label-row">

                        <label for="loginPassword">
                            Password
                        </label>

                        <a
                            href="index.php?page=forgot_password"
                            class="auth-text-button">
                            Forgot password?
                        </a>

                    </div>

                    <div class="auth-control">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            <?= $errorField === 'password'
                                ? 'aria-invalid="true"'
                                : ''
                            ?>
                            required>

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="loginPassword"
                            aria-label="Show password">
                            <i class="fa-regular fa-eye"></i>
                        </button>

                    </div>

                </div>

                <small
                    id="loginCapsLockWarning"
                    class="auth-caps-lock-warning"
                    role="status"
                    hidden>

                    <i class="fa-solid fa-arrow-up"></i>

                    Caps Lock is currently on.
                </small>


                <label class="auth-checkbox">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        <?= $rememberChecked
                            ? 'checked'
                            : ''
                        ?>>



                    <span>
                        Keep me signed in on this device
                    </span>

                </label>

                <button
                    type="submit"
                    id="loginSubmitButton"
                    class="auth-submit-button">

                    <span>Log In</span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>

            <footer class="auth-card-footer">

                <span>
                    New to the Digital Hub?
                </span>

                <a href="index.php?page=register">
                    Create an account
                </a>

            </footer>

            <div class="auth-notice">

                <i class="fa-solid fa-circle-info"></i>

                <p>
                    Newly registered accounts require
                    Administrator approval before login.
                </p>

            </div>

        </section>

    </main>

</section>