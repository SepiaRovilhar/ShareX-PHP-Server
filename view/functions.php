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
        // if the connection failed
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

function checkExist($fileName): array
{
    /**
     * Check if the file name exist in the database
     * @param string $fileName
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    // If the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }

        // test if the file exist
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetchArray();

        // if the file does not exist
        if ($result === false) {
            return [404, 'Not Found', 'File not found'];
        }
        return [200, 'OK', 'The name is existing'];

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
        // test if the file exist
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetch_array();
        // if the file does not exist
        if ($result === null) {
            return [404, 'Not Found', 'File not found'];
        }
        return [200, 'OK', 'The name is existing'];
    }

    return [500, 'Internal Server', "The database type $databaseType is not supported"];

}

function checkName($fileName): array
{
    /**
     * Check if the file name is valid
     * @param string $fileName
     * @return array
     */
    $CONFIG = returnConfig();
    // if the function str_contains does not exist
    if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle)
        {
            return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
    }
    // if file name contains ' or " or .
    if (str_contains($fileName, "'") || str_contains($fileName, '"') || str_contains($fileName, '.')) {
        return [400, 'Bad Request', 'Invalid file name'];
    }

    $allAcceptableChar = $CONFIG['ALL_USABLE_CHARS'];
    $allAcceptableCharTab = str_split($allAcceptableChar);
    // for each char in the file name
    foreach (str_split($fileName) as $char) {
        // if the char is not in the acceptable char tab
        if (!in_array($char, $allAcceptableCharTab)) {
            return [400, 'Bad Request', 'Invalid file name'];
        }
    }
    return [200, 'OK', 'The file name is valid'];
}

function viewFromDatabase($fileName): array
{
    /**
     * Verify the file name and check if it exist in the database
     * @param string $fileName
     * @return array
     */
    // check if the file name is valid
    $nameValid = checkName($fileName);
    if ($nameValid[0] !== 200) {
        return $nameValid;
    }
    return checkExist($fileName);
}

function getExtensionFromDb($fileName)
{
    /**
     * Get the file extension from the database
     * @param string $fileName
     * @return array
     */
    $CONFIG = returnConfig();
    $databaseType = $CONFIG['DATABASE']['type'];
    // If the database type is sqlite
    if ($databaseType === 'sqlite') {
        $databaseLocation = $CONFIG['DATABASE']['location'];
        $database = new SQLite3($databaseLocation);
        // if the connection failed
        if (!$database) {
            return [500, 'Internal Server', "The database connection failed"];
        }
        // test if the file exist
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetchArray();
        // if the file does not exist
        if ($result === false) {
            return [404, 'Not Found', 'File not found'];
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
        // test if the file exist
        $query = $database->query("SELECT * FROM files WHERE id='$fileName'");
        $result = $query->fetch_array();
        // if the file does not exist
        if ($result === null) {
            return [404, 'Not Found', 'File not found'];
        }
    } else {
        return [500, 'Internal Server', "The database type $databaseType is not supported"];
    }
    return [200, 'OK', $result['extension']];
}