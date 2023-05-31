<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/./functions.php';
function view($fileName, $fileExtension): void
{
    /**
     * View the file from the server
     * @param string $fileName
     * @return void
     */
    $CONFIG = returnConfig();
    $fileInDatabase = viewFromDatabase($fileName);
    if ($fileInDatabase[0] !== 200) {
        header("{$_SERVER['SERVER_PROTOCOL']} $fileInDatabase[0] $fileInDatabase[1]");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => $fileInDatabase[2]));
        exit;
    }
    $extension = getExtensionFromDb($fileName);
    if ($extension[0] !== 200) {
        header("{$_SERVER['SERVER_PROTOCOL']} $extension[0] $extension[1]");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => $extension[2]));
        exit;
    }
    if("$fileExtension" !== '' && "$fileExtension" !== null && "$fileExtension" !== "$extension[2]") {
        header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'File extension is not the same as the one in the database'));
        exit;
    }
    $fileName = $fileName . '.' . $extension[2];
    $filePath = $CONFIG['UPLOAD_FOLDER'] . $fileName;
    if (!file_exists($filePath)) {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'File not found'));
        exit;
    } else {
        # get mime type
        $mime_type = mime_content_type($filePath);
        // if the file is a image
        if (strpos($mime_type, 'image') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
            # if it's a video
        } elseif (strpos($mime_type, 'video') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        } # if it's music
        elseif (strpos($mime_type, 'audio') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        } # if it's a text file
        elseif (strpos($mime_type, 'text') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        } # if it's a pdf
        elseif (strpos($mime_type, 'pdf') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        } else {
            # download the file
            # remove the path from the file name
            $fileName = pathinfo($filePath, PATHINFO_BASENAME);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
        }
    }
}