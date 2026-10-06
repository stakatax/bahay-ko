document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /* ==========================================
       HUB ELEMENTS
    ========================================== */

    const searchInput =
        document.getElementById('hubSearch');

    const clearSearchButton =
        document.getElementById('clearHubSearch');

    const viewButtons = Array.from(
        document.querySelectorAll('[data-view]')
    );

    const hubItems = Array.from(
        document.querySelectorAll('[data-hub-item]')
    );

    const resultCount =
        document.getElementById('hubResultCount');

    const viewTitle =
        document.getElementById('hubViewTitle');

    const noResults =
        document.getElementById('hubNoResults');

    const sortSelect =
        document.getElementById('hubSort');

    const hubList =
        document.getElementById('hubList');

const savedActiveView =
    sessionStorage.getItem(
        'hubActiveView'
    );

const validHubViews =
    new Set(
        viewButtons.map(
            (button) =>
                button.dataset.view ??
                'all'
        )
    );

let activeView =
    savedActiveView &&
    validHubViews.has(
        savedActiveView
    )
        ? savedActiveView
        : 'all';
let activeContentType = null;
let activeContentId = null;
let contentLoadSequence = 0;
let activeReaction = null;
let activeInteractionSettings = {
    allow_reactions: false,
    allow_comments: false,
    require_acknowledgment: false
};

    /* ==========================================
       REACTION CONFIGURATION
    ========================================== */

    const reactionConfig = {
        Upvote: { label: 'Upvote', icon: 'fa-solid fa-arrow-up', className: 'reaction-vote' },
        Downvote: { label: 'Downvote', icon: 'fa-solid fa-arrow-down', className: 'reaction-vote' }
    };

    const supportedReactions =
        Object.keys(reactionConfig);

    /* ==========================================
       DATE HELPERS
    ========================================== */

    function parseDate(value) {
        if (!value) {
            return null;
        }

        const normalizedValue =
            String(value).replace(' ', 'T');

        const date =
            new Date(normalizedValue);

        return Number.isNaN(date.getTime())
            ? null
            : date;
    }

    function formatDate(
        value,
        includeTime = false
    ) {
        const date =
            parseDate(value);

        if (!date) {
            return '';
        }

        const options = includeTime
            ? {
                month: 'long',
                day: '2-digit',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            }
            : {
                month: 'long',
                day: '2-digit',
                year: 'numeric'
            };

        return new Intl.DateTimeFormat(
            'en-US',
            options
        ).format(date);
    }

    function isRecent(value) {
        const date =
            parseDate(value);

        if (!date) {
            return false;
        }

        const difference =
            Date.now() - date.getTime();

        const sevenDays =
            7 * 24 * 60 * 60 * 1000;

        return (
            difference >= 0 &&
            difference <= sevenDays
        );
    }

    /* ==========================================
       FILTER AND SORT
    ========================================== */

    function itemMatchesView(item) {
        const type =
            item.dataset.type ?? '';

        if (activeView === 'all') {
            return true;
        }

        if (activeView === 'recent') {
            return isRecent(
                item.dataset.date
            );
        }

        if (activeView === 'urgent') {
            return (
                item.dataset.urgent === '1'
            );
        }

        if (activeView === 'scheduled') {
            return (
                item.dataset.scheduled === '1'
            );
        }

        return activeView === type;
    }

    function updateResults() {
        const query = (
            searchInput?.value ?? ''
        )
            .trim()
            .toLowerCase();

        let visibleCount = 0;

        hubItems.forEach((item) => {
            const searchable = (
                item.dataset.search ?? ''
            ).toLowerCase();

            const matchesView =
                itemMatchesView(item);

            const matchesSearch =
                query === '' ||
                searchable.includes(query);

            const visible =
                matchesView &&
                matchesSearch;

            item.classList.toggle(
                'is-hidden',
                !visible
            );

            if (visible) {
                visibleCount += 1;
            }
        });

        if (resultCount) {
            resultCount.textContent =
                `${visibleCount} ${
                    visibleCount === 1
                        ? 'result'
                        : 'results'
                }`;
        }

        if (hubList) {
            hubList.classList.toggle(
                'is-empty',
                visibleCount === 0
            );
        }

        if (noResults) {
            noResults.hidden =
                visibleCount !== 0;
        }

        if (clearSearchButton) {
            clearSearchButton.hidden =
                query === '';
        }
    }

    viewButtons.forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                activeView =
                    button.dataset.view ??
                    'all';

                    sessionStorage.setItem(
    'hubActiveView',
    activeView
);

                viewButtons.forEach(
                    (item) => {
                        item.classList.toggle(
                            'active',
                            item === button
                        );
                    }
                );

                if (viewTitle) {
                    viewTitle.textContent =
                        button.dataset.label ??
                        'All Updates';
                }

                updateResults();
            }
        );
    });

    searchInput?.addEventListener(
        'input',
        updateResults
    );

    clearSearchButton?.addEventListener(
        'click',
        () => {
            if (!searchInput) {
                return;
            }

            searchInput.value = '';
            searchInput.focus();

            updateResults();
        }
    );

    function diversifyRecommendedItems(
    items,
    maximumConsecutive = 3
) {
    const tierGroups =
        new Map();

    items.forEach((item) => {
        const tier =
            Number(
                item.dataset
                    .rankingTier ??
                1
            );

        if (!tierGroups.has(tier)) {
            tierGroups.set(
                tier,
                []
            );
        }

        tierGroups.get(tier)
            .push(item);
    });

    const orderedTiers =
        [...tierGroups.keys()]
            .sort(
                (first, second) =>
                    second - first
            );

    const diversified = [];

    orderedTiers.forEach((tier) => {
        const remaining = [
            ...tierGroups.get(tier)
        ];

        let lastType = null;
        let consecutiveCount = 0;

        while (remaining.length > 0) {
            let selectedIndex = 0;

            if (
                lastType !== null &&
                consecutiveCount >=
                    maximumConsecutive
            ) {
                const alternativeIndex =
                    remaining.findIndex(
                        (candidate) =>
                            (
                                candidate.dataset
                                    .type ??
                                ''
                            ) !== lastType &&
                            Number(remaining[0].dataset.rankingScore ?? 0) - Number(candidate.dataset.rankingScore ?? 0) <= 90
                    );

                if (alternativeIndex >= 0) {
                    selectedIndex =
                        alternativeIndex;
                }
            }

            const [
                selected
            ] = remaining.splice(
                selectedIndex,
                1
            );

            const selectedType =
                selected.dataset.type ??
                '';

            if (selectedType === lastType) {
                consecutiveCount += 1;
            } else {
                lastType =
                    selectedType;

                consecutiveCount =
                    1;
            }

            diversified.push(
                selected
            );
        }
    });

    return diversified;
}

    sortSelect?.addEventListener(
    'change',
    () => {
        if (!hubList) {
            return;
        }

        const mode =
            sortSelect.value;

        let sortedItems =
    [...hubItems].sort(
                (first, second) => {
                    if (mode === 'title') {
                        return (
                            first.dataset.title ??
                            ''
                        ).localeCompare(
                            second.dataset.title ??
                            ''
                        );
                    }

                    const firstDate =
                        parseDate(
                            first.dataset.date
                        )?.getTime() ?? 0;

                    const secondDate =
                        parseDate(
                            second.dataset.date
                        )?.getTime() ?? 0;

                    if (mode === 'recommended') {

                        const firstTier =
    Number(
        first.dataset
            .rankingTier ??
        1
    );

const secondTier =
    Number(
        second.dataset
            .rankingTier ??
        1
    );

if (firstTier !== secondTier) {
    return (
        secondTier -
        firstTier
    );
}


                        const firstScore =
                            Number(
                                first.dataset
                                    .rankingScore ??
                                0
                            );

                        const secondScore =
                            Number(
                                second.dataset
                                    .rankingScore ??
                                0
                            );

                        if (
                            firstScore !==
                            secondScore
                        ) {
                            return (
                                secondScore -
                                firstScore
                            );
                        }

                        return (
                            secondDate -
                            firstDate
                        );
                    }

                    return mode === 'oldest'
                        ? firstDate -
                            secondDate
                        : secondDate -
                            firstDate;
                }
            );

        if (mode === 'recommended') {
    sortedItems =
        diversifyRecommendedItems(
            sortedItems
        );
}

        sortedItems.forEach((item) => {
            hubList.appendChild(item);
        });

        updateResults();
    }
);

    /* ==========================================
       DRAWER ELEMENTS
    ========================================== */

    const drawer =
        document.getElementById(
            'contentDrawer'
        );

    const overlay =
        document.getElementById(
            'contentDrawerOverlay'
        );

    const closeButton =
        document.getElementById(
            'closeContentDrawer'
        );

    const typeIcon =
        document.getElementById(
            'contentDrawerTypeIcon'
        );

    const typeText =
        document.getElementById(
            'contentDrawerTypeText'
        );

    const drawerTitle =
        document.getElementById(
            'contentDrawerTitle'
        );

    const authorWrap =
        document.getElementById(
            'contentDrawerAuthorWrap'
        );

    const drawerAuthor =
        document.getElementById(
            'contentDrawerAuthor'
        );

    const drawerDate =
        document.getElementById(
            'contentDrawerDate'
        );

    const locationWrap =
        document.getElementById(
            'contentDrawerLocationWrap'
        );

    const drawerLocation =
        document.getElementById(
            'contentDrawerLocation'
        );

    const drawerMedia =
        document.getElementById(
            'contentDrawerMedia'
        );

    const drawerImage =
        document.getElementById(
            'contentDrawerImage'
        );

    const drawerContent =
        document.getElementById(
            'contentDrawerContent'
        );

    const interactionSection =
        document.getElementById(
            'announcementInteraction'
        );

    const commentsSection =
        document.getElementById(
            'announcementComments'
        );

    const documentActions =
        document.getElementById(
            'documentDrawerActions'
        );

    const documentType =
        document.getElementById(
            'documentDrawerType'
        );

    const documentSize =
        document.getElementById(
            'documentDrawerSize'
        );

    const documentDownload =
        document.getElementById(
            'documentDrawerDownload'
        );

    const drawerViewCount =
        document.getElementById(
            'drawerViewCount'
        );

    const drawerReactionCount =
        document.getElementById(
            'drawerReactionCount'
        );

    const drawerCommentCount =
        document.getElementById(
            'drawerCommentCount'
        );

    const drawerAcknowledgmentCount =
        document.getElementById(
            'drawerAcknowledgmentCount'
        );

    const drawerReactionStack =
        document.getElementById(
            'drawerReactionStack'
        );

    const acknowledgmentButton =
        document.getElementById(
            'announcementAcknowledgeButton'
        );

    const reactionButtons = Array.from(
        document.querySelectorAll(
            '#reactionPicker [data-reaction]'
        )
    );

    const commentList =
        document.getElementById(
            'drawerCommentList'
        );

    const commentForm =
        document.getElementById(
            'announcementCommentForm'
        );

 const commentInput =
    document.getElementById(
        'announcementCommentInput'
    );

