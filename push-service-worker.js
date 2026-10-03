'use strict';

/* ==========================================
   SERVICE WORKER LIFECYCLE
========================================== */

self.addEventListener(
    'install',
    () => {
        self.skipWaiting();
    }
);

self.addEventListener(
    'activate',
    (event) => {
        event.waitUntil(
            self.clients.claim()
        );
    }
);

/* ==========================================
   SAFE SAME-ORIGIN URL
========================================== */

function getSafeNotificationUrl(
    requestedUrl
) {
    const fallbackUrl =
        new URL(
            'index.php?page=notifications',
            self.registration.scope
        );

    if (
        typeof requestedUrl !==
            'string' ||
        requestedUrl.trim() === ''
    ) {
        return fallbackUrl.href;
    }

    try {
        const resolvedUrl =
            new URL(
                requestedUrl,
                self.registration.scope
            );

        if (
            resolvedUrl.origin !==
            self.location.origin
        ) {
            return fallbackUrl.href;
        }

        return resolvedUrl.href;
    } catch (error) {
        return fallbackUrl.href;
    }
}

/* ==========================================
   RECEIVE PUSH MESSAGE
========================================== */

self.addEventListener(
    'push',
    (event) => {
        let payload = {};

        if (event.data) {
            try {
                payload =
                    event.data.json();
            } catch (error) {
                payload = {
                    message:
                        event.data.text()
                };
            }
        }

        const title =
            typeof payload.title ===
                'string' &&
            payload.title.trim() !== ''
                ? payload.title.trim()
                : 'OLSHCO Digital Hub';

        const message =
            typeof payload.message ===
                'string' &&
            payload.message.trim() !== ''
                ? payload.message.trim()
                : 'You have a new school notification.';

        const notificationUrl =
            getSafeNotificationUrl(
                payload.url
            );

        const notificationTag =
            typeof payload.tag ===
                'string' &&
            payload.tag.trim() !== ''
                ? payload.tag.trim()
                : 'olshco-notification';

        const iconUrl =
            new URL(
                'Assets/Images/ulsco.png',
                self.registration.scope
            ).href;

        const isUrgent =
            payload.urgency ===
                'high' ||
            payload.urgency ===
                'emergency';

        const options = {
            body:
                message,

            icon:
                iconUrl,

            badge:
                iconUrl,

            tag:
                notificationTag,

            renotify:
                true,

            requireInteraction:
                isUrgent,

            data: {
                url:
                    notificationUrl,

                notificationId:
                    Number(
                        payload.notification_id ||
                        0
                    ),

                contentType:
                    payload.content_type ||
                    null,

                contentId:
                    Number(
                        payload.content_id ||
                        0
                    )
            }
        };

        event.waitUntil(
            self.registration
                .showNotification(
                    title,
                    options
                )
        );
    }
);

/* ==========================================
   OPEN NOTIFICATION
========================================== */

self.addEventListener(
    'notificationclick',
    (event) => {
        event.notification.close();

        const targetUrl =
            getSafeNotificationUrl(
                event.notification
                    ?.data
                    ?.url
            );

        event.waitUntil(
            self.clients
                .matchAll({
                    type:
                        'window',

                    includeUncontrolled:
                        true
                })
                .then(
                    async (
                        windowClients
                    ) => {
                        for (
                            const windowClient
                            of windowClients
                        ) {
                            if (
                                'focus'
                                in windowClient
                            ) {
                                if (
                                    'navigate'
                                    in windowClient
                                ) {
                                    await windowClient
                                        .navigate(
                                            targetUrl
                                        );
                                }

                                return windowClient
                                    .focus();
                            }
                        }

                        return self.clients
                            .openWindow(
                                targetUrl
                            );
                    }
                )
        );
    }
);