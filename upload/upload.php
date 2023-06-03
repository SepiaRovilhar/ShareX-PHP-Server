<?php
require_once(dirname(__FILE__) . '/../config/config.php');
require_once(dirname(__FILE__) . '/functions.php');

function upload($FILE_TO_UPLOAD): array
{
    /**
     * Upload file to the server
     * @param array $FILE_TO_UPLOAD
     * @return array
     */
    $CONFIG = returnConfig();
    // Get the file extension
    $FILE_EXTENSION = pathinfo($FILE_TO_UPLOAD['name'], PATHINFO_EXTENSION);
    // verify if upload folder exists
    if (!file_exists($CONFIG['UPLOAD_FOLDER'])) {
        return [500, 'Internal Server Error', 'The upload directory does not exist'];
    }

    $fileType = 'OTHER';
    // get the filetype, is extension unknow filetype is 'OTHER'. USE $FILE_TO_UPLOAD and EXTENSION_DEFINITION in $CONFIG
    foreach ($CONFIG['EXTENSION_DEFINITION'] as $type => $extensions) {
        // if the extension is in the array $extensions
        if (in_array($FILE_EXTENSION, $extensions)) {
            $fileType = $type;
            break;
        }
    }

    $typeAllowed = false;
    // verify if ALL filetype is allowed
    if ($CONFIG['EXTENSION_ENABLE']["ALL"] === true) {
        $typeAllowed = true;
    } else {
        # verify if the filetype is allowed
        if ($CONFIG['EXTENSION_ENABLE'][$fileType] === true) {
            $typeAllowed = true;
        }
    }
    if (!$typeAllowed) {
        return [415, 'Unsupported Media Type', 'The filetype is not allowed'];
    }

    // verify if the file is not too big. Sise is convert in MB.
    $fileSize = $FILE_TO_UPLOAD['size'] / 1024 / 1024;
    if ($fileSize > $CONFIG['MAX_FILE_SIZE']) {
        return [413, 'Request Entity Too Large', 'The file is too big'];
    }

    // verify if the file is not empty
    if ($FILE_TO_UPLOAD['size'] === 0) {
        return [400, 'The file is empty'];
    }

    // verify if the file is corrupted
    if ($FILE_TO_UPLOAD['error'] !== 0) {
        return [400, 'Bad Request', 'The file is corrupted'];
    }

    // Upload the file to the database
    $data = uploadToDatabase();

    // verify if the upload is successful
    if ($data[0] !== 200) {
        return [500, 'Internal Server Error', $data[2]];
    }

    $randomName = $data[2][0];
    $delete = $data[2][1];

    $dirUpload = $CONFIG['UPLOAD_FOLDER'];

    // verify if the file is uploaded
    if (!is_uploaded_file($FILE_TO_UPLOAD['tmp_name'])) {
        return [500, 'Internal Server Error', 'The file is not uploaded'];
    }

    // Move the file to the upload folder and verify if the file is moved
    if (!move_uploaded_file($FILE_TO_UPLOAD['tmp_name'], "./$dirUpload$randomName.$FILE_EXTENSION")) {
        return [500, 'Internal Server Error', 'The file is not moved'];
    }

    $fullName = $randomName . '.' . $FILE_EXTENSION;

    // return the random name and the delete key
    return [201, 'Created', [$fullName, $delete]];
}