<?php

require_once __DIR__ . '/config/request-errors.php';
installRequestErrorHandling();

require_once __DIR__ . '/config/request-input.php';
validateRequestInput($_GET, $_POST);

require_once __DIR__
  . '/config/security.php';

startSecureSession();
maintainAuthenticatedSession();

require_once __DIR__ . '/config/dbconnect.php';
require_once __DIR__ . '/config/authenticated-session.php';
enforceCurrentAccountSession($conn);

/* ==========================================
   CONTROLLERS
========================================== */

require_once __DIR__ . '/app/controllers/EventController.php';
require_once __DIR__ . '/app/controllers/DocumentDownloadController.php';
require_once __DIR__ . '/app/controllers/PostController.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__
  . '/app/controllers/PasswordRecoveryController.php';
require_once __DIR__
  . '/app/controllers/AccountApprovalController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__
  . '/app/controllers/DepartmentAnalyticsController.php';

require_once __DIR__
  . '/app/controllers/ContentEngagementController.php';
require_once __DIR__
  . '/app/controllers/ContentWorkspaceController.php';
require_once __DIR__
  . '/app/controllers/SurveyController.php';
require_once __DIR__
  . '/app/controllers/NotificationController.php';
require_once __DIR__
  . '/app/controllers/BrowserPushController.php';
require_once __DIR__
  . '/app/controllers/UserManagementController.php';
require_once __DIR__
  . '/app/controllers/AcademicManagementController.php';
require_once __DIR__
  . '/app/controllers/StudentProfileManagementController.php';
require_once __DIR__
  . '/app/controllers/GovernmentAdvisoryController.php';
require_once __DIR__
  . '/app/controllers/StudentProfileController.php';
require_once __DIR__
  . '/app/controllers/AccountProfileController.php';
require_once __DIR__
  . '/app/controllers/AcademicController.php';


/* ==========================================
   SERVICES
========================================== */

require_once __DIR__
  . '/app/services/ContentReleaseService.php';

require_once __DIR__
  . '/app/services/NotificationService.php';

/* ==========================================
   ROUTE CONFIGURATION
========================================== */

$page = $_GET['page'] ?? 'home';

$role = $_SESSION['role'] ?? 'Guest';

$isLoggedIn = !empty($_SESSION['user_id']);

$viewData = [];

$title = 'OLSHCO Digital Hub';

$pageCSS = null;
$pageJS = null;

/* ==========================================
   ROUTE GROUPS
========================================== */

$publicPages = [
  'home',
  'about',
  'contact',
  'academic',
  'login',
  'register',
  'forgot_password',
  'password_reset'
];

$authenticatedPages = [
  'news',
  'calendar',
  'survey_participate',
  'notifications',
  'student_profile',
  'student_profile_survey',
  'account_profile',
  'legal_reconsent',
  'required_password_change'
];

$staffPages = [
  'postings',
  'content_workspace',
  'survey_results',
  'department_analytics',
  'department_content_preview'
];

$adminPages = [
  'dashboard',
  'account_approvals',
  'manage_users',
  'academic_management',
  'student_profile_management',
  'government_advisories'
];

$actionRoutes = [

  'login_action',
  'register_action',
  'password_reset_request',
  'password_reset_action',
  'logout',
  'required_password_change_action',
  'legal_reconsent_action',
  'session_keep_alive',

  'parent_child_update',
  'account_approval_approve',
  'account_approval_reject',
  'manage_user_change_role',
  'manage_user_provision_faculty',
  'manage_user_update_faculty_assignment',

  'manage_user_change_status',
  'manage_user_unlock',

  'academic_create',
  'academic_update',
  'academic_change_status',

  'student_profile_questionnaire_draft_create',

  'student_profile_question_create',
  'student_profile_question_update',
  'student_profile_question_change_status',
  'student_profile_cycle_create',
  'student_profile_cycle_activate',
  'student_profile_cycle_close',

  'government_advisory_prepare',
  'government_advisory_preview',
  'government_advisory_submit',
  'government_advisory_review',

  'student_profile_save',
  'student_profile_survey_save',

  'account_profile_update_details',
  'account_profile_photo_upload',
  'account_profile_photo_remove',

  'dashboard_export',
  'department_analytics_export',



  'document_download',
  'content_open',
  'content_react',
  'content_comment',
  'content_acknowledge',

  'notification_open',
  'notification_mark_all_read',
  'notification_update_preferences',
  'notification_update_category_preferences',
  'notification_update_email_preference',

  'browser_push_configuration',
  'browser_push_subscribe',
  'browser_push_unsubscribe',

  'content_redundancy_check',

  'post_store',


  'survey_store',
  'survey_submit_response',

  /*
     * Workspace
     */

  'workspace_submit_review',
  'workspace_restore_draft',

  'workspace_approve',
  'workspace_reject',
  'workspace_archive'
];

