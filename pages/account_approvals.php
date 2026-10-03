<?php

$pendingRegistrations =
    $viewData['pending_registrations']
    ?? [];

$selectedRegistration =
    $viewData['selected_registration']
    ?? null;

$selectedUserId =
    (int) (
        $viewData['selected_user_id']
        ?? 0
    );

$selectionError =
    trim(
        (string) (
            $viewData['selection_error']
            ?? ''
        )
    );

$successMessage =
    trim(
        (string) (
            $_GET['success']
            ?? ''
        )
    );

$errorMessage =
    trim(
        (string) (
            $_GET['error']
            ?? ''
        )
    );

$escape =
    static function (
        mixed $value
    ): string {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    };

$fullName =
    static function (
        array $registration
    ): string {
        return implode(
            ' ',
            array_filter(
                [
                    trim(
                        (string) (
                            $registration['first_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $registration['middle_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $registration['last_name']
                            ?? ''
                        )
                    ),

                    trim(
                        (string) (
                            $registration['name_suffix']
                            ?? ''
                        )
                    )
                ],
                static fn(
                    string $part
                ): bool =>
                $part !== ''
            )
        );
    };

?>

<section class="app-page account-approvals-page">

    <!-- ======================================
         PAGE HEADER
    ======================================= -->

    <header class="page-header account-approvals-header">

        <div class="page-header-copy">

            <span class="page-eyebrow">
                User Administration
            </span>

            <h1>
                Pending Account Approvals
            </h1>

            <p>
                Review Student and Parent registrations before
                granting access to the OLSHCO Digital Hub.
            </p>

        </div>

        <div class="account-approval-summary">

            <span>

                <i class="fa-solid fa-user-clock"></i>

            </span>

            <div>

                <strong>
                    <?= number_format(
                        count(
                            $pendingRegistrations
                        )
                    ) ?>
                </strong>

                <small>
                    Pending
                    <?= count(
                        $pendingRegistrations
                    ) === 1
                        ? 'registration'
                        : 'registrations'
                    ?>
                </small>

            </div>

        </div>

    </header>

    <?php if ($successMessage !== ''): ?>

        <div class="account-approval-alert success">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                <?= $escape(
                    $successMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div class="account-approval-alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <span>
                <?= $escape(
                    $errorMessage
                ) ?>
            </span>

        </div>

    <?php endif; ?>

    <section class="account-approval-layout">

        <!-- ==================================
             PENDING REGISTRATION QUEUE
        =================================== -->

        <article class="page-card account-approval-queue">

            <div class="card-header">

                <div class="card-header-copy">

                    <span class="page-eyebrow">
                        Review Queue
                    </span>

                    <h2>
                        Pending applicants
                    </h2>

                    <p>
                        Select an applicant to inspect their
                        identity and academic assignment.
                    </p>

                </div>

            </div>

            <div class="account-approval-list">

                <?php if (
                    empty($pendingRegistrations)
                ): ?>

                    <div class="account-approval-empty">

                        <span>

                            <i class="fa-solid fa-user-check"></i>

                        </span>

                        <strong>
                            No pending registrations
                        </strong>

                        <p>
                            New Student and Parent applications
                            will appear here.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach (
                        $pendingRegistrations
                        as $registration
                    ): ?>

                        <?php

                        $registrationId =
                            (int) (
                                $registration['user_id']
                                ?? 0
                            );

                        $registrationRole =
                            trim(
                                (string) (
                                    $registration['role_prefix']
                                    ?? 'Applicant'
                                )
                            );

                        $isSelected =
                            $registrationId > 0
                            && $registrationId ===
                            $selectedUserId;

                        ?>

                        <a
                            href="index.php?page=account_approvals&user_id=<?= $registrationId ?>"
                            class="account-approval-list-item<?= $isSelected
                                                                    ? ' active'
                                                                    : ''
                                                                ?>"
                            <?= $isSelected
                                ? 'aria-current="true"'
                                : ''
                            ?>>

                            <span class="account-approval-avatar">

                                <i class="<?= $registrationRole === 'Parent'
                                                ? 'fa-solid fa-people-roof'
                                                : 'fa-solid fa-user-graduate'
                                            ?>"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= $escape(
                                        $fullName(
                                            $registration
                                        )
                                    ) ?>
                                </strong>

                                <small>
                                    <?= $escape(
                                        $registrationRole
                                    ) ?>

                                    <?php if (
                                        !empty($registration['studID'])
                                    ): ?>

                                        ·

                                        <?= $escape(
                                            $registration['studID']
                                        ) ?>

                                    <?php endif; ?>
                                </small>

                                <small>
                                    Submitted
                                    <?= $escape(
                                        date(
                                            'M d, Y g:i A',
                                            strtotime(
                                                $registration['created_at']
                                                    ?? 'now'
                                            )
                                        )
                                    ) ?>
                                </small>

                            </div>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </article>

        <!-- ==================================
             SELECTED REGISTRATION
        =================================== -->

        <article class="page-card account-approval-review">

            <?php if (
                $selectionError !== ''
            ): ?>

                <div class="account-approval-empty">

                    <span>

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </span>

                    <strong>
                        Unable to load applicant
                    </strong>

                    <p>
                        <?= $escape(
                            $selectionError
                        ) ?>
                    </p>

                </div>

            <?php elseif (
                empty($selectedRegistration)
            ): ?>

                <div class="account-approval-empty">

                    <span>

                        <i class="fa-regular fa-address-card"></i>

                    </span>

                    <strong>
                        Select an applicant
                    </strong>

                    <p>
                        Choose a pending registration from the
                        queue to begin verification.
                    </p>

                </div>

            <?php else: ?>

                <?php

                $role =
                    trim(
                        (string) (
                            $selectedRegistration['role_prefix']
                            ?? 'Applicant'
                        )
                    );

                $isParent =
                    $role === 'Parent';

                ?>

                <div class="account-review-header">

                    <div>

                        <span class="page-eyebrow">
                            Registration Review
                        </span>

                        <h2>
                            <?= $escape(
                                $fullName(
                                    $selectedRegistration
                                )
                            ) ?>
                        </h2>

                        <p>
                            Verify all details before activating
                            this <?= $escape(
                                        $role
                                    ) ?> account.
                        </p>

                    </div>

                    <span class="account-role-badge">

                        <i class="<?= $isParent
                                        ? 'fa-solid fa-people-roof'
                                        : 'fa-solid fa-user-graduate'
                                    ?>"></i>

                        <?= $escape(
                            $role
                        ) ?>

                    </span>

                </div>

                <section class="account-review-section">

                    <h3>
                        Personal information
                    </h3>

                    <dl class="account-review-grid">

                        <div>

                            <dt>Full name</dt>

                            <dd>
                                <?= $escape(
                                    $fullName(
                                        $selectedRegistration
                                    )
                                ) ?>
                            </dd>

                        </div>

                        <div>

                            <dt>Email address</dt>

                            <dd>
                                <?= $escape(
                                    $selectedRegistration['email']
                                        ?? 'Not provided'
                                ) ?>
                            </dd>

                        </div>

                        <div>

                            <dt>Birthdate</dt>

                            <dd>
                                <?= $escape(
                                    !empty($selectedRegistration['birthdate'])
                                        ? date(
                                            'M d, Y',
                                            strtotime(
                                                $selectedRegistration['birthdate']
                                            )
                                        )
                                        : 'Not provided'
                                ) ?>
                            </dd>

                        </div>

                        <div>

                            <dt>Gender and age</dt>

                            <dd>
                                <?= $escape(
                                    $selectedRegistration['gender']
                                        ?? 'Not provided'
                                ) ?>

                                ·

                                <?= number_format(
                                    (int) (
                                        $selectedRegistration['age']
                                        ?? 0
                                    )
                                ) ?>
                            </dd>

                        </div>

                    </dl>

                </section>

                <?php if (!$isParent): ?>

                    <section class="account-review-section">

                        <h3>
                            Student information
                        </h3>

                        <dl class="account-review-grid">

                            <div>

                                <dt>Student ID</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['studID']
                                            ?? 'Not provided'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>School division</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['department_name']
                                            ?? 'Not assigned'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Education level</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['education_level_name']
                                            ?? 'Not assigned'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Program or strand</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['program_name']
                                            ?? 'Not applicable'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Grade or year level</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['grade_level_name']
                                            ?? 'Not assigned'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Section</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['section_name']
                                            ?? 'Not assigned'
                                    ) ?>
                                </dd>

                            </div>

                        </dl>

                    </section>

                <?php elseif (empty($selectedRegistration['child_record'])): ?>

                    <section class="account-review-section">

                        <h3>
                            Student relationship
                        </h3>

                        <dl class="account-review-grid">

                            <div>

                                <dt>Linked Student</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['child_name']
                                            ?? 'Not found'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Student ID</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['child_student_id']
                                            ?? 'Not found'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Relationship</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['relationship']
                                            ?? 'Not provided'
                                    ) ?>
                                </dd>

                            </div>

                            <div>

                                <dt>Relationship status</dt>

                                <dd>
                                    <?= $escape(
                                        $selectedRegistration['relationship_status']
                                            ?? 'Not found'
                                    ) ?>
                                </dd>

                            </div>

                        </dl>

                    </section>

                <?php endif; ?>

                <?php require __DIR__ . '/../include/components/parent-child-review.php'; ?>

                <?php if (($selectedRegistration['status'] ?? '') === 'Pending'): ?>
                <div class="account-review-actions">

                    <!-- ==================================
         APPROVAL ACTION
    =================================== -->

                    <section class="account-decision-card approval">

                        <header>

                            <span>
                                <i class="fa-solid fa-user-check"></i>
                            </span>

                            <div>

                                <h3>
                                    Approve Account
                                </h3>

                                <p>
                                    Activate this account after verifying the
                                    applicant’s identity and submitted information.
                                </p>

                            </div>

                        </header>

                        <form
                            id="accountApprovalForm"
                            method="post"
                            action="index.php?page=account_approval_approve"
                            class="account-approval-form"
                            data-review-form="approve"
                            data-applicant-name="<?= $escape(
                                                        $fullName(
                                                            $selectedRegistration
                                                        )
                                                    ) ?>">
                            <?= csrfInput() ?>

                            <input
                                type="hidden"
                                name="registration_user_id"
                                value="<?= (int) (
                                            $selectedRegistration['user_id']
                                            ?? 0
                                        ) ?>">

                            <input
                                type="hidden"
                                name="confirm_approval"
                                value="0"
                                data-confirmation-value>

                            <label for="accountReviewNotes">

                                <span>
                                    Approval note
                                    <small><?= !empty($selectedRegistration['child_record']) ? 'Required: enrollment and relationship verification' : 'Optional' ?></small>
                                </span>

                                <textarea
                                    id="accountReviewNotes"
                                    <?= !empty($selectedRegistration['child_record']) ? 'required' : '' ?>
                                    name="review_notes"
                                    maxlength="1000"
                                    rows="4"
                                    placeholder="Add an internal verification note if needed."></textarea>

                            </label>

                            <div class="account-decision-notice">

                                <i class="fa-solid fa-circle-info"></i>

                                <span>
                                    Approval activates the account and allows the
                                    applicant to access the Digital Hub.
                                </span>

                            </div>

                            <button
                                type="button"
                                class="app-button primary"
                                data-open-review-confirmation>

                                <i class="fa-solid fa-user-check"></i>

                                Approve and Activate
                            </button>

                        </form>

                    </section>

                    <!-- ==================================
         REJECTION ACTION
    =================================== -->

                    <section class="account-decision-card rejection">

                        <header>

                            <span>
                                <i class="fa-solid fa-user-xmark"></i>
                            </span>

                            <div>

                                <h3>
                                    Reject Registration
                                </h3>

                                <p>
                                    Reject the application only when its identity,
                                    academic, or relationship information cannot
                                    be verified.
                                </p>

                            </div>

                        </header>

                        <form
                            id="accountRejectionForm"
                            method="post"
                            action="index.php?page=account_approval_reject"
                            class="account-rejection-form"
                            data-review-form="reject"
                            data-applicant-name="<?= $escape(
                                                        $fullName(
                                                            $selectedRegistration
                                                        )
                                                    ) ?>">

                            <?= csrfInput() ?>

                            <input
                                type="hidden"
                                name="registration_user_id"
                                value="<?= (int) (
                                            $selectedRegistration['user_id']
                                            ?? 0
                                        ) ?>">

                            <input
                                type="hidden"
                                name="confirm_rejection"
                                value="0"
                                data-confirmation-value>

                            <label for="accountRejectionNotes">

                                <span>
                                    Rejection reason
                                    <small>Required</small>
                                </span>

                                <textarea
                                    id="accountRejectionNotes"
                                    name="review_notes"
                                    maxlength="1000"
                                    rows="4"
                                    required
                                    placeholder="Explain why this registration cannot be approved."></textarea>

                            </label>

                            <small
                                class="account-field-error"
                                data-rejection-error
                                hidden>

                                Enter a rejection reason before continuing.
                            </small>

                            <div class="account-decision-notice danger">

                                <i class="fa-solid fa-triangle-exclamation"></i>

                                <span>
                                    The applicant will be unable to sign in after
                                    this registration is rejected.
                                </span>

                            </div>

                            <button
                                type="button"
                                class="app-button account-rejection-button"
                                data-open-review-confirmation>

                                <i class="fa-solid fa-user-xmark"></i>

                                Reject Registration
                            </button>

                        </form>

                    </section>

                </div>

                <?php endif; ?>

                <!-- ======================================
     DECISION CONFIRMATION MODAL
