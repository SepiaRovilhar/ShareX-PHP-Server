<?php
require_once(dirname(__FILE__) . '/../config/config.php');

function checkDatabaseSetup(): array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        if (!file_exists($databaseLocation)) {
            touch($databaseLocation);
        }
        $database = new SQLite3($databaseLocation);
        # test if the table exist
        $query = $database->query("SELECT name FROM sqlite_master WHERE type='table' AND name='files'");
        $result = $query->fetchArray();
        if ($result === false) {
            # the table does not exist
            $database->query("CREATE TABLE files (id VARCHAR(255) PRIMARY KEY, deleter VARCHAR(255), extension VARCHAR(255), date VARCHAR(255))");
        }
        return [200, 'OK', 'The database is ready'];

    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * 
                                        FROM information_schema.tables
                                        WHERE table_schema = 'sharex' 
                                        AND TABLE_NAME  = 'files'
                                        LIMIT 1;");
        $result = $query->fetch_array();
        if ($result === null) {
            # the table does not exist
            $database->query("CREATE TABLE files (id VARCHAR(255) PRIMARY KEY, deleter VARCHAR(255), extension VARCHAR(255), date VARCHAR(255))");
        }
        return [200, 'OK', 'The database is ready'];

    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
}

function checkExist($fileName): array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetchArray();

        if ($result === false) {
            return [404, 'Not Found', 'File not found'];
        } else {
            return [200, 'OK', 'The name is existing'];
        }
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetch_array();
        if ($result === null) {
            return [404, 'Not Found', 'File not found'];
        } else {
            return [200, 'OK', 'The name is existing'];
        }
    }

    return [500, 'Internal Server', "The database type $databaseType is not supported"];

}

function checkName($fileName): array
{
    $CONFIG = returnConfig();
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle)
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }
    if (str_contains($fileName, "'") || str_contains($fileName, '"') || str_contains($fileName, '.') ) {
        return [400, 'Bad Request', 'Invalid file name'];
    }

    $allAcceptableChar = $CONFIG['ALL_USABLE_CHARS'];
    $allAcceptableCharTab = str_split($allAcceptableChar);
    // for each char in the file name
    foreach (str_split($fileName) as $char) {
        // if the char is not in the array of acceptable char
        if ($char === '.') {
            continue;
        }
        if (!in_array($char, $allAcceptableCharTab)) {
            return [400, 'Bad Request', 'Invalid file name'];
        }
    }
    return [200, 'OK', 'The file name is valid'];
}

function viewFromDatabase($fileName) : array
{
    $nameValid = checkName($fileName);
    if ($nameValid[0] !== 200) {
        return [$nameValid[0], $nameValid[1], $nameValid[2]];
    }
    $exitInDb = checkExist($fileName);
    return [$exitInDb[0], $exitInDb[1], $exitInDb[2]];
}

function getExtensionFromDb($fileName){
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetchArray();

        if ($result === false) {
            return [404, 'Not Found', 'File not found'];
        } else {
            return [200, 'OK', $result['extension']];
        }
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetch_array();

        if ($result === null) {
            return [404, 'Not Found', 'File not found'];
        } else {
            return [200, 'OK', $result['extension']];
        }
    }

    return [500, 'Internal Server', "The database type $databaseType is not supported"];
}