$allowedPages = array_merge(
  $publicPages,
  $authenticatedPages,
  $staffPages,
  $adminPages
);

/* ==========================================
   ROUTE HELPERS
========================================== */

function redirectToPage(
  string $targetPage,
  string $message = '',
  string $messageType = 'error'
): never {
  $url = 'index.php?page=' . urlencode($targetPage);

  if ($message !== '') {
    $url .= '&'
      . urlencode($messageType)
      . '='
      . urlencode($message);
  }

  header('Location: ' . $url);
  exit;
}

function stopForRouteGuard(
  string $targetPage,
  string $code,
  string $message,
  int $status = 403,
  bool $includeRedirectMessage = false
): never {
  header('Cache-Control: no-store');
  if (requestErrorExpectsJson()) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'success' => false,
      'code' => $code,
      'message' => $message,
      'redirect_url' => 'index.php?page=' . rawurlencode($targetPage)
    ], JSON_UNESCAPED_SLASHES);
    exit;
  }

  redirectToPage($targetPage, $includeRedirectMessage ? $message : '');
}

function requirePageLogin(
  bool $isLoggedIn
): void {
  if ($isLoggedIn) {
    return;
  }

  $sessionExpired =
    !empty($_SESSION['session_expired']);

  unset(
    $_SESSION['session_expired']
  );

  stopForRouteGuard(
    'login',
    'authentication_required',
    $sessionExpired
      ? 'Your session expired due to inactivity. Please sign in again.'
      : 'Please log in to continue.',
    401,
    true
  );
}

function requirePageRoles(
  bool $isLoggedIn,
  string $currentRole,
  array $allowedRoles
): void {
  requirePageLogin($isLoggedIn);

  if (!in_array($currentRole, $allowedRoles, true)) {
    stopForRouteGuard(
      'news',
      'access_denied',
      'You are not authorized to access that page.',
      403,
      true
    );
  }
}

/* ==========================================
   INVALID ROUTE SAFETY
========================================== */

$isKnownPage = in_array(
  $page,
  $allowedPages,
  true
);

$isKnownAction = in_array(
  $page,
  $actionRoutes,
  true
);

if (!$isKnownPage && !$isKnownAction) {
  throw new RequestNotFoundException('Page not found. Please check the link or return to Home.');
}

// Home and legacy Hub links share one authenticated feed request.
if ($isLoggedIn && $page === 'home') {
  $page = 'news';
}

/* ==========================================
   REQUIRED PASSWORD CHANGE GUARD
========================================== */

$mustChangePassword =
  $isLoggedIn &&
  !empty($_SESSION['must_change_password']);

$allowedDuringRequiredPasswordChange = [
  'required_password_change',
  'required_password_change_action',
  'logout'
];

if (
  $mustChangePassword &&
  !in_array(
    $page,
    $allowedDuringRequiredPasswordChange,
    true
  )
) {
  stopForRouteGuard(
    'required_password_change',
    'password_change_required',
    'Please change your password before continuing.'
  );
}

/* ==========================================
   REQUIRED LEGAL RE-CONSENT GUARD
========================================== */

$requiresLegalReconsent =
  $isLoggedIn &&
  !$mustChangePassword &&
  !empty($_SESSION['legal_reconsent_required']);

