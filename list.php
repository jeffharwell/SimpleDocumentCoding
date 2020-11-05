<?php
session_start();

include 'config.php';
include 'library/system_lib.php'; // library for database connections, system errors, etc
include $credentials_file; // the file containing the database credentials, pointed to from 'config.php'
include 'library/response_recorder.php'; // this is the library that contains the code which processes and store
                                 // any document codings.
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

/**
 * List files available for coding
 *
 * Look in the corpus directory that is specified and return a list
 * of all of the files in that directory.
 *
 * @param string $corpus_directory Full path of the directory that contains the files to code
 * @param string[] $exclusions a list of documents to exclude
 *
 * @return string[] List of files in the directory
 */
function get_all_files_to_code($corpus_directory, $exclusions) {
    $corpus_files = array();
    $entries = scandir($corpus_directory);
    foreach ($entries as $entry) {
        if (is_file($corpus_directory.$entry) and !in_array($entry, $exclusions)) {
            $corpus_files[] = $entry;
        }
    }
    return($corpus_files);
}

// Build our database connection
try {
	$conn = get_database_connection($database_host, $database, $username, $password);
} catch (SDCDatabaseException $e) {
	// Database connection failed for some reason
	echo(create_system_error_page("Database Connection Error", $e->getMessage()));
	// and die
    exit;
}


// We may have received a document coding. Check and if so process it and save it in the DB
// this function checks the global $_GET variable for a coding response, so you don't need to 
// pass anything to it.
$document_coded = save_document_coding($conn, $coderid);

// Now get our list of documents that have been coded along with the response
$coded_documents = get_coded_documents_list($conn, $coderid);
$coding_data = get_coding_data($conn, $coderid);
$irrelevant_documents = get_irrelevant_document_ids($conn);
$corpus_files = get_all_files_to_code($corpus_directory, $irrelevant_documents);
$documents_to_code = array();

foreach ($corpus_files as $c) {
    if (!in_array($c, $coded_documents)) {
        $documents_to_code[] = $c;
    }
}

$page_title = "Corpus Documents";
$header_title = "Corpus Documents";

?>
<html>
<head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
<script>
$(document).ready(function(){
    $('[data-toggle="popover"]').popover()
});
</script>
</head>
<body>

<!-- If we received a coding for a document let the user know that it has been saved -->
<?php if ($document_coded) { ?>
<div class="alert alert-info" role="alert">
    Coding for document <?php echo($document_coded) ?> has been saved.
</div>
<?php } ?>

<?php include 'includes/title.php' ?>

<div class="container">
    <div class="row"><div class="col">
    Welcome, You are coder number <?php echo($coderid)?>
    </div></div>
    <div class="row"><div class="col">
    To get started click the 'Code Document' button next to one of the documents below.
    </div></div>
    <div class="row mb-3"><div class="col">
    There are <?php echo(count($documents_to_code)) ?> of <?php echo(count($corpus_files)) ?> documents remaining to code
    </div></div>
</div>

<div class="container">
<table class="table">
<thead>
  <tr>
    <th scope="col">Status</th>
    <th scope="col">Action</th>
    <th scope="col">Document Name</th>
  </tr>
<tbody>
<?php
// Deal with the documents that need to be coded
foreach ($documents_to_code as $f) {
    echo("<tr>");
    echo("<td>Not Coded</td>");
?>
<td>
<form action="code.php" methed="get">
    <!-- <div class="form-group"> -->
        <!-- <label for="codeDocument">Code This Document</label> -->
        <input type="hidden" id="document" name="document" value="<?php echo($f) ?>">
    <!-- </div> -->
    <button type="submit" class="btn btn-primary">Code Document</button>
</form>
</td>
<?php
    echo("<td>$f</td>");
    echo("</tr>");
}
// Deal with the documents that have already been coded
foreach ($coded_documents as $f) {
    echo("<tr>");
    echo("<td>Coded</td>");
?>
<td>
<form action="code.php" methed="get">
    <!-- <div class="form-group"> -->
        <!-- <label for="codeDocument">Code This Document</label> -->
        <input type="hidden" id="document" name="document" value="<?php echo($f) ?>">
    <!-- </div> -->
    <button type="submit" class="btn btn-primary">Recode Document</button>
    <button type="button" class="btn btn-info" data-toggle="popover" title="Coding Data" data-content="<?php echo($coding_data[$f]) ?>">View Your Coding</button>
</form>
</td>
<?php
    echo("<td>$f</td>");
    echo("</tr>");
}
?>
</tbody>
</table>
</div>

<div class="container">
<div class="row mb-5">
    <div class="col-md-auto">
    <?php if (is_admin($conn, $coderid)) {?>
    <a href="admin.php" class="btn btn-primary">Admin Page</a>
    <?php } ?>
    </div>
    <div class="col-md-auto">
    <a href="index.php" class="btn btn-primary">Logout</a>
    </div>
</div>
</div>

</body>
</html>
