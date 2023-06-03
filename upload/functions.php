<?php
require_once(dirname(__FILE__) . '/../config/config.php');

function generateRandomString($length, $authorisedChar): string
{
    /**
     * Generate a random string
     * @param int $length
     * @param string $authorisedChar
     * @return string
     */
    $str = '';
    // Generation of a random string
    for ($i = 0; $i < $length; $i++) {
        $str .= $authorisedChar[random_int(0, strlen($authorisedChar) - 1)];
    }
    return $str;
}

function checkDatabaseSetup(): array
{
    /**
     * Check if the database is set up
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];

    // If the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        // If the database does not exist, create it
        if (!file_exists($databaseLocation)) {
            touch($databaseLocation);
        }

        $database = new SQLite3($databaseLocation);
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }

        // test if the table exist
        $query = $database->query("SELECT name FROM sqlite_master WHERE type='table' AND name='files'");
        $result = $query->fetchArray();

        // if the table does not exist, create it
        if ($result === false) {
            $database->query("CREATE TABLE files (id VARCHAR(255) PRIMARY KEY, deleteKey VARCHAR(255), extension VARCHAR(255), date VARCHAR(255))");
            // test if the table exist
            $query = $database->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $result = $query->fetchArray(SQLITE3_ASSOC);

            // if the table does not exist, return error
            if ($result === false) {
                return [500, 'Internal Server', "Cannot setup the database"];
            }
        }
        // If the database type is mysql
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        // if the connection failed
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }

        // test if the table exist
        $query = $database->query("SELECT * 
                                        FROM information_schema.tables
                                        WHERE table_schema = 'sharex' 
                                        AND TABLE_NAME  = 'files'
                                        LIMIT 1;");

        $result = $query->fetch_array();
        // if the table does not exist, create it
        if($result === null){
            $database->query("CREATE TABLE files (id VARCHAR(255) PRIMARY KEY, deleteKey VARCHAR(255), extension VARCHAR(255), date VARCHAR(255))");
            // test if the table exist
            $query = $database->query("SELECT * 
                                        FROM information_schema.tables
                                        WHERE table_schema = 'sharex' 
                                        AND TABLE_NAME  = 'files'
                                        LIMIT 1;");
            $result = $query->fetch_array();
            // if the table does not exist, return error
            if ($result === null) {
                return [500, 'Internal Server', "Cannot setup the database"];
            }
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }

    return [200, 'OK', 'The database is ready'];
}

function checkAvailability($key, $value): array
{
    $CONFIG = returnConfig();
    // Check if the database is set up
    $setup = checkDatabaseSetup();
    // If the database is not ready
    if ($setup[0] !== 200) {
        return $setup;
    }

    $databaseType = $CONFIG['DATABASE']['type'];
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle): bool
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }

    // Check if the key or the value contain ' or "
    if (str_contains($key, "'") || str_contains($key, '"') || str_contains($value, "'") || str_contains($value, '"')) {
        return [500, 'Internal Server', "The key or the value contain ' or \" "];
    }
    // If the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if(!$database){
            return [500, 'Internal Server', "The database connection failed"];
        }
        $query = $database->query("SELECT * FROM files WHERE $key='$value'");
        $result = $query->fetchArray(SQLITE3_ASSOC);
        if($result !== false){
            return [409, 'Conflict', 'The name is not available'];
        }
        // Database type is mysql
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        // if the connection failed
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
        $query = $database->query("SELECT * FROM files WHERE $key='$value'");
        $result = $query->fetch_array(MYSQLI_ASSOC);
        if($result !== null){
            return [409, 'Conflict', 'The name is not available'];
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
    return [200, 'OK', 'The name is available'];
}

function insertInDatabase($id, $delete, $extension, $date): array
{
    $CONFIG = returnConfig();
    // Check if the database is set up
    $setup = checkDatabaseSetup();
    // If the database is not ready
    if ($setup[0] !== 200) {
        return $setup;
    }

    $databaseType = $CONFIG['DATABASE']['type'];
    // If the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if(!$database){
            return [500, 'Internal Server', "The database connection failed"];
        }
        // If the database type is mysql
    } else if ($databaseType === 'mysql') {
        $databaseHost = $CONFIG['DATABASE']['host'];
        $databaseUser = $CONFIG['DATABASE']['user'];
        $databasePassword = $CONFIG['DATABASE']['password'];
        $databaseName = $CONFIG['DATABASE']['database'];
        $database = new mysqli($databaseHost, $databaseUser, $databasePassword, $databaseName);
        // if the connection failed
        if ($database->connect_error) {
            return [500, 'Internal Server', "The database connection failed: " . $database->connect_error];
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }

    // Insert the data in the database
    $query = $database->query("INSERT INTO files (id, deleteKey, extension, date) VALUES ('$id', '$delete', '$extension', '$date')");
    // If the query failed
    if ($query === false || $query === null) {
        return [500, 'Internal Server', "Cannot insert the data in the database"];
    }
    return [200, 'OK', 'The data has been inserted in the database'];
}

function uploadToDatabase()
{
    $CONFIG = returnConfig();
    // Check if the database is set up
    $setup = checkDatabaseSetup();
    // If the database is not ready
    if ($setup[0] !== 200) {
        return $setup;
    }
    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    // If the database is not ready
    if ($setup[0] !== 200) {
        return $setup;
    }
    $length = $CONFIG['RANDOM_STRING_LENGTH'];
    $usableChar = $CONFIG['ALL_USABLE_CHARS'];
    // Generate a random name
    $randomName = generateRandomString($length, $usableChar);
    // Check if the name is available
    $available = checkAvailability('id', $randomName);
    $i = 0;

    // While random name generated is not available
    while ($available[0] !== 200) {
        // Generate a new random name
        $randomName = generateRandomString($length, $usableChar);
        // Check if the name is available
        $available = checkAvailability('id', $randomName);
        $i++;
        // If the number of try is too high
        if ($i > $CONFIG['MAX_TRY']) {
            return [500, 'Internal Server', "Too many try to generate a random name"];
        }
    }

    // Generate a random delete key
    $randomDelete = generateRandomString(24, $usableChar);
    // Check if the delete key is available
    $available = checkAvailability('deleteKey', $randomDelete);
    $i = 0;
    // While random delete key generated is not available
    while ($available[0] !== 200) {
        // Generate a new random delete key
        $randomDelete = generateRandomString(24, $usableChar);
        // Check if the delete key is available
        $available = checkAvailability('deleteKey', $randomDelete);
        $i++;
        if ($i > $CONFIG['MAX_TRY']) {
            return [500, 'Internal Server', "Too many try to generate a delete key"];
        }
    }
    $date = date('d-m-Y H:i:s');
    // Insert the file data in the database
    $insertion = insertInDatabase($randomName, $randomDelete, $extension, $date);
    $check = checkDatabaseSetup();
    // If the file is not in the database
    if ($check[0] !== 200) {
        return $check;
    }
    // If the insertion failed
    if ($insertion[0] !== 200) {
        return [500, 'Internal Server', "The insertion failed"];
    }
    return [200, 'OK', [$randomName, $randomDelete]];
}