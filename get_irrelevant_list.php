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

$page_title = "Upload Irrelevant";
$header_title = "Upload Irrelevant Documents";
?>

<html>
<head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
</head>
<body>
<?php include 'includes/title.php' ?>

<div class="container-fluid">
  <div class="row mb-3"><div class="col">The document ids listed below will replace the existing list of irrelevant documents if any.</div></div>
  <div class="row">
    <div class="col">
        <div class="sidebar-item sticky-top">
            <form action="upload_irrelevant.php" methed="get">
                <div class="form-group">
                    <label for="documentnames">List Irrelevant Documents One Per Line</label> 
                    <textarea class="form-control" name="documentnames" id="documentnames" rows="10"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit List</button>
                <a class="btn btn-primary" href="admin.php" role="button">Return to Admin Page</a>
            </form>
        </div> <!-- ends the sticky sidebar-item -->
    </div> <!-- ends class="col" which contains the form -->
  </div> <!-- ends class="row" which contains the document and the form -->
</div> <!-- ends class="container" -->

</body>
</html>
