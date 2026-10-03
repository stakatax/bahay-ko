document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        /* ======================================
           ELEMENTS
        ======================================= */

        const contentList =
            document.getElementById(
                'workspaceContentList'
            );

        const workspaceItems =
            Array.from(
                document.querySelectorAll(
                    '[data-workspace-item]'
                )
            );

        const workspaceSearch =
            document.getElementById(
                'workspaceSearch'
            );

        const typeFilter =
            document.getElementById(
                'workspaceTypeFilter'
            );

        const sortSelect =
            document.getElementById(
                'workspaceSort'
            );

        const filteredEmpty =
            document.getElementById(
                'workspaceFilteredEmpty'
            );

        const resetFiltersButton =
            document.getElementById(
                'workspaceResetFilters'
            );

        const modalOverlay =
            document.getElementById(
                'workspaceModalOverlay'
            );

        const confirmModal =
            document.getElementById(
                'workspaceConfirmModal'
            );

        const confirmTitle =
            document.getElementById(
                'workspaceConfirmTitle'
            );

        const confirmMessage =
            document.getElementById(
                'workspaceConfirmMessage'
            );

        const confirmIcon =
            document.getElementById(
                'workspaceConfirmIcon'
            );

        const confirmAction =
            document.getElementById(
                'workspaceConfirmAction'
            );

        const previewModal =
            document.getElementById(
                'workspacePreviewModal'
            );

        const previewType =
            document.getElementById(
                'workspacePreviewType'
            );

        const previewTitle =
            document.getElementById(
                'workspacePreviewTitle'
            );

        const previewBadges =
            document.getElementById(
                'workspacePreviewBadges'
            );

        const previewDescription =
            document.getElementById(
                'workspacePreviewDescription'
            );

        const previewDetails =
            document.getElementById(
                'workspacePreviewDetails'
            );

        const previewReview =
            document.getElementById(
                'workspacePreviewReview'
            );

        const previewFile =
            document.getElementById(
                'workspacePreviewFile'
            );

        const filePreviewWrap =
    document.getElementById(
        'workspaceFilePreviewWrap'
    );

const filePreviewFrame =
    document.getElementById(
        'workspaceFilePreviewFrame'
    );

    const fileNotice =
    document.getElementById(
        'workspaceFileNotice'
    );

