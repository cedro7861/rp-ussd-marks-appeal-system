<?php 
require_once "db.php";

function handleAdminUSSD($phoneNumber, $text) {
    global $conn;

    $text = trim($text);
    $textArray = explode("*", $text);
    $level = count($textArray);

    if ($level == 1 && $text == "") {
        return "CON Admin Menu:\n1. Manage Appeals\n2. Manage Marks\n3. Manage Modules\n0. Exit";
    }

    switch ($textArray[0]) {
        case "1": return handleAppealMenu($textArray);
        case "2": return handleMarksMenu($textArray);
        case "3": return handleModuleMenu($textArray);
        case "0": return "END Exited.";
        default:  return "END Invalid Option.";
    }
}

// ----------------- Appeal Management -----------------

function handleAppealMenu($textArray) {
    $level = count($textArray);

    if ($level == 1) {
        return "CON Appeals:\n1. View Appeals\n2. Update Appeal\n3. Delete Appeal\n9. Back\n0. Exit";
    }

    switch ($textArray[1]) {
        case "1":
            return viewAllAppeals();

        case "2":
            if ($level == 2) return listAppealIDs("update");
            elseif ($level == 3) return "CON New status (1-Pending, 2-Under Review, 3-Resolved):\n9. Back\n0. Exit";
            elseif ($level == 4) return updateAppealStatus($textArray[2], $textArray[3]);
            break;

        case "3":
            if ($level == 2) return listAppealIDs("delete");
            elseif ($level == 3) return deleteAppeal($textArray[2]);
            break;

        case "9":
            return "CON Admin Menu:\n1. Manage Appeals\n2. Manage Marks\n3. Manage Modules\n0. Exit";

        case "0":
            return "END Exited.";
    }

    return "END Invalid Appeal Option.";
}

function listAppealIDs($action) {
    global $conn;
    $stmt = $conn->query("SELECT appeal_id, reason FROM appeals");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($results)) return "END No appeals available.";

    $msg = "CON Select Appeal ID to $action:\n";
    foreach ($results as $row) {
        $msg .= "{$row['appeal_id']} - {$row['reason']}\n";
    }
    $msg .= "9. Back\n0. Exit";
    return $msg;
}

// ----------------- Marks Management -----------------

function handleMarksMenu($textArray) {
    $level = count($textArray);

    if ($level == 1) {
        return "CON Marks:\n1. Add Mark\n2. Delete Mark\n9. Back\n0. Exit";
    }

    switch ($textArray[1]) {
        case "1":
            if ($level == 2) return listStudentIDs();
            elseif ($level == 3) return listModuleIDs();
            elseif ($level == 4) return "CON Enter Mark:\n9. Back\n0. Exit";
            elseif ($level == 5) return addMark($textArray[2], $textArray[3], $textArray[4]);
            break;

        case "2":
            if ($level == 2) return listMarkIDs();
            elseif ($level == 3) return deleteMark($textArray[2]);
            break;

        case "9":
            return "CON Admin Menu:\n1. Manage Appeals\n2. Manage Marks\n3. Manage Modules\n0. Exit";

        case "0":
            return "END Exited.";
    }

    return "END Invalid Mark Option.";
}

function listStudentIDs() {
    global $conn;
    $stmt = $conn->query("SELECT student_id, name FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($students)) return "END No students found.";

    $msg = "CON Select Student ID:\n";
    foreach ($students as $s) {
        $msg .= "{$s['student_id']} - {$s['name']}\n";
    }
    $msg .= "9. Back\n0. Exit";
    return $msg;
}

function listModuleIDs() {
    global $conn;
    $stmt = $conn->query("SELECT module_id, module_name FROM modules");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($modules)) return "END No modules found.";

    $msg = "CON Select Module ID:\n";
    foreach ($modules as $m) {
        $msg .= "{$m['module_id']} - {$m['module_name']}\n";
    }
    $msg .= "9. Back\n0. Exit";
    return $msg;
}

function listMarkIDs() {
    global $conn;
    $stmt = $conn->query("SELECT mark_id, mark FROM marks");
    $marks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($marks)) return "END No marks found.";

    $msg = "CON Enter Mark ID to delete:\n";
    foreach ($marks as $m) {
        $msg .= "{$m['mark_id']} - Mark: {$m['mark']}\n";
    }
    $msg .= "9. Back\n0. Exit";
    return $msg;
}