const replyContext =
    document.getElementById(
        'drawerReplyContext'
    );

const replyName =
    document.getElementById(
        'drawerReplyName'
    );

const cancelReplyButton =
    document.getElementById(
        'cancelDrawerReply'
    );

let activeParentCommentId =
    null;

    /* ==========================================
       DRAWER HELPERS
    ========================================== */

    let drawerReturnFocus = null;
    let inlinePost = null;

    function openDrawer() {
        if (!drawer) return;
        if (inlinePost) inlinePost.classList.remove('is-reader-open');
        inlinePost = hubItems.find(item => item.dataset.type === activeContentType && Number(item.dataset.contentId) === activeContentId) ?? null;
        if (!inlinePost) return;
        drawerReturnFocus = document.activeElement;
        inlinePost.classList.add('is-reader-open');
        const content = inlinePost.querySelector('.hub-item-content');
        const actions = content?.querySelector('.hub-item-action');
        if (content) content.insertBefore(drawer, actions ?? null);
        drawer.removeAttribute('inert');
        drawer.classList.add('active');
        drawer.setAttribute('aria-hidden', 'false');
        closeButton?.focus({ preventScroll: true });
    }

    function closeDrawer() {
        contentLoadSequence++;
        drawer?.classList.remove('is-loading');
        drawerContent?.setAttribute('aria-busy', 'false');
        drawer?.classList.remove('active');
        overlay?.classList.remove('active');

        drawer?.setAttribute(
            'aria-hidden',
            'true'
        );

        overlay?.setAttribute(
            'aria-hidden',
            'true'
        );

        inlinePost?.classList.remove('is-reader-open');
        inlinePost = null;
        if (drawerReturnFocus?.isConnected) drawerReturnFocus.focus({ preventScroll: true });
        drawer?.setAttribute('inert', '');

activeContentType =
    null;

activeContentId =
    null;

activeReaction =
    null;

resetReplyState();

if (commentInput) {
    commentInput.value =
        '';
}

clearMedia();

setActiveReaction(
    null
);

resetReactionCounts();
    }

    function clearMedia() {
        if (drawerMedia) {
            drawerMedia.hidden = true;
        }

        if (drawerImage) {
            drawerImage.onerror = null;
            drawerImage.removeAttribute('src');
            drawerImage.alt = '';
        }
    }

    function renderMedia(
        imagePath,
        altText
    ) {
        clearMedia();

        if (
            !imagePath ||
            !drawerImage ||
            !drawerMedia
        ) {
            return;
        }

        drawerImage.onerror = () => {
            clearMedia();
        };

        drawerImage.src = imagePath;
        drawerImage.alt = altText;
        drawerMedia.hidden = false;
    }

    function setDrawerType(
        label,
        iconClass
    ) {
        if (typeText) {
            typeText.textContent = label;
        }

        if (typeIcon) {
            typeIcon.className =
                iconClass;
        }
    }

    function hideSections() {
        if (interactionSection) {
            interactionSection.hidden = true;
        }

        if (commentsSection) {
            commentsSection.hidden = true;
        }

        if (documentActions) {
            documentActions.hidden = true;
        }

        if (locationWrap) {
            locationWrap.hidden = true;
        }
    }

  function applyInteractionSettings(
    settings = {}
) {
    activeInteractionSettings = {
        allow_reactions:
            Boolean(
                Number(
                    settings.allow_reactions
                    ?? 0
                )
            ),

        allow_comments:
            Boolean(
                Number(
                    settings.allow_comments
                    ?? 0
                )
            ),

        require_acknowledgment:
            Boolean(
                Number(
                    settings.require_acknowledgment
                    ?? 0
                )
            )
    };

    if (interactionSection) {
        interactionSection.hidden =
            false;
    }

    if (commentsSection) {
        commentsSection.hidden =
            false;
    }

    reactionButtons.forEach(
        (button) => {
            const disabled =
                !activeInteractionSettings
                    .allow_reactions;

            button.disabled =
                disabled;

            button.classList.toggle(
                'interaction-disabled',
                disabled
            );
        }
    );

    if (commentInput) {
        commentInput.disabled =
            !activeInteractionSettings
                .allow_comments;

        commentInput.placeholder =
            activeInteractionSettings
                .allow_comments
                ? 'Write your feedback...'
                : 'Comments are disabled for this content.';
    }

    const commentSubmit =
        commentForm?.querySelector(
            'button[type="submit"]'
        );

    if (commentSubmit) {
        commentSubmit.disabled =
            !activeInteractionSettings
                .allow_comments;
    }

    if (acknowledgmentButton) {
    const required =
        activeInteractionSettings
            .require_acknowledgment;

    acknowledgmentButton.hidden =
        !required;

    /*
     * Temporarily establish the correct state.
     * updateAcknowledgmentState() will finalize it
     * after engagement data is loaded.
     */
    acknowledgmentButton.disabled =
        !required;


        const label =
            acknowledgmentButton
                .querySelector('span');

        if (
            label &&
            !required
        ) {
            label.textContent =
                'Acknowledgment Not Required';
        }
    }
}

    function escapeHtml(value) {
        const element =
            document.createElement('div');

        element.textContent =
            String(value ?? '');

        return element.innerHTML;
    }

    function formatBytes(bytes) {
        const value =
            Number(bytes || 0);

        if (value <= 0) {
            return '0 KB';
        }

        const units = [
            'B',
            'KB',
            'MB',
            'GB'
        ];

        const index = Math.min(
            Math.floor(
                Math.log(value) /
                Math.log(1024)
            ),
            units.length - 1
        );

        const amount =
            value /
            Math.pow(1024, index);

        return `${amount.toFixed(
            index === 0 ? 0 : 1
        )} ${units[index]}`;
    }

    const newsSecurity =
    document.getElementById(
        'newsSecurity'
    );

