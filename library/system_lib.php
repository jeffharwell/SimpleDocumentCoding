<?php

//
// Classes
//

/**
 * SimpleDocumentCoding Error exception.
 *
 * Used when there is any kind of error when connecting or writing
 * to the database.
 */
class SDCDatabaseException extends Exception { }

//
// Function
//

/**
 * Creates an HTML page describing a system error
 *
 * Using the Bootstrap alert class to display a HTML error page that can be used
 * to describe a system error.
 *
 * @param string $title The title of the error page
 * @param string $message The message to put on the error page
 *
 * @return string The HTML text as a string 
 */
function create_system_error_page($title, $message) {
    $code = <<<EOD
            <html><head><title>$title</title>
            <!-- CSS -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
            <!-- jQuery and JS bundle w/ Popper.js -->
            <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
            </head>
             <body>
                   <div class="alert alert-danger" role="alert">$message</div> 
                   <p>This is a system error. Please contact the administrator of the site.</p>
             </body></html>
EOD;
    return($code);
}

/**
 * Creates the database connection
 *
 * Creates and returns a connection to the database. Throws an Exception if
 * there is an error during connection.
 *
 * @param string $database_host the hostname of the database
 * @param string $database the name of the database
 * @param string $username the username to use when connecting
 * @param string $password the password to use when connecting
 *
 * @throws SDCDatabaseException custom SimpleDocumentCoding database exception
 *
 * @return PDO a PDO database connection
 */
function get_database_connection($database_host, $database, $username, $password) {
    $pdo_args = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false);
    try {
        $conn = new PDO("mysql:host=$database_host;dbname=$database;charset=utf8mb4",$username,$password,$pdo_args);
        return($conn); 
    } catch (Exception $e) {
        if ($e->getCode() == "1045") {
            // This is an incorrect username or password
            error_log("Incorrect username $username or bad password: ".$e->getMessage());
            throw new SDCDatabaseException("Database credentials are incorrect");
        } else if ($e->getCode() == "1044") {
            // This is either the database doesn't exist or the user does not have access to it
            error_log("Access denied for $username to database $database: ".$e->getMessage()."\n");
            throw new SDCDatabaseException("Database does not exist or user does not have access.");
        } else if ($e->getCode() == "2002") {
            // The database is down or we cannot get access for some reason.
            error_log("Cannot connect to the database $database: ".$e->getMessage());
            throw new SDCDatabaseException("Cannot connect to the database.");
        } else {
            error_log("An unknown error occurred when trying to connect to database $database: ".$e->getCode()." ".$e->getMessage());
            throw new SDCDatabaseException("An unknown error occured.");
            echo("An unknown error occured when connecting to the database\n");
            echo($e->getCode()."\n");
            echo($e->getMessage()."\n");
            exit;
        }
    }
}

?>
