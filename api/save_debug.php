<?php
$body = json_decode(file_get_contents('php://input'), true);
if (isset($body['text'])) {
    if (!is_dir(__DIR__ . '/../scratch')) {
        mkdir(__DIR__ . '/../scratch');
    }
    file_put_contents(__DIR__ . '/../scratch/pdf_dump.txt', $body['text']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'No text provided']);
}