const newsCsrfToken =
    newsSecurity?.dataset
        .csrfToken ||
    '';

    let recentPostsCleared = false;
    function recentFeedCards() {
        return hubItems.filter(card => ['announcement', 'event', 'document'].includes(card.dataset.type)
            && card.querySelector('.hub-item-content > h3'))
            .sort((first, second) => (Date.parse(second.dataset.recentDate) || 0) - (Date.parse(first.dataset.recentDate) || 0))
            .slice(0, 5);
    }
    function renderRecentPosts() {
        const list = document.getElementById('recentPostsList');
        if (!list) return;
        list.replaceChildren();
        const cards = recentPostsCleared ? [] : recentFeedCards();
        const clear = document.getElementById('clearRecentPosts');
        if (clear) clear.disabled = cards.length === 0;
        let rendered = 0;
        for (const card of cards) {
            const entry = {type: card.dataset.type, id: Number(card.dataset.contentId)};
            const title = card?.querySelector('.hub-item-content > h3');
            if (!title) continue; // Only server-authorized, currently rendered posts can supply metadata.
            const link = document.createElement('a');
            link.className = 'hub-recent-post';
            link.href = postPageUrl(entry.type, entry.id);
            const meta = document.createElement('span');
            meta.className = 'hub-recent-meta';
            meta.textContent = card.querySelector('.hub-post-identity strong')?.textContent.trim() || 'OLSHCO Digital Hub';
            const heading = document.createElement('strong');
            heading.textContent = title.textContent.trim();
            const stats = document.createElement('span');
            stats.className = 'hub-recent-stats';
            const upvotes = card.querySelector('[data-feed-vote-count="Upvote"]')?.textContent ?? card.dataset.recentUpvotes ?? '0';
            const comments = card.querySelector('[data-feed-comment-count]')?.textContent ?? card.dataset.recentComments ?? '0';
            stats.textContent = `${upvotes} upvotes / ${comments} comments`;
            const copy = document.createElement('div');
            copy.append(meta, heading, stats);
            link.append(copy);
            const media = card.querySelector('.hub-post-media img');
            if (media) {
                const thumbnail = document.createElement('img');
                thumbnail.src = media.src;
                thumbnail.alt = '';
                thumbnail.loading = 'lazy';
                link.append(thumbnail);
            }
            list.append(link);
            if (++rendered === 5) break;
        }
        if (!rendered) {
            const empty = document.createElement('p');
            empty.className = 'hub-recent-empty';
            empty.textContent = recentPostsCleared ? 'Recent posts cleared. Refresh to show them again.' : 'No recent posts available in your feed.';
            list.append(empty);
        }
    }

   async function sendRequest(
    page,
    formData
) {


    if (!newsCsrfToken) {
    throw new Error(
        'The page security token is unavailable. Refresh the page and try again.'
    );
}

formData.set(
    'csrf_token',
    newsCsrfToken
);

    const endpoint = new URL(
        'index.php',
        window.location.href
    );

    endpoint.search = '';

    endpoint.searchParams.set(
        'page',
        page
    );

    const response = await fetch(
        endpoint.toString(),
        {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With':
                    'XMLHttpRequest',
                'Accept':
                    'application/json'
            },
            credentials: 'same-origin'
        }
    );

    /*
     * Must be declared before JSON parsing.
     */
    const responseText =
        await response.text();

    let result;

    try {
        result =
            JSON.parse(responseText);
    } catch (error) {
        console.error(
            'Invalid JSON response:',
            responseText
        );

        throw new Error(
            'The server returned invalid JSON.'
        );
    }

    if (
        !response.ok ||
        !result.success
    ) {
        throw new Error(
            result.message ||
            'Request failed.'
        );
    }

    return result.data ?? {};
}

    /* ==========================================
       REACTION HELPERS
    ========================================== */

    function normalizeReactionCounts(
        source = {}
    ) {
        const breakdown =
            source.reaction_breakdown ??
            source.reactions ??
            source.counts ??
            {};

        return {
            Upvote: Number(breakdown.Upvote ?? source.upvote_count ?? 0),
            Downvote: Number(breakdown.Downvote ?? source.downvote_count ?? 0)
        };
    }

    function calculateReactionTotal(
        counts
    ) {
        return supportedReactions.reduce(
            (total, reaction) =>
                total +
                Number(
                    counts[reaction] ?? 0
                ),
            0
        );
    }

    function resetReactionCounts() {
        const emptyCounts = {
            Upvote: 0,
            Downvote: 0
        };

        renderReactionCounts(
            emptyCounts
        );
    }

    function renderReactionCounts(
        counts
    ) {
        supportedReactions.forEach(
            (reaction) => {
                const countElement =
                    document.querySelector(
                        `[data-reaction-button-count="${reaction}"]`
                    );

                if (countElement) {
                    countElement.textContent =
                        Number(
                            counts[reaction] ??
                            0
                        );
                }
            }
        );

        renderDrawerReactionStack(counts);
    }

    function renderDrawerReactionStack(
        counts
    ) {
        if (!drawerReactionStack) {
            return;
        }

        const sortedReactions =
            supportedReactions
                .map((reaction) => ({
                    reaction,
                    count: Number(
                        counts[reaction] ?? 0
                    )
                }))
                .filter(
                    (item) =>
                        item.count > 0
                )
                .sort(
                    (first, second) =>
                        second.count -
                        first.count
                )
                .slice(0, 4);

        const reactionsToDisplay =
            sortedReactions.length > 0
                ? sortedReactions
                : supportedReactions.map(
                    (reaction) => ({
                        reaction,
                        count: 0
                    })
                );

        drawerReactionStack.innerHTML =
            reactionsToDisplay
                .map(({ reaction, count }) => {
                    const config =
                        reactionConfig[reaction];

                    return `
                        <i
                            class="${config.icon} ${config.className}"
                            title="${escapeHtml(
                                `${config.label}: ${count}`
                            )}"
                        ></i>
                    `;
                })
                .join('');

        drawerReactionStack.classList.toggle(
            'empty',
            sortedReactions.length === 0
        );
    }

    function renderRowReactionStack(
    engagementRow,
    counts
) {
    if (!engagementRow) {
        return;
    }

    const stack =
        engagementRow.querySelector(
            '.hub-reaction-stack'
        );

    if (!stack) {
        return;
    }

    const sortedReactions =
        supportedReactions
            .map((reaction) => ({
                reaction,
                count: Number(
                    counts[reaction] ?? 0
                )
            }))
            .filter(
                (item) =>
                    item.count > 0
            )
            .sort(
                (first, second) =>
                    second.count -
                    first.count
            )
            .slice(0, 3);

    if (sortedReactions.length === 0) {
        stack.innerHTML = `
            <i class="fa-solid fa-arrows-up-down reaction-vote" aria-hidden="true"></i>
        `;

        stack.classList.add(
            'empty'
        );

        return;
    }

    stack.innerHTML =
        sortedReactions
            .map(({ reaction, count }) => {
                const config =
                    reactionConfig[reaction];

                return `
                    <i
                        class="${config.icon} ${config.className}"
                        title="${escapeHtml(
                            `${config.label}: ${count}`
                        )}"
                    ></i>
                `;
            })
            .join('');

    stack.classList.remove(
        'empty'
    );
}

    function setActiveReaction(reaction) {
        activeReaction =
            supportedReactions.includes(
                reaction
            )
                ? reaction
                : null;

        reactionButtons.forEach(
            (button) => {
                button.classList.toggle(
                    'active',
                    button.dataset.reaction ===
                        activeReaction
                );

                button.setAttribute(
                    'aria-pressed',
                    button.dataset.reaction ===
                        activeReaction
                        ? 'true'
                        : 'false'
                );
            }
        );
    }

    function initializeCardUserReactions() {
    document
        .querySelectorAll(
            '[data-content-type][data-engagement-id]'
        )
        .forEach((row) => {
            const reaction =
                row.dataset.userReaction || '';

            row.classList.toggle(
                'has-user-reaction',
                supportedReactions.includes(
                    reaction
                )
            );

            const indicator =
                row.querySelector(
                    '[data-user-reaction-indicator]'
                );

            if (!indicator) {
                return;
            }

            if (
                !supportedReactions.includes(
                    reaction
                )
            ) {
                indicator.hidden = true;
                indicator.innerHTML = '';
                return;
            }

            const config =
                reactionConfig[reaction];

            indicator.innerHTML = `
                <i class="${config.icon} ${config.className}"></i>
                <span>${escapeHtml(config.label)}</span>
            `;

            indicator.hidden = false;
        });
}

   function updateCardUserReaction(
    contentType,
    contentId,
    reaction
) {
    const rows =
        document.querySelectorAll(
            `[data-content-type="${contentType}"][data-engagement-id="${contentId}"]`
        );

    rows.forEach((row) => {
        const validReaction =
            supportedReactions.includes(
                reaction
            )
                ? reaction
                : '';

        row.dataset.userReaction =
            validReaction;

        row.classList.toggle(
            'has-user-reaction',
            validReaction !== ''
        );

        const indicator =
            row.querySelector(
                '[data-user-reaction-indicator]'
            );

        if (!indicator) {
            return;
        }

        if (!validReaction) {
            indicator.hidden = true;
            indicator.innerHTML = '';
            return;
        }

        const config =
            reactionConfig[validReaction];

        indicator.innerHTML = `
            <i class="${config.icon} ${config.className}"></i>
            <span>${escapeHtml(config.label)}</span>
        `;

        indicator.hidden = false;
    });
}

    function setReactionButtonsDisabled(
        disabled
    ) {
        reactionButtons.forEach(
            (button) => {
                button.disabled =
                    disabled;
            }
        );
    }

    /* ==========================================
       ENGAGEMENT COUNTS
    ========================================== */

   function updateCounts(
    engagement = {}
) {
    syncFeedVotes(activeContentType, activeContentId, engagement);

    const views =
        Number(
            engagement.view_count ??
            0
        );

    const comments =
        Number(
            engagement.comment_count ??
            0
        );

    const acknowledgments =
        Number(
            engagement.acknowledgment_count ??
            engagement.acknowledged_count ??
            0
        );

    const reactionCounts =
        normalizeReactionCounts(
            engagement
        );

    const computedReactionTotal =
        calculateReactionTotal(
            reactionCounts
        );

    const reactionTotal =
        Number(
            engagement.reaction_count ??
            engagement.total_reactions ??
            computedReactionTotal
        );

    if (drawerViewCount) {
        drawerViewCount.textContent =
            views;
    }

    if (drawerReactionCount) {
        drawerReactionCount.textContent =
            reactionTotal;
    }

    if (drawerCommentCount) {
        drawerCommentCount.textContent =
            comments;
    }

    if (drawerAcknowledgmentCount) {
        drawerAcknowledgmentCount.textContent =
            acknowledgments;
    }

    renderReactionCounts(
        reactionCounts
    );

    if (
        !activeContentType ||
        !activeContentId
    ) {
        return;
    }

    const engagementRows =
        document.querySelectorAll(
            `[data-content-type="${activeContentType}"][data-engagement-id="${activeContentId}"]`
        );

    engagementRows.forEach(
        (engagementRow) => {
            const rowViews =
                engagementRow.querySelector(
                    '[data-view-count]'
                );

            const rowReactions =
                engagementRow.querySelector(
                    '[data-reaction-count]'
                );

            const rowComments =
                engagementRow.querySelector(
                    '[data-comment-count]'
                );

            const rowAcknowledgments =
                engagementRow.querySelector(
                    '[data-acknowledgment-count]'
                );

            const acknowledgmentLabel =
                engagementRow.querySelector(
                    '[data-acknowledgment-label]'
                );

            if (rowViews) {
                rowViews.textContent =
                    views;
            }

            if (rowReactions) {
                rowReactions.textContent =
                    reactionTotal;
            }

            if (rowComments) {
                rowComments.textContent =
                    comments;
            }

            if (rowAcknowledgments) {
                rowAcknowledgments.textContent =
                    acknowledgments;
            }

            if (acknowledgmentLabel) {
                acknowledgmentLabel.textContent =
                    engagement.user_acknowledged
                        ? 'Acknowledged'
                        : 'Acknowledgments';
            }

            engagementRow.classList.toggle(
                'acknowledged',
                Boolean(
                    engagement.user_acknowledged
                )
            );

            renderRowReactionStack(
                engagementRow,
                reactionCounts
            );
        }
    );
}

    /* ==========================================
       COMMENTS
    ========================================== */

    function resetReplyState() {
    activeParentCommentId =
        null;

    if (replyContext) {
        replyContext.hidden =
            true;
    }

    if (replyName) {
        replyName.textContent =
            '';
    }

    if (commentInput) {
        commentInput.placeholder =
            'Write your feedback...';
    }
}

