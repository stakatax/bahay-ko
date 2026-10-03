document.addEventListener(
    'DOMContentLoaded',
    () => {
        'use strict';

        const heroImage =
            document.getElementById(
                'heroImg'
            );

        if (!heroImage) {
            return;
        }

        const images = [
            'Assets/images/college.jpg',
            'Assets/images/seniorhigh.jpg',
            'Assets/images/juniorhigh.jpg',
            'Assets/images/elementary.jpg'
        ];

        let currentImageIndex =
            0;

        window.setInterval(
            () => {
                currentImageIndex =
                    (
                        currentImageIndex + 1
                    ) % images.length;

                heroImage.src =
                    images[
                        currentImageIndex
                    ];
            },
            5000
        );
    }
);