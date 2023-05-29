<?php
require_once __DIR__ . '/../config/config.php';
function router($httpMethods, $route, $callback, $exit = true)
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

function checkAuth(): void
{
    /**
     * Check if the request is authorized
     * @return void
     */
    $CONFIG = returnConfig();
    $authorisation = $CONFIG['BEARER_TOKEN'];
    $headers = getallheaders();

    // Récupération de la clé d'authentification à partir de l'en-tête
    if (!isset($headers['Authorization'])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
        exit;
    }
    $authKey = $headers['Authorization'];

    // if $authKey is in array of $authorisation
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
    if (!isset($_FILES["file"])) {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'File not existing'));
        exit;
    } else {
        return $_FILES["file"];
    }
}

function postMain(): void
{
    /**
     * Upload the file to the server
     * @return void
     */
    // Check if the upload is activated
    #header('Content-Type: application/json');
    $CONFIG = returnConfig();
    if(!$CONFIG['UPLOAD_ENABLE']) {
        header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
        echo json_encode(array('success' => false, 'error' => 'Upload is not enabled'));
        exit;
    }
    checkAuth();
    $file = getPostFile();
    require_once __DIR__ . '/../upload/upload.php';
    $result = upload($file);

    if (!$result) {
        header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        echo json_encode(array('success' => false, 'error' => 'No data returned'));
        exit;
    } else {
        $httpCode = $result[0];
        $httpMessage = $result[1];
        $data = $result[2];
        header("{$_SERVER['SERVER_PROTOCOL']} $httpCode $httpMessage");
        if ($httpCode == 201) {
            $fullUrl = $CONFIG['BASE_URL'] . $data;
            echo json_encode(array('success' => true, 'data' => "$fullUrl"));
        } else {
            echo json_encode(array('success' => false, 'error' => "$data"));
        }
    }
    exit;
}

function detectLFI($fileName): bool
{
    $CONFIG = returnConfig();

    if (str_contains($fileName, '..') || str_contains($fileName, '/') || str_contains($fileName, '\\') || str_contains($fileName, '%')) {
        return true;
    }
    $allAcceptableChar = $CONFIG['ALL_USABLE_CHARS'];
    $allAcceptableCharTab = str_split($allAcceptableChar);
    // for each char in the file name
    foreach (str_split($fileName) as $char) {
        // if the char is not in the array of acceptable char
        if($char === '.') {
            continue;
        }
        if (!in_array($char, $allAcceptableCharTab)) {
            return true;
        }
    }
    return false;
}
function getMain($fileName) : void
{
    /**
     * View the file from the server
     * @param string $fileName
     * @return void
     */
    $CONFIG = returnConfig();
    if(!$CONFIG['VIEW_ENABLE']) {
        header('Content-Type: application/json');
        header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
        echo json_encode(array('success' => false, 'error' => 'View is not enabled'));
        exit;
    }
    detectLFI($fileName);
    require_once __DIR__ . '/../view/view.php';
    $fullPath = $CONFIG['UPLOAD_DIR'] . $fileName;
    view($fullPath);
}

function showWelcome(): void
{
    /**
     * Show the welcome page
     * @return void
     */
    require_once __DIR__ . '/../public/welcome.html';
}