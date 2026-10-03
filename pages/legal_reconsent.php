<?php

$pendingDocuments =
    $viewData['pending_documents']
    ?? [];

$errorMessage =
    trim(
        (string) (
            $viewData['error']
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
            ?? 'Digital Hub User'
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

<section class="legal-consent-shell">

    <aside class="legal-consent-rail">

        <a
            href="index.php?page=legal_reconsent"
            class="legal-consent-brand">

            <img
                src="Assets/Images/ulsco.png"
                alt="OLSHCO logo">

            <span>

                <strong>OLSHCO</strong>

                <small>Digital Hub</small>

            </span>

        </a>

        <div class="legal-consent-rail-copy">

            <span class="legal-consent-eyebrow">
                Required Review
            </span>

            <h1>
                Your privacy<br>
                matters.
            </h1>

            <p>
                Please review the current legal documents
                before continuing to the Digital Hub.
            </p>

            <ul>

                <li>
                    <i class="fa-solid fa-file-signature"></i>

                    Review each updated document.
                </li>

                <li>
                    <i class="fa-solid fa-shield-halved"></i>

                    Your acceptance is securely recorded.
                </li>

                <li>
                    <i class="fa-solid fa-clock-rotate-left"></i>

                    Previous acceptance records remain preserved.
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

    <main class="legal-consent-workspace">

        <section class="legal-consent-card">

            <header class="legal-consent-header">

                <span class="legal-consent-header-icon">

                    <i class="fa-solid fa-scale-balanced"></i>

                </span>

                <div>

                    <span class="legal-consent-eyebrow">
                        Terms and Privacy
                    </span>

                    <h2>Review current documents</h2>

                    <p>
                        Read each document below and confirm your
                        acceptance to continue.
                    </p>

                </div>

            </header>

            <div class="legal-consent-account">

                <span>

                    <?= $escape(
                        strtoupper(
                            mb_substr(
                                $displayName !== ''
                                    ? $displayName
                                    : 'U',
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

                <div class="legal-consent-alert">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        <?= $escape(
                            $errorMessage
                        ) ?>
                    </span>

                </div>

            <?php endif; ?>

            <div class="legal-consent-documents">

                <?php foreach (
                    $pendingDocuments
                    as $document
                ): ?>

                    <?php

                    $documentType =
                        (string) (
                            $document['document_type']
                            ?? 'Legal'
                        );

                    $documentTitle =
                        (string) (
                            $document['title']
                            ?? $documentType
                        );

                    $documentContent =
                        (string) (
                            $document['content']
                            ?? ''
                        );

                    ?>

                    <article class="legal-consent-document">

                        <header>

                            <span>

                                <i class="<?= $documentType === 'Privacy'
                                                ? 'fa-solid fa-user-shield'
                                                : 'fa-solid fa-file-contract'
                                            ?>"></i>

                            </span>

                            <div>

                                <small>
                                    <?= $escape(
                                        $documentType
                                    ) ?>
                                </small>

                                <h3>
                                    <?= $escape(
                                        $documentTitle
                                    ) ?>
                                </h3>

                            </div>

                            <span class="legal-consent-updated">
                                Current
                            </span>

                        </header>

                        <div class="legal-consent-document-body">

                            <?php if (
                                $documentContent !== ''
                            ): ?>

                                <?= $documentContent ?>

                            <?php else: ?>

                                <p>
                                    This legal document is temporarily
                                    unavailable. Please contact an
                                    administrator.
                                </p>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <form
                action="index.php?page=legal_reconsent_action"
                method="post"
                class="legal-consent-form">

                <?= csrfInput() ?>

                <label class="legal-consent-confirmation">

                    <input
                        type="checkbox"
                        name="accept_current_legal_documents"
                        value="1"
                        required>

                    <span>

                        <strong>
                            I have reviewed and accept the current
                            documents.
                        </strong>

                        <small>
                            My acceptance will be recorded with the
                            date and applicable document versions.
                        </small>

                    </span>

                </label>

                <button
                    type="submit"
                    class="legal-consent-submit">

                    <span>
                        Accept and Continue
                    </span>

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>

        </section>

    </main>

</section>