> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# Complete route reference

Reviewed 2 October 2026 against [index.php](../../index.php). All 86 registered page/action names have switch handlers. Registration groups do not override narrower controller/service authorization.

[Evaluator guide](system-evaluation.md) | [User manual](user-manual.md) | System diagrams (local only: `system-diagrams.md`)

The page selector is `index.php?page=ROUTE`. Omitted page defaults to Home; signed-in Home navigation redirects to the Information Hub (`news`). Unknown selectors return HTTP 404 using the shared safe HTML/JSON renderer. Query/form input shape, encoding and numeric record identifiers are validated before session/database initialization; object authorization remains separate.

Important narrower rules: StudentProfileController is Student-only; Department Analytics is Faculty-only; survey results require Admin or owning Faculty. Faculty Personal Details updates are limited to the current Faculty account. Method, CSRF, target, ownership and workflow checks are enforced by controllers/services. Registered actions are not automatically public.

Browser views normally use GET; state-changing form/JSON actions validate POST and CSRF. Document downloads support GET/HEAD. Required password, legal, Faculty personal-details and Student-profile guards retain their allowed remediation actions. AJAX failures return JSON where applicable rather than an HTML redirect.

| Route | Registration group | Dispatched controller/method or view | Source line |
| --- | --- | --- | --- |
| `login_action` | Action; controller guards apply | [AuthController::login](../../app/controllers/AuthController.php) | 550 |
| `register_action` | Action; controller guards apply | [AuthController::register](../../app/controllers/AuthController.php) | 554 |
| `password_reset_request` | Action; controller guards apply | [PasswordRecoveryController::request](../../app/controllers/PasswordRecoveryController.php) | 558 |
| `password_reset_action` | Action; controller guards apply | [PasswordRecoveryController::reset](../../app/controllers/PasswordRecoveryController.php) | 563 |
| `logout` | Action; controller guards apply | [AuthController::logout](../../app/controllers/AuthController.php) | 568 |
| `session_keep_alive` | Action; controller guards apply | [AuthController::keepAlive](../../app/controllers/AuthController.php) | 574 |
| `required_password_change_action` | Action; controller guards apply | [AuthController::requiredPasswordChange](../../app/controllers/AuthController.php) | 579 |
| `legal_reconsent_action` | Action; controller guards apply | [AuthController::acceptLegalReconsent](../../app/controllers/AuthController.php) | 589 |
| `academic_create` | Action; controller guards apply | [AcademicManagementController::create](../../app/controllers/AcademicManagementController.php) | 599 |
| `academic_update` | Action; controller guards apply | [AcademicManagementController::update](../../app/controllers/AcademicManagementController.php) | 613 |
| `academic_change_status` | Action; controller guards apply | [AcademicManagementController::changeStatus](../../app/controllers/AcademicManagementController.php) | 627 |
| `student_profile_questionnaire_draft_create` | Action; controller guards apply | [StudentProfileManagementController::createDraftVersion](../../app/controllers/StudentProfileManagementController.php) | 641 |
| `student_profile_question_create` | Action; controller guards apply | [StudentProfileManagementController::createQuestion](../../app/controllers/StudentProfileManagementController.php) | 655 |
| `student_profile_question_update` | Action; controller guards apply | [StudentProfileManagementController::updateQuestion](../../app/controllers/StudentProfileManagementController.php) | 669 |
| `student_profile_question_change_status` | Action; controller guards apply | [StudentProfileManagementController::changeQuestionStatus](../../app/controllers/StudentProfileManagementController.php) | 683 |
| `student_profile_cycle_create` | Action; controller guards apply | [StudentProfileManagementController::createCycle](../../app/controllers/StudentProfileManagementController.php) | 697 |
| `student_profile_cycle_activate` | Action; controller guards apply | [StudentProfileManagementController::activateCycle](../../app/controllers/StudentProfileManagementController.php) | 711 |
| `student_profile_cycle_close` | Action; controller guards apply | [StudentProfileManagementController::closeCycle](../../app/controllers/StudentProfileManagementController.php) | 725 |
| `government_advisory_prepare` | Action; controller guards apply | [GovernmentAdvisoryController::prepareAnnouncement](../../app/controllers/GovernmentAdvisoryController.php) | 743 |
| `government_advisory_preview` | Action; controller guards apply | [GovernmentAdvisoryController::preview](../../app/controllers/GovernmentAdvisoryController.php) | 748 |
| `government_advisory_submit` | Action; controller guards apply | [GovernmentAdvisoryController::submit](../../app/controllers/GovernmentAdvisoryController.php) | 762 |
| `government_advisory_review` | Action; controller guards apply | [GovernmentAdvisoryController::review](../../app/controllers/GovernmentAdvisoryController.php) | 776 |
| `parent_child_update` | Action; controller guards apply | [AccountApprovalController::updateChild](../../app/controllers/AccountApprovalController.php) | 794 |
| `account_approval_approve` | Action; controller guards apply | [AccountApprovalController::approve](../../app/controllers/AccountApprovalController.php) | 798 |
| `account_approval_reject` | Action; controller guards apply | [AccountApprovalController::reject](../../app/controllers/AccountApprovalController.php) | 803 |
| `manage_users` | Administrator page | [UserManagementController::index](../../app/controllers/UserManagementController.php) | 808 |
| `manage_user_change_status` | Action; controller guards apply | [UserManagementController::changeAccountStatus](../../app/controllers/UserManagementController.php) | 819 |
| `manage_user_unlock` | Action; controller guards apply | [UserManagementController::unlockAccount](../../app/controllers/UserManagementController.php) | 833 |
| `manage_user_change_role` | Action; controller guards apply | [UserManagementController::changeStaffRole](../../app/controllers/UserManagementController.php) | 847 |
| `manage_user_update_faculty_assignment` | Action; controller guards apply | [UserManagementController::updateFacultyAssignment](../../app/controllers/UserManagementController.php) | 862 |
| `manage_user_provision_faculty` | Action; controller guards apply | [UserManagementController::provisionFaculty](../../app/controllers/UserManagementController.php) | 867 |
| `student_profile_save` | Action; controller guards apply | [StudentProfileController::save](../../app/controllers/StudentProfileController.php) | 886 |
| `student_profile_survey_save` | Action; controller guards apply | [StudentProfileController::saveSurvey](../../app/controllers/StudentProfileController.php) | 900 |
| `account_profile_update_details` | Action; controller guards apply | [AccountProfileController::updateDetails](../../app/controllers/AccountProfileController.php) | 918 |
| `account_profile_photo_upload` | Action; controller guards apply | [AccountProfileController::uploadPhoto](../../app/controllers/AccountProfileController.php) | 923 |
| `account_profile_photo_remove` | Action; controller guards apply | [AccountProfileController::removePhoto](../../app/controllers/AccountProfileController.php) | 929 |
| `document_download` | Action; controller guards apply | [DocumentDownloadController::download](../../app/controllers/DocumentDownloadController.php) | 940 |
| `content_open` | Action; controller guards apply | [ContentEngagementController::open](../../app/controllers/ContentEngagementController.php) | 944 |
| `content_react` | Action; controller guards apply | [ContentEngagementController::react](../../app/controllers/ContentEngagementController.php) | 949 |
| `content_comment` | Action; controller guards apply | [ContentEngagementController::comment](../../app/controllers/ContentEngagementController.php) | 954 |
| `content_acknowledge` | Action; controller guards apply | [ContentEngagementController::acknowledge](../../app/controllers/ContentEngagementController.php) | 959 |
| `content_redundancy_check` | Action; controller guards apply | [PostController::checkRedundancy](../../app/controllers/PostController.php) | 968 |
| `post_store` | Action; controller guards apply | [PostController::store](../../app/controllers/PostController.php) | 983 |
| `survey_store` | Action; controller guards apply | [SurveyController::store](../../app/controllers/SurveyController.php) | 1000 |
| `survey_submit_response` | Action; controller guards apply | [SurveyController::submitResponse](../../app/controllers/SurveyController.php) | 1015 |
| `workspace_submit_review` | Action; controller guards apply | [ContentWorkspaceController::submitForReview](../../app/controllers/ContentWorkspaceController.php) | 1029 |
| `workspace_restore_draft` | Action; controller guards apply | [ContentWorkspaceController::restoreToDraft](../../app/controllers/ContentWorkspaceController.php) | 1041 |
| `workspace_approve` | Action; controller guards apply | [ContentWorkspaceController::approve](../../app/controllers/ContentWorkspaceController.php) | 1053 |
| `workspace_reject` | Action; controller guards apply | [ContentWorkspaceController::reject](../../app/controllers/ContentWorkspaceController.php) | 1065 |
| `workspace_archive` | Action; controller guards apply | [ContentWorkspaceController::archive](../../app/controllers/ContentWorkspaceController.php) | 1077 |
| `dashboard_export` | Action; controller guards apply | [DashboardController::export](../../app/controllers/DashboardController.php) | 1093 |
| `department_analytics_export` | Action; controller guards apply | [DepartmentAnalyticsController::export](../../app/controllers/DepartmentAnalyticsController.php) | 1103 |
| `notification_open` | Action; controller guards apply | [NotificationController::open](../../app/controllers/NotificationController.php) | 1121 |
| `notification_mark_all_read` | Action; controller guards apply | [NotificationController::markAllAsRead](../../app/controllers/NotificationController.php) | 1131 |
| `notification_update_preferences` | Action; controller guards apply | [NotificationController::updatePreferences](../../app/controllers/NotificationController.php) | 1141 |
| `notification_update_category_preferences` | Action; controller guards apply | [NotificationController::updateCategoryPreferences](../../app/controllers/NotificationController.php) | 1146 |
| `browser_push_configuration` | Action; controller guards apply | [BrowserPushController::configuration](../../app/controllers/BrowserPushController.php) | 1151 |
| `browser_push_subscribe` | Action; controller guards apply | [BrowserPushController::subscribe](../../app/controllers/BrowserPushController.php) | 1156 |
| `browser_push_unsubscribe` | Action; controller guards apply | [BrowserPushController::unsubscribe](../../app/controllers/BrowserPushController.php) | 1161 |
| `notification_update_email_preference` | Action; controller guards apply | [NotificationController::updateEmailPreference](../../app/controllers/NotificationController.php) | 1166 |
| `account_approvals` | Administrator page | [AccountApprovalController::index](../../app/controllers/AccountApprovalController.php) | 1176 |
| `dashboard` | Administrator page | [DashboardController::index](../../app/controllers/DashboardController.php) | 1191 |
| `department_analytics` | Staff page | [DepartmentAnalyticsController::index](../../app/controllers/DepartmentAnalyticsController.php) | 1199 |
| `news` | Authenticated page | [PostController::news](../../app/controllers/PostController.php) | 1215 |
| `notifications` | Authenticated page | [NotificationController::index](../../app/controllers/NotificationController.php) | 1224 |
| `department_content_preview` | Staff page | [DepartmentAnalyticsController::preview](../../app/controllers/DepartmentAnalyticsController.php) | 1241 |
| `calendar` | Authenticated page | [EventController::calendar](../../app/controllers/EventController.php) | 1257 |
| `postings` | Staff page | [PostController::create](../../app/controllers/PostController.php) | 1265 |
| `content_workspace` | Staff page | [ContentWorkspaceController::index](../../app/controllers/ContentWorkspaceController.php) | 1273 |
| `survey_participate` | Authenticated page | [SurveyController::participate](../../app/controllers/SurveyController.php) | 1291 |
| `survey_results` | Staff page | [SurveyController::results](../../app/controllers/SurveyController.php) | 1308 |
| `account_profile` | Authenticated page | [AccountProfileController::index](../../app/controllers/AccountProfileController.php) | 1333 |
| `student_profile` | Authenticated page | [StudentProfileController::index](../../app/controllers/StudentProfileController.php) | 1353 |
| `student_profile_management` | Administrator page | [StudentProfileManagementController::index](../../app/controllers/StudentProfileManagementController.php) | 1377 |
| `academic_management` | Administrator page | [AcademicManagementController::index](../../app/controllers/AcademicManagementController.php) | 1401 |
| `government_advisories` | Administrator page | [GovernmentAdvisoryController::index](../../app/controllers/GovernmentAdvisoryController.php) | 1416 |
| `required_password_change` | Authenticated page | [View: required_password_change](../../pages/required_password_change.php) | 1437 |
| `legal_reconsent` | Authenticated page | [AuthController::legalReconsentPage](../../app/controllers/AuthController.php) | 1454 |
| `forgot_password` | Public page | [View: forgot_password](../../pages/forgot_password.php) | 1474 |
| `password_reset` | Public page | [PasswordRecoveryController::resetPage](../../app/controllers/PasswordRecoveryController.php) | 1493 |
| `login` | Public page | [View: login](../../pages/login.php) | 1510 |
| `register` | Public page | [AuthController::registrationOptions](../../app/controllers/AuthController.php) | 1524 |
| `academic` | Public page | [AcademicController::index](../../app/controllers/AcademicController.php) | 1545 |
| `about` | Public page | [View: about](../../pages/about.php) | 1561 |
| `contact` | Public page | [View: contact](../../pages/contact.php) | 1566 |
| `home` | Public page | [Public view: home](../../pages/home.php); signed-in requests redirect to news | 1571 |

Total: **86 routes: 27 page routes and 59 actions.** Source line numbers move when index.php changes. ID-bearing links still require a valid record and authorized actor; a record ID alone grants no access.
