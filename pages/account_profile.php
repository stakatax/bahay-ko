<?php

$viewData =
    isset($viewData) &&
    is_array($viewData)
    ? $viewData
    : [];

$user =
    is_array(
        $viewData['user']
            ?? null
    )
    ? $viewData['user']
    : [];

$profilePhoto =
    trim(
        (string) (
            $viewData['profile_photo']
            ?? ''
        )
    );

$flashType =
    trim(
        (string) (
            $viewData['flash_type']
            ?? ''
        )
    );

$flashMessage =
    trim(
        (string) (
            $viewData['flash_message']
            ?? ''
        )
    );

$firstName =
    trim(
        (string) (
            $user['first_name']
            ?? ''
        )
    );

$lastName =
    trim(
        (string) (
            $user['last_name']
            ?? ''
        )
    );

$displayName =
    trim(
        $firstName
            . ' '
            . $lastName
    );

if ($displayName === '') {
    $displayName =
        'OLSHCO User';
}

$initials =
    mb_strtoupper(
        mb_substr(
            $firstName !== ''
                ? $firstName
                : $displayName,
            0,
            1
        )
            .
            mb_substr(
                $lastName,
                0,
                1
            )
    );

if ($initials === '') {
    $initials = 'U';
}

$escape =
    static fn(
        mixed $value
    ): string =>
    htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

?>