const fileNoticeText =
    document.getElementById(
        'workspaceFileNoticeText'
    );

        const rejectModal =
            document.getElementById(
                'workspaceRejectModal'
            );

        const rejectForm =
            document.getElementById(
                'workspaceRejectForm'
            );

        const rejectContentType =
            document.getElementById(
                'rejectContentType'
            );

        const rejectContentId =
            document.getElementById(
                'rejectContentId'
            );

        const rejectContentName =
            document.getElementById(
                'rejectContentName'
            );

        const reviewNotes =
            document.getElementById(
                'workspaceReviewNotes'
            );

        const reviewCount =
            document.getElementById(
                'workspaceReviewCount'
            );

        const closeModalButtons =
            Array.from(
                document.querySelectorAll(
                    '[data-close-modal]'
                )
            );

        const previewButtons =
            Array.from(
                document.querySelectorAll(
                    '[data-open-preview]'
                )
            );

        const rejectButtons =
            Array.from(
                document.querySelectorAll(
                    '[data-open-reject]'
                )
            );

        const confirmationForms =
            Array.from(
                document.querySelectorAll(
                    '[data-confirm-form]'
                )
            );

        /* ======================================
           STATE
        ======================================= */

        let activeModal = null;

        let pendingForm = null;

        let previouslyFocusedElement = null;

        let formIsSubmitting = false;

        /* ======================================
           UTILITIES
        ======================================= */

        function normalizeText(
            value
        ) {
            return String(
                value ?? ''
            )
                .trim()
                .toLowerCase();
        }

        function parseDateValue(
            value
        ) {
            const text =
                String(
                    value ?? ''
                ).trim();

            if (!text) {
                return 0;
            }

            const timestamp =
                Date.parse(text);

            return Number.isNaN(
                timestamp
            )
                ? 0
                : timestamp;
        }

        function escapeHtml(
            value
        ) {
            const element =
                document.createElement(
                    'div'
                );

            element.textContent =
                String(
                    value ?? ''
                );

            return element.innerHTML;
        }

        function formatBytes(
            bytes
        ) {
            const value =
                Number(bytes);

            if (
                !Number.isFinite(value) ||
                value <= 0
            ) {
                return '';
            }

            const units = [
                'B',
                'KB',
                'MB',
                'GB'
            ];

            let size = value;

            let unitIndex = 0;

            while (
                size >= 1024 &&
                unitIndex <
                    units.length - 1
            ) {
                size /= 1024;

                unitIndex += 1;
            }

            return `${
                size >= 10 ||
                unitIndex === 0
                    ? size.toFixed(0)
                    : size.toFixed(1)
            } ${units[unitIndex]}`;
        }

        function createDetailRow(
            label,
            value
        ) {
            const normalizedValue =
                String(
                    value ?? ''
                ).trim();

            if (!normalizedValue) {
                return '';
            }

            return `
                <div>
                    <dt>
                        ${escapeHtml(label)}
                    </dt>

                    <dd>
                        ${escapeHtml(
                            normalizedValue
                        )}
                    </dd>
                </div>
            `;
        }

        function setButtonLoading(
            button,
            loading
        ) {
            if (!button) {
                return;
            }

            button.disabled =
                loading;

            button.classList.toggle(
                'is-loading',
                loading
            );

            const icon =
                button.querySelector(
                    'i'
                );

            if (!icon) {
                return;
            }

            if (loading) {
                icon.dataset.originalClass =
                    icon.className;

                icon.className =
                    'fa-solid fa-spinner fa-spin';

                return;
            }

            if (
                icon.dataset.originalClass
            ) {
                icon.className =
                    icon.dataset.originalClass;

                delete icon.dataset
                    .originalClass;
            }
        }

        function formatWorkspaceCountdown(
    targetDate
) {
    const difference =
        targetDate.getTime() -
        Date.now();

    if (difference <= 0) {
        return 'Releasing now...';
    }

    const totalSeconds =
        Math.floor(
            difference / 1000
        );

    const days =
        Math.floor(
            totalSeconds / 86400
        );

    const hours =
        Math.floor(
            (
                totalSeconds % 86400
            ) / 3600
        );

    const minutes =
        Math.floor(
            (
                totalSeconds % 3600
            ) / 60
        );

    const seconds =
        totalSeconds % 60;

    if (days > 0) {
        return days === 1
            ? `Releases in 1 day ${hours} hr`
            : `Releases in ${days} days ${hours} hr`;
    }

    if (hours > 0) {
        return `Releases in ${hours} hr ${minutes} min`;
    }

    if (minutes > 0) {
        return `Releases in ${minutes} min ${seconds} sec`;
    }

    return `Releases in ${seconds} sec`;
}

const scheduledCountdowns =
    Array.from(
        document.querySelectorAll(
            '[data-scheduled-countdown]'
        )
    );

let releaseRefreshScheduled =
    false;

function updateWorkspaceCountdowns() {
    scheduledCountdowns.forEach(
        (countdown) => {
            const releaseValue =
                countdown.dataset
                    .releaseAt;

            const textElement =
                countdown.querySelector(
                    '[data-countdown-text]'
                );

            if (
                !releaseValue ||
                !textElement
            ) {
                return;
            }

            const releaseDate =
                new Date(
                    releaseValue
                        .replace(
                            ' ',
                            'T'
                        )
                );

            if (
                Number.isNaN(
                    releaseDate.getTime()
                )
            ) {
                textElement.textContent =
                    'Invalid release time';

                return;
            }

            const remaining =
                releaseDate.getTime() -
                Date.now();

            textElement.textContent =
                formatWorkspaceCountdown(
                    releaseDate
                );

            if (
                remaining <= 0 &&
                !releaseRefreshScheduled
            ) {
                releaseRefreshScheduled =
                    true;

                setTimeout(
                    () => {
                        window.location.reload();
                    },
                    1500
                );
            }
        }
    );
}

updateWorkspaceCountdowns();

