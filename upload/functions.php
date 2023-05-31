<?php
require_once(dirname(__FILE__) . '/../config/config.php');

function generateRandomString($length, $authorisedChar): string
{
    $str = '';
    // Generation of a random string
    for ($i = 0; $i < $length; $i++) {
        $str .= $authorisedChar[random_int(0, strlen($authorisedChar) - 1)];
    }
    return $str;
}

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

function checkAvailability($key, $value): array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    # if key or value contain ' or " the query exit
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle): bool
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }
    if (str_contains($key, "'") || str_contains($key, '"') || str_contains($value, "'") || str_contains($value, '"')) {
        return [500, 'Internal Server', "The key or the value contain ' or \" "];
    }
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("SELECT * FROM files WHERE $key='$value'");
        $result = $query->fetchArray();

        if ($result === false) {
            return [200, 'OK', 'The name is available'];
        } else {
            return [409, 'Conflict', 'The name is not available'];
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
        $query = $database->query("SELECT * FROM files WHERE $key='$value'");
        $result = $query->fetch_array();

        if ($result === null) {
            return [200, 'OK', 'The name is available'];
        } else {
            return [409, 'Conflict', 'The name is not available'];
        }
    }

    return [500, 'Internal Server', "The database type $databaseType is not supported"];

}

function insertInDatabase($id, $delete, $extension, $date): array
{
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        $query = $database->query("INSERT INTO files (id, deleter, extension, date) VALUES ('$id', '$delete', '$extension', '$date')");
        if ($query === false) {
            return [500, 'Internal Server', "The insertion failed"];
        } else {
            return [200, 'OK', 'The insertion was successful'];
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
        $query = $database->query("INSERT INTO files (id, deleter, extension, date) VALUES ('$id', '$delete', '$extension', '$date')");
        if ($query === false) {
            return [500, 'Internal Server', "The insertion failed"];
        } else {
            return [200, 'OK', 'The insertion was successful'];
        }
    }

    return [500, 'Internal Server', "The database type $databaseType is not supported"];
}

function uploadToDatabase()
{
    $setup = checkDatabaseSetup();
    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    if ($setup[0] !== 200) {
        return $setup;
    }
    $CONFIG = returnConfig();
    $length = $CONFIG['RANDOM_STRING_LENGTH'];
    $usableChar = $CONFIG['ALL_USABLE_CHARS'];
    $randomName = generateRandomString($length, $usableChar);
    $available = checkAvailability('id', $randomName);
    $i = 0;

    while ($available[0] !== 200) {
        # the file already exist
        $randomName = generateRandomString($length, $usableChar);
        $available = checkAvailability('id', $randomName);
        $i++;
        if ($i > $CONFIG['MAX_TRY']) {
            return [500, 'Internal Server', "Too many try to generate a random name"];
        }
    }

    $randomDelete = generateRandomString(24, $usableChar);
    $available = checkAvailability('deleter', $randomDelete);
    $i = 0;
    while ($available[0] !== 200) {
        # the delete key already exist
        $randomDelete = generateRandomString(24, $usableChar);
        $available = checkAvailability('deleter', $randomDelete);
        $i++;
        if ($i > $CONFIG['MAX_TRY']) {
            return [500, 'Internal Server', "Too many try to generate a delete key"];
        }
    }
    $date = date('d-m-Y H:i:s');
    $insertion = insertInDatabase($randomName, $randomDelete, $extension, $date);

    if ($insertion[0] !== 200) {
        return [500, 'Internal Server', "The insertion failed"];
    } else {
        return [200, 'OK', $randomName, $randomDelete];
    }
}