<?php

$registrationFlash =
    $_SESSION['registration_flash']
    ?? [];

unset(
    $_SESSION['registration_flash']
);

$oldRegistrationInput =
    is_array(
        $registrationFlash['old_input']
            ?? null
    )
    ? $registrationFlash['old_input']
    : [];

$registrationErrorField =
    trim(
        (string) (
            $registrationFlash['error_field']
            ?? ''
        )
    );

$departments =
    $viewData['departments']
    ?? [];

$educationLevels =
    $viewData['education_levels']
    ?? [];

$academicPrograms =
    $viewData['academic_programs']
    ?? [];

$gradeLevels =
    $viewData['grade_levels']
    ?? [];

$sections =
    $viewData['sections']
    ?? [];

$legalDocuments =
    $viewData['legal_documents']
    ?? [];

$relationships =
    $viewData['relationships']
    ?? [
        'Mother',
        'Father',
        'Guardian',
        'Grandparent',
        'Relative',
        'Other'
    ];

$errorMessage =
    trim(
        (string) (
            $registrationFlash['error']
            ?? $_GET['error']
            ?? ''
        )
    );

$loadError = trim(
    (string) (
        $viewData['load_error']
        ?? ''
    )
);

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script
    type="application/json"
    id="registrationLegalDocuments">
    <?= json_encode(
        $legalDocuments,
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
    ) ?>
</script>

<?php if ($loadError !== ''): ?>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            () => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Academic Options Unavailable',
                    text: <?= json_encode(
                                $loadError,
                                JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_AMP |
                                    JSON_HEX_QUOT
                            ) ?>,
                    confirmButtonColor: '#7f1d1d'
                });
            }
        );
    </script>

<?php endif; ?>