$allowedDuringLegalReconsent = [
  'legal_reconsent',
  'legal_reconsent_action',
  'logout'
];

if (
  $requiresLegalReconsent &&
  !in_array(
    $page,
    $allowedDuringLegalReconsent,
    true
  )
) {
  stopForRouteGuard(
    'legal_reconsent',
    'legal_consent_required',
    'Please review and accept the current Terms and Privacy Notice before continuing.'
  );
}

/* Faculty complete their own personal details after password and legal checks. */
$requiresFacultyProfile = $isLoggedIn && !$mustChangePassword
  && !$requiresLegalReconsent && $role === 'Faculty'
  && !empty($_SESSION['faculty_profile_required']);
if ($requiresFacultyProfile && !in_array($page, [
  'account_profile', 'account_profile_update_details',
  'account_profile_photo_upload', 'account_profile_photo_remove',
  'logout', 'session_keep_alive'
], true)) {
  stopForRouteGuard('account_profile', 'faculty_profile_required',
    'Please complete your personal profile before continuing.');
}

/* The existing next section continues here. */


/* ==========================================
   REQUIRED STUDENT SURVEY GUARD
========================================== */

$requiresStudentSurvey =
  false;

if (
  $isLoggedIn &&
  !$mustChangePassword &&
  !$requiresLegalReconsent &&
  $role === 'Student'
) {
  try {
    /*
     * Check the active cycle on every Student
     * request so newly assigned profile updates
     * take effect without requiring a new login.
     */
    $_SESSION['student_survey_required'] =
      (new StudentProfileService())
      ->requiresSurveyCompletion(
        (int) (
          $_SESSION['user_id']
          ?? 0
        )
      );
  } catch (Throwable $exception) {
    /*
     * Survey-status lookup failure must not
     * block the authenticated application.
     */
    $_SESSION['student_survey_required'] =
      false;

    error_log(
      'Student survey requirement lookup failed: '
        . $exception->getMessage()
    );
  }

  $requiresStudentSurvey =
    !empty($_SESSION['student_survey_required']);
}

$allowedDuringStudentSurvey = [
  'student_profile',
  'student_profile_survey',
  'student_profile_save',
  'student_profile_survey_save',
  'notifications',
  'notification_open',
  'notification_mark_all_read',
  'logout'
];

if (
  $requiresStudentSurvey &&
  !in_array(
    $page,
    $allowedDuringStudentSurvey,
    true
  )
) {
  stopForRouteGuard(
    'student_profile_survey',
    'student_survey_required',
    'Please complete your required Student profile survey before continuing.'
  );
}




/* ==========================================
   PAGE-LEVEL AUTHORIZATION
========================================== */

if (in_array($page, $authenticatedPages, true)) {
  requirePageLogin($isLoggedIn);
}

if (in_array($page, $staffPages, true)) {
  requirePageRoles(
    $isLoggedIn,
    $role,
    ['Admin', 'Faculty']
  );
}

if (in_array($page, $adminPages, true)) {
  requirePageRoles(
    $isLoggedIn,
    $role,
    ['Admin']
  );
}

/* ==========================================
   PROCESS DUE CONTENT RELEASES
========================================== */

try {
  if ($page !== 'document_download') {
    $releaseResult =
      (new ContentReleaseService($conn))
      ->processPendingReleasesIfDue();
  }
} catch (Throwable $exception) {
  /*
   * Do not crash the entire website if
   * automatic release processing fails.
   *
   * The error is written to the PHP log
   * for debugging instead.
   */
  error_log(
    'Content release processing failed: '
      . $exception->getMessage()
  );
}
/* ==========================================
   PROCESS UPCOMING EVENT REMINDERS
========================================== */




/* ==========================================
   ACTION AND PAGE ROUTES
========================================== */