function beginReply(
    commentId,
    userName
) {
    const normalizedCommentId =
        Number(commentId);

    if (
        !Number.isInteger(
            normalizedCommentId
        ) ||
        normalizedCommentId <= 0
    ) {
        return;
    }

    activeParentCommentId =
        normalizedCommentId;

    if (replyName) {
        replyName.textContent =
            userName || 'User';
    }

    if (replyContext) {
        replyContext.hidden =
            false;
    }

    if (commentInput) {
        commentInput.placeholder =
            `Reply to ${
                userName || 'User'
            }...`;

        commentInput.focus();
    }
}


function renderComments(
    comments = []
) {
    if (!commentList) {
        return;
    }

    if (
        !Array.isArray(comments) ||
        comments.length === 0
    ) {
        commentList.innerHTML = `
            <div class="drawer-comments-empty">
                No comments yet.
            </div>
        `;

        return;
    }

    const activeCommentIds =
        new Set(
            comments.map(
                (comment) =>
                    Number(
                        comment.comment_id
                            ?? 0
                    )
            )
        );

    const commentsByParent =
        new Map();

    comments.forEach((comment) => {
        const submittedParentId =
            Number(
                comment.parent_comment_id
                    ?? 0
            );

        const parentId =
            submittedParentId > 0 &&
            activeCommentIds.has(
                submittedParentId
            )
                ? submittedParentId
                : 0;

        if (
            !commentsByParent.has(
                parentId
            )
        ) {
            commentsByParent.set(
                parentId,
                []
            );
        }

        commentsByParent
            .get(parentId)
            .push(comment);
    });

    function renderCommentCard(
        comment,
        isReply = false
    ) {
        const commentId =
            Number(
                comment.comment_id
                    ?? 0
            );

        const userName =
            escapeHtml(
                comment.user_name ||
                'User'
            );

        const role =
            escapeHtml(
                comment.role_prefix ||
                comment.role_name ||
                ''
            );

        const text =
            escapeHtml(
                comment.comment || ''
            );

        const date =
            escapeHtml(
                formatDate(
                    comment.created_at,
                    true
                )
            );

const initials =
    userName
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(
            (namePart) =>
                namePart
                    .charAt(0)
                    .toUpperCase()
        )
        .join('') ||
    'U';

const profilePhoto =
    String(
        comment.profile_photo ??
        ''
    ).trim();

const avatarMarkup =
    profilePhoto !== ''
        ? `
            <span class="drawer-comment-avatar-shell">
                <img
                    src="${escapeHtml(
                        profilePhoto
                    )}"
                    class="drawer-comment-avatar"
                    alt="${userName}"
                    onerror="
                        this.hidden=true;
                        this.nextElementSibling.hidden=false;
                    "
                >

                <span
                    class="drawer-comment-avatar-fallback"
                    hidden>
                    ${escapeHtml(initials)}
                </span>
            </span>
        `
        : `
            <span class="drawer-comment-avatar-shell">
                <span class="drawer-comment-avatar-fallback">
                    ${escapeHtml(initials)}
                </span>
            </span>
        `;

        const replyButton =
            commentId > 0
                ? `
                    <button
                        type="button"
                        class="drawer-comment-reply-button"
                        data-reply-comment-id="${commentId}"
                        data-reply-user-name="${userName}">
                        <i
                            class="fa-solid fa-reply"
                            aria-hidden="true"></i>
                        Reply
                    </button>
                `
                : '';

        return `
            <article class="drawer-comment${
                isReply
                    ? ' is-reply'
                    : ''
            }">
                <div class="drawer-comment-header">
                    <div class="drawer-comment-user">
${avatarMarkup}

                        <div>
                            <strong>${userName}</strong>
                            <small>${role}</small>
                        </div>
                    </div>

                    <time class="drawer-comment-time">
                        ${date}
                    </time>
                </div>

                <p>${text}</p>

                ${replyButton}
            </article>
        `;
    }

    function renderThread(
        comment,
        depth = 0,
        ancestorIds = new Set()
    ) {
        const commentId =
            Number(
                comment.comment_id
                    ?? 0
            );

        if (
            commentId <= 0 ||
            ancestorIds.has(
                commentId
            )
        ) {
            return '';
        }

        const nextAncestorIds =
            new Set(
                ancestorIds
            );

        nextAncestorIds.add(
            commentId
        );

        const childReplies =
            commentsByParent.get(
                commentId
            ) ?? [];

        const renderedReplies =
            childReplies
                .map(
                    (reply) =>
                        renderThread(
                            reply,
                            depth + 1,
                            nextAncestorIds
                        )
                )
                .join('');

        return `
            <div
                class="drawer-comment-thread"
                data-comment-depth="${depth}">

                ${renderCommentCard(
                    comment,
                    depth > 0
                )}

                ${
                    renderedReplies !== ''
                        ? `
                            <div class="drawer-comment-replies">
                                ${renderedReplies}
                            </div>
                        `
                        : ''
                }

            </div>
        `;
    }

    const rootComments =
        commentsByParent.get(0)
        ?? [];

    commentList.innerHTML =
        rootComments
            .map(
                (comment) =>
                    renderThread(
                        comment
                    )
            )
            .join('');
}