// ----------------- Module Management -----------------

function handleModuleMenu($textArray) {
    $level = count($textArray);

    if ($level == 1) {
        return "CON Modules:\n1. Add Module\n2. Update Module\n3. Delete Module\n9. Back\n0. Exit";
    }

    switch ($textArray[1]) {
        case "1":
            if ($level == 2) return "CON Enter Module Name:\n9. Back\n0. Exit";
            elseif ($level == 3) return addModule($textArray[2]);
            break;

        case "2":
            if ($level == 2) return listModuleIDsForEdit("update");
            elseif ($level == 3) return "CON Enter New Module Name:\n9. Back\n0. Exit";
            elseif ($level == 4) return updateModule($textArray[2], $textArray[3]);
            break;

        case "3":
            if ($level == 2) return listModuleIDsForEdit("delete");
            elseif ($level == 3) return deleteModule($textArray[2]);
            break;

        case "9":
            return "CON Admin Menu:\n1. Manage Appeals\n2. Manage Marks\n3. Manage Modules\n0. Exit";

        case "0":
            return "END Exited.";
    }

    return "END Invalid Module Option.";
}

function listModuleIDsForEdit($action) {
    global $conn;
    $stmt = $conn->query("SELECT module_id, module_name FROM modules");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($modules)) return "END No modules found.";

    $msg = "CON Select Module ID to $action:\n";
    foreach ($modules as $m) {
        $msg .= "{$m['module_id']} - {$m['module_name']}\n";
    }
    $msg .= "9. Back\n0. Exit";
    return $msg;
}

// ----------------- Action Functions -----------------

function viewAllAppeals() {
    global $conn;

    $stmt = $conn->query("SELECT a.appeal_id, s.name, m.module_name, a.reason, st.status_name
        FROM appeals a 
        JOIN students s ON a.student_id = s.student_id
        JOIN modules m ON a.module_id = m.module_id
        JOIN appeal_status st ON a.status_id = st.status_id");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($results)) return "END No appeals found.";

    $response = "END Appeals:\n";
    foreach ($results as $row) {
        $response .= "ID: {$row['appeal_id']}, {$row['name']} - {$row['module_name']} ({$row['status_name']})\n";
    }
    return $response;
}

function updateAppealStatus($appeal_id, $status_id) {
    global $conn;
    if (!in_array($status_id, ["1", "2", "3"])) return "END Invalid status.";
    $stmt = $conn->prepare("UPDATE appeals SET status_id = ? WHERE appeal_id = ?");
    $stmt->execute([$status_id, $appeal_id]);
    return "END Appeal updated.";
}

function deleteAppeal($appeal_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM appeals WHERE appeal_id = ?");
    $stmt->execute([$appeal_id]);
    return "END Appeal deleted.";
}

function addMark($student_id, $module_id, $mark) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO marks (student_id, module_id, mark) VALUES (?, ?, ?)");
    $stmt->execute([$student_id, $module_id, $mark]);
    return "END Mark added.";
}

function deleteMark($mark_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM marks WHERE mark_id = ?");
    $stmt->execute([$mark_id]);
    return "END Mark deleted.";
}

function addModule($module_name) {
    global $conn;
    if (empty($module_name)) return "END Module name cannot be empty.";

    $check = $conn->prepare("SELECT * FROM modules WHERE module_name = ?");
    $check->execute([$module_name]);
    if ($check->rowCount() > 0) return "END Module already exists.";

    $stmt = $conn->prepare("INSERT INTO modules (module_name) VALUES (?)");
    $stmt->execute([$module_name]);
    return "END Module added.";
}

function updateModule($module_id, $new_name) {
    global $conn;
    $stmt = $conn->prepare("UPDATE modules SET module_name = ? WHERE module_id = ?");
    $stmt->execute([$new_name, $module_id]);
    return "END Module updated.";
}

function deleteModule($module_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
    $stmt->execute([$module_id]);
    return "END Module deleted.";
}

// ---------- Africa's Talking Entry Point ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionId   = $_POST["sessionId"] ?? '';
    $serviceCode = $_POST["serviceCode"] ?? '';
    $phoneNumber = $_POST["phoneNumber"] ?? '';
    $text        = $_POST["text"] ?? '';

    header('Content-type: text/plain');
    echo handleAdminUSSD($phoneNumber, $text);
}
?>