switch ($page) {

  /* ------------------------------------------
       AUTHENTICATION ACTIONS
    ------------------------------------------ */

  case 'login_action':
    (new AuthController())->login();
    exit;

  case 'register_action':
    (new AuthController())->register();
    exit;

  case 'password_reset_request':
    (new PasswordRecoveryController())
      ->request();
    exit;

  case 'password_reset_action':
    (new PasswordRecoveryController())
      ->reset();
    exit;

  case 'logout':
    requirePageLogin($isLoggedIn);

    (new AuthController())->logout();
    exit;

  case 'session_keep_alive':
    (new AuthController())
      ->keepAlive();
    exit;

  case 'required_password_change_action':
    requirePageLogin(
      $isLoggedIn
    );

    (new AuthController())
      ->requiredPasswordChange();

    break;

  case 'legal_reconsent_action':
    requirePageLogin(
      $isLoggedIn
    );

    (new AuthController())
      ->acceptLegalReconsent();

    exit;

  case 'academic_create':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new AcademicManagementController())
      ->create();

    exit;

  case 'academic_update':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new AcademicManagementController())
      ->update();

    exit;

  case 'academic_change_status':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new AcademicManagementController())
      ->changeStatus();

    exit;

  case 'student_profile_questionnaire_draft_create':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->createDraftVersion();

    exit;

  case 'student_profile_question_create':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->createQuestion();

    exit;

  case 'student_profile_question_update':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->updateQuestion();

    exit;

  case 'student_profile_question_change_status':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->changeQuestionStatus();

    exit;

  case 'student_profile_cycle_create':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->createCycle();

    exit;

  case 'student_profile_cycle_activate':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->activateCycle();

    exit;

  case 'student_profile_cycle_close':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new StudentProfileManagementController())
      ->closeCycle();

    exit;

    /* ------------------------------------------
   GOVERNMENT ADVISORY ACTIONS
------------------------------------------ */

  case 'government_advisory_prepare':
    requirePageRoles($isLoggedIn, $role, ['Admin']);
    (new GovernmentAdvisoryController())->prepareAnnouncement();
    exit;

  case 'government_advisory_preview':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new GovernmentAdvisoryController())
      ->preview();

    exit;

  case 'government_advisory_submit':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new GovernmentAdvisoryController())
      ->submit();

    exit;

  case 'government_advisory_review':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new GovernmentAdvisoryController())
      ->review();

    exit;

    /* ------------------------------------------
   ACCOUNT APPROVAL ACTIONS
------------------------------------------ */

  case 'parent_child_update':
    (new AccountApprovalController())->updateChild();
    exit;

  case 'account_approval_approve':
    (new AccountApprovalController())
      ->approve();
    exit;

  case 'account_approval_reject':
    (new AccountApprovalController())
      ->reject();
    exit;

  case 'manage_users':
    $viewData =
      (new UserManagementController())
      ->index();

    $title = 'Manage Users';
    $pageCSS = 'manage-users.css';
    $pageJS = 'manage-users.js';
    break;


  case 'manage_user_change_status':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new UserManagementController())
      ->changeAccountStatus();

    break;

  case 'manage_user_unlock':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new UserManagementController())
      ->unlockAccount();

    break;

  case 'manage_user_change_role':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new UserManagementController())
      ->changeStaffRole();

    break;


  case 'manage_user_update_faculty_assignment':
    requirePageRoles($isLoggedIn, $role, ['Admin']);
    (new UserManagementController())->updateFacultyAssignment();
    break;

  case 'manage_user_provision_faculty':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    (new UserManagementController())
      ->provisionFaculty();

    break;


  /* ------------------------------------------
     STUDENT PROFILE ACTIONS
  ------------------------------------------ */

  case 'student_profile_save':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Student'
      ]
    );

    (new StudentProfileController())
      ->save();

    exit;

  case 'student_profile_survey_save':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Student'
      ]
    );

    (new StudentProfileController())
      ->saveSurvey();

    exit;

    /* ------------------------------------------
     ACCOUNT PROFILE ACTIONS
  ------------------------------------------ */

  case 'account_profile_update_details':
    requirePageLogin($isLoggedIn);
    (new AccountProfileController())->updateDetails();
    exit;

  case 'account_profile_photo_upload':
    (new AccountProfileController())
      ->uploadPhoto();

    exit;

  case 'account_profile_photo_remove':
    (new AccountProfileController())
      ->removePhoto();

    exit;


    /* ------------------------------------------
   UNIFIED CONTENT ENGAGEMENT
------------------------------------------ */

  case 'document_download':
    (new DocumentDownloadController())->download();
    exit;

  case 'content_open':
    (new ContentEngagementController())
      ->open();
    exit;

  case 'content_react':
    (new ContentEngagementController())
      ->react();
    exit;

  case 'content_comment':
    (new ContentEngagementController())
      ->comment();
    exit;

  case 'content_acknowledge':
    (new ContentEngagementController())
      ->acknowledge();
    exit;

    /* ------------------------------------------
       CONTENT ACTIONS
    ------------------------------------------ */

  case 'content_redundancy_check':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin',
        'Faculty'
      ]
    );

    (new PostController())
      ->checkRedundancy();

    exit;

  case 'post_store':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Admin', 'Faculty']
    );

    (new PostController())->store();
    exit;




    /* ------------------------------------------
     SURVEY ACTIONS
  ------------------------------------------ */

  case 'survey_store':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin',
        'Faculty'
      ]
    );

    (new SurveyController())
      ->store();

    exit;

  case 'survey_submit_response':
    requirePageLogin(
      $isLoggedIn
    );

    (new SurveyController())
      ->submitResponse();

    exit;

    /* ------------------------------------------
   CONTENT WORKSPACE ACTIONS
------------------------------------------ */

  case 'workspace_submit_review':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Faculty']
    );

    (new ContentWorkspaceController())
      ->submitForReview();

    exit;

  case 'workspace_restore_draft':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Admin', 'Faculty']
    );

    (new ContentWorkspaceController())
      ->restoreToDraft();

    exit;

  case 'workspace_approve':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Admin']
    );

    (new ContentWorkspaceController())
      ->approve();

    exit;

  case 'workspace_reject':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Admin']
    );

    (new ContentWorkspaceController())
      ->reject();

    exit;

  case 'workspace_archive':
    requirePageRoles(
      $isLoggedIn,
      $role,
      ['Admin']
    );

    (new ContentWorkspaceController())
      ->archive();

    exit;

    /* ------------------------------------------
   DASHBOARD REPORT EXPORT
------------------------------------------ */

  case 'dashboard_export':
    (new DashboardController())
      ->export();

    exit;

    /* ------------------------------------------
   FACULTY DEPARTMENT ANALYTICS EXPORT
------------------------------------------ */

  case 'department_analytics_export':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Faculty'
      ]
    );

    (new DepartmentAnalyticsController())
      ->export();

    exit;

    /* ------------------------------------------
   NOTIFICATION ACTIONS
------------------------------------------ */

  case 'notification_open':
    requirePageLogin(
      $isLoggedIn
    );

    (new NotificationController())
      ->open();

    exit;

  case 'notification_mark_all_read':
    requirePageLogin(
      $isLoggedIn
    );

    (new NotificationController())
      ->markAllAsRead();

    exit;

  case 'notification_update_preferences':
    (new NotificationController())
      ->updatePreferences();
    exit;

  case 'notification_update_category_preferences':
    (new NotificationController())
      ->updateCategoryPreferences();
    exit;

  case 'browser_push_configuration':
    (new BrowserPushController())
      ->configuration();
    exit;

  case 'browser_push_subscribe':
    (new BrowserPushController())
      ->subscribe();
    exit;

  case 'browser_push_unsubscribe':
    (new BrowserPushController())
      ->unsubscribe();
    exit;

  case 'notification_update_email_preference':
    (new NotificationController())
      ->updateEmailPreference();
    exit;


    /* ------------------------------------------
       AUTHENTICATED PAGES
    ------------------------------------------ */

  case 'account_approvals':
    $viewData =
      (new AccountApprovalController())
      ->index();

    $title =
      'Pending Account Approvals';

    $pageCSS =
      'account-approvals.css';

    $pageJS =
      'account-approvals.js';
    break;

  case 'dashboard':
    $viewData = (new DashboardController())->index();

    $title = 'Dashboard';
    $pageCSS = 'dashboard.css';
    $pageJS = 'dashboard.js';
    break;

  case 'department_analytics':
    $viewData =
      (new DepartmentAnalyticsController())
      ->index();

    $title =
      'Department Analytics';

    $pageCSS =
      'dashboard.css';

    $pageJS =
      'dashboard.js';

    break;

  case 'news':
    // Feed reads share this request connection; write routes keep their own setup.
    $viewData = (new PostController($conn))->news();

    $title = 'Home';
    $pageCSS = 'news.css';
    $pageJS = 'news.js';
    break;

  case 'notifications':
    $viewData =
      (new NotificationController())
      ->index();

    $title =
      'Notifications';

    $pageCSS =
      'notifications.css';

    $pageJS =
      'notifications.js';

    break;


  case 'department_content_preview':
    $viewData =
      (new DepartmentAnalyticsController())
      ->preview();

    $title =
      'Department Content Preview';

    $pageCSS =
      'dashboard.css';

    $pageJS =
      'dashboard.js';

    break;

  case 'calendar':
    $viewData = (new EventController())->calendar();

    $title = 'Event Calendar';
    $pageCSS = 'calendar.css';
    $pageJS = 'calendar.js';
    break;

  case 'postings':
    $viewData = (new PostController())->create();

    $title = 'Create Post';
    $pageCSS = 'posting.css';
    $pageJS = 'posting.js';
    break;

  case 'content_workspace':
    $viewData =
      (new ContentWorkspaceController())
      ->index();

    $title =
      'Content Workspace';

    $pageCSS =
      'content-workspace.css';

    $pageJS =
      'content-workspace.js';

    break;



  case 'survey_participate':
    requirePageLogin(
      $isLoggedIn
    );

    $viewData =
      (new SurveyController())
      ->participate();

    $title =
      'Survey';

    $pageCSS =
      'survey-participate.css';

    break;

  case 'survey_results':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin',
        'Faculty'
      ]
    );

    $viewData =
      (new SurveyController())
      ->results();

    $title =
      'Survey Results';

    $pageCSS =
      'survey-results.css';

    $pageJS =
      'survey-results.js';

    break;

  case 'account_profile':
    requirePageLogin(
      $isLoggedIn
    );

    $viewData =
      (new AccountProfileController())
      ->index();

    $title =
      'My Account';

    $pageCSS =
      'account-profile.css';

    $pageJS =
      'account-profile.js';

    break;

  case 'student_profile':
  case 'student_profile_survey':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Student'
      ]
    );

    $viewData =
      (new StudentProfileController())
      ->index();

    $title =
      $page === 'student_profile_survey' ? 'School Profile Survey' : 'My Interests';

    $pageCSS =
      'student-profile.css';

    $pageJS =
      'student-profile.js';

    break;

  case 'student_profile_management':
    requirePageRoles(
      $isLoggedIn,
      $role,
      [
        'Admin'
      ]
    );

    $viewData =
      (new StudentProfileManagementController())
      ->index();

    $title =
      'Student Profile Management';

    $pageCSS =
      'student-profile-management.css';

    $pageJS =
      'student-profile-management.js';

    break;

  case 'academic_management':
    $viewData =
      (new AcademicManagementController())
      ->index();

    $title =
      'Academic Structure';

    $pageCSS =
      'academic-management.css';

    $pageJS =
      'academic-management.js';
    break;

  case 'government_advisories':
    $viewData =
      (new GovernmentAdvisoryController())
      ->index();

    $title =
      'Government Advisory Intake';

    $pageCSS =
      'government-advisories.css';

    $pageJS =
      'government-advisories.js';

    break;

  /* ------------------------------------------
       AUTHENTICATION PAGES
    ------------------------------------------ */


  case 'required_password_change':
    requirePageLogin(
      $isLoggedIn
    );

    $title =
      'Secure Your Account';

    $pageCSS =
      'required-password-change.css';

    $pageJS =
      'required-password-change.js';

    break;


  case 'legal_reconsent':
    requirePageLogin(
      $isLoggedIn
    );

    $viewData =
      (new AuthController())
      ->legalReconsentPage();

    $title =
      'Review Terms and Privacy';

    $pageCSS =
      'legal-reconsent.css';

    $pageJS =
      '';

    break;

  case 'forgot_password':
    if ($isLoggedIn) {
      redirectToPage(
        'account_profile'
      );
    }

    $title =
      'Recover Your Account';

    $pageCSS =
      'password-recovery.css';

    $pageJS =
      'password-recovery.js';

    break;


  case 'password_reset':
    $viewData =
      (new PasswordRecoveryController())
      ->resetPage();

    $title =
      'Reset Your Password';

    $pageCSS =
      'password-recovery.css';

    $pageJS =
      'password-recovery.js';

    break;


  case 'login':
    if ($isLoggedIn) {
      redirectToPage(
        $role === 'Admin'
          ? 'dashboard'
          : 'news'
      );
    }

    $title = 'Sign In';
    $pageCSS = 'login.css';
    $pageJS = 'login.js';
    break;

  case 'register':
    if ($isLoggedIn) {
      redirectToPage(
        $role === 'Admin'
          ? 'dashboard'
          : 'news'
      );
    }

    $viewData = (new AuthController())
      ->registrationOptions();

    $title = 'Create Account';
    $pageCSS = 'register.css';
    $pageJS = 'register.js';
    break;

  /* ------------------------------------------
       PUBLIC PAGES
    ------------------------------------------ */

  case 'academic':
    $viewData =
      (new AcademicController())
      ->index();

    $title =
      'Academics';

    $pageCSS =
      'academic.css';

    $pageJS =
      'academic.js';

    break;

  case 'about':
    $title = 'About Us';
    $pageCSS = 'about.css';
    break;

  case 'contact':
    $title = 'Contact';
    $pageCSS = 'contact.css';
    break;

  case 'home':
  default:
    $page = 'home';

    $title =
      'OLSHCO Digital Hub';

    $pageCSS =
      'home.css';

    break;
}
/* ==========================================
   LAYOUT DETECTION
========================================== */