setInterval(
    updateWorkspaceCountdowns,
    1000
);

        /* ======================================
           SEARCH, FILTER, AND SORT
        ======================================= */

        function sortItems(
            items
        ) {
            const sortValue =
                String(
                    sortSelect?.value ||
                    'newest'
                );

            return [...items].sort(
                (
                    first,
                    second
                ) => {
                    const firstTitle =
                        normalizeText(
                            first.dataset
                                .title
                        );

                    const secondTitle =
                        normalizeText(
                            second.dataset
                                .title
                        );

                    const firstCreated =
                        parseDateValue(
                            first.dataset
                                .created
                        );

                    const secondCreated =
                        parseDateValue(
                            second.dataset
                                .created
                        );

                    const firstReviewed =
                        parseDateValue(
                            first.dataset
                                .reviewed
                        );

                    const secondReviewed =
                        parseDateValue(
                            second.dataset
                                .reviewed
                        );

                    switch (
                        sortValue
                    ) {
                        case 'oldest':
                            return (
                                firstCreated -
                                secondCreated
                            );

                        case 'title_asc':
                            return firstTitle
                                .localeCompare(
                                    secondTitle
                                );

                        case 'title_desc':
                            return secondTitle
                                .localeCompare(
                                    firstTitle
                                );

                        case 'reviewed':
                            return (
                                secondReviewed -
                                firstReviewed
                            );

                        case 'newest':
                        default:
                            return (
                                secondCreated -
                                firstCreated
                            );
                    }
                }
            );
        }

function applyWorkspaceControls() {
    const selectedType =
        normalizeText(
            typeFilter?.value ||
            'all'
        );

    const searchTerm =
        normalizeText(
            workspaceSearch?.value
        );

    let visibleCount = 0;

    workspaceItems.forEach(
        (item) => {
            const contentType =
                normalizeText(
                    item.dataset
                        .contentType
                );

            const searchableText =
                normalizeText(
                    item.dataset
                        .search
                );

            const matchesType =
                selectedType ===
                    'all' ||
                contentType ===
                    selectedType;

            const matchesSearch =
                searchTerm === '' ||
                searchableText.includes(
                    searchTerm
                );

            const visible =
                matchesType &&
                matchesSearch;

            item.classList.toggle(
                'is-hidden',
                !visible
            );

            if (visible) {
                visibleCount += 1;
            }
        }
    );

    if (contentList) {
        const sortedItems =
            sortItems(
                workspaceItems
            );

        sortedItems.forEach(
            (item) => {
                contentList.appendChild(
                    item
                );
            }
        );
    }

    if (filteredEmpty) {
        filteredEmpty.hidden =
            !filtersApplied ||
            visibleCount > 0;
    }

    if (resetFiltersButton) {
        resetFiltersButton.hidden =
            !filtersApplied;
    }
}

function applyFilters() {
    filtersApplied = true;

    applyWorkspaceControls();
}

function resetWorkspaceControls() {
    if (workspaceSearch) {
        workspaceSearch.value =
            '';
    }

    if (typeFilter) {
        typeFilter.value =
            'all';
    }

    if (sortSelect) {
        sortSelect.value =
            'newest';
    }

    filtersApplied = false;

    applyWorkspaceControls();

    workspaceSearch?.focus();
}

workspaceSearch?.addEventListener(
    'input',
    applyFilters
);

typeFilter?.addEventListener(
    'change',
    applyFilters
);

/*
 * Sorting only changes order.
 */
sortSelect?.addEventListener(
    'change',
    applyWorkspaceControls
);