commentList?.addEventListener(
    'click',
    (event) => {
        const replyButton =
            event.target.closest(
                '[data-reply-comment-id]'
            );

        if (!replyButton) {
            return;
        }

        event.preventDefault();

        beginReply(
            replyButton.dataset
                .replyCommentId,
            replyButton.dataset
                .replyUserName ||
                'User'
        );
    }
);

cancelReplyButton?.addEventListener(
    'click',
    () => {
        resetReplyState();

        commentInput?.focus();
    }
);

    /* ==========================================
       ACKNOWLEDGMENT STATE
    ========================================== */

function updateAcknowledgmentState(
    engagement = {}
) {
    if (!acknowledgmentButton) {
        return;
    }

    const required =
        Boolean(
            activeInteractionSettings
                .require_acknowledgment
        );

    const acknowledged =
        Boolean(
            engagement.user_acknowledged ??
            false
        );

    acknowledgmentButton.hidden =
        !required;

    acknowledgmentButton.disabled =
        !required ||
        acknowledged;

    acknowledgmentButton.classList.toggle(
        'acknowledged',
        acknowledged
    );

    const label =
        acknowledgmentButton.querySelector(
            'span'
        );

    if (!label) {
        return;
    }

    if (!required) {
        label.textContent =
            'Acknowledgment Not Required';

        return;
    }

    label.textContent =
        acknowledged
            ? 'Content Acknowledged'
            : 'Acknowledge Content';
}



/* ==========================================
   SAFE RICH CONTENT RENDERER
========================================== */

function renderRichContent(
    element,
    html,
    fallback
) {
    if (!element) {
        return;
    }

    const source =
        String(html || '').trim();

    if (source === '') {
        element.textContent =
            fallback;

        return;
    }

    const template =
        document.createElement(
            'template'
        );

    template.innerHTML =
        source;


    /* ======================================
       NORMALIZE COMMON BROWSER TAGS
    ====================================== */

    template.content
        .querySelectorAll('b')
        .forEach((bold) => {
            const strong =
                document.createElement(
                    'strong'
                );

            while (bold.firstChild) {
                strong.appendChild(
                    bold.firstChild
                );
            }

            bold.replaceWith(
                strong
            );
        });

    template.content
        .querySelectorAll('i')
        .forEach((italic) => {
            const emphasis =
                document.createElement(
                    'em'
                );

            while (italic.firstChild) {
                emphasis.appendChild(
                    italic.firstChild
                );
            }

            italic.replaceWith(
                emphasis
            );
        });


    /* ======================================
       ALLOWED RICH-TEXT ELEMENTS
    ====================================== */

    const allowedTags =
        new Set([
            'P',
            'BR',
            'STRONG',
            'EM',
            'U',
            'UL',
            'OL',
            'LI',
            'A'
        ]);


    const cleanNode =
        (node) => {
            const children =
                Array.from(
                    node.childNodes
                );

            children.forEach(
                (child) => {
                    if (
                        child.nodeType !==
                        Node.ELEMENT_NODE
                    ) {
                        return;
                    }

                    if (
                        !allowedTags.has(
                            child.tagName
                        )
                    ) {
                        cleanNode(child);

                        while (
                            child.firstChild
                        ) {
                            child.parentNode
                                ?.insertBefore(
                                    child.firstChild,
                                    child
                                );
                        }

                        child.remove();

                        return;
                    }


                    /* ==========================
                       SAFE LINKS
                    ========================== */

                    let safeHref = '';

                    if (
                        child.tagName === 'A'
                    ) {
                        const href =
                            child
                                .getAttribute(
                                    'href'
                                )
                                ?.trim() ||
                            '';

                        if (
                            /^(https?:\/\/|mailto:)/i
                                .test(href)
                        ) {
                            safeHref =
                                href;
                        }
                    }


                    /*
                     * Strip all attributes.
                     */
                    Array
                        .from(
                            child.attributes
                        )
                        .forEach(
                            (attribute) => {
                                child
                                    .removeAttribute(
                                        attribute.name
                                    );
                            }
                        );


                    if (
                        child.tagName ===
                            'A' &&
                        safeHref
                    ) {
                        child.setAttribute(
                            'href',
                            safeHref
                        );

                        child.setAttribute(
                            'target',
                            '_blank'
                        );

                        child.setAttribute(
                            'rel',
                            'noopener noreferrer'
                        );
                    }

                    cleanNode(
                        child
                    );
                }
            );
        };

    cleanNode(
        template.content
    );

    element.replaceChildren(
        template.content.cloneNode(
            true
        )
    );
}


/* ==========================================
   ANNOUNCEMENT AUDIO RENDERER
========================================== */

