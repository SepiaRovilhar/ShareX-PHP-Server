<?php
require_once(dirname(__FILE__) . '/config/config.php');
# get the config ($CONFIG) in config/config.php

// Vérification de l'authentification
function checkAuth()
{
    $CONFIG = returnConfig();
    $authorisation = $CONFIG['BEARER_TOKEN'];
    $headers = getallheaders();

    // Récupération de la clé d'authentification à partir de l'en-tête
    if (!isset($headers['Authorization'])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
        echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
        exit;
    }
    $authKey = $headers['Authorization'];

    // if $authKey is in array of $authorisation
    if (!in_array($authKey, $authorisation)) {
        header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
        echo json_encode(array('success' => false, 'error' => 'Invalid auth key', 'authKey' => $authKey));
        exit;
    } else {
        return true;
    }
}

function getPostFile()
{
    if (!isset($_FILES["file"])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 400 File not existing");
        echo json_encode(array('success' => false, 'error' => 'File not existing'));
        exit;
    } else {
        return $_FILES["file"];
    }
}

// Traitement des requêtes PUT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $CONFIG = returnConfig();
    checkAuth();
    $file = getPostFile();
    // Récupération du type de données à partir du paramètre dans l'URL

    header('Content-Type: application/json');
    require_once 'upload/upload.php';
    $result = main($file);
    $httpCode = $result[0];
    $httpMessage = $result[1];
    $data = $result[2];

    if (!$result) {
        header("{$_SERVER['SERVER_PROTOCOL']}  500 Internal Server Error");
        echo json_encode(array('success' => false, 'error' => 'No data returned'));
        exit;
    }

    if($httpCode === 201){
        header("{$_SERVER['SERVER_PROTOCOL']}  201 Created");
        $fullUrl = $CONFIG['BASE_UPLOAD_URL'] . $data;
        echo json_encode(array('success' => true, 'data' => $fullUrl));
    } else {
        header("{$_SERVER['SERVER_PROTOCOL']} $httpCode $httpMessage");
        echo json_encode(array('success' => false, 'error' => $data));
    }
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $CONFIG = returnConfig();
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $last_part = pathinfo($path, PATHINFO_BASENAME);
    if($last_part == ''){
        # show welcome.html page NO JSON RETURN
        require_once 'welcome.html';
        exit;
    } else {
        # verify if the file exist
        if (!file_exists($CONFIG['UPLOAD_DIR'] . $last_part)) {
            header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
            echo json_encode(array('success' => false, 'error' => 'File not found'));
        } else {
            # get mime type
            $mime_type = mime_content_type($CONFIG['UPLOAD_DIR'] . $last_part);
            // if the file is a image
            if (strpos($mime_type, 'image') !== false) {
                header('Content-Type: ' . $mime_type);
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
            # if it's a video
            } elseif (strpos($mime_type, 'video') !== false) {
                header('Content-Type: ' . $mime_type);
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
        }
            # if it's music
            elseif (strpos($mime_type, 'audio') !== false) {
                header('Content-Type: ' . $mime_type);
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
            }
            # if it's a text file
            elseif (strpos($mime_type, 'text') !== false) {
                header('Content-Type: ' . $mime_type);
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
            }
            # if it's a pdf
            elseif (strpos($mime_type, 'pdf') !== false) {
                header('Content-Type: ' . $mime_type);
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
            } else {
            # download the file
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $last_part . '"');
                readfile($CONFIG['UPLOAD_DIR'] . $last_part);
            }
        }

        exit;
    }
}


// Traitement des erreurs
header('Content-Type: application/json');
header("{$_SERVER['SERVER_PROTOCOL']}  400 Bad Request");
echo json_encode(array('error' => 'Invalid request method'));
exit;
