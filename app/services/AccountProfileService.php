<?php

require_once __DIR__
    . '/../models/User.php';

class AccountProfileService
{
    private const MAXIMUM_FILE_SIZE =
    3 * 1024 * 1024;

    private const MAXIMUM_IMAGE_WIDTH =
    2000;

    private const MAXIMUM_IMAGE_HEIGHT =
    2000;

    private const PUBLIC_UPLOAD_DIRECTORY =
    'Assets/uploads/profile-photos';

    private User $user;

    public function __construct()
    {
        $this->user =
            new User();
    }

    /* ==========================================
       ACCOUNT PROFILE DATA
    ========================================== */

    public function updatePersonalDetails(int $userId, array $data): array
    {
        $user = $this->findActiveUser($userId);
        if (($user['role_prefix'] ?? '') !== 'Faculty' || !empty($user['must_change_password'])) {
            throw new RuntimeException('Only Faculty who have changed their temporary password may update these details.');
        }
        foreach (['middle_name', 'name_suffix', 'gender', 'birthdate'] as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                throw new InvalidArgumentException('Enter valid personal details.');
            }
        }
        $middle = trim((string) ($data['middle_name'] ?? ''));
        $suffix = trim((string) ($data['name_suffix'] ?? ''));
        $gender = (string) ($data['gender'] ?? '');
        $birthdate = (string) ($data['birthdate'] ?? '');
        if (mb_strlen($middle) > 50 || !in_array($suffix, ['', 'Jr.', 'Sr.', 'II', 'III', 'IV', 'V'], true)) {
            throw new InvalidArgumentException('Enter a valid middle name and name suffix.');
        }
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            throw new InvalidArgumentException('Select your gender.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthdate);
        $today = new DateTimeImmutable('today');
        if (!$date || $date->format('Y-m-d') !== $birthdate || $date > $today) {
            throw new InvalidArgumentException('Enter a valid birthdate.');
        }
        $age = $date->diff($today)->y;
        if ($age < 18 || $age > 100) {
            throw new InvalidArgumentException('Faculty must be between 18 and 100 years old.');
        }
        if (!$this->user->updateFacultyPersonalDetails($userId, [
            'middle_name'=>$middle !== '' ? $middle : null,
            'name_suffix'=>$suffix !== '' ? $suffix : null,
            'gender'=>$gender, 'birthdate'=>$birthdate, 'age'=>$age
        ])) {
            throw new RuntimeException('Your personal details could not be saved.');
        }
        return $this->findActiveUser($userId);
    }

    public function getProfileData(
        int $userId
    ): array {
        $user =
            $this->findActiveUser(
                $userId
            );

        return [
            'user' =>
            $user,

            'profile_photo' =>
            trim(
                (string) (
                    $user['profile_photo']
                    ?? ''
                )
            )
        ];
    }

    /* ==========================================
       UPDATE PROFILE PHOTO
    ========================================== */

