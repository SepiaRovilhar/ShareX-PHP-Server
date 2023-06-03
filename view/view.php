<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/./functions.php';
function view($fileName, $fileExtension): array
{
    /**
     * View the file from the server
     * @param string $fileName
     * @return void
     */
    $CONFIG = returnConfig();
    // verify if the file exists in the database
    $fileInDatabase = viewFromDatabase($fileName);
    // if the file is not in the database
    if ($fileInDatabase[0] !== 200) {
        return $fileInDatabase;
    }
    // get the extension from the database
    $extension = getExtensionFromDb($fileName);
    // if the extension is not in the database
    if ($extension[0] !== 200) {
        return $extension;
    }
    // if the extension is specified and is not the same as the one in the database
    if ("$fileExtension" !== '' && "$fileExtension" !== null && "$fileExtension" !== "$extension[2]") {
        return [400, 'Bad Request', 'The extension is not the same as the one in the database'];
    }
    $fileName = $fileName . '.' . $extension[2];
    $filePath = $CONFIG['UPLOAD_FOLDER'] . $fileName;
    // verify if the file exists in the server
    if (!file_exists($filePath)) {
        return [404, 'Not Found', 'File not found'];
    } else {
            // get mime type
        $mime_type = mime_content_type($filePath);
            // if the file is a image
        if (strpos($mime_type, 'image') !== false) {
            return [200, 'OK', [$mime_type, $filePath, false]];
            // if it's a video
        } elseif (strpos($mime_type, 'video') !== false) {
            return [200, 'OK', [$mime_type,$filePath, false]];
        } // if it's music
        elseif (strpos($mime_type, 'audio') !== false) {
            return [200, 'OK', [$mime_type,$filePath, false]];
        } // if it's a text file
        elseif (strpos($mime_type, 'text') !== false) {
            return [200, 'OK', [$mime_type,$filePath, false]];
        } // if it's a pdf
        elseif (strpos($mime_type, 'pdf') !== false) {
            return [200, 'OK', [$mime_type,$filePath, false]];
            // it's an other file
        } else {
            return [200, 'OK', [$mime_type,$filePath, true]];
        }
    }
}