<?php

$isLoggedIn =
    !empty($_SESSION['user_id']);

$pageWrapperClass =
    $isLoggedIn
    ? 'app-page about-app-page'
    : 'public-about-page';

?>

<section class="<?= $pageWrapperClass ?>">

    <!-- ======================================
         PAGE INTRODUCTION
    ======================================= -->

    <header
        class="about-page-header"
        id="aboutOverview">

        <div class="about-header-copy">

            <span class="about-eyebrow">
                Our Institution
            </span>

            <h1>
                About Our School
            </h1>

            <p>
                Our Lady of the Sacred Heart College of Guimba, Inc.
                is committed to nurturing young minds through
                quality education, strong values, and a
                Christ-centered learning environment.
            </p>


        </div>

        <div class="about-identity-card">

            <img
                src="Assets/Images/ulsco.png"
                alt="OLSHCO logo">

            <div>

                <span>
                    Established June 26, 1947
                </span>

                <strong>
                    Rooted in Faith,<br>
                    Grounded in Excellence
                </strong>

                <p>
                    A private Catholic educational institution
                    serving the community of Guimba,
                    Nueva Ecija.
                </p>

            </div>

        </div>

    </header>

    <nav
        class="about-section-navigation"
        aria-label="About page sections">

        <a href="#aboutOverview">
            Overview
        </a>

        <a href="#aboutHistory">
            History
        </a>

        <a href="#aboutFoundation">
            Foundation
        </a>

        <a href="#aboutGoals">
            Goals
        </a>

        <a href="#aboutValues">
            Core Values
        </a>

        <a href="#aboutProfile">
            School Profile
        </a>

    </nav>

    <!-- ======================================
         OUR STORY
    ======================================= -->

    <section
        class="about-story-card"
        id="aboutHistory">

        <div class="about-story-image">

            <img
                src="Assets/Images/about 2.jpg"
                alt="Entrance of the OLSHCO campus in Guimba, Nueva Ecija">

        </div>

        <div class="about-story-content">

            <span class="about-eyebrow">
                Our History
            </span>

            <h2>
                Our Story
            </h2>

            <p>
                Founded with a mission to provide faith-based
                and quality education, Our Lady of the Sacred
                Heart College of Guimba, Inc. has continuously
                served the community by shaping students into
                responsible and competent individuals.
            </p>

            <p>
                From humble beginnings, the school has grown
                into a trusted institution known for academic
                excellence, character formation, and strong
                spiritual foundations.
            </p>

            <blockquote>
                “Rooted in Faith, Grounded in Excellence.”
            </blockquote>

        </div>

    </section>

    <!-- ======================================
         MISSION, VISION, PHILOSOPHY
    ======================================= -->

    <section
        class="about-content-section"
        id="aboutFoundation">

        <div class="about-section-heading">

            <div>

                <span class="about-eyebrow">
                    Institutional Foundation
                </span>

                <h2>
                    Mission, Vision, and Philosophy
                </h2>

                <p>
                    The principles that guide the school community,
                    its learning environment, and its service.
                </p>

            </div>

        </div>

        <div class="about-card-grid three-columns">

            <article class="about-info-card">

                <span class="about-card-icon">

                    <i class="fa-solid fa-bullseye"></i>

                </span>

                <h3>
                    Our Mission
                </h3>

                <p>
                    Inspired by and devoted to the oneness
                    of Heart of Jesus and Mary, OLSHCO
                    is in mission to:
                </p>

                <ol>

                    <li>
                        Develop among students modern-world
                        skills for them to succeed as empowered
                        global citizens.
                    </li>

                    <li>
                        Nurture a learning environment that
                        proclaims and models values of
                        human integrity.
                    </li>

                </ol>

            </article>

            <article class="about-info-card">

                <span class="about-card-icon">

                    <i class="fa-solid fa-eye"></i>

                </span>

                <h3>
                    Our Vision
                </h3>

                <p>
                    A diocesan Catholic school community that
                    is founded on the oneness of Heart of Jesus
                    and Mary and passionate in the communal work
                    of integral human development of the young
                    towards the fullness of life.
                </p>

            </article>

            <article class="about-info-card">

                <span class="about-card-icon">

                    <i class="fa-solid fa-book-open"></i>

                </span>

                <h3>
                    Our Philosophy
                </h3>

                <p>
                    Our Lady of the Sacred Heart College of Guimba,
                    Inc. believes that education shall lead young
                    men and women into human fullness, combining
                    their life and work skills with their sacred
                    love for all persons as desired by the oneness
                    of the Heart of Jesus and Mary.
                </p>

                <p>
                    The school gracefully dedicates its existence
                    in cultivating student-centered learning for
                    the holistic development of a person’s intellect,
                    physical well-being, social life, and spiritual life.
                </p>

            </article>

        </div>

    </section>

    <!-- ======================================
         SCHOOL GOALS
    ======================================= -->

    <section
        class="about-content-section"
        id="aboutGoals">

        <div class="about-section-heading">

            <div>

                <span class="about-eyebrow">
                    Institutional Direction
                </span>

                <h2>
                    Our Goals
                </h2>

                <p>
                    The outcomes the institution seeks for its
                    students, community, and mission.
                </p>

            </div>

        </div>

        <div class="about-card-grid three-columns">

            <article class="about-goal-card">

                <span>01</span>

                <p>
                    To produce holistic young individuals who are
                    motivated by success and who can integrate
                    themselves into societies and places of work.
                </p>

            </article>

            <article class="about-goal-card">

                <span>02</span>

                <p>
                    To actively participate in national and global
                    development initiatives through a culture of
                    educational excellence.
                </p>

            </article>

            <article class="about-goal-card">

                <span>03</span>

                <p>
                    To support the local Church in carrying out
                    its mission of evangelization and discipleship.
                </p>

            </article>

        </div>

    </section>

    <!-- ======================================
         CORE VALUES
    ======================================= -->

    <section
        class="about-content-section"
        id="aboutValues">

        <div class="about-section-heading">

            <div>

                <span class="about-eyebrow">
                    School Identity
                </span>

                <h2>
                    Our Core Values
                </h2>

                <p>
                    Values that shape the character, conduct,
                    and service of the OLSHCO community.
                </p>

            </div>

        </div>

        <div class="about-values-grid">

            <article class="about-value-card">

                <span class="about-value-icon">

                    <i class="fa-solid fa-heart"></i>

                </span>

                <h3>
                    Filial Compassion
                </h3>

                <p>
                    Nurturing a deep, loving, and compassionate
                    heart patterned after Jesus and Mary.
                </p>

            </article>

            <article class="about-value-card">

                <span class="about-value-icon">

                    <i class="fa-solid fa-people-group"></i>

                </span>

                <h3>
                    Leadership
                </h3>

                <p>
                    Fostering transformational leadership
                    and competence.
                </p>

            </article>

            <article class="about-value-card">

                <span class="about-value-icon">

                    <i class="fa-solid fa-scale-balanced"></i>

                </span>

                <h3>
                    Accountability
                </h3>

                <p>
                    Promoting responsibility in all actions
                    and stewardship.
                </p>

            </article>

            <article class="about-value-card">

                <span class="about-value-icon">

                    <i class="fa-solid fa-hands-praying"></i>

                </span>

                <h3>
                    Mary-Inspired Obedience
                </h3>

                <p>
                    Rooted in the Catholic faith,
                    embodying obedience and humility.
                </p>

            </article>

            <article class="about-value-card">

                <span class="about-value-icon">

                    <i class="fa-solid fa-seedling"></i>

                </span>

                <h3>
                    Enduring Discipleship
                </h3>

                <p>
                    Committing to lifelong learning,
                    faith, service, and formation.
                </p>

            </article>

        </div>

    </section>

    <!-- ======================================
         SCHOOL PROFILE
    ======================================= -->

    <section
        class="about-profile-card"
        id="aboutProfile">

        <div class="about-profile-heading">

            <span class="about-profile-icon">

                <i class="fa-solid fa-school-flag"></i>

            </span>

            <div>

                <span class="about-eyebrow">
                    School Profile
                </span>

                <h2>
                    Our Lady of the Sacred Heart
                    College of Guimba, Inc.
                </h2>

            </div>

        </div>

        <div class="about-profile-grid">

            <div class="about-profile-item">

                <span>
                    Institution Type
                </span>

                <strong>
                    Private Catholic Educational Institution
                </strong>

            </div>

            <div class="about-profile-item">

                <span>
                    Location
                </span>

                <strong>
                    Afan Salvador Street,
                    Guimba, Nueva Ecija
                </strong>

            </div>

            <div class="about-profile-item">

                <span>
                    Date Founded
                </span>

                <strong>
                    June 26, 1947
                </strong>

            </div>

            <div class="about-profile-item">

                <span>
                    Educational Community
                </span>

                <strong>
                    Integrated Basic Education
                    and College Departments
                </strong>

            </div>

        </div>

    </section>

</section>