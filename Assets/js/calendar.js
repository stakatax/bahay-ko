document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';



        const eventList =
            document.getElementById(
                'eventList'
            );

            const createEventLink =
    document.getElementById(
        'createCalendarEventLink'
    );

        const selectedDayNumber =
            document.querySelector(
                '.calendar-selected-date-icon strong'
            );

        const selectedMonth =
            document.querySelector(
                '.calendar-selected-date-icon small'
            );

        const selectedDateHeading =
            document.querySelector(
                '.calendar-selected-header h3'
            );

        const scrollingContainer =
            document.querySelector(
                '.event-container'
            );

        const eventMap =
            typeof EVENTS !== 'undefined' &&
            EVENTS &&
            typeof EVENTS === 'object'
                ? EVENTS
                : {};

        const holidayMap =
            typeof HOLIDAYS !== 'undefined' &&
            HOLIDAYS &&
            typeof HOLIDAYS === 'object'
                ? HOLIDAYS
                : {};

        let selectedDate =
            document.querySelector(
                '.day-cell.selected'
            )?.dataset.date ||
            null;

        /* ======================================
           HORIZONTAL EVENT SCROLLING
        ======================================= */

        scrollingContainer?.addEventListener(
            'wheel',
            (event) => {
                if (event.deltaY === 0) {
                    return;
                }

                event.preventDefault();

                scrollingContainer.scrollLeft +=
                    event.deltaY;
            },
            {
                passive: false
            }
        );

        /* ======================================
           ELEMENT HELPERS
        ======================================= */

        function createElement(
            tagName,
            className = '',
            text = ''
        ) {
            const element =
                document.createElement(
                    tagName
                );

            if (className !== '') {
                element.className =
                    className;
            }

            if (text !== '') {
                element.textContent =
                    text;
            }

            return element;
        }

        function formatDate(
            date
        ) {
            const parsedDate =
                new Date(
                    `${date}T00:00:00`
                );

            if (
                Number.isNaN(
                    parsedDate.getTime()
                )
            ) {
                return date;
            }

            return new Intl.DateTimeFormat(
                'en-PH',
                {
                    month: 'long',
                    day: '2-digit',
                    year: 'numeric'
                }
            ).format(
                parsedDate
            );
        }

        function formatHolidayType(
            holidayType
        ) {
            const labels = {
                Regular:
                    'Regular Holiday',

                SpecialNonWorking:
                    'Special Non-Working Holiday',

                SpecialWorking:
                    'Special Working Holiday',

                Local:
                    'Local Holiday',

                School:
                    'School Holiday'
            };

            return labels[holidayType] ||
                'Holiday';
        }

        function updateSelectedDateHeader(
            date
        ) {
            const parsedDate =
                new Date(
                    `${date}T00:00:00`
                );

            if (
                Number.isNaN(
                    parsedDate.getTime()
                )
            ) {
                return;
            }

            if (selectedDayNumber) {
                selectedDayNumber.textContent =
                    String(
                        parsedDate.getDate()
                    ).padStart(
                        2,
                        '0'
                    );
            }

            if (selectedMonth) {
                selectedMonth.textContent =
                    new Intl.DateTimeFormat(
                        'en-PH',
                        {
                            month: 'short'
                        }
                    )
                    .format(parsedDate)
                    .toUpperCase();
            }

            if (selectedDateHeading) {
                selectedDateHeading.textContent =
                    formatDate(
                        date
                    );
            }
        }

        /* ======================================
           SELECTED-DATE CARDS
        ======================================= */

        function createHolidayCard(
            holiday
        ) {
const holidayTypeClasses = {
    Regular:
        'holiday-regular',

    SpecialNonWorking:
        'holiday-special-non-working',

    SpecialWorking:
        'holiday-special-working',

    Local:
        'holiday-local',

    School:
        'holiday-school'
};

const holidayTypeClass =
    holidayTypeClasses[
        holiday.holiday_type
    ] ||
    'holiday-other';

const article =
    createElement(
        'article',
        `calendar-selected-event calendar-selected-holiday ${holidayTypeClass}`
    );

            const icon =
                createElement(
                    'span',
                    'calendar-selected-event-icon calendar-selected-holiday-icon'
                );

            const iconElement =
                createElement(
                    'i',
                    'fa-solid fa-star'
                );

            icon.appendChild(
                iconElement
            );

            const content =
                createElement(
                    'div'
                );

content.appendChild(
    createElement(
        'span',
        'calendar-selected-event-type',
        formatHolidayType(
            holiday.holiday_type
        )
    )
);


content.appendChild(
    createElement(
        'h4',
        '',
        holiday.title ||
            'Philippine Holiday'
    )
);

            if (holiday.description) {
                content.appendChild(
                    createElement(
                        'p',
                        '',
                        holiday.description
                    )
                );
            }

const reference =
    holiday.proclamation_reference ||
    '';

            if (reference !== '') {
                const referenceElement =
                    createElement(
                        'small'
                    );

                referenceElement.appendChild(
                    createElement(
                        'i',
                        'fa-solid fa-landmark'
                    )
                );

                referenceElement.append(
                    document.createTextNode(
                        ` ${reference}`
                    )
                );

                content.appendChild(
                    referenceElement
                );
            }

            article.append(
                icon,
                content
            );

            return article;
        }

        function createEventCard(
            event
        ) {
            const article =
                createElement(
                    'article',
                    'calendar-selected-event'
                );

            const icon =
                createElement(
                    'span',
                    'calendar-selected-event-icon'
                );

            icon.appendChild(
                createElement(
                    'i',
                    'fa-solid fa-calendar-check'
                )
            );

            const content =
                createElement(
                    'div'
                );

            content.appendChild(
                createElement(
                    'span',
                    'calendar-selected-event-type',
                    event.type ||
                        'School Event'
                )
            );

            content.appendChild(
                createElement(
                    'h4',
                    '',
                    event.title ||
                        'Untitled Event'
                )
            );

            if (event.description) {
                content.appendChild(
                    createElement(
                        'p',
                        '',
                        event.description
                    )
                );
            }

            if (event.location) {
                const location =
                    createElement(
                        'small'
                    );

                location.appendChild(
                    createElement(
                        'i',
                        'fa-solid fa-location-dot'
                    )
                );

                location.append(
                    document.createTextNode(
                        ` ${event.location}`
                    )
                );

                content.appendChild(
                    location
                );
            }

            article.append(
                icon,
                content
            );

            return article;
        }

        function createEmptyState() {
            const emptyState =
                createElement(
                    'div',
                    'calendar-no-date-events'
                );

            const icon =
                createElement(
                    'span'
                );

            icon.appendChild(
                createElement(
                    'i',
                    'fa-regular fa-calendar'
                )
            );

            emptyState.append(
                icon,
                createElement(
                    'h4',
                    '',
                    'No scheduled activities'
                ),
                createElement(
                    'p',
                    '',
                    'No events or holidays are listed for this date.'
                )
            );

            return emptyState;
        }