    public function updateProfilePhoto(
        int $userId,
        array $uploadedFile
    ): array {
        $user =
            $this->findActiveUser(
                $userId
            );

        $uploadError =
            (int) (
                $uploadedFile['error']
                ?? UPLOAD_ERR_NO_FILE
            );

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException(
                'Choose a profile photo to upload.'
            );
        }

        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                $this->resolveUploadErrorMessage(
                    $uploadError
                )
            );
        }

        $temporaryPath =
            (string) (
                $uploadedFile['tmp_name']
                ?? ''
            );

        if (
            $temporaryPath === '' ||
            !is_uploaded_file(
                $temporaryPath
            )
        ) {
            throw new RuntimeException(
                'The uploaded profile photo could not be verified.'
            );
        }

        $fileSize =
            (int) (
                $uploadedFile['size']
                ?? 0
            );

        if (
            $fileSize <= 0 ||
            $fileSize >
            self::MAXIMUM_FILE_SIZE
        ) {
            throw new InvalidArgumentException(
                'The profile photo must not exceed 3 MB.'
            );
        }

        $fileInfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mimeType =
            strtolower(
                (string) $fileInfo->file(
                    $temporaryPath
                )
            );

        $allowedMimeTypes = [
            'image/jpeg' =>
            'jpg',

            'image/png' =>
            'png',

            'image/webp' =>
            'webp'
        ];

        if (
            !isset(
                $allowedMimeTypes[$mimeType]
            )
        ) {
            throw new InvalidArgumentException(
                'Use a JPG, PNG, or WebP profile photo.'
            );
        }

        $imageDetails =
            @getimagesize(
                $temporaryPath
            );

        if (
            !is_array($imageDetails) ||
            empty($imageDetails[0]) ||
            empty($imageDetails[1])
        ) {
            throw new InvalidArgumentException(
                'The uploaded file is not a valid image.'
            );
        }

        $imageWidth =
            (int) $imageDetails[0];

        $imageHeight =
            (int) $imageDetails[1];

        $detectedImageMime =
            strtolower(
                (string) (
                    $imageDetails['mime']
                    ?? ''
                )
            );

        if (
            $detectedImageMime !==
            $mimeType
        ) {
            throw new InvalidArgumentException(
                'The profile photo file type could not be verified.'
            );
        }

        if (
            $imageWidth >
            self::MAXIMUM_IMAGE_WIDTH ||
            $imageHeight >
            self::MAXIMUM_IMAGE_HEIGHT
        ) {
            throw new InvalidArgumentException(
                'The profile photo dimensions must not exceed 2000 × 2000 pixels.'
            );
        }

        if (
            $imageWidth < 100 ||
            $imageHeight < 100
        ) {
            throw new InvalidArgumentException(
                'The profile photo must be at least 100 × 100 pixels.'
            );
        }

        $absoluteDirectory =
            $this->getAbsoluteUploadDirectory();

        if (
            !is_dir($absoluteDirectory) &&
            !mkdir(
                $absoluteDirectory,
                0755,
                true
            ) &&
            !is_dir($absoluteDirectory)
        ) {
            throw new RuntimeException(
                'The profile photo directory could not be created.'
            );
        }

        $extension =
            $allowedMimeTypes[$mimeType];

        $fileName =
            'profile-'
            . $userId
            . '-'
            . bin2hex(
                random_bytes(16)
            )
            . '.'
            . $extension;

        $absolutePath =
            $absoluteDirectory
            . DIRECTORY_SEPARATOR
            . $fileName;

        if (
            !move_uploaded_file(
                $temporaryPath,
                $absolutePath
            )
        ) {
            throw new RuntimeException(
                'The profile photo could not be stored.'
            );
        }

        $publicPath =
            self::PUBLIC_UPLOAD_DIRECTORY
            . '/'
            . $fileName;

        try {
            $this->user
                ->updateProfilePhoto(
                    $userId,
                    $publicPath
                );
        } catch (Throwable $exception) {
            @unlink(
                $absolutePath
            );

            throw $exception;
        }

        $oldProfilePhoto =
            trim(
                (string) (
                    $user['profile_photo']
                    ?? ''
                )
            );

        if (
            $oldProfilePhoto !== '' &&
            $oldProfilePhoto !==
            $publicPath
        ) {
            $this->deleteManagedPhoto(
                $oldProfilePhoto
            );
        }

        return $this->findActiveUser(
            $userId
        );
    }

    /* ==========================================
       REMOVE PROFILE PHOTO
    ========================================== */

    public function removeProfilePhoto(
        int $userId
    ): array {
        $user =
            $this->findActiveUser(
                $userId
            );

        $oldProfilePhoto =
            trim(
                (string) (
                    $user['profile_photo']
                    ?? ''
                )
            );

        $this->user
            ->updateProfilePhoto(
                $userId,
                null
            );

        if ($oldProfilePhoto !== '') {
            $this->deleteManagedPhoto(
                $oldProfilePhoto
            );
        }

        return $this->findActiveUser(
            $userId
        );
    }

    /* ==========================================
       ACTIVE USER VALIDATION
    ========================================== */

    private function findActiveUser(
        int $userId
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException(
                'A valid authenticated account is required.'
            );
        }

        $user =
            $this->user
            ->findById(
                $userId
            );

        if (!$user) {
            throw new RuntimeException(
                'The account could not be found.'
            );
        }

        if (
            (
                $user['status']
                ?? ''
            ) !== 'Active'
        ) {
            throw new RuntimeException(
                'Only an Active account may manage its profile.'
            );
        }

        return $user;
    }

    /* ==========================================
       MANAGED FILE CLEANUP
    ========================================== */

    private function deleteManagedPhoto(
        string $publicPath
    ): void {
        $normalizedPath =
            str_replace(
                '\\',
                '/',
                trim($publicPath)
            );

        $expectedPrefix =
            self::PUBLIC_UPLOAD_DIRECTORY
            . '/';

        if (
            !str_starts_with(
                $normalizedPath,
                $expectedPrefix
            )
        ) {
            return;
        }

        $fileName =
            basename(
                $normalizedPath
            );

        if (
            $fileName === '' ||
            $fileName === '.' ||
            $fileName === '..'
        ) {
            return;
        }

        $absolutePath =
            $this->getAbsoluteUploadDirectory()
            . DIRECTORY_SEPARATOR
            . $fileName;

        if (is_file($absolutePath)) {
            @unlink(
                $absolutePath
            );
        }
    }

    private function getAbsoluteUploadDirectory(): string
    {
        return dirname(
            __DIR__,
            2
        )
            . DIRECTORY_SEPARATOR
            . 'Assets'
            . DIRECTORY_SEPARATOR
            . 'uploads'
            . DIRECTORY_SEPARATOR
            . 'profile-photos';
    }

    private function resolveUploadErrorMessage(
        int $uploadError
    ): string {
        return match ($uploadError) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE =>
            'The profile photo exceeds the allowed upload size.',

            UPLOAD_ERR_PARTIAL =>
            'The profile photo upload was interrupted. Try again.',

            UPLOAD_ERR_NO_TMP_DIR =>
            'The server upload directory is unavailable.',

            UPLOAD_ERR_CANT_WRITE =>
            'The server could not write the uploaded profile photo.',

            UPLOAD_ERR_EXTENSION =>
            'The server rejected the uploaded profile photo.',

            default =>
            'The profile photo upload failed.'
        };
    }
}
