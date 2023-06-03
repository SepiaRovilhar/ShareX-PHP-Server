<?php
require_once(dirname(__FILE__) . '/../config/config.php');

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
            $result = $query->fetchArray();

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
        if ($result === null) {
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

function checkDeletion($deleteKey): array
{
    /**
     * Check if the deletion key is valid
     * @param string $deleteKey
     * @return array
     */
    $CONFIG = returnConfig();
    // if the function str_contains not exist
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle)
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }
    // if the delete key contain a ' ou " or .
    if (str_contains($deleteKey, "'") || str_contains($deleteKey, '"') || str_contains($deleteKey, '.')) {
        return [400, 'Bad Request', 'Invalid file name'];
    }

    $allAcceptableChar = $CONFIG['ALL_USABLE_CHARS'];
    $allAcceptableCharTab = str_split($allAcceptableChar);
    // for each char in the file name
    foreach (str_split($deleteKey) as $char) {
        // if the char is not in the allAcceptableCharTab²
        if (!in_array($char, $allAcceptableCharTab)) {
            return [400, 'Bad Request', 'Invalid delete key name'];
        }
    }
    return [200, 'OK', 'The delete key name is valid'];
}

function checkExist($deleteKey): array
{
    /**
     * Check if the delete key exist
     * @param string $deleteKey
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }
        // test if the delete key exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetchArray();
        // if the delete key does not exist
        if ($result === false) {
            return [404, 'Not Found', 'The delete key does not exist'];
        }
        // if the database type is mysql
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
        // test if the delete key exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetch_array();
        // if the delete key does not exist
        if ($result === null) {
            return [404, 'Not Found', 'The delete key does not exist'];
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
    return [200, 'OK', 'The file exist'];
}

function returnNameAndExtention($deleteKey): array
{
    /**
     * Return the file name and extension
     * @param string $deleteKey
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    // if the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }
        // test if the delete key exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetchArray();
        // if the delete key does not exist
        if ($result === false) {
            return [404, 'Not Found', 'The delete key does not exist'];
        }
        $fileName = $result['id'];
        $extension = $result['extension'];
        // if the database type is mysql
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
        // test if the delete key exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetch_array();
        // if the delete key does not exist
        if ($result === null) {
            return [404, 'Not Found', 'The delete key does not exist'];
        }
        $fileName = $result['id'];
        $extension = $result['extension'];
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
    return [200, 'OK', $fileName, $extension];
}

function deleteOnDatabase($deleteKey): array
{
    /**
     * Delete the file on the database
     * @param string $deleteKey
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }
        $database->query("DELETE FROM files WHERE deleteKey='$deleteKey'");
        // test if the deleteKey exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetchArray();
        // if the deleteKey exist
        if ($result !== false) {
            return [500, 'Internal Server', "The file has not been deleted on the database"];
        }
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
        $database->query("DELETE FROM files WHERE deleteKey='$deleteKey'");
        // test if the deleteKey exist
        $query = $database->query("SELECT * FROM files WHERE deleteKey='$deleteKey'");
        $result = $query->fetch_array();
        // if the deleteKey exist
        if ($result !== null) {
            return [500, 'Internal Server', "The file has not been deleted on the database"];
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
    return [200, 'OK', 'The file has been deleted'];
}
