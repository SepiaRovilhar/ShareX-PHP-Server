<?php
# Note for harmonisation: all return must only have [0], [1] and [2] as return. All return must be an array of type array('success' => false, 'error' => 'error message') or array('success' => true, 'data' => 'data')
require_once __DIR__ . '/../config/config.php';
function router($httpMethods, $route, $callback, $exit = true) : void
{
    /**
     * The router function comes from dexit: https://gist.github.com/dexit/ef6fab604b84fa3c527d0ca6141ef613
     * @param string|array $httpMethods
     * @param string $route
     * @param callable $callback
     * @param bool $exit
     * @return void
     */
    static $path = null;
    if ($path === null) {
        $path = parse_url($_SERVER['REQUEST_URI'])['path'];
        $scriptName = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $scriptName = str_replace('\\', '/', $scriptName);
        $len = strlen($scriptName);
        if ($len > 0 && $scriptName !== '/') {
            $path = substr($path, $len);
        }
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], (array)$httpMethods)) {
        return;
    }
    $matches = null;
    $regex = '/' . str_replace('/', '\/', $route) . '/';
    if (!preg_match_all($regex, $path, $matches)) {
        return;
    }
    if (empty($matches)) {
        $callback();
    } else {
        $params = array();
        foreach ($matches as $k => $v) {
            if (!is_numeric($k) && !isset($v[1])) {
                $params[$k] = $v[0];
            }
        }
        $callback($params);
    }
    if ($exit) {
        exit;
    }
}

function checkAuth() : void
{
    /**
     * Check if the request is authorized
     * @return void
     */
    $CONFIG = returnConfig();
    $authorisation = $CONFIG['BEARER_TOKEN'];
    // Get all the headers
    $headers = getallheaders();

    // if Authorization is not set
    if (!isset($headers['Authorization'])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
        exit;
    }

    // Get the Authorization key
    $authKey = $headers['Authorization'];

    // if $authKey isn't in array of $authorisation
    if (!in_array($authKey, $authorisation)) {
        header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Invalid auth key'));
        exit;
    }
}

function getPostFile()
{
    /**
     * Get the file from the POST request
     * @return array
     */
    // Check if the file is existing
    if (!isset($_FILES["file"])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'File not existing'));
        exit;
    } else {
        return $_FILES["file"];
    }
}

function postMain() : void
{
    /**
     * Upload the file to the server
     * @return void
     */
    header('Content-Type: application/json');

    $CONFIG = returnConfig();
    // Check if the upload is activated
    if(!$CONFIG['UPLOAD_ENABLE']) {
        header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
        echo json_encode(array('success' => false, 'error' => 'Upload is not enabled'));
        exit;
    }
    // Check if the request is authorized
    checkAuth();
    // Get the file from the POST request
    $file = getPostFile();

    require_once __DIR__ . '/../upload/upload.php';
    // Upload the file
    $result = upload($file);
    if (!$result) {
        header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        echo json_encode(array('success' => false, 'error' => 'No data returned'));
        exit;
    } else {
        $httpCode = $result[0];
        $httpMessage = $result[1];
        header("{$_SERVER['SERVER_PROTOCOL']} $httpCode $httpMessage");
        if ($httpCode == 201) {
            $fullName = $result[2][0];
            $deleteKey = $result[2][1];
            $fullUrlAcces = $CONFIG['BASE_URL'] . $fullName;
            $fullUrlDelete = $CONFIG['BASE_URL'] . "delete/" . $deleteKey;
            echo json_encode(array('success' => true, 'data' => "$fullUrlAcces", 'delete' => "$fullUrlDelete"));
        } else {
            $data = $result[2];
            echo json_encode(array('success' => false, 'error' => "$data"));
        }
    }
    exit;
}

function getMain($fileName) : void
{
    /**
     * View the file from the server
     * @param string $fileName
     * @return void
     */
    $CONFIG = returnConfig();
    // Check if the view is activated
    if(!$CONFIG['VIEW_ENABLE']) {
        header('Content-Type: application/json');
        header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
        echo json_encode(array('success' => false, 'error' => 'View is not enabled'));
        exit;
    }

    require_once __DIR__ . '/../view/view.php';
    $fileExtension = explode('.', $fileName)[1];
    $fileName = explode('.', $fileName)[0];
    // View the file
    $dataFile = view($fileName, $fileExtension);
    if(!$dataFile) {
        header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'No data returned'));
        exit;
    } else {
        $httpCode = $dataFile[0];
        $httpMessage = $dataFile[1];
        header("{$_SERVER['SERVER_PROTOCOL']} $httpCode $httpMessage");
        if ($httpCode === 200) {
            $mimeTypes = $dataFile[2][0];
            $filePath = $dataFile[2][1];
            $isDownload = $dataFile[2][2];
            if($isDownload) {
                $fileName = pathinfo($filePath, PATHINFO_BASENAME);
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
            } else {
                header('Content-Type: ' . $mimeTypes);
            }
            readfile($filePath);
        } else {
            $data = $dataFile[2];
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => "$data"));
        }
    }
}

function getDeletion($fullName) : void
{
    /**
     * Delete the file from the server
     * @param string $fullName
     * @return void
     */
    header('Content-Type: application/json');

    $deleteKey = explode('/', $fullName)[1];
    require_once __DIR__ . '/../deletion/deletion.php';
    // Delete the file
    $result = deletion($deleteKey);
    if (!$result) {
        header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        echo json_encode(array('success' => false, 'error' => 'No data returned'));
        exit;
    } else {
        $httpCode = $result[0];
        $httpMessage = $result[1];
        header("{$_SERVER['SERVER_PROTOCOL']} $httpCode $httpMessage");
        if ($httpCode === 200) {
            $data = $result[2];
            echo json_encode(array('success' => true, 'data' => "$data"));
        } else {
            $data = $result[2];
            echo json_encode(array('success' => false, 'error' => "$data"));
        }
    }
}
function showWelcome() : void
{
    /**
     * Show the welcome page
     * @return void
     */
    require_once __DIR__ . '/../public/welcome.html';
}