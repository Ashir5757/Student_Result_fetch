<?php
// Enable CORS for frontend access
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Check if starting and ending roll numbers are provided
if (isset($_GET['startRollNo']) && isset($_GET['endRollNo'])) {
    $startRollNo = intval($_GET['startRollNo']);
    $endRollNo = intval($_GET['endRollNo']);
    $results = [];

    for ($rollNo = $startRollNo; $rollNo <= $endRollNo; $rollNo++) {
        $url = "https://portal.fbise.edu.pk/fbise-conduct/result/Result-link-hssc1.php?rollNo=$rollNo&name=&annual=HSSC-I";

        // Fetch data from the target URL
        $response = @file_get_contents($url);

        if ($response === FALSE) {
            $results[] = [
                "rollNo" => $rollNo,
                "error" => "Failed to fetch data"
            ];
            continue;
        }

        // Load the response into DOMDocument to parse HTML
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress parsing errors
        $doc->loadHTML($response);
        libxml_clear_errors();

        // Extract data using XPath
        $xpath = new DOMXPath($doc);
        $studentName = trim($xpath->query("//td[contains(text(), 'Student Name')]/following-sibling::td")->item(0)->nodeValue ?? '');
        $fatherName = trim($xpath->query("//td[contains(text(), 'Father Name')]/following-sibling::td")->item(0)->nodeValue ?? '');
        $marksObt = trim($xpath->query("//td[contains(text(), 'Marks Obt')]/following-sibling::td")->item(0)->nodeValue ?? '');
        $institution = trim($xpath->query("//td[contains(text(), 'INSTITUTION')]/following-sibling::td")->item(0)->nodeValue ?? '');

        // Extract subject-wise marks
        $subjectRows = $xpath->query("//table//tr");
        $subjects = [];

        foreach ($subjectRows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length >= 4) {
                $subjectNumber = trim($cells->item(0)->nodeValue ?? '');
                $subjectName = trim($cells->item(1)->nodeValue ?? '');
                $marksObtained = trim($cells->item(2)->nodeValue ?? '');
                $practicalMarks = trim($cells->item(3)->nodeValue ?? '');

                if (!empty($subjectName)) {
                    $subjects[] = [
                        "subjectNumber" => $subjectNumber,
                        "subjectName" => $subjectName,
                        "marksObtained" => $marksObtained,
                        "practicalMarks" => $practicalMarks
                    ];
                }
            }
        }

        $results[] = [
            "rollNo" => $rollNo,
            "studentName" => $studentName,
            "fatherName" => $fatherName,
            "marksObt" => $marksObt,
            "institution" => $institution,
            "subjects" => $subjects
        ];
    }

    // Return JSON data
    echo json_encode($results, JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Starting and ending roll numbers are required"]);
}
?>
