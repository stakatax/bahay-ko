document.addEventListener(
    'DOMContentLoaded',
    setupAccountProfile
);

function setupAccountProfile() {
    const personalForm = document.querySelector('[data-faculty-personal-form]');
    let savingPersonalDetails = false;
    personalForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (savingPersonalDetails || !personalForm.reportValidity()) return;
        savingPersonalDetails = true;
        let confirmed = false;
        try {
            if (!window.AppDialog) {
                console.error('The application dialog component is unavailable.');
                return;
            }
            confirmed = await window.AppDialog.confirm({
                type: 'question',
                title: 'Save personal details?',
                message: 'Your account profile will be updated with these details.',
                confirmText: 'Save details',
                cancelText: 'Keep editing'
            });
        } catch (error) {
            console.error('Unable to open the profile confirmation dialog.', error);
        } finally {
            if (!confirmed) savingPersonalDetails = false;
        }
        if (!confirmed) { savingPersonalDetails = false; return; }
        const button = personalForm.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.textContent = 'Saving...'; }
        personalForm.submit();
    });

    const form =
        document.querySelector(
            '[data-account-profile-upload-form]'
        );

    const fileInput =
        form?.querySelector(
            '[data-account-profile-file]'
        );

    const dropzone =
        form?.querySelector(
            '[data-account-profile-dropzone]'
        );

    const fileName =
        form?.querySelector(
            '[data-account-profile-file-name]'
        );

    const preview =
        document.querySelector(
            '[data-account-profile-preview]'
        );

    const initials =
        document.querySelector(
            '[data-account-profile-initials]'
        );

    const clientError =
        form?.querySelector(
            '[data-account-profile-client-error]'
        );

    const saveButton =
        form?.querySelector(
            '[data-save-profile-photo]'
        );

    const removeButton =
        document.querySelector(
            '[data-open-profile-photo-removal]'
        );

    const removalForm =
        document.querySelector(
            '[data-profile-photo-removal-form]'
        );

    let previewObjectUrl =
        null;

    let selectedFileValid =
        false;

    let validatingFile =
        false;

    if (
        form &&
        fileInput &&
        dropzone
    ) {
        fileInput.addEventListener(
            'change',
            () => {
                validateSelectedFile(
                    fileInput.files?.[0]
                        ?? null
                );
            }
        );

        [
            'dragenter',
            'dragover'
        ].forEach(
            (eventName) => {
                dropzone.addEventListener(
                    eventName,
                    (event) => {
                        event.preventDefault();

                        dropzone.classList.add(
                            'is-dragging'
                        );
                    }
                );
            }
        );

        [
            'dragleave',
            'drop'
        ].forEach(
            (eventName) => {
                dropzone.addEventListener(
                    eventName,
                    (event) => {
                        event.preventDefault();

                        dropzone.classList.remove(
                            'is-dragging'
                        );
                    }
                );
            }
        );

        dropzone.addEventListener(
            'drop',
            (event) => {
                const droppedFile =
                    event.dataTransfer
                        ?.files?.[0]
                    ?? null;

                if (!droppedFile) {
                    return;
                }

                const transfer =
                    new DataTransfer();

                transfer.items.add(
                    droppedFile
                );

                fileInput.files =
                    transfer.files;

                validateSelectedFile(
                    droppedFile
                );
            }
        );

        form.addEventListener(
            'submit',
            (event) => {
                if (
                    validatingFile ||
                    !selectedFileValid
                ) {
                    event.preventDefault();

                    showClientError(
                        validatingFile
                            ? 'Wait while the selected image is being verified.'
                            : 'Choose a valid JPG, PNG, or WebP image before saving.'
                    );

                    return;
                }

                if (saveButton) {
                    saveButton.disabled =
                        true;

                    saveButton.innerHTML = `
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Uploading Photo
                    `;
                }
            }
        );
    }

    removeButton?.addEventListener(
        'click',
        async () => {
            if (!removalForm) {
                return;
            }

            let confirmed =
                false;

            if (window.Swal) {
                const result =
                    await window.Swal.fire({
                        icon:
                            'warning',

                        title:
                            'Remove Profile Photo?',

                        text:
                            'Your account will return to the default initials avatar.',

                        showCancelButton:
                            true,

                        confirmButtonText:
                            'Remove Photo',

                        cancelButtonText:
                            'Keep Photo',

                        confirmButtonColor:
                            '#a82126'
                    });

                confirmed =
                    result.isConfirmed;
            } else {
                confirmed =
                    window.confirm(
                        'Remove your current profile photo?'
                    );
            }

            if (!confirmed) {
                return;
            }

            removeButton.disabled =
                true;

            removeButton.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Removing Photo
            `;

            removalForm.submit();
        }
    );

    setupAccountProfileAlert();

    function validateSelectedFile(
        file
    ) {
        clearClientError();

        selectedFileValid =
            false;

        if (!file) {
            resetSelectionState();
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (
            !allowedTypes.includes(
                file.type
            )
        ) {
            resetSelectionState();

            showClientError(
                'Use a JPG, PNG, or WebP image.'
            );

            fileInput.value =
                '';

            return;
        }

        const maximumSize =
            3 * 1024 * 1024;

        if (
            file.size <= 0 ||
            file.size > maximumSize
        ) {
            resetSelectionState();

            showClientError(
                'The profile photo must not exceed 3 MB.'
            );

            fileInput.value =
                '';

            return;
        }

        validatingFile =
            true;

        const objectUrl =
            URL.createObjectURL(
                file
            );

        const image =
            new Image();

        image.onload =
            () => {
                validatingFile =
                    false;

                const validDimensions =
                    image.naturalWidth >= 100 &&
                    image.naturalHeight >= 100 &&
                    image.naturalWidth <= 2000 &&
                    image.naturalHeight <= 2000;

                if (!validDimensions) {
                    URL.revokeObjectURL(
                        objectUrl
                    );

                    resetSelectionState();

                    showClientError(
                        'Image dimensions must be between 100 × 100 and 2000 × 2000 pixels.'
                    );

                    fileInput.value =
                        '';

                    return;
                }

                if (previewObjectUrl) {
                    URL.revokeObjectURL(
                        previewObjectUrl
                    );
                }

                previewObjectUrl =
                    objectUrl;

                selectedFileValid =
                    true;

                dropzone.classList.add(
                    'has-file'
                );

                if (fileName) {
                    fileName.textContent =
                        `${file.name} · ${formatFileSize(
                            file.size
                        )} · ${image.naturalWidth} × ${image.naturalHeight}`;
                }

                if (preview) {
                    preview.src =
                        previewObjectUrl;

                    preview.hidden =
                        false;
                }

                if (initials) {
                    initials.hidden =
                        true;
                }
            };

        image.onerror =
            () => {
                validatingFile =
                    false;

                URL.revokeObjectURL(
                    objectUrl
                );

                resetSelectionState();

                showClientError(
                    'The selected file could not be read as an image.'
                );

                fileInput.value =
                    '';
            };

        image.src =
            objectUrl;
    }

    function resetSelectionState() {
        selectedFileValid =
            false;

        validatingFile =
            false;

        dropzone?.classList.remove(
            'has-file'
        );

        if (fileName) {
            fileName.textContent =
                'Maximum 3 MB · 100–2000 pixels per side';
        }
    }

    function showClientError(
        message
    ) {
        if (!clientError) {
            return;
        }

        const messageElement =
            clientError.querySelector(
                'span'
            );

        if (messageElement) {
            messageElement.textContent =
                message;
        }

        clientError.hidden =
            false;
    }

    function clearClientError() {
        if (!clientError) {
            return;
        }

        clientError.hidden =
            true;
    }

    function formatFileSize(
        bytes
    ) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (
            bytes <
            1024 * 1024
        ) {
            return `${(
                bytes / 1024
            ).toFixed(1)} KB`;
        }

        return `${(
            bytes /
            (
                1024 * 1024
            )
        ).toFixed(1)} MB`;
    }
}

function setupAccountProfileAlert() {
    const alert =
        document.querySelector(
            '[data-account-profile-alert]'
        );

    if (!alert) {
        return;
    }

    const dismissButton =
        alert.querySelector(
            '[data-dismiss-account-profile-alert]'
        );

    dismissButton?.addEventListener(
        'click',
        () => {
            alert.remove();
        }
    );
}