resetFiltersButton?.addEventListener(
    'click',
    resetWorkspaceControls
);
        /* ======================================
           MODAL MANAGEMENT
        ======================================= */

        function openModal(
            modal
        ) {
            if (
                !modal ||
                !modalOverlay
            ) {
                return;
            }

            previouslyFocusedElement =
                document.activeElement;

            activeModal =
                modal;

            modalOverlay.hidden =
                false;

            modal.hidden =
                false;

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            document.body
                .classList.add(
                    'workspace-modal-open'
                );

            const firstFocusable =
                modal.querySelector(
                    [
                        'button:not([disabled])',
                        'a[href]',
                        'input:not([disabled])',
                        'textarea:not([disabled])',
                        'select:not([disabled])'
                    ].join(',')
                );

            window.setTimeout(
                () => {
                    firstFocusable
                        ?.focus();
                },
                0
            );
        }

        function closeModal() {
            if (!activeModal) {
                return;
            }

            activeModal.hidden =
                true;

            activeModal.setAttribute(
                'aria-hidden',
                'true'
            );

            if (modalOverlay) {
                modalOverlay.hidden =
                    true;
            }

            document.body
                .classList.remove(
                    'workspace-modal-open'
                );

            activeModal =
                null;

            pendingForm =
                null;

            if (
                previouslyFocusedElement &&
                typeof previouslyFocusedElement
                    .focus === 'function'
            ) {
                previouslyFocusedElement
                    .focus();
            }

            previouslyFocusedElement =
                null;
        }

        closeModalButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    closeModal
                );
            }
        );

        modalOverlay
            ?.addEventListener(
                'click',
                closeModal
            );

        document.addEventListener(
            'keydown',
            (event) => {
                if (
                    event.key ===
                        'Escape' &&
                    activeModal
                ) {
                    closeModal();

                    return;
                }

                if (
                    event.key !==
                        'Tab' ||
                    !activeModal
                ) {
                    return;
                }

                const focusable =
                    Array.from(
                        activeModal
                            .querySelectorAll(
                                [
                                    'button:not([disabled])',
                                    'a[href]',
                                    'input:not([disabled])',
                                    'textarea:not([disabled])',
                                    'select:not([disabled])'
                                ].join(',')
                            )
                    ).filter(
                        (element) =>
                            !element.hidden
                    );

                if (
                    focusable.length ===
                    0
                ) {
                    return;
                }

                const first =
                    focusable[0];

                const last =
                    focusable[
                        focusable.length -
                            1
                    ];

                if (
                    event.shiftKey &&
                    document.activeElement ===
                        first
                ) {
                    event.preventDefault();

                    last.focus();

                    return;
                }

                if (
                    !event.shiftKey &&
                    document.activeElement ===
                        last
                ) {
                    event.preventDefault();

                    first.focus();
                }
            }
        );

        /* ======================================
           CONFIRMATION MODAL
        ======================================= */

        let filtersApplied = false;

        function resetConfirmTone() {
            if (!confirmIcon) {
                return;
            }

            confirmIcon.classList.remove(
                'tone-approve',
                'tone-archive',
                'tone-danger'
            );

            confirmIcon.innerHTML = `
                <i class="fa-solid fa-circle-question"></i>
            `;
        }

        function applyConfirmTone(
            tone
        ) {
            resetConfirmTone();

            if (!confirmIcon) {
                return;
            }

            switch (tone) {
                case 'approve':
                    confirmIcon.classList.add(
                        'tone-approve'
                    );

                    confirmIcon.innerHTML = `
                        <i class="fa-solid fa-circle-check"></i>
                    `;
                    break;

                case 'archive':
                    confirmIcon.classList.add(
                        'tone-archive'
                    );

                    confirmIcon.innerHTML = `
                        <i class="fa-solid fa-box-archive"></i>
                    `;
                    break;

                case 'danger':
                    confirmIcon.classList.add(
                        'tone-danger'
                    );

                    confirmIcon.innerHTML = `
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    `;
                    break;

                case 'primary':
                default:
                    confirmIcon.innerHTML = `
                        <i class="fa-solid fa-circle-question"></i>
                    `;
                    break;
            }
        }

        function configureConfirmButton(
            tone,
            label
        ) {
            if (!confirmAction) {
                return;
            }

            confirmAction.className =
                'app-button';

            switch (tone) {
                case 'approve':
                    confirmAction.classList.add(
                        'approve'
                    );
                    break;

                case 'archive':
                    confirmAction.classList.add(
                        'archive'
                    );
                    break;

                case 'danger':
                    confirmAction.classList.add(
                        'danger'
                    );
                    break;

                case 'primary':
                default:
                    confirmAction.classList.add(
                        'primary'
                    );
                    break;
            }

            confirmAction.textContent =
                label ||
                'Continue';
        }

        confirmationForms.forEach(
            (form) => {
                form.addEventListener(
                    'submit',
                    (event) => {
                        if (
                            formIsSubmitting ||
                            form.dataset
                                .confirmed ===
                                'true'
                        ) {
                            return;
                        }

                        event.preventDefault();

                        pendingForm =
                            form;

                        const title =
                            String(
                                form.dataset
                                    .confirmTitle ||
                                'Confirm Action'
                            );

                        const message =
                            String(
                                form.dataset
                                    .confirmMessage ||
                                'Continue with this action?'
                            );

                        const label =
                            String(
                                form.dataset
                                    .confirmLabel ||
                                'Continue'
                            );

                        const tone =
                            String(
                                form.dataset
                                    .confirmTone ||
                                'primary'
                            );

                        if (confirmTitle) {
                            confirmTitle
                                .textContent =
                                title;
                        }

                        if (confirmMessage) {
                            confirmMessage
                                .textContent =
                                message;
                        }

                        applyConfirmTone(
                            tone
                        );

                        configureConfirmButton(
                            tone,
                            label
                        );

                        openModal(
                            confirmModal
                        );
                    }
                );
            }
        );

        confirmAction?.addEventListener(
            'click',
            () => {
                if (
                    !pendingForm ||
                    formIsSubmitting
                ) {
                    return;
                }

                const form =
                    pendingForm;

                const submitButton =
                    form.querySelector(
                        'button[type="submit"]'
                    );

                formIsSubmitting =
                    true;

                form.dataset.confirmed =
                    'true';

                setButtonLoading(
                    confirmAction,
                    true
                );

                setButtonLoading(
                    submitButton,
                    true
                );

                closeModal();

                form.requestSubmit();
            }
        );

        /* ======================================
           PREVIEW MODAL
        ======================================= */

        function renderPreviewBadges(
            data
        ) {
            if (!previewBadges) {
                return;
            }

            const badges = [
                data.type,
                data.status,
                data.priority,
                data.release_mode,
                data.file_type
            ].filter(
                (value) =>
                    String(
                        value ?? ''
                    ).trim() !== ''
            );

            previewBadges.innerHTML =
                badges
                    .map(
                        (badge) => `
                            <span>
                                ${escapeHtml(
                                    badge
                                )}
                            </span>
                        `
                    )
                    .join('');
        }

        function renderPreviewDetails(
    data
) {
    if (!previewDetails) {
        return;
    }

    const fileSize =
        formatBytes(
            data.file_size
        );

    const details = [
        createDetailRow(
            'Content Type',
            data.type
        ),

        createDetailRow(
            'Workflow Status',
            data.status
        ),

        createDetailRow(
            'Author',
            data.author
        ),

        createDetailRow(
            'Created',
            data.created
        ),

        createDetailRow(
            'Last Updated',
            data.updated_at
        ),

        createDetailRow(
            'Category',
            data.category
        ),

        createDetailRow(
            'Priority',
            data.priority
        ),

        createDetailRow(
            'Target Audience',
            data.audience
        ),

        createDetailRow(
    'Release Mode',
    data.release_mode
),

createDetailRow(
    'Scheduled Release',
    data.scheduled_publish_at
),

createDetailRow(
    'Linked Event',
    data.linked_event_title
),

createDetailRow(
    'Linked Event Starts',
    data.linked_event_date
),

createDetailRow(
    'Release Behavior',
    data.linked_event_title
        ? 'Publishes automatically when the linked event begins.'
        : null
),

createDetailRow(
    'Event Starts',
    data.event_date
),

        createDetailRow(
            'Event Ends',
            data.end_date
        ),

        createDetailRow(
            'Location',
            data.location
        ),

        createDetailRow(
            'File Name',
            data.file_name
        ),

        createDetailRow(
            'File Type',
            data.file_type
        ),

        createDetailRow(
            'File Size',
            fileSize
        ),

        createDetailRow(
            'Reactions',
            data.allow_reactions
        ),

        createDetailRow(
            'Comments',
            data.allow_comments
        ),

        createDetailRow(
            'Acknowledgment',
            data.require_acknowledgment
        ),

        createDetailRow(
            'Notifications',
            data.send_notification
        ),

        createDetailRow(
            'Reviewed By',
            data.reviewer
        ),

        createDetailRow(
            'Reviewed At',
            data.reviewed_at
        )
    ].filter(Boolean);

    previewDetails.innerHTML =
        details.join('');

    previewDetails.hidden =
        details.length === 0;
}

        function renderPreviewReview(
            data
        ) {
            if (!previewReview) {
                return;
            }

            const notes =
                String(
                    data.review_notes ??
                    ''
                ).trim();

            if (!notes) {
                previewReview.hidden =
                    true;

                previewReview.innerHTML =
                    '';

                return;
            }

            previewReview.innerHTML = `
                <div>
                    <i class="fa-solid fa-message"></i>

                    <strong>
                        Review Notes
                    </strong>
                </div>

                <p>
                    ${escapeHtml(notes)}
                </p>
            `;

            previewReview.hidden =
                false;
        }

