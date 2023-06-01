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

function checkDeletion($fileName): array
{
    $CONFIG = returnConfig();
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle)
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }
    if (str_contains($fileName, "'") || str_contains($fileName, '"') || str_contains($fileName, '.')) {
        return [400, 'Bad Request', 'Invalid file name'];
    }

    $allAcceptableChar = $CONFIG['ALL_USABLE_CHARS'];
    $allAcceptableCharTab = str_split($allAcceptableChar);
    // for each char in the file name
    foreach (str_split($fileName) as $char) {
        if (!in_array($char, $allAcceptableCharTab)) {
            return [400, 'Bad Request', 'Invalid file name'];
        }
    }
    return [200, 'OK', 'The file name is valid'];
}

function checkExist($deleter) : array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("SELECT * FROM files WHERE deleter='$deleter'");
        $result = $query->fetchArray();
        if ($result === false) {
            return [404, 'Not Found', 'The file does not exist'];
        }
        return [200, 'OK', 'The file exist'];
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * FROM files WHERE deleter='$deleter'");
        $result = $query->fetch_array();
        if ($result === null) {
            return [404, 'Not Found', 'The file does not exist'];
        }
        return [200, 'OK', 'The file exist'];
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
}
function returnNameAndExtention($deleter) : array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    # with deleter='$deleter' get on the database the file name and extension from the database
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("SELECT * FROM files WHERE deleter='$deleter'");
        $result = $query->fetchArray();
        if ($result === false) {
            return [404, 'Not Found', 'The file does not exist'];
        }
        $fileName = $result['id'];
        $extension = $result['extension'];
        return [200, 'OK', $fileName, $extension];
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * FROM files WHERE deleter='$deleter'");
        $result = $query->fetch_array();
        if ($result === null) {
            return [404, 'Not Found', 'The file does not exist'];
        }
        $fileName = $result['id'];
        $extension = $result['extension'];
        return [200, 'OK', $fileName, $extension];
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
}

function deleteOnDatabase($deleter) : array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $database->query("DELETE FROM files WHERE deleter='$deleter'");
        return [200, 'OK', 'The file has been deleted'];
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $database->query("DELETE FROM files WHERE deleter='$deleter'");
        return [200, 'OK', 'The file has been deleted'];
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
}
