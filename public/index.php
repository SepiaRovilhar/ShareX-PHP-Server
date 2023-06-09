<?php
// The code inspired from dexit: https://gist.github.com/dexit/ef6fab604b84fa3c527d0ca6141ef613
// We can only set up response here
require_once __DIR__ . '/../src/functions.php';

// Default index page
router('GET', '^/', function () {
    // get content after /
    $name = substr($_SERVER['REQUEST_URI'], 1);
    // if the name is empty
    if (empty($name)) {
        showWelcome();
        // if the name is too long
    } elseif (strlen($name) >= 50) {
        header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'The request is too long'));
    } else {
        // check if they are / in the name. SORRY FOR THE UGLY PART, i'm looking for a better way to do this
        if (!function_exists('str_contains')) {
            function str_contains($haystack, $needle)
            {
                return $needle !== '' && mb_strpos($haystack, $needle) !== false;
            }
        }
        // there are / in the name
        if (str_contains($name, '/')) {
            $namebackup = $name;
            $name = explode('/', $name);
            $name = $name[0];
            // if the name is delete
            if ($name == 'delete') {
                getDeletion($namebackup);
            } else {
                header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'error' => 'Invalid request method'));
            }
        } else {
            getMain($name);
        }
    }
});

// POST request to /users
router('POST', '^/$', function () {
    postMain();
});

header('Content-Type: application/json');
header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
echo json_encode(array('success' => false, 'error' => 'Invalid request method'));
exit;