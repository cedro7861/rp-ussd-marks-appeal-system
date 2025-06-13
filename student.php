<?php
require_once "db.php";

function handleStudentUSSD($phoneNumber, $text) {
    global $conn;

    $text = trim($text, "* ");
    $textArray = explode("*", $text);
    $level = count($textArray);

    // Step 1: Check registration
    $stmt = $conn->prepare("SELECT * FROM students WHERE contact_number = ?");
    $stmt->execute([$phoneNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Registration flow
        if ($text == "") {
            return "CON Welcome! Enter your RegNumber:\n0. Exit";
        } elseif ($level == 1) {
            if ($textArray[0] == "0") return "END Thank you.";
            $regNumber = trim($textArray[0]);

            $regCheck = $conn->prepare("SELECT * FROM students WHERE regnumber = ?");
            $regCheck->execute([$regNumber]);
            if ($regCheck->fetch()) {
                return "END RegNumber already used.";
            }

            $stmt = $conn->prepare("INSERT INTO students (name, contact_number, regnumber) VALUES (?, ?, ?)");
            $stmt->execute(['Unknown', $phoneNumber, $regNumber]);

            return "END Registration successful!";
        } else {
            return "END Invalid input. Try again.";
        }
    } else {
        // Logged-in student
        $menuLevel = count($textArray);
        $step1 = $textArray[0] ?? "";

        // Main grouped menu
        if ($text == "") {
            return "CON Welcome back.\n1. Marks\n2. Appeals\n0. Exit";
        }

        if ($step1 == "0") return "END Thank you.";

        switch ($step1) {
            case "1": // MARKS GROUP
                if ($menuLevel == 1) {
                    return checkMarks($student['student_id']) . "\n9. Back\n0. Exit";
                } elseif ($textArray[1] == "9") {
                    return "CON Welcome back.\n1. Marks\n2. Appeals\n0. Exit";
                } elseif ($textArray[1] == "0") {
                    return "END Thank you.";
                } else {
                    return "END Invalid option.";
                }

            case "2": // APPEALS GROUP
                if ($menuLevel == 1) {
                    return "CON Appeals Menu:\n1. Submit Appeal\n2. View My Appeals\n3. Cancel Appeal\n9. Back\n0. Exit";
                }

                $appealStep = $textArray[1] ?? "";
                switch ($appealStep) {
                    case "1": // Submit Appeal
                        if ($menuLevel == 2) {
                            return listModules($student['student_id']) . "\n9. Back\n0. Exit";
                        } elseif ($menuLevel == 3) {
                            if ($textArray[2] == "9") return "CON Appeals Menu:\n1. Submit Appeal\n2. View My Appeals\n3. Cancel Appeal\n9. Back\n0. Exit";
                            if ($textArray[2] == "0") return "END Thank you.";
                            return "CON Enter your appeal reason:\n9. Back\n0. Exit";
                        } elseif ($menuLevel == 4) {
                            if ($textArray[3] == "9") return listModules($student['student_id']) . "\n9. Back\n0. Exit";
                            if ($textArray[3] == "0") return "END Thank you.";
                            return submitAppeal($student['student_id'], $textArray[2], $textArray[3]);
                        }
                        break;

                    case "2": // View Appeals
                        if ($menuLevel == 2) {
                            return viewAppeals($student['student_id']) . "\n9. Back\n0. Exit";
                        } elseif ($textArray[2] == "9") {
                            return "CON Appeals Menu:\n1. Submit Appeal\n2. View My Appeals\n3. Cancel Appeal\n9. Back\n0. Exit";
                        } elseif ($textArray[2] == "0") {
                            return "END Thank you.";
                        }
                        break;

                    case "3": // Cancel Appeal
                        if ($menuLevel == 2) {
                            return listCancelableAppeals($student['student_id']) . "\n9. Back\n0. Exit";
                        } elseif ($menuLevel == 3) {
                            if ($textArray[2] == "9") return "CON Appeals Menu:\n1. Submit Appeal\n2. View My Appeals\n3. Cancel Appeal\n9. Back\n0. Exit";
                            if ($textArray[2] == "0") return "END Thank you.";
                            return cancelAppeal($student['student_id'], $textArray[2]);
                        }
                        break;

                    case "9":
                        return "CON Welcome back.\n1. Marks\n2. Appeals\n0. Exit";

                    case "0":
                        return "END Thank you.";

                    default:
                        return "END Invalid Appeals option.";
                }
                break;

            default:
                return "END Invalid option.";
        }
    }

    return "END Unexpected error.";
}

// -------------------- Helper Functions --------------------

function checkMarks($student_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT m.module_name, mk.mark FROM marks mk
                            JOIN modules m ON mk.module_id = m.module_id
                            WHERE mk.student_id = ?");
    $stmt->execute([$student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) return "END No marks available.";

    $response = "END Your Marks:\n";
    foreach ($results as $row) {
        $response .= $row['module_name'] . ": " . $row['mark'] . "\n";
    }
    return $response;
}

function listModules($student_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT DISTINCT m.module_id, m.module_name FROM modules m
                            JOIN marks mk ON mk.module_id = m.module_id
                            WHERE mk.student_id = ?");
    $stmt->execute([$student_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($modules)) return "END No modules found.";

    $msg = "CON Select Module ID:\n";
    foreach ($modules as $mod) {
        $msg .= $mod['module_id'] . ". " . $mod['module_name'] . "\n";
    }
    return $msg;
}

function submitAppeal($student_id, $module_id, $reason) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO appeals (student_id, module_id, reason, status_id) VALUES (?, ?, ?, 1)");
    $stmt->execute([$student_id, $module_id, $reason]);
    return "END Appeal submitted successfully.";
}

function viewAppeals($student_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT a.appeal_id, m.module_name, s.status_name FROM appeals a
                            JOIN modules m ON a.module_id = m.module_id
                            JOIN appeal_status s ON a.status_id = s.status_id
                            WHERE a.student_id = ?");
    $stmt->execute([$student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) return "END No appeals found.";

    $msg = "END Appeals:\n";
    foreach ($results as $row) {
        $msg .= "ID: " . $row['appeal_id'] . " " . $row['module_name'] . ": " . $row['status_name'] . "\n";
    }
    return $msg;
}

function listCancelableAppeals($student_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT appeal_id, module_id FROM appeals WHERE student_id = ? AND status_id = 1");
    $stmt->execute([$student_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) return "END No pending appeals.";

    $msg = "CON Enter Appeal ID to cancel:\n";
    foreach ($results as $row) {
        $msg .= $row['appeal_id'] . " (Module ID: " . $row['module_id'] . ")\n";
    }
    return $msg;
}

function cancelAppeal($student_id, $appeal_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM appeals WHERE appeal_id = ? AND student_id = ? AND status_id = 1");
    $stmt->execute([$appeal_id, $student_id]);
    $appeal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appeal) return "END Invalid or non-cancelable appeal.";

    $stmt = $conn->prepare("DELETE FROM appeals WHERE appeal_id = ?");
    $stmt->execute([$appeal_id]);

    return "END Appeal cancelled.";
}

// -------------------- Entry Point --------------------

if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST') {
    $phoneNumber = $_REQUEST['phoneNumber'] ?? '';
    $text = $_REQUEST['text'] ?? '';

    header('Content-type: text/plain');
    echo handleStudentUSSD($phoneNumber, $text);
    exit;
}
?>