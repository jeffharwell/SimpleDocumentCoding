<?php

include 'library/user_functions.php';

## https://stackoverflow.com/questions/173851/what-is-the-canonical-way-to-determine-commandline-vs-http-execution-of-a-php-s
if (php_sapi_name() != "cli") {
    echo("<b>Setup can only be run from the command line.</b>");
    exit;
}

if (count($argv) != 7) {
    echo("\n");
    echo("Usage: setup.php database_user database_password database_name database_hostname credentials_file_location corpus_directory\n");
    echo("       database_user             [=] the username to use when connecting to the MySQL database\n");
    echo("       database_password         [=] the password to use when connecting to the MySQL database\n");
    echo("       database_hostname         [=] the hostname of the database server\n");
    echo("       credentials_file_location [=] the full path of the directory where the database credentials should be written.\n");
    echo("                                     This should not be in a location in which it could be served directly by the web server\n");
    echo("                                     but must be readable by the user that the web server is running as.\n");
    echo("       corpus_directory          [=] the full path of the directory that contains the corpus of documents being coded\n");
    exit;
}

function add_separator($s) {
    if (substr( $s, -1) == DIRECTORY_SEPARATOR) {
        return($s);
    } else {
        return($s.DIRECTORY_SEPARATOR);
    }
}

$username = $argv[1];
$password = $argv[2];
$database = $argv[3];
$database_host = $argv[4];
// This works for Linux/Unix, if you are on windows you will need to implement your own path creation logic
$credentials_file = add_separator($argv[5])."simpledocumentcoding_configuration".uniqid().".php";
$corpus_directory = add_separator($argv[6]);

## Does the config file exist, if so exit with an error
if (file_exists($credentials_file)) {
    echo("Configuration file $credentials_file already exists.\n");
    echo("If you are wanting to re-setup the application please remove this file and try again.\n");
    exit;
} 

// https://phpbestpractices.org/#mysql
// PDO::ATTR_ERRMODE enables exceptions for errors.  This is optional but can be handy.
// PDO::ATTR_PERSISTENT disables persistent connections, which can cause concurrency issues in certain cases.  See "Gotchas".
$pdo_args = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false);
try {
    $conn = new PDO("mysql:host=$database_host;dbname=$database;charset=utf8mb4",$username,$password,$pdo_args);
} catch (Exception $e) {
    if ($e->getCode() == "1045") {
        echo($e->getMessage()."\n");
        echo("Unable to log in as user '$username'\n");
        echo("  1) Has the user been created in the database and is the password correct?\n");
        echo("      create user '$username'@'%' identified by '$password';\n");
        exit;
    } else if ($e->getCode() == "1044") {
        echo($e->getMessage()."\n");
        echo("Access denied for user '$username' to database '$database'.\n");
        echo("  1) Does the database '$database' exist?\n");
        echo("      create database $database;\n");
        echo("  2) Does the user $username have all privileges on the database $database?\n");
        echo("      grant all privileges on $database.* to '$username'@'%';\n");
        echo("Please ensure the above are correct and try the setup script again.\n");
        exit;
    } else if ($e->getCode() == "1049") {
        echo($e->getMessage()."\n");
        echo("Does the database '$database' exist?\n");
        echo("  create database $database;\n");
        exit;
    } else if ($e->getCode() == "2002") {
        echo($e->getMessage()."\n");
        echo("Unable to connect to the database $database on host $database_host, is it running and avaiable for connection?\n");
        exit;
    } else {
        echo("An unknown error occured when connecting to the database\n");
        echo($e->getCode()."\n");
        echo($e->getMessage()."\n");
        exit;
    }
}

// See if the table exists, if so abandon ship
function DoesTableExist($connection) {
    $query = "select * from codingresponse";
    try {
        $result = $connection->query($query);
    } catch (Exception $e) {
        if ($e->getCode() == "42S02") {
            return(False);
        }
    }
    return(True);
}

if (DoesTableExist($conn)) {
    echo("The codingresponse table already exists in database $database, if you want to do a new setup please delete the table and try again.\n");
    exit;
}

// Throw a warning if the corpus directory does not exist
if (!is_dir($corpus_directory)) {
    echo("-------\n");
    echo("WARNING: The directory $corpus_directory does not exist. You must create it\n");
    echo("         and put the files that need to be coded\n");
    echo("         into $corpus_directory before the application will function.\n");
    echo("-------\n");
}

//
// Do the Setup
// 1) Create the database table
// 2) Create the configuration file pointing to the credentials file
// 3) Create the credentials file
//

// 1) Create the database table
$create_table = "create table codingresponse (
                    ID INT(11) AUTO_INCREMENT PRIMARY KEY,
                    coder VARCHAR(50) NOT NULL,
                    document VARCHAR(200) NOT NULL,
                    response TEXT NOT NULL,
                    UNIQUE(coder, document)) ENGINE=InnoDB ROW_FORMAT=DYNAMIC
                ";
$conn->exec($create_table);

// Create the coder table
$create_coder_table = "create table coders (
                           coder_id VARCHAR(50) PRIMARY KEY,
                           invited_by_coder VARCHAR(50) NOT NULL
                       )";
$conn->exec($create_coder_table);

// Create the admin table
$create_coder_table = "create table admincoders (
                           coder_id VARCHAR(50) PRIMARY KEY
                       )";
$conn->exec($create_coder_table);

// Create the irrelevant document table 
$create_coder_table = "create table irrelevantdocuments (
                           document VARCHAR(200) PRIMARY KEY
                       ) ENGINE=InnoDB ROW_FORMAT=DYNAMIC";
$conn->exec($create_coder_table);


// 2) Create the configuration file
echo("* Creating config.php. This file tells the web application where to find\n");
echo("  the credentials for the database connection.\n");
$config_file_content = "<?php
\$credentials_file = '$credentials_file';
?>\n";
file_put_contents("config.php",$config_file_content);

echo("\n\n* Type the query keywords below seperated by spaces, this will be used to highlight\n");
echo("  text during coding. You can modify these keywords later by editing $credentials_file.\n\n");
$line = readline("Keywords: ");
$raw_elements = explode(' ', trim($line));
$trimmed_elements = array();
foreach ($raw_elements as $e) {
    $t = trim($e);
    if ($t != "") {
        $trimmed_elements[] = $t;
    }
}
$query_keyword_array = "array('".implode($trimmed_elements,"','")."')";

echo("\n\n* Enter the URL of the coding standard.\n");
echo("  You can modify this later by editing $credentials_file.\n\n");
$line = readline("URL for Coding Standard: ");
$coding_standard_url = trim($line);


$credentials_file_content = "<?php
\$username = '$username';
\$password = '$password';
\$database = '$database';
\$database_host = '$database_host';
\$corpus_directory = '$corpus_directory';
\$query_keywords = $query_keyword_array;
\$coding_standard_url = '$coding_standard_url';
?>
";
echo("\n\n* Creating $credentials_file.\n");
echo("   This file tells the web application how to connect to the database.\n");
echo("   Please ensure that it cannot be served by your \n");
echo("   web-server as clear text or you will compromise the\n");
echo("   security of your database.\n");
file_put_contents($credentials_file, $credentials_file_content);

// 3) Create the inital admin coder
$new_coderid = create_coder($conn, 0, true);
echo("* Admin Coder is id $new_coderid.\n");
echo("    This is your first valid coder and can be used to both code documents\n");
echo("    in the corpus and invite other coders.\n");
echo("    Log in using this id to get started. The admin page can be accessed by\n");
echo("    navigating to admin.php\n");

?>