<section class="auth-shell register-shell">

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
                Account Registration
            </span>

            <h1>
                <span>Join your school.</span>
                <span>Stay connected.</span>
            </h1>

            <p>
                Register as a Student or Parent.
                Every public registration is verified
                before the account becomes active.
            </p>

            <div class="registration-steps">

                <span>

                    <b>1</b>

                    Complete your details

                </span>

                <span>

                    <b>2</b>

                    Submit for verification

                </span>

                <span>

                    <b>3</b>

                    Wait for approval

                </span>

            </div>

        </div>

        <a
            href="index.php?page=login"
            class="auth-rail-login">

            <i class="fa-solid fa-arrow-left"></i>

            Return to login

        </a>

    </aside>

    <main class="auth-workspace register-workspace">

        <section class="auth-card register-card">

            <header class="register-header">

                <div>

                    <span class="auth-eyebrow">
                        Create Account
                    </span>

                    <h2>
                        Registration form
                    </h2>

                    <p>
                        Complete the required information below.
                    </p>

                </div>

                <a href="index.php?page=login">
                    Log In
                </a>

            </header>

            <?php if ($errorMessage !== ''): ?>

                <div
                    id="registrationErrorCard"
                    class="registration-error-card"
                    role="alert"
                    aria-live="assertive"
                    tabindex="-1">

                    <span>
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </span>

                    <div>

                        <strong>
                            Registration could not be submitted
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $errorMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <small>
                            Your completed information was preserved.
                            Review the highlighted field and try again.
                        </small>

                    </div>

                    <button
                        type="button"
                        data-dismiss-registration-error
                        aria-label="Dismiss registration error">

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>

            <?php endif; ?>

            <div
                id="registrationRestoreState"
                hidden
                data-error-field="<?= htmlspecialchars(
                                        $registrationErrorField,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                data-old-input="<?= htmlspecialchars(
                                    json_encode(
                                        $oldRegistrationInput,
                                        JSON_UNESCAPED_SLASHES |
                                            JSON_UNESCAPED_UNICODE
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
            </div>

            <form
                id="registrationForm"
                action="index.php?page=register_action"
                method="POST"
                class="register-form"
                novalidate>

                <?= csrfInput() ?>

                <!-- ==================================
                     ACCOUNT TYPE
                =================================== -->

                <section class="register-section">

                    <div class="register-section-heading">

                        <span>01</span>

                        <div>

                            <h3>
                                Account type
                            </h3>

                            <p>
                                Choose how you will use the platform.
                            </p>

                        </div>

                    </div>

                    <div class="role-options">

                        <label class="role-option active">

                            <input
                                type="radio"
                                name="role_type"
                                value="Student"
                                checked>

                            <i class="fa-solid fa-user-graduate"></i>

                            <span>

                                <strong>
                                    Student
                                </strong>

                                <small>
                                    Access official school information
                                    and activities.
                                </small>

                            </span>

                            <i class="fa-solid fa-circle-check"></i>

                        </label>

                        <label class="role-option">

                            <input
                                type="radio"
                                name="role_type"
                                value="Parent">

                            <i class="fa-solid fa-people-roof"></i>

                            <span>

                                <strong>
                                    Parent
                                </strong>

                                <small>
                                    Receive information related
                                    to a linked Student.
                                </small>

                            </span>

                            <i class="fa-solid fa-circle-check"></i>

                        </label>

                    </div>

                </section>

                <!-- ==================================
                     PERSONAL INFORMATION
                =================================== -->

                <section class="register-section">

                    <div class="register-section-heading">

                        <span>02</span>

                        <div>

                            <h3>
                                Personal information
                            </h3>

                            <p>
                                Use your correct legal information.
                            </p>

                        </div>

                    </div>

                    <div class="register-grid name-columns">

                        <div class="auth-field">

                            <label for="firstName">
                                First Name *
                            </label>

                            <div class="auth-control">

                                <input
                                    type="text"
                                    id="firstName"
                                    name="first_name"
                                    maxlength="100"
                                    autocomplete="given-name"
                                    required>

                            </div>

                            <small
                                class="auth-field-foot"
                                aria-hidden="true">
                                &nbsp;
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="middleName">
                                Middle Name
                            </label>

                            <div class="auth-control">

                                <input
                                    type="text"
                                    id="middleName"
                                    name="middle_name"
                                    maxlength="50"
                                    autocomplete="additional-name">

                            </div>

                            <small
                                class="auth-field-foot"
                                aria-hidden="true">
                                &nbsp;
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="lastName">
                                Last Name *
                            </label>

                            <div class="auth-control">

                                <input
                                    type="text"
                                    id="lastName"
                                    name="last_name"
                                    maxlength="100"
                                    autocomplete="family-name"
                                    required>

                            </div>

                            <small
                                class="auth-field-foot"
                                aria-hidden="true">
                                &nbsp;
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="nameSuffix">
                                Suffix
                            </label>

                            <div class="auth-control">

                                <select
                                    id="nameSuffix"
                                    name="name_suffix"
                                    autocomplete="honorific-suffix">

                                    <option value="">
                                        None
                                    </option>

                                    <option value="Jr.">
                                        Jr.
                                    </option>

                                    <option value="Sr.">
                                        Sr.
                                    </option>

                                    <option value="II">
                                        II
                                    </option>

                                    <option value="III">
                                        III
                                    </option>

                                    <option value="IV">
                                        IV
                                    </option>

                                    <option value="V">
                                        V
                                    </option>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                If applicable.
                            </small>

                        </div>

                    </div>

                    <div class="register-grid three-columns">

                        <div class="auth-field">

                            <label for="registrationEmail">
                                Email Address *
                            </label>

                            <div class="auth-control">

                                <i class="fa-regular fa-envelope"></i>

                                <input
                                    type="email"
                                    id="registrationEmail"
                                    name="email"
                                    maxlength="100"
                                    autocomplete="email"
                                    required>

                            </div>

                            <small class="auth-field-foot">
                                Use an email address you can access.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="birthdate">
                                Birthdate *
                            </label>

                            <div class="auth-control">

                                <i class="fa-regular fa-calendar"></i>

                                <input
                                    type="date"
                                    id="birthdate"
                                    name="birthdate"
                                    autocomplete="bday"
                                    required>

                            </div>

                            <small
                                id="calculatedAge"
                                class="auth-field-foot">
                                Age will be calculated automatically.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="gender">
                                Gender *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="gender"
                                    name="gender"
                                    required>

                                    <option value="">
                                        Select gender
                                    </option>

                                    <option value="Male">
                                        Male
                                    </option>

                                    <option value="Female">
                                        Female
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>

                                </select>

                            </div>

                            <small
                                class="auth-field-foot"
                                aria-hidden="true">
                                &nbsp;
                            </small>

                        </div>

                    </div>

                </section>

                <!-- ==================================
                     STUDENT ACADEMIC INFORMATION
                =================================== -->

                <section
                    id="studentFields"
                    class="register-section"
                    data-role-panel="Student">

                    <div class="register-section-heading">

                        <span>03</span>

                        <div>

                            <h3>
                                Academic information
                            </h3>

                            <p>
                                Used for role-based and precise
                                information delivery.
                            </p>

                        </div>

                    </div>

                    <div class="register-grid three-columns">

                        <div class="auth-field">

                            <label for="studentId">
                                Student ID *
                            </label>

                            <div class="auth-control">

                                <i class="fa-solid fa-id-card"></i>

                                <input
                                    type="text"
                                    id="studentId"
                                    name="student_id"
                                    maxlength="50"
                                    data-required-for="Student"
                                    required>

                            </div>

                            <small class="auth-field-foot">
                                Enter your official school-issued ID.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="departmentId">
                                School Division *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="departmentId"
                                    name="department_id"
                                    data-required-for="Student"
                                    required>

                                    <option value="">
                                        Select School Division
                                    </option>

                                    <?php foreach ($departments as $department): ?>

                                        <option
                                            value="<?= (int) (
                                                        $department['department_id']
                                                        ?? 0
                                                    ) ?>">

                                            <?= htmlspecialchars(
                                                $department['department_name']
                                                    ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                Select IBED or College.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="educationLevelId">
                                Education Level *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="educationLevelId"
                                    name="education_level_id"
                                    data-required-for="Student"
                                    required
                                    disabled>

                                    <option value="">
                                        Select School Division first
                                    </option>

                                    <?php foreach (
                                        $educationLevels
                                        as $educationLevel
                                    ): ?>

                                        <option
                                            value="<?= (int) (
                                                        $educationLevel['education_level_id'] ?? 0
                                                    ) ?>"
                                            data-department-id="<?= (int) (
                                                                    $educationLevel['department_id'] ?? 0
                                                                ) ?>"
                                            hidden>

                                            <?= htmlspecialchars(
                                                $educationLevel['education_level_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                Options update based on the selected
                                School Division.
                            </small>

                        </div>

                        <div
                            id="academicProgramField"
                            class="auth-field"
                            hidden>

                            <label for="academicProgramId">
                                Program or Strand *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="academicProgramId"
                                    name="academic_program_id"
                                    data-required-for-program="true"
                                    disabled>

                                    <option value="">
                                        Select Education Level first
                                    </option>

                                    <?php foreach ($academicPrograms as $program): ?>

                                        <option
                                            value="<?= (int) (
                                                        $program['academic_program_id'] ?? 0
                                                    ) ?>"
                                            data-education-level-id="<?= (int) (
                                                                            $program['education_level_id'] ?? 0
                                                                        ) ?>"
                                            data-program-type="<?= htmlspecialchars(
                                                                    $program['program_type'] ?? '',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                            hidden>
                                            <?= htmlspecialchars(
                                                (
                                                    $program['program_code'] ?? ''
                                                )
                                                    . ' — '
                                                    . (
                                                        $program['program_name'] ?? ''
                                                    ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small
                                id="academicProgramHint"
                                class="auth-field-foot">
                                Select the Program or Strand offered
                                under this division.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="gradeLevelId">
                                Grade or Year Level *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="gradeLevelId"
                                    name="grade_level_id"
                                    data-required-for="Student"
                                    required
                                    disabled>

                                    <option value="">
                                        Select Education Level first
                                    </option>

                                    <?php foreach ($gradeLevels as $grade): ?>

                                        <option
                                            value="<?= (int) (
                                                        $grade['grade_level_id'] ?? 0
                                                    ) ?>"
                                            data-education-level-id="<?= (int) (
                                                                            $grade['education_level_id'] ?? 0
                                                                        ) ?>"
                                            hidden>
                                            <?= htmlspecialchars(
                                                $grade['grade_level_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                Options update based on the selected Education Level.
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="sectionId">
                                Section *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="sectionId"
                                    name="section_id"
                                    data-required-for="Student"
                                    required
                                    disabled>

                                    <option value="">
                                        Complete the academic fields first
                                    </option>

                                    <?php foreach ($sections as $section): ?>

                                        <option
                                            value="<?= (int) (
                                                        $section['section_id'] ?? 0
                                                    ) ?>"
                                            data-grade-level-id="<?= (int) (
                                                                        $section['grade_level_id'] ?? 0
                                                                    ) ?>"
                                            data-academic-program-id="<?=
                                                                        !empty($section['academic_program_id'])
                                                                            ? (int) $section['academic_program_id']
                                                                            : ''
                                                                        ?>"
                                            hidden>
                                            <?= htmlspecialchars(
                                                $section['section_name'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                Used for section-specific announcements
                                and notifications.
                            </small>

                        </div>

                    </div>

                </section>

                <!-- ==================================
                     PARENT-STUDENT LINK
                =================================== -->

                <section
                    id="parentFields"
                    class="register-section"
                    data-role-panel="Parent"
                    hidden>

                    <div class="register-section-heading">

                        <span>03</span>

                        <div>

                            <h3>
                                Student relationship
                            </h3>

                            <p>
                                Link an existing Student account or request verification for a child without an account.
                            </p>

                        </div>

                    </div>

                    <?php if (!empty($viewData['child_registration_available'])): ?>
                    <label class="register-child-choice" for="childNoAccount">
                        <input type="checkbox" id="childNoAccount" name="child_no_account" value="1"
                            aria-describedby="childNoAccountHelp" aria-controls="childRegistrationDetails" disabled>
                        <span>
                            <strong>My child does not have a Digital Hub account yet</strong>
                            <span id="childNoAccountHelp">Select this to register as a Parent before your child has an account.</span>
                        </span>
                    </label>
                    <fieldset id="childRegistrationDetails" class="register-child-details" hidden>
                        <legend>Child details</legend>
                        <p class="register-group-help">Provide your child's school details so an Administrator can verify enrollment.</p>
                        <div class="register-grid two-columns">
                        <div class="auth-field">
                            <label for="childFullName">Child full name *</label>
                            <div class="auth-control"><input id="childFullName" name="child_name" maxlength="200" data-child-required disabled></div>
                        </div>
                        <div class="auth-field">
                            <label for="childSection">Child grade and section *</label>
                            <div class="auth-control"><select id="childSection" name="child_section_id" data-child-required disabled>
                                <option value="">Select class</option>
                                <?php foreach ($viewData['child_sections'] ?? [] as $childSection): ?>
                                    <option value="<?= (int)$childSection['section_id'] ?>"><?= htmlspecialchars($childSection['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <small class="auth-field-foot">Administrator verification against school records is required.</small>
                        </div>
                        <div class="auth-field">
                            <label for="childReason">Reason for assistance *</label>
                            <div class="auth-control"><select id="childReason" name="child_reason" data-child-required disabled>
                                <option value="">Select a reason</option>
                                <?php foreach ($viewData['child_reasons'] ?? [] as $key => $label): ?>
                                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        </div>
                        <div class="auth-field">
                            <label for="childReasonDetails">Additional explanation (optional)</label>
                            <div class="auth-control"><input id="childReasonDetails" name="child_reason_details" maxlength="500" disabled></div>
                            <small class="auth-field-foot">Avoid medical details or other sensitive information.</small>
                        </div>
                        </div>
                    </fieldset>
                    <?php endif; ?>

                    <h4 class="register-parent-subheading">School ID and relationship</h4>
                    <div class="register-grid two-columns">

                        <div class="auth-field">

                            <label for="childStudentId">
                                <span id="childStudentIdLabel">Child Student ID *</span>
                            </label>

                            <div class="auth-control">

                                <i class="fa-solid fa-id-card-clip"></i>

                                <input
                                    type="text"
                                    id="childStudentId"
                                    name="child_student_id"
                                    maxlength="50"
                                    data-required-for="Parent"
                                    disabled>

                            </div>

                            <small class="auth-field-foot">
                                <span id="childStudentIdHint">Enter the Student ID of an active Student account.</span>
                            </small>

                        </div>

                        <div class="auth-field">

                            <label for="relationship">
                                Relationship *
                            </label>

                            <div class="auth-control">

                                <select
                                    id="relationship"
                                    name="relationship"
                                    data-required-for="Parent"
                                    disabled>

                                    <option value="">
                                        Select relationship
                                    </option>

                                    <?php foreach ($relationships as $relationship): ?>

                                        <option
                                            value="<?= htmlspecialchars(
                                                        $relationship,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">
                                            <?= htmlspecialchars(
                                                $relationship,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <small class="auth-field-foot">
                                The relationship is verified
                                by an Administrator.
                            </small>

                        </div>

                    </div>

                    <div class="auth-notice">

                        <i class="fa-solid fa-shield-halved"></i>

                        <p>
                            Your application stays Pending until an Administrator verifies your child
                            and your relationship.
                        </p>

                    </div>

                </section>

                <!-- ==================================
                     ACCOUNT SECURITY
                =================================== -->

                <section class="register-section">

                    <div class="register-section-heading">

                        <span>04</span>

                        <div>

                            <h3>
                                Account security
                            </h3>

                            <p>
                                Create a secure password for your account.
                            </p>

                        </div>

                    </div>

                    <div class="register-grid two-columns">

                        <div class="auth-field">

                            <label for="registrationPassword">
                                Password *
                            </label>

                            <div class="auth-control">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="registrationPassword"
                                    name="password"
                                    minlength="8"
                                    maxlength="72"
                                    autocomplete="new-password"
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-toggle="registrationPassword"
                                    aria-label="Show password">

                                    <i class="fa-regular fa-eye"></i>

                                </button>

                            </div>

                            <div
                                id="passwordStrength"
                                class="password-strength auth-field-foot"
                                data-score="0">

                                <span></span>

                                <small>
                                    Uppercase, lowercase,
                                    and number required.
                                </small>

                            </div>

                        </div>

                        <div class="auth-field">

                            <label for="passwordConfirmation">
                                Confirm Password *
                            </label>

                            <div class="auth-control">

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    id="passwordConfirmation"
                                    name="password_confirmation"
                                    minlength="8"
                                    maxlength="72"
                                    autocomplete="new-password"
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-toggle="passwordConfirmation"
                                    aria-label="Show password">

                                    <i class="fa-regular fa-eye"></i>

                                </button>

                            </div>

                            <small
                                id="passwordMatchMessage"
                                class="auth-field-foot">
                                Passwords must match.
                            </small>

                        </div>

                    </div>

                </section>

                <!-- ==================================
                     CONSENT AND SUBMISSION
                =================================== -->

                <section class="register-submit">

                    <label class="auth-checkbox">

                        <input
                            type="checkbox"
                            id="acceptTerms"
                            name="accept_terms"
                            value="1"
                            required>

                        <span>
                            I have read and agree to the
                            <button
                                type="button"
                                class="register-legal-link"
                                data-legal-document="terms">
                                Terms and Conditions
                            </button>.
                        </span>

                    </label>

                    <label class="auth-checkbox">

                        <input
                            type="checkbox"
                            id="acceptPrivacy"
                            name="accept_privacy"
                            value="1"
                            required>

                        <span>
                            I have read and acknowledge the
                            <button
                                type="button"
                                class="register-legal-link"
                                data-legal-document="privacy">
                                Privacy Notice
                            </button>.
                        </span>

                    </label>

                    <button
                        type="submit"
                        id="registrationSubmitButton"
                        class="auth-submit-button">

                        <span>
                            Submit Registration
                        </span>

                        <i class="fa-solid fa-paper-plane"></i>

                    </button>

                </section>

            </form>

        </section>

    </main>

</section>
