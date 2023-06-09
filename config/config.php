<?php

 global $CONFIG;
$CONFIG = [
    /** The bearer token to authenticate to the server.
     * All tokens in the array will be accepted.
     */
    'BEARER_TOKEN' =>  ["Bearer YWRtaW46YWRtaW4="], # default: "Bearer YWRtaW46YWRtaW4="

    /**
     * The database configuration.
     * You can use by default sqlite
     * If you want use mysql, you need to change type to mysql
     * Only SQLite and MySQL are supported.
     */
    'DATABASE' => [
        'type' => 'sqlite', # default: 'sqlite'
        'location' => './../database/database.sqlite', # default: '/../db/database.sqlite' (only for sqlite)
        'host' => 'localhost', # default: 'localhost' (only for mysql)
        'user' => 'root', # default: 'root' (only for mysql)
        'password' => 'root', # default: 'root' (only for mysql)
        'database' => 'sharex' # default: 'sharex' (only for mysql)
    ],

    /** If you want disable the upload on your ShareX server.
     * Replace the value of "UPLOAD_ENABLE" variable.
     * true => The server is enabled
     * false => The server is disabled
     * If other value is set, the server will be disabled.
     **/
    'UPLOAD_ENABLE' => true, # default: true

    /**
     * If you want disable the view system of image uploaded.
     * Replace the value of "VIEW_ENABLE" variable.
     * true => The view system is enabled
     * false => The view system is disabled
     * If other value is set, the view system will be disabled.
     **/
    'VIEW_ENABLE' => true, # default: true

    /**
     * The base folder where the files will be uploaded.
     * WARNING / after the folder name is required.
     **/
    'UPLOAD_FOLDER' => "../files/", # default: "../files/"

    /**
     * The base URL where the files will be uploaded.
     * WARNING / after the folder url is required.
     **/
    'BASE_URL' => "http://{$_SERVER['SERVER_NAME']}/", # default: "https://{$_SERVER['SERVER_NAME']}"

    /**
     * The length of the random string that will be generated.
     **/
    'RANDOM_STRING_LENGTH' => 12, # default: 12

    /**
     * The characters that will be used to generate the random string.
     * WARNING: Addinc char: . or / or \ or % or ' can be cause a LFI (Local File Inclusion) vulnerability.
     **/
    'ALL_USABLE_CHARS' => "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", # default: "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"

    /**
     * The max retry to generate a random string.
     **/
    'MAX_TRY' => 10, # default: 10
    /**
     * The extensions corresponding to the type of file.
     * You can add or remove your own extensions. But don't forget to add the extension in the corresponding type and add in the 'EXTENSION_ENABLE'.
     */
    'EXTENSION_DEFINITION' => [
        'IMG' => ['png', 'jpg', 'jpeg', 'gif', 'webp'], # default: ['png', 'jpg', 'jpeg', 'gif', 'webp']
        'EXECUTABLE' => ['exe', 'msi', 'bat', 'sh', 'ps1', 'vbs'], # default: ['exe', 'msi', 'bat', 'sh', 'ps1', 'vbs']
        'EXECUTABLE_INDIRECT' => ['jar', 'js', 'py'], # default: ['jar', 'js', 'py']
        'ARCHIVE' => ['zip', 'rar', '7z', 'tar', 'gz'], # default: ['zip', 'rar', '7z', 'tar', 'gz']
        'DOCUMENT' => ['txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], # default: ['txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
        'VIDEO' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm'], # default: ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv', 'webm']
        'AUDIO' => ['mp3', 'wav', 'ogg', 'flac', 'wma', 'm4a'], # default: ['mp3', 'wav', 'ogg', 'flac', 'wma', 'm4a']
    ],

    /**
     * The extensions that will be enable and disable.
     * You can enable or disable your own extensions. But don't forget to add the extension in the 'EXTENSION_DEFINITION'.
     * If OTHER is enable, all extensions not in the 'EXTENSION_DEFINITION' will be allowed.
     * If ALL is enable, other rule will be ignored.
     **/
    'EXTENSION_ENABLE' => [
        'IMG' => true, # default: true
        'EXECUTABLE' => false, # default: false
        'EXECUTABLE_INDIRECT' => false, # default: false
        'ARCHIVE' => true, # default: false
        'DOCUMENT' => true, # default: false
        'VIDEO' => true, # default: false
        'AUDIO' => true, # default: false
        'OTHER' => true, # default: false
        'ALL' => false # default: true
    ],

    /**
     * The maximum size of the file that will be uploaded.
     * The value is in megabytes (mb).
     */
    'MAX_FILE_SIZE' => 50, # default: 10
    ];

function returnConfig() {
    global $CONFIG;
    return $CONFIG;
}
?>