<?php

/**
 * Get All Existing Coders
 *
 * Function which checks the database and returns a list of all valid coders
 *
 * @param PDO $conn the database connection
 *
 * @return string[] a list of all valid coder ids
 */
function get_all_coders($conn) {
    $all_coders = array();
    $stmt = $conn->prepare("select coder_id from coders");
    $stmt->execute();
    $coders = $stmt->fetchAll();
    foreach ($coders as $c) {
        $all_coders[] = $c['coder_id'];
    }
    return($all_coders);
}

/**
 * Creates a new coder
 *
 * Function that creates a new random coder id (5 digits) and inserts that into the database
 * along with record the person who created the invitation.
 *
 * @param PDO $conn the database connection
 * @param string $coderid the id of the coder who is creating the invitation
 * @param boolean $create_admin true if we should create an admin coder, false otherwise
 *
 * @return string the random id of the coder that was created
 */
function create_coder($conn, $coderid, $create_admin) {
    // Begin a transaction so that we don't run into concurrency issues if two people are creating a coder at the same time
    $conn->beginTransaction();

    // Get all of the coders who are already in the system
    $all_coders = get_all_coders($conn);

    // Generate a new random coder id
    $tries = 1;
    $id_candidate = rand(10000, 99999);

    // Keep generating random coder ids until we find a unique one. This should work pretty well
    // up to 70,000 or 80,000 coders ... at which point a real user management system should be implemented!
    while (in_array($id_candidate, $all_coders)) {
        $id_candidate = rand(10000,99999);
        $tries = $tries + 1;
        if ($tries > 100) {
            echo("<h1>Congratulations, you have run out of coder ids to assign, you must have a very big project running. Perhaps you should consider more robust software?</h1>\n");
            error_log("Unable to find a coder id that has not already been assigned, and we tried $tries times. You will need a more robust user management strategy for a project of the size you are trying to run.");
            exit;
        }
    }

    // Insert the new random coder id into the database and return it
    $stmt = $conn->prepare("insert into coders (coder_id, invited_by_coder) values (:new_coder_id, :current_coder_id)");
    $stmt->execute(array(':new_coder_id' => $id_candidate, ':current_coder_id' => $coderid));

    // Create an admin coder if specified
    if ($create_admin == true) {
        $stmt = $conn->prepare("insert into admincoders (coder_id) values (:new_coder_id)");
        $stmt->execute(array(':new_coder_id' => $id_candidate));
    }

    $conn->commit();

    return($id_candidate);
}

/**
 * Check if a given coder is an administrator
 *
 * Checks the database and returns true if a given coder is an administrator.
 *
 * @param PDO $conn the database connection
 * @param string $coder_id The id of the coder to check
 *
 * @return boolean True if the coder_id is an administrator, false otherwise
 */
function is_admin($conn, $coder_id) {
    $all_coders = array();
    $stmt = $conn->prepare("select count(*) from admincoders where coder_id = :coder_id");
    $stmt->execute(array(':coder_id' => $coder_id));
    $coders = $stmt->fetchAll();
    // Should return at most one
    if ($coders[0][0] == 1) {
        return(true);
    }
    return(false);
}

/**
 * Protect an admin page
 *
 * Protects an admin page, if the page should not be accessed by a normal user
 * it redirects the user back to the login page with a message
 *
 * @param PDO $conn the database connection
 * @param string $coder_id The id of the coder to check
 * @param string $target the target page to redirect to in case of a non-admin user
 *
 * @return void
 */
function protect_admin_page($conn, $coder_id, $target='index.php') {
    if (!is_admin($conn, $coder_id)) {
        // Redirect back to the login page
        $host  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $message = urlencode("Page is reserved for admins only.");
        header("Location: $host$uri/$target?message=$message");
        exit;
    }
}

/**
 * Set Coder ID
 *
 * Sets the coder_id from the environment. If a coder id has been passed in the URL that one
 * will be used and the session will be updated. If no coder id has been passed in the URL
 * the function will check the session and return that coder id. Otherwise the request is
 * redirected back to the login page.
 *
 * @param PDO @conn the database connection
 * 
 * @return string the coder id
 */
function verify_and_set_coder_id($conn) {
    // Set up some constants we might need later
    $host  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $message = urlencode("Please sign-in with a valid coder id to begin");

    // First get the coder id, use the GET variables if we have them, otherwise check the session
    // if we can't find a coder id then bump the user back to the login screen
	if (array_key_exists('coderid',$_GET)) {
		$_SESSION["coderid"] = $_GET['coderid'];
	} else if (!array_key_exists('coderid', $_SESSION)) {
		header("Location: $host$uri/index.php?message=$message");
		exit;
	}
    $coder_id = $_SESSION["coderid"];

    // Make sure that we have a valid coder id, if not redirect back to the login screen
    $valid_coders = get_all_coders($conn);
    if (!in_array($coder_id, $valid_coders)) {
		header("Location: $host$uri/index.php?message=$message");
		exit;
    }
    return($coder_id);
}