$isAuthPage = in_array(
  $page,
  [
    'login',
    'register',
    'forgot_password',
    'password_reset',
    'legal_reconsent',
    'required_password_change'
  ],
  true
);

$isApplication =
  $isLoggedIn &&
  !$isAuthPage;

$usesSidebarLayout =
  !$isAuthPage;


$globalUnreadNotificationCount =
  0;

if ($isApplication) {
  try {
    /*
     * Reuse the count already loaded by the
     * Notification Center to avoid a duplicate query.
     */
    if (
      $page === 'notifications' &&
      isset($viewData['unread_count'])
    ) {
      $globalUnreadNotificationCount =
        (int) $viewData['unread_count'];
    } else {
      $globalUnreadNotificationCount =
        (new NotificationService($conn))
        ->countUnread(
          (int) (
            $_SESSION['user_id']
            ?? 0
          )
        );
    }
  } catch (Throwable $exception) {
    error_log(
      'Global notification count error: '
        . $exception->getMessage()
    );

    /*
     * Notification failure must not prevent
     * the rest of the application from loading.
     */
    $globalUnreadNotificationCount =
      0;
  }
}


/* ==========================================
   GLOBAL PENDING REGISTRATION COUNT
========================================== */

$globalPendingRegistrationCount =
  0;

