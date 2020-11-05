<?php
session_start();

include 'config.php';
include 'library/system_lib.php'; // library for database connections, system errors, etc
include $credentials_file; // the file containing the database credentials, pointed to from 'config.php'
include 'library/user_functions.php'; // functions around the creation and manipulations of coding users

// Build our database connection
try {
    $conn = get_database_connection($database_host, $database, $username, $password);
} catch (SDCDatabaseException $e) {
    // Database connection failed for some reason
    echo(create_system_error_page("Database Connection Error", $e->getMessage()));
    // and die
    exit;
}

// Get and verify the coder id
$coderid = verify_and_set_coder_id($conn);

// Ensure that only administrators can access this page
protect_admin_page($conn, $coderid);

// Determine of we are creating and administrator. If so set the flag and also
// change the target page in the invitation URL from list.php to admin.php.
$create_admin = false;
$target_page = "list.php";
if (array_key_exists('admin',$_GET) && $_GET['admin'] == "true") {
    $create_admin = true;
    $target_page = "admin.php";
}

// Actually create the new coder
$new_coderid = create_coder($conn, $coderid, $create_admin);

$full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$u = parse_url($full_url);
$path_to_coding = implode("/", array_slice(explode('/',$u['path']),0,-1))."/$target_page?coderid=$new_coderid";
$coding_url = $u['scheme']."://".$u['host'].$path_to_coding;
$subject = "You are Being Invited to Code a Set of Documents";
$message = "Thank you for being willing to help code this corpus. You have been assigned coder id number '$new_coderid'. Please go to this URL: $coding_url to begin coding.";
if ($create_admin) {
    $creation_message="A new admin coder has been created with id: <b>$new_coderid</b>This coder can invite other coders and create new admin coders.";
} else {
    $creation_message="A new coder has been created with id: <b>$new_coderid</b>";
}


$page_title = "Invite Coder";
$header_title = "Invite a New Coder";
?>


<html>
<head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
</head>
<body>
<?php include 'includes/title.php' ?>

<div class="container">
	<div class="row">
		<div class="col-sm-6 mx-auto">
			<div class="row pb-3"><?php echo($creation_message)?></div>
			<div class="row pb-3">The following URL will take them to the list of documents so that they can start coding:</div>
			<div class="row pb-3 mx-auto"><b><?php echo($coding_url) ?></b></div>
			<div class="row pb-3">Please send them the above URL or click the button below to have an e-mail generated that you can modify before sending.</div>
			<div class="row mx-auto">
                <div class="col-mx-auto m-3">
				<a class="btn btn-primary" href="mailto:jeff.harwell@gmail.com?subject=<?php echo($subject) ?>&body=<?php echo($message) ?>" target="_blank">Create Invitation</a>
                </div>
                <div class="col-mx-auto m-3">
                    <a href="admin.php" class="btn btn-primary">Go to Admin Page</a>
                </div>
			</div>
		</div>
	</div>
</div>

</body>
</html>
