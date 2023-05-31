<?php
// The code inspired from dexit: https://gist.github.com/dexit/ef6fab604b84fa3c527d0ca6141ef613
require_once __DIR__ . '/../src/functions.php';

// Default index page
router('GET', '^/', function() {
    // get content after /
    $name = substr($_SERVER['REQUEST_URI'], 1);
    # if the name is empty
    if (empty($name)) {
        showWelcome();
    } else {
        // check if the are / in the name. SORRY FOR THE UGLY PART, i'm looking for a better way to do this
        if (!function_exists('str_contains')) {
            function str_contains($haystack, $needle) {
                return $needle !== '' && mb_strpos($haystack, $needle) !== false;
            }
        }
        if (str_contains($name, '/')) {
            // there are / in the name
            $name = explode('/', $name);
            $name = $name[0];
            if ($name == 'delete') {
                header('Content-Type: application/json');
                header("{$_SERVER['SERVER_PROTOCOL']} 501 Not Implemented");
                echo json_encode(array('success' => false, 'error' => 'Delete not implemented'));
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
router('POST', '^/$', function() {
    postMain();
});

header('Content-Type: application/json');
header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
echo json_encode(array('success' => false, 'error' => 'Invalid request method'));
exit;