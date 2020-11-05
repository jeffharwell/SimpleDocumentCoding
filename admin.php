<?php
session_start();

include 'config.php';
include 'library/system_lib.php'; // library for database connections, system errors, etc
include $credentials_file; // the file containing the database credentials, pointed to from 'config.php'
include 'library/user_functions.php';

// Build our database connection
try {
    $conn = get_database_connection($database_host, $database, $username, $password);
} catch (SDCDatabaseException $e) {
    // Database connection failed for some reason
    echo(create_system_error_page("Database Connection Error", $e->getMessage()));
    // and die
    exit;
}
$coderid = verify_and_set_coder_id($conn);
protect_admin_page($conn, $coderid);
$page_title = "Admin";
$header_title = "Administrative Functions";

?>
<html>
<head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
</head>
<body>

<!-- Display any notification messages that we may be passed -->
<?php if ($_GET['message']) { ?>
<div class="alert alert-info" role="alert">
    <?php echo($_GET['message']) ?>
</div>
<?php } ?>

<?php include 'includes/title.php' ?>
<div class="container">
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="list.php">Code Documents</a> 
	</div></div>
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="invite_coder.php">Create and Invite a New Coder</a> 
	</div></div>
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="invite_coder.php?admin=true">Create and Invite a New Admin Coder</a> 
	</div></div>
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="get_irrelevant_list.php">Upload List of Irrelevant Documents</a> 
	</div></div>
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="upload_coding_list.php">Upload a List of Codings as JSON</a> 
	</div></div>
	<div class="row mb-2 mt-2"><div class="col">
		<a class="btn btn-primary" href="index.php">Logout</a> 
	</div></div>

</div>

</body>
</html>
