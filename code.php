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

// Make sure we have a document that we are specified to code, otherwise
// send them back to the list
if (!array_key_exists('document', $_GET)) {
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: http://$host$uri/list.php");
    exit;
} 

$document_text = file_get_contents($corpus_directory.$_GET['document']);
$doc_to_code = $_GET['document'];

$page_title = "Code Document";
$header_title = "Code Document";

// This creates the javascript array that the keyword highlighting code will use
$keyword_javascript_array = "['".implode($query_keywords,"','")."']";
$keyword_list = implode($query_keywords, ', ');
?>

<html>
<head profile="http://www.w3.org/2005/10/profile">
<?php include 'includes/html_head.php' ?>
<script>
$(document).ready(function() {
  const $valueSpan = $('.valueSpan2');
  const $value = $('#formControlRange');
  $valueSpan.html($value.val());
  $value.on('input change', () => {
    $valueSpan.html($value.val());
  });

  // Highlight the contents of the warc container based on the keywords
  // used in the search query.
  let keywords = <?php echo($keyword_javascript_array) ?>;

  // Load the contents from the div with class .warc
  const $doc_container = $('.warc');
  var content = $doc_container.html();

  // go through each keyword and highlight all occurances in
  // the content using the <mark> tag
  for (let i in keywords) {
      if (keywords[i] != "") {
          console.log("replacing term "+keywords[i]+" in document.");
          // Sometimes the document does not have a space between the matched
          // word and the next word, so account for this in the match. Additionally 
          // account for a closing parenthesis quotes, period, question mark 
          // and exclamation point.
          let re = new RegExp("\\s("+keywords[i]+")([A-Za-z'),.?!\"]*)\\s", 'ig');
          content = content.replace(re, " <mark>$1</mark>$2 ");
      }
  }

  // Write the content back out into the document
  $doc_container.html(content);
});
</script>
</head>
<body>
<?php include 'includes/title.php' ?>

<div class="container-fluid">
  <div class="row pb-5">
    <div class="col">
        <div class="title-section">
            <p><?php echo("Coder: ".$coderid)?></p>
            <p><?php echo("Document: ".$doc_to_code)?></p>
            <p class="h2">How does the document below answer the following question:
<?php echo($coding_question) ?></p>
            <p><i>Highlighting Keywords: <?php echo($keyword_list) ?></i></p>
        </div> <!-- end title section -->
    </div>
  </div>
</div>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-6">
      <pre class="warc">
<?php echo($document_text) ?>
      </pre>
    </div>
    <div class="col">
        <div class="sidebar-item sticky-top">
            <p>Please review the <a href="<?php echo($coding_standard_url) ?>" target="_blank">Coding Standards Document</a> before selecting.</p>
            <form action="list.php" methed="get">
                <div class="form-group form-check">
                    <input class="form-check-input" type="radio" name="coding-viewpoint" id="viewpoint1" value="yes" required>
                    <label class="form-check-label" for="viewpoint1">
			<?php echo($affirmative_message) ?>
                    </label>
                 </div>
                <div class="form-group form-check">
                    <input class="form-check-input" type="radio" name="coding-viewpoint" id="viewpoint2" value="no">
                    <label class="form-check-label" for="viewpoint2">
		      <?php echo($dissenting_message) ?>
                    </label>
                </div>
                <div class="form-group form-check">
                    <input class="form-check-input" type="radio" name="coding-viewpoint" id="viewpoint3" value="irrelevant">
                    <label class="form-check-label" for="viewpoint3">
                        Document is Irrelevant to the Question
                    </label>
                </div>

                <div class="form-group">
                    <label for="formControlRange">How strongly do you feel that the document express this viewpoint, or how sure are you that the document is irrelevant, from <b>1</b> - not strongly, to <b>7</b> - very strongly?</label>
                    <div class="btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range1" value="1" required>1
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range2" value="2" required>2
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range3" value="3" required>3
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range4" value="4" required>4
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range5" value="5" required>5
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range6" value="6" required>6
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="coding-range" id="coding-range7" value="7" required>7
                        </label>
                    </div>
                    <!--
                    <div class="d-flex justify-content-center my-4">
                        <div class="w-50">
                            <input type="range" name="coding-range" class="form-control-range" id="formControlRange" min="1" max="7" value="3">
                        </div>
                        <span class="font-weight-bold text-primary ml-2 valueSpan2"></span>
                    </div>
                    -->
                <div class="form-group">
                    <label for="note">Notes</label>
                    <textarea class="form-control" name="coding-note" id="note" rows="4"></textarea>
                </div>
                <input type="hidden" id="document" name="coding-document" value="<?php echo($doc_to_code) ?>">
                <button type="submit" class="btn btn-primary">Submit Coding</button>
                <a class="btn btn-primary" href="list.php" role="button">Return to Document List</a>
            </form>
        </div> <!-- ends the sticky sidebar-item -->
    </div> <!-- ends class="col" which contains the form -->
  </div> <!-- ends class="row" which contains the document and the form -->
</div> <!-- ends class="container" -->

</body>
</html>
