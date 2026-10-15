<?php
// CampusCred - 1-Click PowerPoint Presentation Downloader
$file = __DIR__ . '/CampusCred_Final_Presentation.pptx';

if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
    header('Content-Disposition: attachment; filename="CampusCred_Final_Presentation.pptx"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    ob_clean();
    flush();
    readfile($file);
    exit;
} else {
    http_response_code(404);
    echo "Presentation file not found at " . htmlspecialchars($file);
    exit;
}