function renderPreviewFile(
    data
) {
    if (
        !previewFile ||
        !filePreviewWrap ||
        !filePreviewFrame ||
        !fileNotice ||
        !fileNoticeText
    ) {
        return;
    }

    const contentType =
        normalizeText(
            data.type
        );

    const filePath =
        String(
            data.file_path ??
            ''
        ).trim();

    const fileType =
        normalizeText(
            data.file_type
        );

    const isDocument =
        contentType ===
        'document';

    const previewableTypes = [
        'pdf',
        'txt',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif'
    ];

    const canPreviewInline =
        isDocument &&
        filePath !== '' &&
        previewableTypes.includes(
            fileType
        );

    /*
     * Reset preview elements first.
     */
    filePreviewWrap.hidden =
        true;

    filePreviewFrame.src =
        '';

    fileNotice.hidden =
        true;

    previewFile.hidden =
        true;

    previewFile.href =
        '#';

    previewFile.removeAttribute(
        'download'
    );

    previewFile.innerHTML = `
        <i class="fa-solid fa-file-arrow-down"></i>
        Access File
    `;

    /*
     * Announcement and Event do not
     * have an attached document file.
     */
    if (
        !isDocument ||
        filePath === ''
    ) {
        return;
    }

    previewFile.href =
        filePath;

    previewFile.hidden =
        false;

    /*
     * Files supported by the browser
     * are displayed inside the modal.
     */
    if (canPreviewInline) {
        filePreviewFrame.src =
            filePath;

        filePreviewWrap.hidden =
            false;

        previewFile.target =
            '_blank';

        previewFile.rel =
            'noopener noreferrer';

        return;
    }

    /*
     * Office documents cannot normally
     * be rendered directly by browsers.
     */
    fileNoticeText.textContent =
        'This file type cannot be previewed directly in the browser. Use the Access File button to download it, then open it using a compatible application.';

    fileNotice.hidden =
        false;

    previewFile.removeAttribute(
        'target'
    );

    previewFile.setAttribute(
        'download',
        ''
    );
}

        previewButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        let data = {};

                        try {
                            data =
                                JSON.parse(
                                    button.dataset
                                        .preview ||
                                    '{}'
                                );
                        } catch (
                            error
                        ) {
                            console.error(
                                'Invalid preview data:',
                                error
                            );

                            window.alert(
                                'Unable to load this preview.'
                            );

                            return;
                        }

                        if (previewType) {
                            previewType
                                .textContent =
                                data.type ||
                                'Content Preview';
                        }

                        if (previewTitle) {
                            previewTitle
                                .textContent =
                                data.title ||
                                'Untitled Content';
                        }

                        if (
                            previewDescription
                        ) {
                            previewDescription
                                .textContent =
                                data.description ||
                                'No additional description provided.';
                        }

                        renderPreviewBadges(
                            data
                        );

                        renderPreviewDetails(
                            data
                        );

                        renderPreviewReview(
                            data
                        );

                        renderPreviewFile(
                            data
                        );

                        openModal(
                            previewModal
                        );
                    }
                );
            }
        );

        /* ======================================
   WORKSPACE DEEP LINK
======================================= */

