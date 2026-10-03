<?php

$isLoggedIn =
    !empty($_SESSION['user_id']);

$pageWrapperClass =
    $isLoggedIn
    ? 'app-page contact-app-page'
    : 'public-contact-page';

$contactDetails = [
    [
        'icon' => 'fa-solid fa-location-dot',
        'label' => 'School Address',
        'value' => 'Afan Salvador Street, Guimba, Nueva Ecija',
        'href' => null
    ],
    [
        'icon' => 'fa-solid fa-phone',
        'label' => 'Phone Number',
        'value' => '+63 956 277 4029',
        'href' => 'tel:+639562774029'
    ],
    [
        'icon' => 'fa-solid fa-envelope',
        'label' => 'Email Address',
        'value' => 'olshco@gmail.com',
        'href' => 'mailto:olshco@gmail.com'
    ],
    [
        'icon' => 'fa-brands fa-facebook-f',
        'label' => 'Facebook Page',
        'value' => 'facebook.com/olshco1947',
        'href' => 'https://facebook.com/olshco1947'
    ]
];

$schoolOffices = [
    [
        'icon' => 'fa-solid fa-user-graduate',
        'name' => 'Admissions Office',
        'description' =>
        'For enrollment requirements, application procedures, and admission-related concerns.'
    ],
    [
        'icon' => 'fa-solid fa-file-signature',
        'name' => 'Registrar',
        'description' =>
        'For school records, enrollment documents, certificates, and academic credentials.'
    ],
    [
        'icon' => 'fa-solid fa-school',
        'name' => 'Basic Education Department',
        'description' =>
        'For Elementary, Junior High School, and Senior High School concerns.'
    ],
    [
        'icon' => 'fa-solid fa-building-columns',
        'name' => 'College Department',
        'description' =>
        'For College programs, academic requirements, and department-related inquiries.'
    ]
];

?>

