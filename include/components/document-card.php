<?php

if (!isset($document) || !is_array($document)) {
    return;
}

$fileName = $document['file_name'] ?? 'Untitled Document';
$fileType = $document['file_type'] ?? 'FILE';
$filePath = 'index.php?page=document_download&document_id='
    . (int) ($document['document_id'] ?? 0);
?>

<div class="widget-item document-widget-item">

    <span class="widget-icon">
        <i class="fa-regular fa-file-lines"></i>
    </span>

    <span class="widget-item-content">

        <strong title="<?= htmlspecialchars($fileName) ?>">
            <?= htmlspecialchars($fileName) ?>
        </strong>

        <small><?= htmlspecialchars(strtoupper($fileType)) ?></small>

    </span>

    <a
        href="<?= htmlspecialchars($filePath) ?>"
        class="widget-download"
        download
        title="Download document"
        aria-label="Download <?= htmlspecialchars($fileName) ?>">
        <i class="fa-solid fa-arrow-down"></i>
    </a>

</div>