function renderAnnouncementAudio(
    element,
    content
) {
    if (!element) {
        return;
    }

    const audioPath =
        String(
            content?.audio_path ||
            ''
        ).trim();

    if (
        !/^Assets\/uploads\/announcement-audio\/[a-zA-Z0-9._-]+$/
            .test(
                audioPath
            )
    ) {
        return;
    }

    const container =
        document.createElement(
            'section'
        );

    container.className =
        'hub-drawer-audio';

    const heading =
        document.createElement(
            'div'
        );

    heading.className =
        'hub-drawer-audio-heading';

    const icon =
        document.createElement(
            'span'
        );

    icon.innerHTML =
        '<i class="fa-solid fa-volume-high" aria-hidden="true"></i>';

    const copy =
        document.createElement(
            'div'
        );

    const title =
        document.createElement(
            'strong'
        );

    title.textContent =
        'Audio Broadcast';

    const metadata =
        document.createElement(
            'small'
        );

    const fileName =
        String(
            content.audio_file_name ||
            'Announcement audio'
        ).trim();

    const fileSize =
        Number(
            content.audio_file_size ||
            0
        );

    metadata.textContent =
        fileSize > 0
            ? `${fileName} • ${formatBytes(fileSize)}`
            : fileName;

    copy.append(
        title,
        metadata
    );

    heading.append(
        icon,
        copy
    );

    const audio =
        document.createElement(
            'audio'
        );

    audio.preload =
        'metadata';

    audio.src =
        audioPath;

    audio.setAttribute(
        'aria-label',
        `Audio broadcast: ${fileName}`
    );

    const player =
        document.createElement(
            'div'
        );

    player.className =
        'hub-audio-player';

    player.innerHTML = `
        <button
            type="button"
            class="hub-audio-play"
            aria-label="Play audio broadcast">

            <i
                class="fa-solid fa-play"
                aria-hidden="true"></i>

        </button>

        <div class="hub-audio-timeline">

            <input
                type="range"
                class="hub-audio-progress"
                min="0"
                max="100"
                value="0"
                step="0.1"
                aria-label="Audio playback position">

            <div class="hub-audio-time">
                <span data-audio-current>0:00</span>
                <span data-audio-duration>0:00</span>
            </div>

        </div>

        <a
            class="hub-audio-download"
            aria-label="Download audio broadcast"
            title="Download audio">

            <i
                class="fa-solid fa-download"
                aria-hidden="true"></i>

        </a>
    `;

    const playButton =
        player.querySelector(
            '.hub-audio-play'
        );

    const playIcon =
        playButton?.querySelector(
            'i'
        );

    const progress =
        player.querySelector(
            '.hub-audio-progress'
        );

    const currentTime =
        player.querySelector(
            '[data-audio-current]'
        );

    const duration =
        player.querySelector(
            '[data-audio-duration]'
        );

    const download =
        player.querySelector(
            '.hub-audio-download'
        );

    if (download) {
        download.href =
            audioPath;

        download.download =
            fileName;
    }

    const formatAudioTime =
        (seconds) => {
            if (
                !Number.isFinite(seconds) ||
                seconds < 0
            ) {
                return '0:00';
            }

            const minutes =
                Math.floor(
                    seconds / 60
                );

            const remainingSeconds =
                Math.floor(
                    seconds % 60
                );

            return `${minutes}:${
                String(
                    remainingSeconds
                ).padStart(
                    2,
                    '0'
                )
            }`;
        };

    playButton?.addEventListener(
        'click',
        async () => {
            if (audio.paused) {
                try {
                    await audio.play();
                } catch (error) {
                    console.error(
                        'Unable to play announcement audio.',
                        error
                    );
                }

                return;
            }

            audio.pause();
        }
    );

    audio.addEventListener(
        'play',
        () => {
            if (playIcon) {
                playIcon.className =
                    'fa-solid fa-pause';
            }

            playButton?.setAttribute(
                'aria-label',
                'Pause audio broadcast'
            );

            player.classList.add(
                'is-playing'
            );
        }
    );

    audio.addEventListener(
        'pause',
        () => {
            if (playIcon) {
                playIcon.className =
                    'fa-solid fa-play';
            }

            playButton?.setAttribute(
                'aria-label',
                'Play audio broadcast'
            );

            player.classList.remove(
                'is-playing'
            );
        }
    );

    audio.addEventListener(
        'loadedmetadata',
        () => {
            if (duration) {
                duration.textContent =
                    formatAudioTime(
                        audio.duration
                    );
            }
        }
    );

    audio.addEventListener(
        'timeupdate',
        () => {
            const percentage =
                audio.duration > 0
                    ? (
                        audio.currentTime /
                        audio.duration
                    ) * 100
                    : 0;

            if (progress) {
                progress.value =
                    String(percentage);

                progress.style.setProperty(
                    '--audio-progress',
                    `${percentage}%`
                );
            }

            if (currentTime) {
                currentTime.textContent =
                    formatAudioTime(
                        audio.currentTime
                    );
            }
        }
    );

    progress?.addEventListener(
        'input',
        () => {
            if (audio.duration > 0) {
                audio.currentTime =
                    (
                        Number(
                            progress.value
                        ) / 100
                    ) * audio.duration;
            }
        }
    );

    audio.addEventListener(
        'ended',
        () => {
            audio.currentTime =
                0;
        }
    );

    container.append(
        heading,
        player,
        audio
    );

    const transcript =
        String(
            content.audio_transcript ||
            ''
        ).trim();

    if (transcript !== '') {
        const details =
            document.createElement(
                'details'
            );

        details.className =
            'hub-drawer-audio-transcript';

        const summary =
            document.createElement(
                'summary'
            );

        summary.textContent =
            'Read audio transcript';

        const transcriptText =
            document.createElement(
                'p'
            );

        transcriptText.textContent =
            transcript;

        details.append(
            summary,
            transcriptText
        );

        container.appendChild(
            details
        );
    }

    element.appendChild(
        container
    );
}


    /* ==========================================
       OPEN ANNOUNCEMENT
    ========================================== */

function markHubItemViewed(
    contentType,
    contentId
) {
    const normalizedType =
        String(
            contentType ?? ''
        )
            .trim()
            .toLowerCase();

    const normalizedId =
        String(
            contentId ?? ''
        );

    const item =
        hubItems.find(
            (candidate) =>
                (
                    candidate.dataset.type ??
                    ''
                ) === normalizedType &&
                (
                    candidate.dataset
                        .contentId ??
                    ''
                ) === normalizedId
        );

    if (!item) {
        return;
    }

    const unreadBadge =
        item.querySelector(
            '.hub-unread-badge'
        );

    /*
     * No badge means this card was already
     * viewed and must not lose points again.
     */
    if (!unreadBadge) {
        return;
    }

    unreadBadge.remove();

    const currentScore =
        Number(
            item.dataset
                .rankingScore ??
            0
        );

    /*
     * Transition:
     * unread +120 becomes viewed -40.
     * Net score change is -160.
     */
    item.dataset.rankingScore =
        String(
            currentScore - 160
        );

    const reasons =
        String(
            item.dataset
                .rankingReason ??
            ''
        )
            .split('•')
            .map(
                (reason) =>
                    reason.trim()
            )
            .filter(
                (reason) =>
                    reason !== '' &&
                    reason.toLowerCase() !==
                        'not viewed yet'
            );

    item.dataset.rankingReason =
        reasons.join(' • ');


}

