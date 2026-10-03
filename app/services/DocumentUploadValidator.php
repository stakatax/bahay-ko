<?php

/** Validates new uploads only; stored documents retain their authorized download path. */
class DocumentUploadValidator
{
    public static function validate(?array $file): array
    {
        if (!$file || !isset($file['error']) || !is_int($file['error'])) {
            throw new InvalidArgumentException('A document file is required.');
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new InvalidArgumentException('Document exceeds the permitted upload size.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The document upload did not complete. Please select the file again.');
        }
        if (!isset($file['name'], $file['tmp_name']) || !is_string($file['name'])
            || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid document upload.');
        }
        $name = basename(str_replace('\\', '/', $file['name']));
        if ($name === '' || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            throw new InvalidArgumentException('Invalid document filename.');
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'], true)) {
            throw new InvalidArgumentException('Unsupported document file type.');
        }
        $size = filesize($file['tmp_name']);
        if ($size === false) {
            throw new RuntimeException('Unable to inspect document upload.');
        }
        if ($size > 20 * 1024 * 1024) {
            throw new InvalidArgumentException('Document must not exceed 20 MB.');
        }
        if ($size === 0) {
            throw new InvalidArgumentException('The selected document is empty.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $head = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        $valid = false;
        if ($extension === 'pdf') {
            $valid = $mime === 'application/pdf' && str_starts_with($head, '%PDF-');
        } elseif ($extension === 'txt') {
            $valid = $mime === 'text/plain';
        } elseif (in_array($extension, ['docx', 'xlsx', 'pptx'], true)) {
            $expected = [
                'docx' => ['word/document.xml', 'wordprocessingml.document'],
                'xlsx' => ['xl/workbook.xml', 'spreadsheetml.sheet'],
                'pptx' => ['ppt/presentation.xml', 'presentationml.presentation']
            ][$extension];
            $valid = in_array($mime, [
                'application/zip', 'application/x-zip', 'application/x-zip-compressed',
                'application/vnd.openxmlformats-officedocument.' . $expected[1]
            ], true) && str_starts_with($head, "PK\x03\x04")
                && self::isOfficePackage($file['tmp_name'], $expected);
        } else {
            $mimes = [
                'doc' => 'application/msword',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint'
            ];
            $valid = in_array($mime, [$mimes[$extension], 'application/x-ole-storage',
                'application/CDFV2', 'application/vnd.ms-office', 'application/octet-stream'], true)
                && str_starts_with($head, "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1");
            if ($valid) {
                $bytes = file_get_contents($file['tmp_name']);
                $streams = ['doc' => ['WordDocument'], 'xls' => ['Workbook', 'Book'],
                    'ppt' => ['PowerPoint Document']][$extension];
                $valid = false;
                foreach ($streams as $stream) {
                    $valid = $valid || str_contains($bytes, mb_convert_encoding($stream . "\0", 'UTF-16LE', 'UTF-8'));
                }
            }
        }
        if (!$valid) {
            throw new InvalidArgumentException('The document contents do not match its file type, or the document is unsupported.');
        }
        return ['file_name' => $name, 'file_type' => $extension, 'file_size' => $size];
    }

    private static function isOfficePackage(string $path, array $expected): bool
    {
        // Inspect a bounded manifest only. Never extract uploaded archives or deserialize metadata.
        try {
            $archive = new PharData($path);
            if (!$archive->isFileFormat(Phar::ZIP) || !isset($archive['[Content_Types].xml'], $archive[$expected[0]])) {
                return false;
            }
            $manifest = $archive['[Content_Types].xml'];
            if ($manifest->getSize() > 65536 || $archive[$expected[0]]->getSize() === 0) {
                return false;
            }
            $xml = $manifest->getContent();
            if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
                return false;
            }
            $previous = libxml_use_internal_errors(true);
            try {
                $document = new DOMDocument();
                if (!$document->loadXML($xml, LIBXML_NONET)) {
                    return false;
                }
                $xpath = new DOMXPath($document);
                $xpath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
                foreach ($xpath->query('/ct:Types/ct:Override') as $part) {
                    if ($part->getAttribute('PartName') === '/' . $expected[0]
                        && $part->getAttribute('ContentType') === 'application/vnd.openxmlformats-officedocument.' . $expected[1] . '.main+xml') {
                        return true;
                    }
                }
                return false;
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        } catch (UnexpectedValueException | RuntimeException $exception) {
            return false;
        }
    }
}