<section class="<?= $pageWrapperClass ?>">

    <!-- ======================================
         PAGE INTRODUCTION
    ======================================= -->

    <header class="contact-page-header">

        <div class="contact-header-copy">

            <span class="contact-eyebrow">
                Contact OLSHCO
            </span>

            <h1>
                We are here to help.
            </h1>

            <p>
                Reach the appropriate school office for admissions,
                academic concerns, records, enrollment assistance,
                or general inquiries.
            </p>

            <div class="contact-header-actions">

                <a
                    href="tel:+639562774029"
                    class="app-button primary">
                    <i class="fa-solid fa-phone"></i>

                    Call the School
                </a>

                <a
                    href="mailto:olshco@gmail.com"
                    class="app-button secondary">
                    <i class="fa-solid fa-envelope"></i>

                    Send an Email
                </a>

            </div>

        </div>

        <div class="contact-summary-card">

            <span class="contact-summary-icon">

                <i class="fa-solid fa-headset"></i>

            </span>

            <div>

                <span>
                    School Assistance
                </span>

                <strong>
                    Connect with the right office
                </strong>

                <p>
                    Use the contact directory or inquiry form
                    to send your concern to the school.
                </p>

            </div>

            <div class="contact-summary-tags">

                <span>Admissions</span>
                <span>Registrar</span>
                <span>Basic Education</span>
                <span>College</span>

            </div>

        </div>

    </header>

    <!-- ======================================
         CONTACT DIRECTORY
    ======================================= -->

    <section class="contact-content-section">

        <div class="contact-section-heading">

            <div>

                <span class="contact-eyebrow">
                    Contact Directory
                </span>

                <h2>
                    Official contact information
                </h2>

                <p>
                    Use the following channels for official
                    school communication and inquiries.
                </p>

            </div>

        </div>

        <div class="contact-detail-grid">

            <?php foreach (
                $contactDetails
                as $detail
            ): ?>

                <?php if ($detail['href'] !== null): ?>

                    <a
                        href="<?= htmlspecialchars(
                                    $detail['href'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                        class="contact-detail-card"
                        <?php if (
                            str_starts_with(
                                $detail['href'],
                                'http'
                            )
                        ): ?>
                        target="_blank"
                        rel="noopener noreferrer"
                        <?php endif; ?>>

                    <?php else: ?>

                        <article class="contact-detail-card">

                        <?php endif; ?>

                        <span class="contact-detail-icon">

                            <i class="<?= htmlspecialchars(
                                            $detail['icon'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"></i>

                        </span>

                        <div>

                            <span>
                                <?= htmlspecialchars(
                                    $detail['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $detail['value'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>

                        <?php if (
                            $detail['href'] !== null
                        ): ?>

                            <i class="fa-solid fa-arrow-up-right-from-square"></i>

                        <?php endif; ?>

                        <?php if ($detail['href'] !== null): ?>

                    </a>

                <?php else: ?>

                    </article>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- ======================================
         MAP AND INQUIRY FORM
    ======================================= -->

    <section class="contact-main-grid">

        <article class="contact-map-card">

            <div class="contact-card-heading">

                <div>

                    <span class="contact-eyebrow">
                        School Location
                    </span>

                    <h2>
                        Visit the OLSHCO campus
                    </h2>

                    <p>
                        Afan Salvador Street,
                        Guimba, Nueva Ecija.
                    </p>

                </div>

                <span class="contact-card-icon">

                    <i class="fa-solid fa-map-location-dot"></i>

                </span>

            </div>

            <div class="contact-map-frame">

                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1139.252763825339!2d120.76766541984874!3d15.661201607582324!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33912cdb2318296d%3A0xe4e2117e97dfc92e!2sOur%20Lady%20of%20The%20Sacred%20Heart%20College!5e1!3m2!1sen!2sus!4v1776728814641!5m2!1sen!2sus"
                    title="Our Lady of the Sacred Heart College location"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"></iframe>

            </div>

            <a
                href="https://www.google.com/maps/search/?api=1&query=Our+Lady+of+the+Sacred+Heart+College+Guimba"
                class="contact-map-action"
                target="_blank"
                rel="noopener noreferrer">
                Open in Google Maps

                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </article>

        <article class="contact-form-card">

            <div class="contact-card-heading">

                <div>

                    <span class="contact-eyebrow">
                        Send an Inquiry
                    </span>

                    <h2>
                        Get in touch
                    </h2>

                    <p>
                        Complete the form and provide
                        the details of your concern.
                    </p>

                </div>

                <span class="contact-card-icon">

                    <i class="fa-solid fa-paper-plane"></i>

                </span>

            </div>

            <form
                id="contactForm"
                class="contact-inquiry-form"
                action="#"
                method="POST">

                <div class="contact-form-grid">

                    <div class="contact-field">

                        <label for="contactName">
                            Full Name *
                        </label>

                        <div class="contact-control">

                            <i class="fa-regular fa-user"></i>

                            <input
                                type="text"
                                id="contactName"
                                name="name"
                                maxlength="150"
                                autocomplete="name"
                                required>

                        </div>

                    </div>

                    <div class="contact-field">

                        <label for="contactEmail">
                            Email Address *
                        </label>

                        <div class="contact-control">

                            <i class="fa-regular fa-envelope"></i>

                            <input
                                type="email"
                                id="contactEmail"
                                name="email"
                                maxlength="150"
                                autocomplete="email"
                                required>

                        </div>

                    </div>

                </div>

                <div class="contact-field">

                    <label for="contactSubject">
                        Inquiry Type *
                    </label>

                    <div class="contact-control">

                        <i class="fa-solid fa-list"></i>

                        <select
                            id="contactSubject"
                            name="subject"
                            required>

                            <option value="">
                                Select inquiry type
                            </option>

                            <option value="Admissions">
                                Admissions
                            </option>

                            <option value="Enrollment">
                                Enrollment
                            </option>

                            <option value="Academic Records">
                                Academic Records
                            </option>

                            <option value="Basic Education">
                                Basic Education
                            </option>

                            <option value="College">
                                College
                            </option>

                            <option value="Technical Support">
                                Digital Hub Support
                            </option>

                            <option value="General Inquiry">
                                General Inquiry
                            </option>

                        </select>

                    </div>

                </div>

                <div class="contact-field">

                    <label for="contactMessage">
                        Message *
                    </label>

                    <div class="contact-control textarea-control">

                        <textarea
                            id="contactMessage"
                            name="message"
                            maxlength="1500"
                            rows="7"
                            required></textarea>

                    </div>

                    <small>
                        Include important details so the school
                        can understand your concern.
                    </small>

                </div>

                <button
                    type="submit"
                    class="contact-submit-button">
                    <span>
                        Send Inquiry
                    </span>

                    <i class="fa-solid fa-paper-plane"></i>
                </button>

            </form>

            <div class="contact-form-notice">

                <i class="fa-solid fa-circle-info"></i>

                <p>
                    This form is currently prepared for the Contact
                    Inquiry module. Backend email delivery and inquiry
                    storage must be connected before production use.
                </p>

            </div>

        </article>

    </section>

    <!-- ======================================
         SCHOOL OFFICES
    ======================================= -->

    <section class="contact-content-section">

        <div class="contact-section-heading">

            <div>

                <span class="contact-eyebrow">
                    School Offices
                </span>

                <h2>
                    Find the right office
                </h2>

                <p>
                    Directing concerns to the correct office
                    helps the school respond more efficiently.
                </p>

            </div>

        </div>

        <div class="contact-office-grid">

            <?php foreach (
                $schoolOffices
                as $office
            ): ?>

                <article class="contact-office-card">

                    <span>

                        <i class="<?= htmlspecialchars(
                                        $office['icon'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"></i>

                    </span>

                    <h3>
                        <?= htmlspecialchars(
                            $office['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $office['description'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- ======================================
         FINAL CALL TO ACTION
    ======================================= -->

    <section class="contact-cta">

        <div>

            <span>
                OLSHCO Digital Hub
            </span>

            <h2>
                Stay connected with the school.
            </h2>

            <p>
                Sign in to access official announcements,
                events, documents, and information
                intended for your account.
            </p>

        </div>

        <?php if ($isLoggedIn): ?>

            <a
                href="index.php?page=news"
                class="contact-cta-button">
                Open Information Hub

                <i class="fa-solid fa-arrow-right"></i>
            </a>

        <?php else: ?>

            <a
                href="index.php?page=login"
                class="contact-cta-button">
                Sign In

                <i class="fa-solid fa-arrow-right"></i>
            </a>

        <?php endif; ?>

    </section>

</section>