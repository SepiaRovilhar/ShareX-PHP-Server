<?php
function view($filePath) : void
{
    /**
     * View the file from the server
     * @param string $filePath
     * @return void
     */

    if (!file_exists($filePath)) {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'File not found'));
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
        }
        # if it's music
        elseif (strpos($mime_type, 'audio') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        }
        # if it's a text file
        elseif (strpos($mime_type, 'text') !== false) {
            header('Content-Type: ' . $mime_type);
            readfile($filePath);
        }
        # if it's a pdf
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