<section class="app-page account-profile-page">

    <header class="page-header account-profile-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                Personal Account
            </span>

            <h1>
                My Account
            </h1>

            <p>
                Manage your personal account details and profile photo.
                Your role, academic assignment, and account access
                remain controlled by the school.
            </p>

        </div>

        <a
            href="index.php?page=home"
            class="app-button secondary">

            <i class="fa-solid fa-arrow-left"></i>

            Return to Home
        </a>

    </header>

    <?php if ($flashMessage !== ''): ?>

        <div
            class="account-profile-alert <?= $escape(
                                                $flashType
                                            ) ?>"
            data-account-profile-alert
            role="alert">

            <i class="<?= $flashType === 'success'
                            ? 'fa-solid fa-circle-check'
                            : 'fa-solid fa-circle-exclamation'
                        ?>"></i>

            <span>
                <?= $escape(
                    $flashMessage
                ) ?>
            </span>

            <button
                type="button"
                data-dismiss-account-profile-alert
                aria-label="Dismiss message">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

    <?php endif; ?>

    <?php if (($user['role_prefix'] ?? '') === 'Faculty'): ?>
        <section class="page-card faculty-personal-card">
            <h2>Personal details</h2>
            <p><?= empty($user['gender']) || empty($user['birthdate'])
                ? 'Complete your profile to continue. Your posting assignment is managed by the Administrator.'
                : 'Update your personal details. Your posting assignment is managed by the Administrator.' ?></p>
            <form action="index.php?page=account_profile_update_details" method="post" data-faculty-personal-form>
                <?= csrfInput() ?>
                <div class="faculty-personal-grid">
                    <label>Middle name (optional)
                        <input name="middle_name" maxlength="50" autocomplete="additional-name" value="<?= $escape($user['middle_name'] ?? '') ?>">
                    </label>
                    <label>Name suffix (optional)
                        <select name="name_suffix">
                            <?php foreach ([''=>'None', 'Jr.'=>'Jr.', 'Sr.'=>'Sr.', 'II'=>'II', 'III'=>'III', 'IV'=>'IV', 'V'=>'V'] as $value=>$label): ?>
                                <option value="<?= $escape($value) ?>" <?= ($user['name_suffix'] ?? '') === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Gender *
                        <select name="gender" required>
                            <option value="">Select gender</option>
                            <?php foreach (['Male', 'Female', 'Other'] as $value): ?>
                                <option value="<?= $escape($value) ?>" <?= ($user['gender'] ?? '') === $value ? 'selected' : '' ?>><?= $escape($value) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Birthdate *
                        <input type="date" name="birthdate" required autocomplete="bday" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" value="<?= $escape($user['birthdate'] ?? '') ?>">
                    </label>
                </div>
                <button class="app-button primary" type="submit">Save personal details</button>
            </form>
        </section>
    <?php endif; ?>

    <div class="account-profile-layout">

        <aside class="page-card account-profile-summary">

            <div
                class="account-profile-avatar"
                data-account-profile-avatar>

                <?php if ($profilePhoto !== ''): ?>

                    <img
                        src="<?= $escape(
                                    $profilePhoto
                                ) ?>"
                        alt="<?= $escape(
                                    $displayName
                                ) ?> profile photo"
                        data-account-profile-preview>

                <?php else: ?>

                    <span
                        data-account-profile-initials>
                        <?= $escape(
                            $initials
                        ) ?>
                    </span>

                    <img
                        src=""
                        alt="<?= $escape(
                                    $displayName
                                ) ?> profile photo preview"
                        data-account-profile-preview
                        hidden>

                <?php endif; ?>

            </div>

            <div class="account-profile-identity">

                <span class="page-eyebrow">
                    Signed-in account
                </span>

                <h2>
                    <?= $escape(
                        $displayName
                    ) ?>
                </h2>

                <p>
                    <?= $escape(
                        $user['email']
                            ?? ''
                    ) ?>
                </p>

                <span class="account-profile-role">

                    <i class="fa-solid fa-id-badge"></i>

                    <?= $escape(
                        $user['role_prefix']
                            ?? 'User'
                    ) ?>

                </span>

            </div>

            <dl class="account-profile-directory">

                <?php if (
                    !empty($user['studID'])
                ): ?>

                    <div>

                        <dt>
                            <?= ($user['role_prefix'] ?? '') === 'Student'
                                ? 'Student ID'
                                : 'Account ID' ?>
                        </dt>

                        <dd>
                            <?= $escape(
                                $user['studID']
                            ) ?>
                        </dd>

                    </div>

                <?php endif; ?>

                <?php if (
                    !empty($user['department_name'])
                ): ?>

                    <div>

                        <dt>
                            Department
                        </dt>

                        <dd>
                            <?= $escape(
                                $user['department_name']
                            ) ?>
                        </dd>

                    </div>

                <?php endif; ?>

                <?php if (
                    !empty($user['grade_level_name']) ||
                    !empty($user['section_name'])
                ): ?>

                    <div>

                        <dt>
                            Academic assignment
                        </dt>

                        <dd>
                            <?= $escape(
                                trim(
                                    (
                                        $user['grade_level_name']
                                        ?? ''
                                    )
                                        . ' '
                                        . (
                                            $user['section_name']
                                            ?? ''
                                        )
                                )
                            ) ?>
                        </dd>

                    </div>

                <?php endif; ?>

                <div>

                    <dt>
                        Account status
                    </dt>

                    <dd class="is-active">
                        <?= $escape(
                            $user['status']
                                ?? 'Active'
                        ) ?>
                    </dd>

                </div>

            </dl>

        </aside>

        <main class="page-card account-profile-photo-card">

            <header>

                <span class="account-profile-card-icon">

                    <i class="fa-regular fa-image"></i>

                </span>

                <div>

                    <span class="page-eyebrow">
                        Profile Photo
                    </span>

                    <h2>
                        Choose how you appear
                    </h2>

                    <p>
                        Your photo appears in account navigation
                        and supported content interactions.
                    </p>

                </div>

            </header>

            <form
                method="post"
                action="index.php?page=account_profile_photo_upload"
                enctype="multipart/form-data"
                class="account-profile-upload-form"
                data-account-profile-upload-form>

                <?= csrfInput() ?>

                <label
                    class="account-profile-dropzone"
                    data-account-profile-dropzone>

                    <input
                        type="file"
                        name="profile_photo"
                        accept="image/jpeg,image/png,image/webp"
                        data-account-profile-file
                        required>

                    <span class="account-profile-upload-icon">

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                    </span>

                    <span>

                        <strong>
                            Select a JPG, PNG, or WebP image
                        </strong>

                        <small data-account-profile-file-name>
                            Maximum 3 MB &middot; 100&ndash;2000 pixels per side
                        </small>

                    </span>

                    <span class="app-button secondary">
                        Browse Image
                    </span>

                </label>

                <div
                    class="account-profile-client-error"
                    data-account-profile-client-error
                    role="alert"
                    hidden>

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span></span>

                </div>

                <div class="account-profile-security-note">

                    <i class="fa-solid fa-shield-halved"></i>

                    <span>

                        <strong>
                            Secure image validation
                        </strong>

                        <small>
                            The server verifies the real file type,
                            file size, and image dimensions before
                            storing a randomized filename.
                        </small>

                    </span>

                </div>

                <footer>

                    <?php if ($profilePhoto !== ''): ?>

                        <button
                            type="button"
                            class="app-button danger-outline"
                            data-open-profile-photo-removal>

                            <i class="fa-regular fa-trash-can"></i>

                            Remove Current Photo
                        </button>

                    <?php endif; ?>

                    <button
                        type="submit"
                        class="app-button primary"
                        data-save-profile-photo>

                        <i class="fa-solid fa-floppy-disk"></i>

                        Save Profile Photo
                    </button>

                </footer>

            </form>

            <?php if ($profilePhoto !== ''): ?>

                <form
                    method="post"
                    action="index.php?page=account_profile_photo_remove"
                    data-profile-photo-removal-form
                    hidden>

                    <?= csrfInput() ?>

                </form>

            <?php endif; ?>

        </main>

    </div>

</section>