<?php
session_start();

include 'config.php';
include $credentials_file;
include 'library/user_functions.php';
include 'library/system_lib.php';

// Build our database connection
try {
    $conn = get_database_connection($database_host, $database, $username, $password);
} catch (SDCDatabaseException $e) {
    // Database connection failed for some reason
    echo(create_system_error_page("Database Connection Error", $e->getMessage()));
    // and die
    exit;
}

// Handle coder id verification
$coderid = verify_and_set_coder_id($conn);

// Only admins can access this page
protect_admin_page($conn, $coderid);

$conn->beginTransaction();

// First clear the table
$query = "delete from irrelevantdocuments";
$conn->prepare($query)->execute();

// Prepare the query that will do the inserts
$query = "insert into irrelevantdocuments (document) values (:document)";
$stmt = $conn->prepare($query);

$list = explode("\r\n", trim($_GET['documentnames']));
$raw_list = array();

foreach ($list as $l) {
    $raw_list[] = trim($l);
}

foreach (array_unique($raw_list) as $d) {
    echo("Adding $d\n");
    $stmt->execute(array(':document' => trim($d)));
}

$conn->commit();

// Redirect back to the admin pages
$target = "admin.php";
$host  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$message = urlencode(count($raw_list)." irrelevant document ids have been uploaded.");
header("Location: $host$uri/$target?message=$message");
exit;

?>
