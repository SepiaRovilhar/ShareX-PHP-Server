<?php
require_once(dirname(__FILE__) . '/../config/config.php');
require_once (dirname(__FILE__) . '/functions.php');

function upload($FILE_TO_UPLOAD): array
{
    /**
     * Upload file to the server
     * @param array $FILE_TO_UPLOAD
     * @return array
     */
    $CONFIG = returnConfig();
    $FILE_EXTENSION = pathinfo($FILE_TO_UPLOAD['name'], PATHINFO_EXTENSION);
    # verify if $CONFIG['UPLOAD_DIR'] exist
    if (!file_exists($CONFIG['UPLOAD_DIR'])) {
        # $CONFIG['UPLOAD_DIR'] does not exist
        return [500, 'Internal Server Error', 'The upload directory does not exist'];
    }

    $fileType = 'OTHER';
    # get the filetype, is extension unknow filetype is 'OTHER'. USE $FILE_TO_UPLOAD and EXTENSION_DEFINITION in $CONFIG
    foreach ($CONFIG['EXTENSION_DEFINITION'] as $type => $extensions) {
        # verify if $FILE_EXTENSION is in array $extension
        if (in_array($FILE_EXTENSION, $extensions)) {
            # $FILE_EXTENSION is in array $extension
            $fileType = $type;
            break;
        }
    }
    # verify if all filetypes are allowed
    $typeAllowed = false;
    if ($CONFIG['EXTENSION_ENABLE']["ALL"] === true ){
        $typeAllowed = true;
    } else {
        # verify if the filetype is allowed
        if ($CONFIG['EXTENSION_ENABLE'][$fileType] === true) {
            $typeAllowed = true;
        }
    }
    if (!$typeAllowed) {
        # the filetype is not allowed
        return [415, 'Unsupported Media Type', 'The filetype is not allowed'];
    }

    # verify if the file is not too big.
    # convert file size to mb
    $fileSize = $FILE_TO_UPLOAD['size'] / 1024 / 1024;
    if ($fileSize > $CONFIG['MAX_FILE_SIZE']) {
        # the file is too big
        return [413, 'Request Entity Too Large', 'The file is too big'];
    }

    # verify if the file is not empty
    if ($FILE_TO_UPLOAD['size'] === 0) {
        # the file is empty
        return [400, 'The file is empty'];
    }

    # verify if the file is not corrupted
    if ($FILE_TO_UPLOAD['error'] !== 0) {
        # the file is corrupted
        return [400, 'Bad Request', 'The file is corrupted'];
    }

    $data = uploadToDatabase();;
    if ($data[0] !== 200) {
        return [500, 'Internal Server Error', $data[2]];
    }
    $randomName = $data[2];
    $delete = $data[3];

    $dirUpload = $CONFIG['UPLOAD_DIR'];

    # verify if the file is uploaded
    if (!is_uploaded_file($FILE_TO_UPLOAD['tmp_name'])) {
        # the file is not uploaded
        return [500, 'Internal Server Error', 'The file is not uploaded'];
    }

    # move the file to the upload directory
    if (!move_uploaded_file($FILE_TO_UPLOAD['tmp_name'], "./$dirUpload$randomName.$FILE_EXTENSION")) {
        # the file is not moved
        return [500, 'Internal Server Error', 'The file is not moved'];
    }

    $fullName = $randomName . '.' . $FILE_EXTENSION;
    # return the random name
    return [201, 'Created', $fullName, $delete];
}