<?php
require_once (dirname(__FILE__) . '/../config/config.php');
require_once (dirname(__FILE__) . '/functions.php');
function deletion($deleter): array
{
    $nameValid = checkDeletion($deleter);
    if ($nameValid[0] !== 200) {
        return [$nameValid[0], $nameValid[1], $nameValid[2]];
    }
    $exitInDb = checkExist($deleter);
    if ($exitInDb[0] !== 200) {
        return [$exitInDb[0], $exitInDb[1], $exitInDb[2]];
    }
    $file = returnNameAndExtention($deleter);
    if ($file[0] !== 200) {
        return [$file[0], $file[1], $file[2]];
    }
    $fileName = $file[2];
    $extension = $file[3];

    $deleteStatus = deleteOnDatabase($deleter);
    if ($deleteStatus[0] !== 200) {
        return [$deleteStatus[0], $deleteStatus[1], $deleteStatus[2]];
    }
    $CONFIG = returnConfig();
    $fileLocation = $CONFIG['UPLOAD_FOLDER'] . $fileName . '.' . $extension;
    if (file_exists($fileLocation)) {
        unlink($fileLocation);
        if(file_exists($fileLocation)){
            return [500, 'Internal Server', 'The file could not be deleted'];
        } else {
            return [200, 'OK', 'The file has been deleted'];
        }
    } else {
        return [404, 'Not Found', 'The file does not exist'];
    }


}