async function openContent(
    contentType,
    contentId
) {
    activeContentType =
        String(contentType || '')
            .trim()
            .toLowerCase();

    activeContentId =
        Number(contentId);

    activeReaction = null;

    const typeConfig = {
        announcement: {
            label: 'Announcement',
            icon: 'fa-solid fa-bullhorn',
            loadingText:
                'Loading announcement...'
        },

        event: {
            label: 'Event',
            icon: 'fa-regular fa-calendar',
            loadingText:
                'Loading event...'
        },

        document: {
            label: 'Document',
            icon: 'fa-regular fa-file-lines',
            loadingText:
                'Loading document...'
        }
    };

    const config =
        typeConfig[activeContentType];

    if (
        !config ||
        !activeContentId
    ) {
        window.alert(
            'Invalid content selection.'
        );

        return;
    }

    const loadSequence = ++contentLoadSequence;
    drawer?.classList.add('is-loading');
    drawerContent?.setAttribute('aria-busy', 'true');
    if (drawerAuthor) drawerAuthor.textContent = '';
    if (drawerDate) drawerDate.textContent = '';

    setDrawerType(
        config.label,
        config.icon
    );

    hideSections();
    clearMedia();
    resetReactionCounts();
    setActiveReaction(null);
    openDrawer();

    if (drawerTitle) {
        drawerTitle.textContent =
            config.label;
    }

    if (drawerContent) {
        drawerContent.innerHTML = `
            <div class="drawer-loading" role="status">
                <span class="drawer-loading-label">${config.loadingText}</span>
                <div class="drawer-skeleton" aria-hidden="true">
                    <span class="drawer-skeleton-line drawer-skeleton-heading"></span>
                    <span class="drawer-skeleton-line"></span>
                    <span class="drawer-skeleton-line"></span>
                    <span class="drawer-skeleton-line drawer-skeleton-short"></span>
                </div>
            </div>
        `;
    }

const formData =
    new FormData();

formData.append(
    'content_type',
    activeContentType
);

formData.append(
    'content_id',
    String(activeContentId)
);

try {
    const data =
        await sendRequest(
            'content_open',
            formData
        );

    if (loadSequence !== contentLoadSequence) return;

    const content =
        data.content ?? {};

        const engagement =
            data.engagement ?? {};

        if (engagement.user_viewed) {
    markHubItemViewed(
        activeContentType,
        activeContentId
    );
}

        const comments =
            data.comments ?? [];

        const settings =
            data.settings ?? {};

        applyInteractionSettings(
            settings
        );

        if (authorWrap) {
            authorWrap.hidden = false;
        }

        if (
            activeContentType ===
            'announcement'
        ) {
            if (drawerTitle) {
                drawerTitle.textContent =
                    content.title ||
                    'Announcement';
            }

            if (drawerAuthor) {
                drawerAuthor.textContent =
                    content.author_name?.trim() ||
                    'School Administrator';
            }

            if (drawerDate) {
                drawerDate.textContent =
                    formatDate(
                        content.published_at ||
                        content.created_at,
                        true
                    ) ||
                    'Date unavailable';
            }

            if (locationWrap) {
                locationWrap.hidden = true;
            }

            renderMedia(
                content.image_path || '',
                `${
                    content.title ||
                    'Announcement'
                } pubmat`
            );

            renderRichContent(
                drawerContent,
                content.content,
                'No announcement content available.'
            );

            if (content.government_source_url && drawerContent) {
                try {
                    const source = new URL(content.government_source_url);
                    if (source.protocol === 'https:' && !source.username && !source.password) {
                        const attachment = document.createElement('div');
                        attachment.className = 'hub-government-source';
                        const label = document.createElement('span');
                        label.className = 'hub-government-badge';
                        label.textContent = 'Government advisory';
                        const link = document.createElement('a');
                        link.href = source.href;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = 'View official source (new tab)';
                        attachment.append(label, link);
                        drawerContent.append(attachment);
                    }
                } catch (_) { /* Invalid stored URLs are not rendered as links. */ }
            }

            renderAnnouncementAudio(
                drawerContent,
                content
            );

            if (documentActions) {
                documentActions.hidden = true;
            }
        }

        if (
            activeContentType ===
            'event'
        ) {
            if (drawerTitle) {
                drawerTitle.textContent =
                    content.title ||
                    'Event';
            }

            if (drawerAuthor) {
                drawerAuthor.textContent =
                    content.author_name?.trim() ||
                    'School Administrator';
            }

            if (drawerDate) {
                const startText =
                    formatDate(
                        content.event_date,
                        true
                    );

                const endText =
                    formatDate(
                        content.end_date,
                        true
                    );

                drawerDate.textContent =
                    endText
                        ? `${startText} – ${endText}`
                        : startText ||
                          'Schedule unavailable';
            }

            if (
                locationWrap &&
                drawerLocation
            ) {
                const location =
                    content.location || '';

                locationWrap.hidden =
                    location === '';

                drawerLocation.textContent =
                    location;
            }

            renderMedia(
                content.image_path || '',
                `${
                    content.title ||
                    'Event'
                } event poster`
            );

           renderRichContent(
    drawerContent,
    content.description,
    'No event description available.'
);

            if (documentActions) {
                documentActions.hidden = true;
            }
        }

        if (
            activeContentType ===
            'document'
        ) {
            if (drawerTitle) {
                drawerTitle.textContent =
                    content.file_name ||
                    'Document';
            }

            if (drawerAuthor) {
                drawerAuthor.textContent =
                    content.author_name?.trim() ||
                    'School Administrator';
            }

            if (drawerDate) {
                drawerDate.textContent =
                    formatDate(
                        content.created_at,
                        true
                    ) ||
                    'Upload date unavailable';
            }

            if (locationWrap) {
                locationWrap.hidden = true;
            }

            renderMedia(
                content.cover_image_path || '',
                `${
                    content.file_name ||
                    'Document'
                } cover`
            );

            if (drawerContent) {
                drawerContent.textContent =
                    'Review the document details, then download the file when ready.';
            }

            if (documentActions) {
                documentActions.hidden =
                    false;
            }

            if (documentType) {
                documentType.textContent =
                    String(
                        content.file_type ||
                        'FILE'
                    ).toUpperCase();
            }

            if (documentSize) {
                documentSize.textContent =
                    formatBytes(
                        content.file_size
                    );
            }

            if (documentDownload) {
                documentDownload.href =
                    content.file_path ||
                    '#';

                documentDownload.download =
                    content.file_name ||
                    'document';
            }
        }

        updateCounts(
            engagement
        );

        setActiveReaction(
            engagement.user_reaction ??
            null
        );

        updateCardUserReaction(
    activeContentType,
    activeContentId,
    engagement.user_reaction ??
        null
);

        updateAcknowledgmentState(
            engagement
        );

        renderComments(
            comments
        );
    } catch (error) {
        if (loadSequence !== contentLoadSequence) return;
        hideSections();
        clearMedia();
        console.error(error);

        if (drawerTitle) {
            drawerTitle.textContent =
                'Unable to load content';
        }

        if (drawerContent) {
            drawerContent.textContent =
                error.message;
        }
    } finally {
        if (loadSequence === contentLoadSequence) {
            drawer?.classList.remove('is-loading');
            drawerContent?.setAttribute('aria-busy', 'false');
        }
    }
}

    /* ==========================================
       CONTENT OPEN LISTENERS
    ========================================== */

    document
    .querySelectorAll(
        '[data-open-announcement]'
    )
    .forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                openPostTab(
                    'announcement',
                    button.dataset
                        .announcementId
                );
            }
        );
    });

document
    .querySelectorAll(
        '[data-open-event]'
    )
    .forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                openPostTab(
                    'event',
                    button.dataset
                        .eventId
                );
            }
        );
    });

document
    .querySelectorAll(
        '[data-open-document]'
    )
    .forEach((button) => {
        button.addEventListener(
            'click',
            () => {
                openPostTab(
                    'document',
                    button.dataset
                        .documentId
                );
            }
        );
    });


    /* ==========================================
   AUTHORIZED HUB DEEP LINK
========================================== */

function openRequestedHubItem() {
    const parameters =
        new URLSearchParams(
            window.location.search
        );

    const requestedType = (
        parameters.get('open_type')
        ?? ''
    )
        .trim()
        .toLowerCase();

    const requestedId =
        Number(
            parameters.get('open_id')
            ?? 0
        );

    const allowedTypes = [
        'announcement',
        'event',
        'document',
        'survey'
    ];

    if (
        !allowedTypes.includes(
            requestedType
        ) ||
        !Number.isInteger(
            requestedId
        ) ||
        requestedId <= 0
    ) {
        return;
    }

    /*
     * Search only the cards rendered by the
     * server. Therefore, content excluded by
     * recipient filtering cannot be opened
     * through a manually edited URL.
     */
    const requestedCard =
        hubItems.find((item) => {
            return (
                (
                    item.dataset.type
                    ?? ''
                ) === requestedType &&
                Number(
                    item.dataset.contentId
                    ?? 0
                ) === requestedId
            );
        });

    if (!requestedCard) {
        console.warn(
            'The requested content is unavailable or not authorized.'
        );

        return;
    }

    requestedCard.classList.remove(
        'is-hidden'
    );

    requestedCard.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });

    let openElement = null;

    if (
        requestedType ===
        'announcement'
    ) {
        openElement =
            requestedCard.querySelector(
                '[data-open-announcement]'
            );
    } else if (
        requestedType ===
        'event'
    ) {
        openElement =
            requestedCard.querySelector(
                '[data-open-event]'
            );
    } else if (
        requestedType ===
        'document'
    ) {
        openElement =
            requestedCard.querySelector(
                '[data-open-document]'
            );
    } else if (
        requestedType ===
        'survey'
    ) {
        openElement =
            requestedCard.querySelector(
                'a[href*="page=survey_participate"]'
            );
    }

    if (!openElement) {
        return;
    }

    // Notification links navigate directly without opening an automatic popup.
    if (requestedType === 'survey') {
        window.location.assign(openElement.href);
    } else {
        window.location.assign(postPageUrl(requestedType, requestedId));
    }

}

