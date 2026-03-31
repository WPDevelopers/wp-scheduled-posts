<?php
// WPCS Test Sample - intentional violations for testing pre-commit hook

function getData($userId) {
    $data = array("name" => "test", "id" => $userId);

    // Missing sanitization
    $input = $_GET['search'];

    // Unescaped output
    echo $input;

    // Yoda condition violation
    if ($data['id'] == 1) {
        echo "admin";
    }

    return $data;
}

$result = getData(42);
echo $result['name'];
// trigger change
// change2
// change3.
