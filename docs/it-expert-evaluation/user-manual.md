> Evaluation-pack snapshot prepared 6 October 2026. Read [current state](CURRENT-STATE.md) first; original verification dates and limitations below remain applicable. Relative source-code links require the project repository.

# OLSHCO Digital Hub - User Manual

Version 1.1 - 2 October 2026 - For Administrator, Faculty, Student and Parent users

This manual describes the current screens and workflows. Your account role, academic assignment, approval status and content recipients determine what you can see. Example records should be created only in a designated training/evaluation environment. Screenshots are not included; screen and button names below are taken from the current page source.

For technical setup and evaluation, use the separate [system evaluation guide](system-evaluation.md). For test scenarios, use the [evaluation checklist](evaluation-checklist.md).

## Contents

1. [Getting started](#getting-started)
2. [Tasks available to signed-in users](#tasks-available-to-signed-in-users)
3. [Student guide](#student-guide)
4. [Parent guide](#parent-guide)
5. [Faculty guide](#faculty-guide)
6. [Administrator guide](#administrator-guide)
7. [Troubleshooting](#troubleshooting)
8. [Glossary and safe use](#glossary-and-safe-use)

## Getting started

### Open the system

Use the website address supplied by the school. The developer's local address is `http://localhost/bahay-ko/index.php`; it works only on the relevant local machine. A public hosting address has not yet been finalized.

The public pages include Home, Academics, About School and Contact. Sign in to use the Information Hub and account-specific functions. A missing menu option may reflect your permissions; entering another page's address does not grant access.

### Register as a Student

1. Open **Register** and select **Student**.
2. Enter your Student ID, name, email, birthdate, gender and the other required fields. Use your own accessible email address.
3. Select the correct School Division, Education Level, Program or Strand where applicable, Grade or Year Level, and Section. Choose the hierarchy in order so its dependent options can update.
4. Create and confirm your password. Follow the displayed requirements; the current public registration rules require 8–72 bytes with uppercase, lowercase and a number.
5. Read and accept the Terms and Conditions and Privacy Notice, then submit the registration.
6. A successful submission means the application is awaiting Administrator approval. It does not immediately activate your account. Contact the school if you need to correct an academic assignment or approval detail.

If the email or Student ID is already registered, use sign-in/password recovery or ask the Administrator to check the account instead of submitting duplicate applications.

### Register as a Parent

1. Open **Register**, select **Parent**, and enter your own name, email and required personal fields.
2. If your child has an active Digital Hub Student account, enter the child's school Student ID and select your relationship.
3. If your child has no account, select **My child does not have a Digital Hub account yet**. Enter the child's full name, choose the current grade and section, select a reason, and provide the school Student ID if available. An additional explanation is optional; avoid sensitive details.
4. Create your password, accept the required legal documents and submit. Registration stays **Pending** until Admin verification.
5. Admin verifies enrollment and your relationship against school records. After approval, eligible content follows the verified child's class; your child does not need to register or log in.

**Availability:** migration 029 was applied locally on 26 September 2026, enabling the no-account option alongside the existing linked-account path. See [verification status](parent-registration-verification.md).

The reason explains why assistance is needed; it does not establish enrollment or grant access by itself. If your child's class is missing, contact the Administrator instead of selecting an unrelated class.

### Sign in and complete required steps

1. Open **Sign In** and enter your account ID or email and password. For Students, the account ID is their Student ID; staff without an assigned account ID and Parents should use email.
2. If you received a temporary Faculty password, sign in with it, then complete **Required Password Change** using a new private password and its confirmation.
3. If asked, review the current Terms and Privacy Notice and confirm acceptance.
4. Students may then be required to complete an assigned profile survey. Follow the Student instructions below.
5. Continue to the page available to your role. Administrators normally arrive at the Dashboard; other roles normally arrive at Home, the signed-in Information Hub. Faculty must first complete any required Personal Details step in My Account.

Password change takes precedence over legal acceptance. Faculty then complete their required personal details; Students complete any required assigned profile survey. Logout remains available during these steps. The temporary password is entered at sign-in only; Required Password Change asks for the new password and its confirmation.

Failed sign-in uses a generic credentials message so it does not reveal whether an account exists. Five failed passwords lock an active account for 15 minutes. Separate request limits can also require a waiting period. Wait for the indicated time or ask the Administrator for help; repeated retries do not bypass the limit.

### Recover a forgotten password

1. Open **Forgot Password** from the sign-in flow.
2. Enter your account ID or registered email and submit once.
3. Check the registered mailbox and spam folder. A generic acknowledgement does not establish that an account or delivery exists.
4. Open the recovery link and enter/confirm a new password. A link expires after 30 minutes and can be used once.
5. Return to Sign In with the new password. Existing sessions may need to sign in again after a password change.

Recovery depends on the school's email configuration. If no message arrives, contact the Administrator; never share the link or your password with another person.

### Sign out

Use **Logout** when finished, especially on a shared device. Closing a tab is not the same as signing out. The system also expires inactive sessions; save unfinished form work before stepping away. A session renewal cannot bypass a required password change, legal acceptance or profile assignment.

## Tasks available to signed-in users

### Read school content

1. Open **Home**, the signed-in Information Hub.
2. Use the available search/filter controls to locate a relevant announcement, event, document or survey.
3. Open the item and read its details. The content you receive depends on your role and academic/Parent relationship eligibility.
4. Where shown, react, comment/reply or acknowledge. These controls can be disabled by the author. Acknowledgment records that action; it is distinct from simply viewing the item.

A missing item may be a draft, awaiting review, scheduled for later, archived or outside your recipient group. Ask the publisher/Administrator to check its state and recipients. Do not assume that a direct link overrides those rules.

Comments are limited to 1,000 characters. Repeated actions may return a waiting message. Do not resubmit rapidly when a request is already pending.

### View events

Open **Events** to inspect the calendar and available event details. Check the listed start/end time and location. Scheduled release determines when content becomes visible; it is separate from the event's own start date. Use the school-configured time shown on the page.

### Open or download documents

Open the document through an eligible Information Hub item or authorized staff preview. Use the provided preview/download action. PDF and plain-text files may be previewed when supported. Other formats may download for a compatible desktop application.

The system checks your access for each document request. A raw storage address is not the supported download method. If access changes or a file becomes unavailable, ask the school contact to verify the content and its attachment.

### Answer a content survey

1. Open a survey offered to your recipient group.
2. Read its description and opening/closing period.
3. Answer the displayed text, choice, checkbox, rating or Yes/No questions. Required answers must be provided.
4. Select **Submit Response** and wait for confirmation.
5. If the page says you already responded or the survey is unavailable, follow that state rather than submitting again.

Content surveys differ from the required Student profile questionnaire. Results are restricted to authorized staff and small-group privacy rules; participants do not gain a result-management page by submitting.

### Manage notifications

1. Open **Notifications** and select a notice. Content notices open their eligible item; account-approval notices open My Account; Student profile-cycle notices open the assigned profile survey.
2. Use **Mark All as Read** when appropriate.
3. Set the in-system notification preference and use **Save Preference**.
4. Review the email and browser channel controls. For browser notifications, use the displayed enable action and respond to the browser permission prompt.
5. Under **Choose what you receive**, select category/channel choices and use **Save Category Preferences**.

Student and Parent accounts do not show the staff workflow notification category. Staff categories do not grant access to publishing or management tools.

Delivery depends on your preferences, target eligibility, a working delivery service and, for browser push, permission on that browser/device. If permission was denied, review the browser's site settings. Changing preferences does not prove that a queued message reached an external inbox/device.

### Manage your profile photo

Open **My Account** (`index.php?page=account_profile`). Choose **Browse Image**, select a JPG, PNG or WebP image, review it and use **Save Profile Photo**. The displayed limit is 3 MB and 100–2000 pixels per side. Use **Remove Current Photo** to remove it.

Faculty can complete/update their own Personal Details in My Account and confirm changes in the confirmation dialog. Academic assignment, account role and access stay Administrator-controlled. Student/Parent users should ask the Administrator about identity or assignment corrections. Profile-photo changes do not change permissions.

## Student guide

### Interests

Open **My Interests** in the sidebar. On **My Student Profile**, choose **My interests**. Select the topics you want to follow and set the offered preference levels: Interested, Very interested or High priority. Use **Save My Interests** or **Update My Interests** to submit changes. **Clear** removes a selection; confirm that the updated values appear after saving/reloading.

Interest preferences support relevance within your permitted audience. They do not grant access to content targeted at another program or education level.

### School profile survey

1. Open the profile-update notification, or **My Interests** in the sidebar, then choose **School profile survey**. A required assignment may redirect you to this page.
2. Review the **School profile survey** instructions. The section counter and required-answer count show your progress.
3. Answer required operational questions. Read each optional-consent explanation before supplying sensitive answers; those answers are stored only with the corresponding consent.
4. Use **Next** and **Previous** to move between sections. These buttons do not save. The unsaved-changes notice tells you when answers need saving; leaving the page may trigger a browser warning.
5. Use **Save and continue later** to preserve partial work. This does not mark the assignment complete or remove a completion requirement.
6. On the last section, choose **Complete Survey** and confirm completion. A completed survey may display **Update Completed Survey** when the current assignment permits updates.
7. Verify the completion/saved indication. If the questionnaire is unavailable, ask the Administrator to inspect the active assignment and version.

A new cycle may request updated information later. You cannot edit the questionnaire definition or another Student's answers.

## Parent guide

After account/relationship verification, use Information Hub, Events, Notifications, your account photo and eligible content surveys as described above.

If expected school content is missing, ask the Administrator to check the verified child record or linked Student account, relationship status and current academic placement. A Parent may be eligible through verified links; the relationship does not reveal the child's private Student-profile questionnaire answers. There is no self-service relationship-management workflow described here because the current registration/approval flow controls that linkage.

## Faculty guide

### Confirm your posting assignment

College Faculty may target their assigned program. IBED Faculty may target their assigned education level, such as Junior High; Elementary, Junior High and Senior High are separate scopes. Valid grade/section/strand choices may narrow that scope.

If the assignment is missing, inactive or wrong, contact the Administrator. Do not choose a different scope as a workaround. Assignment changes can affect the validation of existing drafts when they are submitted.

### Create content

1. Open **Create Post** and choose Announcement, Event, Document or Survey.
2. Under **Target Recipients**, select only the recipient roles and academic groups available within your assignment. Add valid academic targets if required.
3. Select relevant content topics if applicable.
4. Enter the selected content type's details:
   - Announcement: category, priority, title, content and optional media/transcript.
   - Event: title, start date/time, optional end, location, description and optional poster.
   - Document: title, description, required document file and optional cover.
   - Survey: title, purpose, opening/closing times and questions/options; mark required questions as appropriate.
5. Review release settings shown for that type. A requested future release still requires Administrator approval. Calendar-based release is offered only where supported and requires a valid linked event.
6. Review available interaction/notification options.
7. Use **Save Draft** for unfinished work. When ready, use the submission-for-review action; its label follows the selected content type. Review the confirmation before continuing.
8. Check **Content Workspace** for the resulting status.

Document uploads support PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX and TXT, up to 20 MiB. Renaming an unsupported file does not make it valid. The announcement/event media controls show their own limits; the current announcement image limit is 5 MB and audio limit is 15 MB. If a file is rejected, follow its error message and use a compatible unencrypted document.

### Follow review and revise

1. Open **Content Workspace** and select the relevant status tab. Use search/content-type filters.
2. Preview your item and verify its recipients and release details.
3. For editable drafts/rejected content, open the offered edit action, correct the item and save or resubmit.
4. For a rejected item, read the rejection reason before submitting again.
5. Published or pending items may not expose the same editing actions as drafts. Use only the actions currently offered for the state and ownership.

Faculty cannot approve their own submission or directly publish. A scheduled approved item waits for its release condition. **Restore to Draft**, where available, returns an item to the draft workflow; it does not immediately republish it.

### View staff results and analytics

Use your authorized survey-result link or **Department Analytics**. Survey results for fewer than five distinct respondents are protected, and individual questions/choice groups can also be suppressed. Even an Administrator cannot bypass this rule. Available exports and previews remain subject to staff/department scope.

## Administrator guide

### Review account applications

1. Open **Account Approvals** and select the application.
2. Verify identity, role, school details and, for a Parent, the linked Student/relationship using the school's process.
3. Choose **Approve Account** and **Approve and Activate** only after verification.
4. If the application should be rejected, choose **Reject Registration**, enter the required reason and confirm.
5. Verify the resulting account state. An approval decision and relationship eligibility should be checked before diagnosing missing Parent content.
### Review children without login accounts

For a new application, **Account Approvals** shows the submitted child name, optional Student ID, class, relationship and reason as read-only details. Verify these against school records and record an approval note, then approve or reject the application. Do not overwrite the Parent's pending claim. If details are incorrect, reject with a clear reason and follow the school's correction process.

For an already approved Parent, open **Manage Users**, select the Parent, then **Review child verification**:

- **Verify current enrollment and save details:** update the verified name, school ID or class after checking school records. This also permits re-verification after revocation.
- **Revoke this child verification:** remove eligibility supplied by this record. Other independently verified links remain effective.
- **Link an existing active Student account:** enter the child's Student ID and confirm that the account belongs to the same child. If the recorded ID differs, first correct it using school evidence. Linking uses the existing relationship table and retires the independent eligibility record. The Student account's current class then controls access.

Every action requires a verification note and confirmation. **Verification history** retains the review decisions. The reason alone is not evidence. Parents cannot make these changes themselves, and this workflow does not grant access to private Student questionnaire answers.


### Create Faculty and manage access

1. Open **Manage Users** and select **Create Faculty**.
2. Enter the Faculty's first name, last name, email and posting assignment: division, education level and College program where required. Faculty complete their own personal details after sign-in.
3. Choose **Review Account**, verify the details and confirm creation.
4. Deliver the displayed temporary credentials privately through the school's approved process. The Faculty member must change the temporary password, accept current legal documents, and complete Personal Details in My Account on first sign-in. Do not publish temporary credentials in reports or screenshots.
5. To correct an existing Faculty assignment, select the account and use **Faculty Posting Assignment** → **Update Faculty Assignment**.
6. Use **Access & Security** for available status/lock/staff-role actions. Provide the required reason and review the confirmation. **Unlock Account** clears an account lock when justified; it does not override every request-rate bucket.
7. Use **Activity History** to inspect recorded account activity. Role/status changes affect the user's current access and can invalidate an existing session.

### Manage academic structure

1. Open **Academic Structure**.
2. Choose the catalog type and add/edit its record: School Division → Education Level → Program/Strand and Grade/Year → Section.
3. Select valid parent records. The section's program and grade/year must be consistent with the education level.
4. Enter the required reason, use the review action and confirm.
5. For an activation/deactivation, review the affected record and reason before confirming. Check dependent assignments and filters afterward; do not delete historical records to simplify the list.

### Review and publish content

1. Open **Content Workspace**, choose the review queue and **Preview** the item.
2. Check content accuracy, attachments, recipients, Faculty assignment, release timing and interaction settings.
3. Choose **Approve** to authorize publication/release, or **Reject** and provide a useful correction reason.
4. For immediate release, verify that the item becomes published and visible to an eligible recipient. For scheduled/calendar release, verify its waiting state and later release condition.
5. Use **Archive** when the available workflow permits removal from active circulation. **Restore to Draft** restarts preparation/review; confirm the resulting state.

Administrators may also use **Create Post** to create content and choose authorized schoolwide or custom recipients. Review the target preview carefully. Publication success means the content and durable notification work were saved; email/browser delivery may complete later.

### Manage Student profile questionnaires and cycles

1. Open **Student Profiles** and select a questionnaire under **Questionnaire Versions**.
2. Use **Copy questionnaire** when you need a new editable Draft based on an existing version.
3. In a Draft, use **Add Question** or the edit/status controls. Enter the question, category, optional instructions, answer format and choices. Review required, analytics, sensitive-answer and consent settings. The system generates the internal identifier automatically and preserves it during edits. Set display order as needed.
4. Non-Draft question definitions cannot be edited. Keep historical versions intact.
5. Choose **Request profile update**. Enter a request name, choose the questionnaire, and select All Active Students, College Students or IBED Students.
6. Under **Schedule**, enter the academic year if needed and select the period. Expand **Set dates (optional)** only when you need an opening date or deadline. Blank dates mean opening on activation and no deadline.
7. Select **Save draft and preview**. Check the questionnaire, dates and eligible Student list. Saving the draft does not assign or notify Students.
8. Choose **Activate update** and confirm. The system assigns eligible Students and attempts their reminders. Remaining or failed reminders are retried by the notification worker. A future opening date is included in the reminder; Students can answer when the update opens.
9. Monitor **Not started**, **In progress**, and **Completed**. Choose **Close update** when finished; closing preserves answers and history.

Profile reminders open the school profile survey. Students can access Notifications while a survey is required, but other restricted pages remain blocked until completion. Notification preferences apply; email and browser push also require configured delivery and running workers. If delivery is delayed, ask the operator to check the worker rather than activating a duplicate request.

The current UI exposes Draft creation and cycle activation. Do not assume there is a separate general-purpose questionnaire-version activation screen. If the required version is unavailable for a cycle, ask the technical maintainer to inspect the configured version policy.

### Use a government advisory in an announcement

Administrators can do this from the normal announcement form:

1. Open content creation and choose **Announcement**.
2. Expand **Use government advisory** and paste an official HTTPS link. Checking starts automatically after you pause typing; **Check link** also lets you retry.
3. Wait for verification and review the source preview. Use an official article, bulletin, or live updates page. Invalid, pending or unattached links block saving/posting; fix the link or remove it to continue.
4. Choose **Attach advisory** and confirm that the official source is relevant to the school. Your announcement title and caption stay unchanged.
5. Write your announcement title and caption, choose recipients and publishing options, then save a draft or publish using the normal controls.

Checking a link does not publish anything. Attaching it records the verified source and your review; saving the announcement commits it together with its source association. The server rechecks the source before saving; if verification or linking fails, the new announcement is not saved. Eligible readers see a **Government advisory** tag and can open the official source from the announcement. **Remove advisory link** removes that association from the unsaved form without changing its title or caption. If you leave before saving, the prepared advisory remains in history and can be resumed through its existing announcement-draft action.

The retained advisory administration route (`index.php?page=government_advisories`) keeps historical records and specialized intake available to Administrators. The announcement form also accepts previously used official URLs without asking for import metadata. Faculty cannot import government advisories; their normal content scope and approval rules remain unchanged.

### Monitor reports and delivery

Use **Dashboard** for the displayed period, workflow/activity summaries and profile completion indicators, and its available Export action for the current report. **Department Analytics** and **Export Department Report** are Faculty-only in the current controller; Administrators use Dashboard reporting. Keep exported Student/profile information private.

Ask the technical operator to inspect the worker and queues when scheduled content or delivery is delayed. Do not repeatedly republish the same content as a delivery test. The separate technical guide explains worker monitoring, backups and recovery.

## Validation and safe submission

Required fields must be completed with valid displayed selections. Unexpected field formats, invalid record selections and invalid links are rejected by the server. Review the message and correct the form; do not repeatedly click Save or Publish. A government advisory link must pass verification before its announcement can be saved. Attaching a link does not replace your announcement title or caption.

The Contact form emails inquiries to sapinjanfortun1@gmail.com. Enter your name, a valid reply email, an inquiry type and a message of 10–1,500 characters, then choose Send Inquiry. Success confirms acceptance by the email service, not final inbox delivery. Failed submissions preserve your entries; email/phone links remain available as fallback contact methods. Submission limits protect the inbox from abuse.

## Troubleshooting

| Message or symptom | What to do |
| --- | --- |
| Invalid login credentials | Recheck the identifier/password. After repeated failures, wait for the lock period or use recovery/contact the Administrator. |
| Pending, rejected or inactive account | Contact the school approval/account Administrator; correct-password guidance may identify the state. |
| Required password/legal/profile screen | Complete the stated step. A background request cannot bypass it. |
| Page/form session expired | Refresh the page, sign in again if required, and retry after reviewing unsaved input. |
| Too many requests / wait in seconds | Wait for the stated period; avoid repeated clicks or multiple submission tabs. |
| Content not found or no longer visible | Ask the owner/Administrator to check publication state, targets, assignment and relationship eligibility. |
| Protected survey results | Wait for sufficient participation. Optional questions and small answer groups can remain protected even when the overall survey has enough respondents. |
| Document rejected | Check format and size; use an unencrypted supported file. Changing the extension alone will not help. |
| Notification/email not received | Review preferences and browser permission; check spam; ask the operator to inspect queue/delivery configuration. |
| Request could not be completed, with a reference | Give support the reference, approximate time, page and action. Do not send your password, cookie or reset link. |
| Page not found (404) | The address is unknown. Return through the menu and report the broken link. Unknown routes no longer silently open Home. |

## Glossary and safe use

- **Recipient target:** the role/academic group allowed to receive content.
- **Draft:** unfinished content, not normal recipient-facing publication.
- **Pending review:** submitted content awaiting an Administrator decision.
- **Scheduled:** content waiting for an approved release condition.
- **Archive:** removal from active circulation under the workflow; not an instruction to erase history.
- **Profile cycle:** a defined Student profile update period with assigned recipients.
- **Suppression:** withholding small-group result details to reduce identification risk.

Share accounts, credentials, reset links and personal exports with nobody outside the school's authorized process. Use your own account for each action. Report incorrect scope or identity details to the Administrator rather than trying to alter URLs or browser requests.

### Maintenance note

Screen sources reviewed include [sidebar](../../include/sidebar.php), [registration](../../pages/register.php), [posting](../../pages/postings.php), [workspace](../../pages/content_workspace.php), [user management](../../pages/manage_users.php), [Student profile](../../pages/student_profile.php), [profile management](../../pages/student_profile_management.php), [notifications](../../pages/notifications.php), [advisories](../../pages/government_advisories.php) and [account photo](../../pages/account_profile.php). Browser walkthrough results should be recorded separately in the evaluation checklist; source review is not a claim that every interaction has been visually tested.

Faculty onboarding: Administrators provide first/last name, email and authorized posting assignment. Faculty change their temporary password, accept current legal documents, then complete their own middle name/suffix (optional), gender and birthdate in My Account before using the Hub. Personal details can be updated there later; role and posting scope remain Administrator-controlled. Migration 030 permits pending gender/age without placeholder values.