openRequestedHubItem();

    // Feed actions use the same authorized engagement endpoint as the reader.
    function syncFeedVotes(contentType, contentId, engagement) {
        const counts = normalizeReactionCounts(engagement);
        document.querySelectorAll('[data-feed-comment]').forEach(button => {
            if (button.dataset.commentType !== contentType || Number(button.dataset.commentId) !== Number(contentId)) return;
            const count = button.querySelector('[data-feed-comment-count]');
            if (count) count.textContent = engagement.comment_count ?? 0;
        });
        document.querySelectorAll('[data-feed-votes]').forEach(group => {
            if (group.dataset.voteType !== contentType || Number(group.dataset.voteId) !== Number(contentId)) return;
            group.querySelectorAll('[data-feed-vote]').forEach(button => {
                button.setAttribute('aria-pressed', String(button.dataset.feedVote === engagement.user_reaction));
                const count = button.querySelector('[data-feed-vote-count]');
                if (count) count.textContent = counts[button.dataset.feedVote] ?? 0;
            });
        });
    }
    document.querySelectorAll('[data-feed-votes]').forEach(group => {
        group.querySelectorAll('[data-feed-vote]').forEach(button => {
            button.addEventListener('click', async () => {
                if (group.dataset.pending === 'true') return;
                const type = group.dataset.voteType;
                const id = Number(group.dataset.voteId);
                const buttons = group.querySelectorAll('[data-feed-vote]');
                group.dataset.pending = 'true';
                group.setAttribute('aria-busy', 'true');
                buttons.forEach(control => { control.disabled = true; });
                const form = new FormData();
                form.append('content_type', type);
                form.append('content_id', String(id));
                form.append('reaction', button.dataset.feedVote);
                try {
                    const data = await sendRequest('content_react', form);
                    const engagement = data.engagement ?? {};
                    syncFeedVotes(type, id, engagement);
                    updateCardUserReaction(type, id, engagement.user_reaction ?? null);
                    document.querySelectorAll(`[data-content-type="${type}"][data-engagement-id="${id}"]`).forEach(row => {
                        const count = row.querySelector('[data-reaction-count]');
                        if (count) count.textContent = engagement.reaction_count ?? 0;
                        renderRowReactionStack(row, normalizeReactionCounts(engagement));
                    });
                    if (activeContentType === type && activeContentId === id) {
                        updateCounts(engagement);
                        setActiveReaction(engagement.user_reaction ?? null);
                    }
                } catch (error) {
                    window.alert(error.message);
                } finally {
                    group.dataset.pending = 'false';
                    group.setAttribute('aria-busy', 'false');
                    buttons.forEach(control => { control.disabled = false; });
                }
            });
        });
    });
    function postPageUrl(type, id, discussion = false) {
        const parameters = new URLSearchParams({ page: 'content_post', content_type: type, content_id: String(id) });
        return `index.php?${parameters}${discussion ? '#discussion' : ''}`;
    }
    function openPostTab(type, id, discussion = false) {
        window.location.assign(postPageUrl(type, id, discussion));
    }
    document.querySelectorAll('[data-feed-comment]').forEach(button => {
        button.addEventListener('click', () => openPostTab(button.dataset.commentType, button.dataset.commentId, true));
    });

    function initializePostCardLinks(cards) {
        cards.forEach(card => {
            const type = card.dataset.type;
            const id = Number(card.dataset.contentId);
            if (!['announcement', 'event', 'document'].includes(type) || !Number.isInteger(id) || id <= 0) {
                return;
            }
            const title = card.querySelector('.hub-item-content > h3');
            if (!title) {
                return;
            }
            const link = document.createElement('a');
            link.className = 'hub-post-title-link';
            link.href = postPageUrl(type, id);
            link.setAttribute('aria-label', title.textContent.trim());
            while (title.firstChild) {
                link.appendChild(title.firstChild);
            }
            title.appendChild(link);
            card.classList.add('hub-item-clickable');
            card.addEventListener('click', event => {
                if (event.defaultPrevented || event.button !== 0 || event.target.closest('a, button, input, select, textarea, label, summary, details, audio, video, [contenteditable], #contentDrawer')) {
                    return;
                }
                if (window.getSelection()?.toString().trim()) {
                    return;
                }
                openPostTab(type, id);
            });
        });
    }

    /* ==========================================
   UNIFIED REACTIONS
========================================== */

reactionButtons.forEach((button) => {
    button.addEventListener(
        'click',
        async () => {
            if (
                !activeContentType ||
                !activeContentId ||
                !activeInteractionSettings
                    .allow_reactions
            ) {
                return;
            }

            const selectedReaction =
                button.dataset.reaction;

            if (
                !supportedReactions.includes(
                    selectedReaction
                )
            ) {
                return;
            }

            setReactionButtonsDisabled(
                true
            );

            const requestContentType =
                activeContentType;

            const requestContentId =
                activeContentId;



            const formData =
                new FormData();

            formData.append(
                'content_type',
                requestContentType
            );

            formData.append(
                'content_id',
                String(requestContentId)
            );

            formData.append(
                'reaction',
                selectedReaction
            );

            try {
                const data =
                    await sendRequest(
                        'content_react',
                        formData
                    );

                /*
                 * Prevent an old response from updating
                 * a newly opened content drawer.
                 */
                if (
                    activeContentType !==
                        requestContentType ||
                    activeContentId !==
                        requestContentId
                ) {
                    return;
                }

                const engagement =
                    data.engagement ??
                    data;

                updateCounts(
                    engagement
                );

                setActiveReaction(
                    data.selected_reaction ??
                    engagement.user_reaction ??
                    null
                );

                updateCardUserReaction(
    requestContentType,
    requestContentId,
    data.selected_reaction ??
        engagement.user_reaction ??
        null
);


            } catch (error) {
                console.error(error);

                window.alert(
                    error.message
                );
            } finally {
                setReactionButtonsDisabled(
                    !activeInteractionSettings
                        .allow_reactions
                );
            }
        }
    );
});

    /* ==========================================
   UNIFIED ACKNOWLEDGMENT
========================================== */

acknowledgmentButton?.addEventListener(
    'click',
    async () => {
        if (
            !activeContentType ||
            !activeContentId ||
            !activeInteractionSettings
                .require_acknowledgment ||
            acknowledgmentButton.disabled
        ) {
            return;
        }

        const requestContentType =
            activeContentType;

        const requestContentId =
            activeContentId;

        acknowledgmentButton.disabled =
            true;

        const formData =
            new FormData();

        formData.append(
            'content_type',
            requestContentType
        );

        formData.append(
            'content_id',
            String(requestContentId)
        );

        try {
            const data =
                await sendRequest(
                    'content_acknowledge',
                    formData
                );

            if (
                activeContentType !==
                    requestContentType ||
                activeContentId !==
                    requestContentId
            ) {
                return;
            }

            const engagement =
                data.engagement ??
                data;

            updateCounts(
                engagement
            );

            updateAcknowledgmentState(
                engagement
            );
        } catch (error) {
            console.error(error);

            acknowledgmentButton.disabled =
                false;

            window.alert(
                error.message
            );
        }
    }
);

/* ==========================================
   UNIFIED COMMENTS
========================================== */

commentForm?.addEventListener(
    'submit',
    async (event) => {
        event.preventDefault();

        if (
            !activeContentType ||
            !activeContentId ||
            !activeInteractionSettings
                .allow_comments ||
            !commentInput
        ) {
            return;
        }

const comment =
    commentInput.value.trim();

if (!comment) {
    commentInput.focus();
    return;
}

const requestContentType =
    activeContentType;

const requestContentId =
    activeContentId;

const requestParentCommentId =
    activeParentCommentId;

        const submitButton =
            commentForm.querySelector(
                'button[type="submit"]'
            );

        if (submitButton) {
            submitButton.disabled =
                true;
        }

        const formData =
            new FormData();

        formData.append(
            'content_type',
            requestContentType
        );

        formData.append(
            'content_id',
            String(requestContentId)
        );

        formData.append(
            'comment',
            comment
        );

        if (
    requestParentCommentId !==
    null
) {
    formData.append(
        'parent_comment_id',
        String(
            requestParentCommentId
        )
    );
}

        try {
            const data =
                await sendRequest(
                    'content_comment',
                    formData
                );

            if (
                activeContentType !==
                    requestContentType ||
                activeContentId !==
                    requestContentId
            ) {
                return;
            }

            commentInput.value = '';

            resetReplyState();

            updateCounts(
                data.engagement ??
                {}
            );

            renderComments(
                data.comments ??
                []
            );
        } catch (error) {
            console.error(error);

            window.alert(
                error.message
            );
        } finally {
            if (submitButton) {
                submitButton.disabled =
                    !activeInteractionSettings
                        .allow_comments;
            }
        }
    }
);

    closeButton?.addEventListener(
    'click',
    (event) => {
        event.preventDefault();
        event.stopPropagation();

        closeDrawer();
    }
);

overlay?.addEventListener(
    'click',
    closeDrawer
);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer?.classList.contains('active') && drawer.contains(document.activeElement)) {
        event.preventDefault();
        closeDrawer();
    }
});

    /* ==========================================
       INITIALIZE
    ========================================== */

document.querySelectorAll('[data-feed-body]').forEach(body => {
    renderRichContent(body, body.dataset.feedBody, '');
});

initializePostCardLinks(hubItems);

initializeCardUserReactions();

const restoredViewButton =
    viewButtons.find(
        (button) =>
            (
                button.dataset.view ??
                'all'
            ) === activeView
    );

if (restoredViewButton) {
    viewButtons.forEach(
        (button) => {
            button.classList.toggle(
                'active',
                button ===
                    restoredViewButton
            );
        }
    );

    if (viewTitle) {
        viewTitle.textContent =
            restoredViewButton
                .dataset.label ??
            'All Updates';
    }
}

updateResults();
const postPage = document.querySelector('[data-post-page]');
if (postPage) {
    openContent(postPage.dataset.postType, Number(postPage.dataset.postId)).then(() => {
        if (window.location.hash === '#discussion' && !commentsSection?.hidden) {
            commentsSection?.scrollIntoView({ block: 'start' });
            commentInput?.focus({ preventScroll: true });
        }
    });
}

document.getElementById('clearRecentPosts')?.addEventListener('click', () => {
    recentPostsCleared = true;
    renderRecentPosts();
});
window.addEventListener('focus', renderRecentPosts);
renderRecentPosts();

});