======================================= -->

                <div
                    id="accountDecisionModal"
                    class="account-decision-modal"
                    hidden>

                    <div
                        class="account-decision-backdrop"
                        data-close-review-confirmation>
                    </div>

                    <section
                        class="account-decision-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="accountDecisionTitle"
                        aria-describedby="accountDecisionMessage">

                        <button
                            type="button"
                            class="account-decision-close"
                            data-close-review-confirmation
                            aria-label="Close confirmation">

                            <i class="fa-solid fa-xmark"></i>

                        </button>

                        <span
                            id="accountDecisionIcon"
                            class="account-decision-dialog-icon">

                            <i class="fa-solid fa-user-check"></i>

                        </span>

                        <span class="page-eyebrow">
                            Confirm Account Decision
                        </span>

                        <h3 id="accountDecisionTitle">
                            Approve this account?
                        </h3>

                        <p id="accountDecisionMessage">
                            Confirm this account review decision.
                        </p>

                        <div
                            id="accountDecisionReason"
                            class="account-decision-reason"
                            hidden>

                            <small>Rejection reason</small>

                            <p></p>

                        </div>

                        <div class="account-decision-dialog-actions">

                            <button
                                type="button"
                                class="app-button secondary"
                                data-close-review-confirmation>

                                Cancel
                            </button>

                            <button
                                type="button"
                                id="accountDecisionConfirm"
                                class="app-button primary">

                                Confirm Approval
                            </button>

                        </div>

                    </section>

                </div>

            <?php endif; ?>

        </article>

    </section>

</section>