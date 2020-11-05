<?php

/**
 * Contains the functions needed to code and retrieve responses
 */

/**
 * Processes a document coding then inserts or updates the database
 *
 * Receives a document coding from the HTML form, converts it to JSON
 * and then puts it into the database. If it is a new entry for the coder then
 * the record is inserted, otherwise the new coding is updated, erasing the
 * previous response.
 *
 * @param PDO $conn the database connection object
 * @param string $coderid the id number of the person doing the coding
 *
 * @return string the name of the document that was coded, null if the save fails
 */
// Look for a document coding in the _GET post, if it exists
// then save it in the database.
function save_document_coding($conn, $coderid) {

    // make sure there is a document, if not this is certainly not a coding response
    if (array_key_exists('coding-document', $_GET) && $_GET['coding-document'] != "") {
        $document_name = $_GET['coding-document'];

        $result = array("coding" => array());
        //echo("<ul>\n");
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, strlen("coding")) == "coding" && $value != "") {
                $result["coding"][$key] = $value;
                //echo("<li>$key - ".$value."</li>\n");
            }
        }
        //echo("</ul>\n");
        $coding_data = json_encode($result);
        //echo("<pre>".$coding_data."</pre>");

        // Start the transaction
        $conn->beginTransaction();
        // See if this document has been coded by this coder
        $stmt = $conn->prepare("select count(*) from codingresponse where coder = :coder and document = :document");
        $stmt->execute(array(':coder' => $coderid, ':document' => $document_name));
        $count = $stmt->fetch()[0];
        //echo("<pre>Count: ".$count."</pre>");
        if ($count == 0) {
            // this is an insert
            $query = "insert into codingresponse (coder, document, response) values (:coder, :document, :response)";
        } else {
            // this is an update
            $query = "update codingresponse set response = :response where coder = :coder and document = :document";
        }
        // do the insert or update
        //echo("<pre>executing $query, $coderid, $document_name, $coding_data</pre>");
        $data = ['coder' => $coderid, 'document' => $document_name, 'response' => $coding_data];
        $stmt = $conn->prepare($query);
        $stmt->execute($data);

        // and commit
        $conn->commit();
        return($document_name);
    } // end the else, we only do something if the coding-document exists and is not empty
    return(null);
}

/**
 * Get a list of documents that this user has already coded.
 *
 * Get a list of all the documents in the database that this user has already coded
 *
 * @param PDO $conn the database connection
 * @param string $coderid the id of the person doing the coding
 *
 * @return string[] a list of document names that have already been coded
 */
function get_coded_documents_list($conn, $coderid) {
    $coded_documents = array();

    //echo("<pre>Getting Coded Documents for $coderid</pre>");
    $coded_documents_query = "select document from codingresponse where coder = :coder";
    $stmt = $conn->prepare($coded_documents_query);
    $stmt->execute(array(':coder' => $coderid));
    $results = $stmt->fetchAll();
    foreach ($results as $d) {
        $coded_documents[] = $d['document'];
        //echo("<pre>".$d['document']."</pre>");
    }
    return($coded_documents);
}

/**
 * Get a coding data for all documents that the user has coded.
 *
 * Get the coding data for all documents that the user has coded, keyed by document name
 *
 * @param PDO $conn the database connection
 * @param string $coderid the id of the person doing the coding
 *
 * @return string[] an array keyed by document name with the data that has been coded
 */
function get_coding_data($conn, $coderid) {
    $data = array();
    //echo("<pre>Getting all data for $coderid</pre>");
    $coded_documents_query = "select document, response from codingresponse where coder = :coder";
    $stmt = $conn->prepare($coded_documents_query);
    $stmt->execute(array(':coder' => $coderid));
    $results = $stmt->fetchAll();
    foreach ($results as $d) {
		$r = json_decode($d['response'], true);
        $viewpoint = $r['coding']['coding-viewpoint'];
        $strength = $r['coding']['coding-range'];
        $note = $r['coding']['coding-note'];
        $response_string = "Response: $viewpoint, Strength: $strength, Note: $note";
        $data[$d['document']] = $response_string;
        //echo("<pre> ".$response_string."</pre>");
    }
    return($data);
}

/**
 * Get list of irrelevant documents
 *
 * Retrieve an array of all irrelevant documents from the database.
 *
 * @param PDO $conn the database connection
 *
 * @return string[] list if ids for documents considered irrelevent to the corpus
 */

function get_irrelevant_document_ids($conn) {
    $query = "select document from irrelevantdocuments";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $raw = $stmt->fetchAll();
    $results = array();
    foreach ($raw as $r) {
        $results[] = $r['document'];
    }
    return($results);
}

?>
