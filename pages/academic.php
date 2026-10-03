<?php

$isLoggedIn =
    !empty($_SESSION['user_id']);

$pageWrapperClass =
    $isLoggedIn
    ? 'app-page academic-app-page'
    : 'public-academic-page';

$academicLevels = [
    [
        'name' => 'Elementary',
        'subtitle' => 'Grade 1 to Grade 6',
        'image' => 'Assets/Images/elementary.jpg',
        'icon' => 'fa-solid fa-child-reaching',
        'description' =>
        'Builds strong foundations in reading, writing, mathematics, values formation, and holistic development.',
        'offerings' => [],
        'classification' => 'Basic Education'
    ],
    [
        'name' => 'Junior High School',
        'subtitle' => 'Grade 7 to Grade 10',
        'image' => 'Assets/Images/juniorhigh.jpg',
        'icon' => 'fa-solid fa-book-open-reader',
        'description' =>
        'Enhances academic skills, critical thinking, discipline, leadership, and preparation for Senior High School.',
        'offerings' => [],
        'classification' => 'Basic Education'
    ],
    [
        'name' => 'Senior High School',
        'subtitle' => 'Grade 11 to Grade 12',
        'image' => 'Assets/Images/seniorhigh.jpg',
        'icon' => 'fa-solid fa-flask-vial',
        'description' =>
        'Provides specialized strands that prepare students for college, employment, entrepreneurship, and technical careers.',
        'offerings' => [],
        'classification' => 'Senior High Strands'
    ],
    [
        'name' => 'College',
        'subtitle' => 'Higher Education Programs',
        'image' => 'Assets/Images/college.jpg',
        'icon' => 'fa-solid fa-graduation-cap',
        'description' =>
        'Offers professional degree programs designed to prepare students for employment, leadership, service, and lifelong learning.',
        'offerings' => [],
        'classification' => 'College Programs'
    ]
];





$learningStrengths = [
    [
        'icon' => 'fa-solid fa-award',
        'title' => 'Tradition of Excellence',
        'description' =>
        'The institution is committed to delivering quality education that meets national and global standards.'
    ],
    [
        'icon' => 'fa-solid fa-laptop-file',
        'title' => 'Modern Facilities and Resources',
        'description' =>
        'Students gain access to learning tools, technology, and facilities that support academic development and well-being.'
    ],
    [
        'icon' => 'fa-solid fa-heart',
        'title' => 'Holistic Formation',
        'description' =>
        'The school nurtures the mind, heart, character, faith, and social responsibility of every learner.'
    ],
    [
        'icon' => 'fa-solid fa-chalkboard-user',
        'title' => 'Dedicated Educators',
        'description' =>
        'Faculty members guide students through instruction, mentorship, encouragement, and personalized support.'
    ],
    [
        'icon' => 'fa-solid fa-rocket',
        'title' => 'Future-Ready Learning',
        'description' =>
        'Students are prepared for real-world challenges through practical skills, leadership, and a strong sense of purpose.'
    ]
];

/* ==========================================
   ACTIVE PUBLIC ACADEMIC CATALOG
========================================== */

$academicCatalog =
    isset(
        $viewData['academic_catalog']
    ) &&
    is_array(
        $viewData['academic_catalog']
    )
    ? $viewData['academic_catalog']
    : [];

$academicCatalogError =
    !empty($viewData['academic_catalog_error']);

$activeEducationLevels =
    is_array(
        $academicCatalog['education_levels']
            ?? null
    )
    ? $academicCatalog['education_levels']
    : [];

$activeAcademicPrograms =
    is_array(
        $academicCatalog['academic_programs']
            ?? null
    )
    ? $academicCatalog['academic_programs']
    : [];

$activeGradeLevels =
    is_array(
        $academicCatalog['grade_levels']
            ?? null
    )
    ? $academicCatalog['grade_levels']
    : [];

/*
 * Match the editorial presentation cards to
 * their database-managed education levels.
 */
$catalogNameMap = [
    'Elementary' =>
    'elementary',

    'Junior High School' =>
    'junior high',

    'Senior High School' =>
    'senior high',

    'College' =>
    'college'
];

