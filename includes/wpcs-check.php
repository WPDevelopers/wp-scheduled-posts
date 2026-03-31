<?php
// This file is for testing WPCS pre-commit hook.
// Try staging this file and committing - it should be BLOCKED.
// Violations introduced below (new code, not old):

// BAD: camelCase function name (should be snake_case)
function getUserData($id) {
    // BAD: no sanitization on $_GET
    $search = $_GET['query'];

    // BAD: unescaped output
    echo $search;

    // BAD: Yoda condition not used (should be: if ( 1 == $id ))
    if ($id == 1) {
        echo "admin found";
    }

    return $id;
}
