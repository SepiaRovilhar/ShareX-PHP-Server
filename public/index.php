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
        if (strpos($name, '/') !== false) {
            // there are / in the name
            $name = explode('/', $name);
            $name = $name[0];
            if ($name == 'delete') {
                header('Content-Type: application/json');
                header("{$_SERVER['SERVER_PROTOCOL']} 501 Not Implemented");
                echo json_encode(array('error' => 'Delete not implemented'));
            } else {
                $LFIProtectTrigger = detectLFI($name);
                if ($LFIProtectTrigger) {
                    header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
                    header('Content-Type: application/json');
                    echo json_encode(array('success' => false, 'error' => 'Invalid char in file name'));
                    exit;
                }
                getMain($name);
            }
        } else {
            $LFIProtectTrigger = detectLFI($name);
            if ($LFIProtectTrigger) {
                header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
                header('Content-Type: application/json');
                echo json_encode(array('success' => false, 'error' => 'Invalid char in file name'));
                exit;
            }
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