const workspaceRequest =
    new URLSearchParams(
        window.location.search
    );

const requestedContentType =
    (
        workspaceRequest.get(
            'open_type'
        ) || ''
    )
    .trim()
    .toLowerCase();

const requestedContentId =
    Number.parseInt(
        workspaceRequest.get(
            'open_id'
        ) || '0',
        10
    );

if (
    requestedContentType !== '' &&
    Number.isInteger(
        requestedContentId
    ) &&
    requestedContentId > 0
) {
    const requestedCard =
        workspaceItems.find(
            (item) =>
                (
                    item.dataset
                        .contentType ||
                    ''
                )
                .trim()
                .toLowerCase() ===
                    requestedContentType
                &&
                Number.parseInt(
                    item.dataset
                        .contentId ||
                        '0',
                    10
                ) ===
                    requestedContentId
        );

    if (requestedCard) {
        const requestedPreviewButton =
            requestedCard
            .querySelector(
                '[data-open-preview]'
            );

        requestedCard.classList.add(
            'workspace-content-card-requested'
        );

        window.requestAnimationFrame(
            () => {
                requestedCard
                    .scrollIntoView({
                        behavior:
                            'smooth',

                        block:
                            'center'
                    });

                requestedPreviewButton
                    ?.click();
            }
        );
    }
}

        /* ======================================
           REJECTION MODAL
        ======================================= */

        function updateReviewCounter() {
            if (
                !reviewNotes ||
                !reviewCount
            ) {
                return;
            }

            const length =
                reviewNotes
                    .value
                    .length;

            reviewCount.textContent =
                `${length} / 1000`;

            reviewCount.classList.toggle(
                'near-limit',
                length >= 800 &&
                    length < 1000
            );

            reviewCount.classList.toggle(
                'at-limit',
                length >= 1000
            );
        }

        rejectButtons.forEach(
            (button) => {
                button.addEventListener(
                    'click',
                    () => {
                        const contentType =
                            String(
                                button.dataset
                                    .contentType ||
                                ''
                            ).trim();

                        const contentId =
                            Number(
                                button.dataset
                                    .contentId ||
                                0
                            );

                        const contentTitle =
                            String(
                                button.dataset
                                    .contentTitle ||
                                'Selected content'
                            ).trim();

                        if (
                            !contentType ||
                            contentId <= 0
                        ) {
                            window.alert(
                                'Invalid content selection.'
                            );

                            return;
                        }

                        if (
                            rejectContentType
                        ) {
                            rejectContentType
                                .value =
                                contentType;
                        }

                        if (
                            rejectContentId
                        ) {
                            rejectContentId
                                .value =
                                String(
                                    contentId
                                );
                        }

                        if (
                            rejectContentName
                        ) {
                            rejectContentName
                                .textContent =
                                contentTitle;
                        }

                        if (reviewNotes) {
                            reviewNotes.value =
                                '';
                        }

                        updateReviewCounter();

                        openModal(
                            rejectModal
                        );

                        window.setTimeout(
                            () => {
                                reviewNotes
                                    ?.focus();
                            },
                            0
                        );
                    }
                );
            }
        );

        reviewNotes?.addEventListener(
            'input',
            updateReviewCounter
        );

        rejectForm?.addEventListener(
            'submit',
            (event) => {
                if (formIsSubmitting) {
                    event.preventDefault();

                    return;
                }

                const reason =
                    String(
                        reviewNotes?.value ||
                        ''
                    ).trim();

                if (!reason) {
                    event.preventDefault();

                    window.alert(
                        'Please provide a rejection reason.'
                    );

                    reviewNotes?.focus();

                    return;
                }

                if (
                    reason.length >
                    1000
                ) {
                    event.preventDefault();

                    window.alert(
                        'The rejection reason cannot exceed 1,000 characters.'
                    );

                    reviewNotes?.focus();

                    return;
                }

                const submitButton =
                    rejectForm
                        .querySelector(
                            'button[type="submit"]'
                        );

                formIsSubmitting =
                    true;

                setButtonLoading(
                    submitButton,
                    true
                );
            }
        );

        /* ======================================
           FEEDBACK URL CLEANUP
        ======================================= */

        function cleanFeedbackParameters() {
            const currentUrl =
                new URL(
                    window.location.href
                );

            const hasFeedback =
                currentUrl
                    .searchParams
                    .has(
                        'success'
                    ) ||
                currentUrl
                    .searchParams
                    .has(
                        'error'
                    );

            if (!hasFeedback) {
                return;
            }

            currentUrl
                .searchParams
                .delete(
                    'success'
                );

            currentUrl
                .searchParams
                .delete(
                    'error'
                );

            window.history
                .replaceState(
                    {},
                    document.title,
                    currentUrl
                        .toString()
                );
        }

        /* ======================================
           INITIALIZATION
        ======================================= */

        applyWorkspaceControls();

        updateReviewCounter();

        cleanFeedbackParameters();
    }
);