function updateCreateEventLink(
    date
) {
    if (
        !createEventLink ||
        !date
    ) {
        return;
    }

    const currentDate =
        createEventLink.dataset
            .currentDate ||
        '';

    const isPastDate =
        currentDate !== '' &&
        date < currentDate;

    const linkText =
    createEventLink.querySelector(
        'span'
    );

if (linkText) {
    linkText.textContent =
        isPastDate
            ? 'Past Date'
            : 'Create Event';
}

    createEventLink.classList.toggle(
        'is-disabled',
        isPastDate
    );

    createEventLink.setAttribute(
        'aria-disabled',
        String(
            isPastDate
        )
    );

    if (isPastDate) {
        createEventLink.removeAttribute(
            'href'
        );

        createEventLink.setAttribute(
            'title',
            'Events cannot be created for a past date.'
        );

        return;
    }



    createEventLink.setAttribute(
        'href',
        'index.php?page=postings'
            + '&type=event'
            + '&calendar_date='
            + encodeURIComponent(
                date
            )
    );

    createEventLink.removeAttribute(
        'title'
    );
}

        function renderSelectedDate(
            date
        ) {
            if (!eventList || !date) {
                return;
            }

            const events =
                Array.isArray(
                    eventMap[date]
                )
                    ? eventMap[date]
                    : [];

            const holidays =
                Array.isArray(
                    holidayMap[date]
                )
                    ? holidayMap[date]
                    : [];

            eventList.replaceChildren();

            holidays.forEach(
                (holiday) => {
                    eventList.appendChild(
                        createHolidayCard(
                            holiday
                        )
                    );
                }
            );

            events.forEach(
                (event) => {
                    eventList.appendChild(
                        createEventCard(
                            event
                        )
                    );
                }
            );

            if (
                holidays.length === 0 &&
                events.length === 0
            ) {
                eventList.appendChild(
                    createEmptyState()
                );
            }

            updateSelectedDateHeader(
                date
            );

            updateCreateEventLink(
    date
);
        }

        /* ======================================
           DATE SELECTION
        ======================================= */

        document.addEventListener(
            'click',
            (event) => {
                const cell =
                    event.target.closest(
                        '.day-cell'
                    );

                if (!cell) {
                    return;
                }

                event.preventDefault();

                selectedDate =
                    cell.dataset.date ||
                    null;

                document
                    .querySelectorAll(
                        '.day-cell.selected'
                    )
                    .forEach(
                        (selectedCell) => {
                            selectedCell.classList
                                .remove(
                                    'selected'
                                );
                        }
                    );

                cell.classList.add(
                    'selected'
                );

                renderSelectedDate(
                    selectedDate
                );
            }
        );



        if (selectedDate) {
            renderSelectedDate(
                selectedDate
            );
        }
    }
);