$educationLevelIdByName =
    [];

foreach (
    $activeEducationLevels
    as $educationLevel
) {
    $catalogName =
        strtolower(
            trim(
                (string) (
                    $educationLevel['education_level_name']
                    ?? ''
                )
            )
        );

    $educationLevelId =
        (int) (
            $educationLevel['education_level_id']
            ?? 0
        );

    if (
        $catalogName !== '' &&
        $educationLevelId > 0
    ) {
        $educationLevelIdByName[$catalogName] =
            $educationLevelId;
    }
}

/*
 * Keep only active education-level cards and
 * replace their hardcoded offering lists with
 * active grade levels or programs.
 */
$activeAcademicLevels =
    [];

foreach (
    $academicLevels
    as $academicLevel
) {
    $presentationName =
        (string) (
            $academicLevel['name']
            ?? ''
        );

    $catalogName =
        $catalogNameMap[$presentationName]
        ?? '';

    $educationLevelId =
        (int) (
            $educationLevelIdByName[$catalogName]
            ?? 0
        );

    if ($educationLevelId <= 0) {
        continue;
    }

    $gradeOfferings =
        [];

    foreach (
        $activeGradeLevels
        as $gradeLevel
    ) {
        if (
            (int) (
                $gradeLevel['education_level_id']
                ?? 0
            ) !== $educationLevelId
        ) {
            continue;
        }

        $gradeName =
            trim(
                (string) (
                    $gradeLevel['grade_level_name']
                    ?? ''
                )
            );

        if ($gradeName !== '') {
            $gradeOfferings[] =
                $gradeName;
        }
    }

    $programOfferings =
        [];

    foreach (
        $activeAcademicPrograms
        as $academicProgram
    ) {
        if (
            (int) (
                $academicProgram['education_level_id']
                ?? 0
            ) !== $educationLevelId
        ) {
            continue;
        }

        $programCode =
            trim(
                (string) (
                    $academicProgram['program_code']
                    ?? ''
                )
            );

        if ($programCode !== '') {
            $programOfferings[] =
                $programCode;
        }
    }

    $academicLevel['offerings'] =
        !empty($programOfferings)
        ? $programOfferings
        : $gradeOfferings;

    $activeAcademicLevels[] =
        $academicLevel;
}

$academicLevels =
    $activeAcademicLevels;

/*
 * Build the dedicated Senior High and College
 * program grids from the same active catalog.
 */
$seniorHighLevelId =
    (int) (
        $educationLevelIdByName['senior high']
        ?? 0
    );

$collegeLevelId =
    (int) (
        $educationLevelIdByName['college']
        ?? 0
    );

$seniorHighStrands =
    [];

$collegePrograms =
    [];

foreach (
    $activeAcademicPrograms
    as $academicProgram
) {
    $educationLevelId =
        (int) (
            $academicProgram['education_level_id']
            ?? 0
        );

    $program = [
        'code' =>
        trim(
            (string) (
                $academicProgram['program_code']
                ?? ''
            )
        ),

        'name' =>
        trim(
            (string) (
                $academicProgram['program_name']
                ?? ''
            )
        )
    ];

    if (
        $program['code'] === '' ||
        $program['name'] === ''
    ) {
        continue;
    }

    if (
        $educationLevelId ===
        $seniorHighLevelId
    ) {
        $seniorHighStrands[] =
            $program;
    }

    if (
        $educationLevelId ===
        $collegeLevelId
    ) {
        $collegePrograms[] =
            $program;
    }
}

$hasPublicAcademicCatalog =
    !empty($academicLevels);

?>