if (
  $isApplication &&
  $role === 'Admin'
) {
  try {
    /*
     * Reuse the queue already loaded on the
     * Account Approvals page.
     */
    if (
      $page === 'account_approvals' &&
      isset($viewData['registrations']) &&
      is_array($viewData['registrations'])
    ) {
      $globalPendingRegistrationCount =
        count(
          $viewData['registrations']
        );
    } else {
      $globalPendingRegistrationCount =
        (new AccountApprovalService($conn))
          ->countPendingRegistrations(100);
    }
  } catch (Throwable $exception) {
    error_log(
      'Global pending registration count error: '
        . $exception->getMessage()
    );

    $globalPendingRegistrationCount =
      0;
  }
}



/* ==========================================
   ASSET VERSIONING
========================================== */

function assetVersion(string $relativePath): string
{
  $absolutePath = __DIR__ . '/' . $relativePath;

  return file_exists($absolutePath)
    ? (string) filemtime($absolutePath)
    : (string) time();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

  <title>
    <?= htmlspecialchars(
      $title,
      ENT_QUOTES,
      'UTF-8'
    ) ?>
  </title>

  <link
    rel="stylesheet"
    href="Assets/css/index.css?v=<?=
                                  assetVersion('Assets/css/index.css')
                                  ?>">


  <link
    rel="stylesheet"
    href="Assets/css/root.css?v=<?= assetVersion('Assets/css/root.css') ?>">

  <link
    rel="stylesheet"
    href="Assets/css/app-dialog.css?v=<?=
                                      assetVersion(
                                        'Assets/css/app-dialog.css'
                                      )
                                      ?>">




  <?php if ($usesSidebarLayout): ?>

    <link
      rel="stylesheet"
      href="Assets/css/app.css?v=<?=
                                  assetVersion('Assets/css/app.css')
                                  ?>">



  <?php endif; ?>





  <?php if (!empty($pageCSS)): ?>

    <link
      rel="stylesheet"
      href="Assets/css/<?=
                        htmlspecialchars(
                          $pageCSS,
                          ENT_QUOTES,
                          'UTF-8'
                        )
                        ?>?v=<?=
                              assetVersion(
                                'Assets/css/' . $pageCSS
                              )
                              ?>">

  <?php endif; ?>

  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>

  <?php if ($isAuthPage): ?>

    <!-- MINIMALIST AUTHENTICATION SHELL -->

    <main class="auth-root">

      <?php
      include __DIR__
        . '/pages/'
        . $page
        . '.php';
      ?>

    </main>

  <?php else: ?>

    <!-- UNIFIED APPLICATION AND PUBLIC SHELL -->

    <div class="app-layout">

      <?php
      include __DIR__
        . '/include/sidebar.php';
      ?>

      <main class="app-main">

        <section class="page-content">

          <?php
          include __DIR__
            . '/pages/'
            . $page
            . '.php';
          ?>

        </section>

      </main>

    </div>

  <?php endif; ?>

  <?php if ($isApplication): ?>



    <div
      id="sessionTimeoutConfig"
      hidden
      data-timeout-seconds="1800"
      data-warning-seconds="300"
      data-csrf-token="<?= htmlspecialchars(
                          csrfToken(),
                          ENT_QUOTES,
                          'UTF-8'
                        ) ?>"
      data-keep-alive-url="index.php?page=session_keep_alive"
      data-login-url="index.php?page=login">
    </div>

    <script
      src="Assets/js/session-timeout.js?v=<?=
                                          assetVersion(
                                            'Assets/js/session-timeout.js'
                                          )
                                          ?>"></script>

  <?php endif; ?>


  <script
    src="Assets/js/app-dialog.js?v=<?=
                                    assetVersion(
                                      'Assets/js/app-dialog.js'
                                    )
                                    ?>"></script>

  <?php if ($usesSidebarLayout): ?>

    <script
      src="Assets/js/sidebar.js?v=<?=
                                  assetVersion(
                                    'Assets/js/sidebar.js'
                                  )
                                  ?>"></script>

  <?php endif; ?>

  <?php if (!empty($pageJS)): ?>

    <script
      src="Assets/js/<?=
                      htmlspecialchars(
                        $pageJS,
                        ENT_QUOTES,
                        'UTF-8'
                      )
                      ?>?v=<?=
                            assetVersion(
                              'Assets/js/' . $pageJS
                            )
                            ?>"></script>

  <?php endif; ?>

</body>

</html>
