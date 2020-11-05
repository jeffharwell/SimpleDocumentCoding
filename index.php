<?php
include 'config.php';
include $credentials_file;

// Destroy any existing session
session_start();
session_destroy();

function get_all_files_to_code($corpus_directory) {
    $corpus_files = array();
    $entries = scandir($corpus_directory);
    foreach ($entries as $entry) {
        if (is_file($corpus_directory.$entry)) {
            $corpus_files[] = $entry;
        }
    }
    return($corpus_files);
}

$corpus_files = get_all_files_to_code($corpus_directory);

$page_title = "Sign-in";
$header_title = "Sign-in with your Coder ID";
?>

<html><head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
<title>Login</title>
</head>
<body>

<?php if ($_GET['message']) { ?>
<div class="alert alert-info" role="alert">
    <?php echo($_GET['message']) ?>
</div>
<?php } ?>

<?php include 'includes/title.php' ?>
<div class="container">
    <div class="row"><div class="col">Welcome! There are <?php count($corpus_files) ?> files to code in this corpus.</div></div>
    <div class="row mb-3"><div class="col">Please input your coder id and click 'Log In' to get started.</div></div>
</div>
<div class="container">
    <div class="col">
        <div class="row">
            <form action="list.php" method="get">
            <div class="form-group">
                <label for="coderid">Enter your coder id: </label>
                <input type="text" name="coderid" id="coderid" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
            </form>
        </div>
    </div>
</div>


</body>
</html>