<section class="<?= $pageWrapperClass ?>">

    <header class="academic-page-header">

        <div class="academic-header-copy">

            <span class="academic-eyebrow">
                Academic Offerings
            </span>

            <h1>
                Learning pathways for every stage
            </h1>

            <p>
                Our Lady of the Sacred Heart College of Guimba, Inc.
                provides educational opportunities from Elementary
                to College, guided by academic excellence,
                faith, values formation, and service.
            </p>

            <div class="academic-header-actions">

                <a
                    href="index.php?page=contact"
                    class="app-button primary">
                    <i class="fa-solid fa-envelope"></i>

                    Ask About Admissions
                </a>

                <a
                    href="index.php?page=about"
                    class="app-button secondary">
                    <i class="fa-solid fa-school"></i>

                    About OLSHCO
                </a>

            </div>

        </div>

        <div class="academic-overview-card">

            <span class="academic-overview-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </span>

            <div>

                <span>
                    Educational Coverage
                </span>

                <strong>
                    Elementary to College
                </strong>

                <p>
                    Academic information is organized according
                    to School Division, Program or Strand,
                    Grade or Year Level, and Section.
                </p>

            </div>

            <div class="academic-overview-tags">

                <span>Elementary</span>
                <span>Junior High</span>
                <span>Senior High</span>
                <span>College</span>

            </div>

        </div>

    </header>

    <?php if (
        $academicCatalogError ||
        !$hasPublicAcademicCatalog
    ): ?>

        <div
            class="academic-catalog-status"
            role="status">

            <span>

                <i
                    class="fa-solid fa-circle-info"
                    aria-hidden="true"></i>

            </span>

            <div>

                <strong>
                    Academic offerings are temporarily unavailable
                </strong>

                <p>
                    Please contact the school for the latest
                    program and enrollment information.
                </p>

            </div>

            <a href="index.php?page=contact">
                Contact the School
            </a>

        </div>

    <?php endif; ?>

    <?php if ($hasPublicAcademicCatalog): ?>

        <?php if (!empty($seniorHighStrands)): ?>

            <section class="academic-content-section">

                <div class="academic-section-heading">

                    <div>

                        <span class="academic-eyebrow">
                            School Divisions
                        </span>

                        <h2>
                            Explore our academic levels
                        </h2>

                        <p>
                            Each division supports a distinct stage
                            of student development and preparation.
                        </p>

                    </div>

                </div>

                <div class="academic-level-grid">

                    <?php foreach ($academicLevels as $level): ?>

                        <article class="academic-level-card">

                            <div class="academic-level-image">

                                <img
                                    src="<?= htmlspecialchars(
                                                $level['image'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    alt="<?= htmlspecialchars(
                                                $level['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                <span class="academic-level-badge">
                                    <?= htmlspecialchars(
                                        $level['classification'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>

                            <div class="academic-level-content">

                                <div class="academic-level-title">

                                    <span>
                                        <i class="<?= htmlspecialchars(
                                                        $level['icon'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"></i>
                                    </span>

                                    <div>

                                        <h3>
                                            <?= htmlspecialchars(
                                                $level['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </h3>

                                        <small>
                                            <?= htmlspecialchars(
                                                $level['subtitle'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                                <p>
                                    <?= htmlspecialchars(
                                        $level['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                                <?php if (!empty($level['offerings'])): ?>

                                    <div class="academic-offering-list">

                                    <?php endif; ?>

                                    <?php foreach (
                                        $level['offerings']
                                        as $offering
                                    ): ?>

                                        <span>

                                            <i class="fa-solid fa-circle-check"></i>

                                            <?= htmlspecialchars(
                                                $offering,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    <?php endforeach; ?>

                                    </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>

        <?php if (!empty($seniorHighStrands)): ?>

            <section class="academic-content-section">

                <div class="academic-section-heading">

                    <div>

                        <span class="academic-eyebrow">
                            Senior High School
                        </span>

                        <h2>
                            Available strands
                        </h2>

                        <p>
                            Senior High School students may select
                            an academic or technical pathway based
                            on their interests and future goals.
                        </p>

                    </div>

                </div>

                <div class="academic-specialization-grid">

                    <?php foreach ($seniorHighStrands as $strand): ?>

                        <article class="academic-specialization-card">

                            <span class="academic-specialization-code">
                                <?= htmlspecialchars(
                                    $strand['code'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $strand['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>

        <?php if (!empty($collegePrograms)): ?>

            <section class="academic-content-section">

                <div class="academic-section-heading">

                    <div>

                        <span class="academic-eyebrow">
                            College Department
                        </span>

                        <h2>
                            Available degree programs
                        </h2>

                        <p>
                            College offerings support professional preparation,
                            technical competence, leadership, and service.
                        </p>

                    </div>

                </div>

                <div class="academic-specialization-grid college-program-grid">

                    <?php foreach ($collegePrograms as $program): ?>

                        <article class="academic-specialization-card">

                            <span class="academic-specialization-code">
                                <?= htmlspecialchars(
                                    $program['code'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $program['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>
        <?php endif; ?>

    <?php endif; ?>

    <section class="academic-content-section">

        <div class="academic-section-heading">

            <div>

                <span class="academic-eyebrow">
                    Digital Hub Classification
                </span>

                <h2>
                    How academic information is organized
                </h2>

                <p>
                    This hierarchy supports precise announcements,
                    notifications, analytics, and role-based dissemination.
                </p>

            </div>

        </div>

        <div
            class="academic-flow-grid"
            role="list"
            aria-label="Academic information hierarchy">

            <article
                class="academic-flow-step"
                role="listitem">

                <span>01</span>

                <i
                    class="fa-solid fa-building-columns"
                    aria-hidden="true"></i>

                <h3>
                    Department
                </h3>

                <p>
                    Identifies the primary academic unit,
                    such as IBED or College.
                </p>

            </article>

            <i
                class="academic-flow-arrow fa-solid fa-arrow-right"
                aria-hidden="true"></i>

            <article
                class="academic-flow-step"
                role="listitem">

                <span>02</span>

                <i
                    class="fa-solid fa-school"
                    aria-hidden="true"></i>

                <h3>
                    Education Level
                </h3>

                <p>
                    Elementary, Junior High,
                    Senior High, or College.
                </p>

            </article>

            <i
                class="academic-flow-arrow fa-solid fa-arrow-right"
                aria-hidden="true"></i>

            <article
                class="academic-flow-step"
                role="listitem">

                <span>03</span>

                <i
                    class="fa-solid fa-diagram-project"
                    aria-hidden="true"></i>

                <h3>
                    Program or Strand
                </h3>

                <p>
                    Specifies a Senior High strand
                    or College degree program when applicable.
                </p>

            </article>

            <i
                class="academic-flow-arrow fa-solid fa-arrow-right"
                aria-hidden="true"></i>

            <article
                class="academic-flow-step"
                role="listitem">

                <span>04</span>

                <i
                    class="fa-solid fa-layer-group"
                    aria-hidden="true"></i>

                <h3>
                    Grade or Year Level
                </h3>

                <p>
                    Identifies the learner’s
                    current academic stage.
                </p>

            </article>

            <i
                class="academic-flow-arrow fa-solid fa-arrow-right"
                aria-hidden="true"></i>

            <article
                class="academic-flow-step"
                role="listitem">

                <span>05</span>

                <i
                    class="fa-solid fa-users"
                    aria-hidden="true"></i>

                <h3>
                    Section
                </h3>

                <p>
                    Enables section-specific
                    information delivery.
                </p>

            </article>

        </div>

    </section>

    <section class="academic-content-section">

        <div class="academic-section-heading">

            <div>

                <span class="academic-eyebrow">
                    Learning Experience
                </span>

                <h2>
                    Why learn with us?
                </h2>

                <p>
                    OLSHCO supports academic achievement,
                    character formation, faith, and future readiness.
                </p>

            </div>

        </div>

        <div class="academic-strength-grid">

            <?php foreach ($learningStrengths as $strength): ?>

                <article class="academic-strength-card">

                    <span>

                        <i class="<?= htmlspecialchars(
                                        $strength['icon'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                            aria-hidden="true"></i>

                    </span>

                    <h3>
                        <?= htmlspecialchars(
                            $strength['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $strength['description'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                </article>

            <?php endforeach; ?>

        </div>

    </section>

    <section class="academic-cta">

        <div>

            <span>
                Learn More
            </span>

            <h2>
                Find the right academic pathway.
            </h2>

            <p>
                Contact the school for admission requirements,
                enrollment schedules, and program information.
            </p>

        </div>

        <a
            href="index.php?page=contact"
            class="academic-cta-button">
            Contact the School

            <i
                class="fa-solid fa-arrow-right"
                aria-hidden="true"></i>
        </a>

    </section>

</section>