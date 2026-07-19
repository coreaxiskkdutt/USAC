<?php
require_once __DIR__ . '/helpers.php';
$db = getDB();

$usersData = [
    ['full_name' => 'System Administrator', 'username' => 'admin', 'email' => 'admin@usac.local', 'designation' => 'Director', 'department' => 'HSE', 'role' => 'admin', 'is_approved' => 1, 'password' => 'admin123'],
    ['full_name' => 'Maria Santos', 'username' => 'msantos', 'email' => 'msantos@usac.local', 'designation' => 'Manager', 'department' => 'HSE', 'role' => 'safety_officer', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Rafael Cruz', 'username' => 'rcruz', 'email' => 'rcruz@usac.local', 'designation' => 'Superintendent', 'department' => 'HSE', 'role' => 'safety_officer', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Juan Dela Cruz', 'username' => 'jdelacruz', 'email' => 'jdelacruz@usac.local', 'designation' => 'Operator', 'department' => 'Operations', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Ana Reyes', 'username' => 'areyes', 'email' => 'areyes@usac.local', 'designation' => 'Technician', 'department' => 'Maintenance', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Carlos Garcia', 'username' => 'cgarcia', 'email' => 'cgarcia@usac.local', 'designation' => 'Engineer', 'department' => 'Engineering', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Elena Mendoza', 'username' => 'emendoza', 'email' => 'emendoza@usac.local', 'designation' => 'Supervisor', 'department' => 'Operations', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Roberto Lim', 'username' => 'rlim', 'email' => 'rlim@usac.local', 'designation' => 'Foreman', 'department' => 'Maintenance', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Sofia Tan', 'username' => 'stan', 'email' => 'stan@usac.local', 'designation' => 'Inspector', 'department' => 'Quality', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Miguel Bautista', 'username' => 'mbautista', 'email' => 'mbautista@usac.local', 'designation' => 'Coordinator', 'department' => 'Logistics', 'role' => 'employee', 'is_approved' => 1, 'password' => 'pass123'],
    ['full_name' => 'Patricia Aquino', 'username' => 'paquino', 'email' => 'paquino@usac.local', 'designation' => 'Operator', 'department' => 'Operations', 'role' => 'employee', 'is_approved' => 0, 'password' => 'pass123'],
    ['full_name' => 'Daniel Ramos', 'username' => 'dramos', 'email' => 'dramos@usac.local', 'designation' => 'Technician', 'department' => 'IT', 'role' => 'employee', 'is_approved' => 0, 'password' => 'pass123'],
];

$userMap = [];
foreach ($usersData as $u) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
    $stmt->bindValue(':u', $u['username'], SQLITE3_TEXT);
    $existing = $stmt->execute()->fetchArray();
    if ($existing) {
        $userMap[$u['username']] = $existing['id'];
        continue;
    }
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (full_name, username, email, password_hash, role, designation, department, is_approved) VALUES (:fn, :un, :em, :pw, :rl, :dg, :dp, :ap)');
    $stmt->bindValue(':fn', $u['full_name'], SQLITE3_TEXT);
    $stmt->bindValue(':un', $u['username'], SQLITE3_TEXT);
    $stmt->bindValue(':em', $u['email'], SQLITE3_TEXT);
    $stmt->bindValue(':pw', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':rl', $u['role'], SQLITE3_TEXT);
    $stmt->bindValue(':dg', $u['designation'], SQLITE3_TEXT);
    $stmt->bindValue(':dp', $u['department'], SQLITE3_TEXT);
    $stmt->bindValue(':ap', $u['is_approved'], SQLITE3_INTEGER);
    $stmt->execute();
    $userMap[$u['username']] = $db->lastInsertRowID();
}
echo "Users seeded: " . count($userMap) . "\n";

$reportCount = $db->querySingle('SELECT COUNT(*) FROM reports');
if ($reportCount > 0) {
    echo "Reports already exist. Skipping.\n";
} else {
    $reports = [
        ['type' => 'Unsafe Act', 'title' => 'Forklift operated without seatbelt', 'desc' => 'Operator observed driving loaded forklift through main aisle without wearing the seatbelt. Risk of ejection during sudden stop or collision.', 'category' => 'PPE Violation', 'severity' => 'High', 'status' => 'Open', 'department' => 'Operations', 'location' => 'Warehouse A, Aisle 6', 'reporter' => 'Ana Reyes', 'assignee' => 'Roberto Lim', 'days_ago' => 2],
        ['type' => 'Unsafe Act', 'title' => 'Hot work performed without permit', 'desc' => 'Contractor observed welding near flammable storage area without a visible hot work permit or fire watch in place.', 'category' => 'Working Without Authorization', 'severity' => 'Critical', 'status' => 'Under Investigation', 'department' => 'Maintenance', 'location' => 'Yard 3, Tank Farm perimeter', 'reporter' => 'Carlos Garcia', 'assignee' => 'Maria Santos', 'days_ago' => 5],
        ['type' => 'Unsafe Act', 'title' => 'Bypassing machine guard on press', 'desc' => 'Operator removed interlock guard to clear a jam without following lockout/tagout procedure. Machine could cycle unexpectedly.', 'category' => 'Bypassing Safety Devices', 'severity' => 'Critical', 'status' => 'Open', 'department' => 'Operations', 'location' => 'Production Line 2, Press Station', 'reporter' => 'Elena Mendoza', 'assignee' => 'Rafael Cruz', 'days_ago' => 1],
        ['type' => 'Unsafe Act', 'title' => 'Climbing racking instead of using ladder', 'desc' => 'Warehouse worker climbed storage racking 3 meters high to retrieve a pallet instead of using the available order picker.', 'category' => 'Unsafe Positioning', 'severity' => 'Medium', 'status' => 'Corrective Action In Progress', 'department' => 'Logistics', 'location' => 'Warehouse B, Zone C', 'reporter' => 'Miguel Bautista', 'assignee' => 'Roberto Lim', 'days_ago' => 8],
        ['type' => 'Unsafe Act', 'title' => 'Entering confined space without gas test', 'desc' => 'Maintenance crew began work inside a vessel without performing atmospheric testing or having a standby person stationed at the entry point.', 'category' => 'Failure to Lock Out/Tag Out', 'severity' => 'Critical', 'status' => 'Under Investigation', 'department' => 'Maintenance', 'location' => 'Plant 1, Vessel V-204', 'reporter' => 'Roberto Lim', 'assignee' => 'Maria Santos', 'days_ago' => 3],
        ['type' => 'Unsafe Act', 'title' => 'Working at height without harness', 'desc' => 'Electrician working on overhead cable tray at 5m height without fall protection. No anchor points established.', 'category' => 'PPE Violation', 'severity' => 'High', 'status' => 'Open', 'department' => 'Engineering', 'location' => 'Admin Building, Ceiling void Floor 3', 'reporter' => 'Carlos Garcia', 'assignee' => 'Rafael Cruz', 'days_ago' => 1],
        ['type' => 'Unsafe Act', 'title' => 'Horseplay in loading dock area', 'desc' => 'Two workers engaged in pushing match near active forklift traffic. Risk of being struck by moving equipment.', 'category' => 'Horseplay', 'severity' => 'Medium', 'status' => 'Closed', 'department' => 'Operations', 'location' => 'Loading Dock 2', 'reporter' => 'Juan Dela Cruz', 'assignee' => 'Elena Mendoza', 'days_ago' => 14, 'root_cause' => 'Lack of supervision during break period. Workers unaware of loading dock traffic risks.', 'corrective_action' => 'Verbal warning issued to both workers. Refresher safety briefing conducted for all shift workers. Increased supervisor presence during breaks.'],
        ['type' => 'Unsafe Act', 'title' => 'Improper manual lifting of 30kg drum', 'desc' => 'Worker attempted to lift a 30kg chemical drum alone without mechanical aid or assistance, risking back injury.', 'category' => 'Improper Lifting', 'severity' => 'Medium', 'status' => 'Closed', 'department' => 'Operations', 'location' => 'Chemical Storage Area', 'reporter' => 'Sofia Tan', 'assignee' => 'Roberto Lim', 'days_ago' => 20, 'root_cause' => 'Drum trolley was out of service. Worker chose to lift manually rather than wait for replacement.', 'corrective_action' => 'Replacement drum trolley procured. Training on manual handling limits reinforced. Procedure updated to mandate mechanical aids for loads over 15kg.'],
        ['type' => 'Unsafe Condition', 'title' => 'Exposed wiring near wash station', 'desc' => 'Junction box cover missing, live wiring exposed at floor level next to a wet-process area. Risk of electrocution.', 'category' => 'Electrical Hazard', 'severity' => 'Critical', 'status' => 'Open', 'department' => 'Maintenance', 'location' => 'Plant 2, Wash Bay', 'reporter' => 'Ana Reyes', 'assignee' => 'Carlos Garcia', 'days_ago' => 1],
        ['type' => 'Unsafe Condition', 'title' => 'Spill kit signage faded and unreadable', 'desc' => 'Signage pointing to spill kit location has faded to the point of being unreadable. New personnel cannot locate spill response equipment.', 'category' => 'Missing Signage', 'severity' => 'Low', 'status' => 'Closed', 'department' => 'HSE', 'location' => 'Mixing Room 2', 'reporter' => 'Sofia Tan', 'assignee' => 'Maria Santos', 'days_ago' => 25, 'root_cause' => 'Signage material not rated for chemical exposure. No periodic inspection schedule in place.', 'corrective_action' => 'Chemical-resistant signage installed. Quarterly signage inspection added to HSE checklist.'],
        ['type' => 'Unsafe Condition', 'title' => 'Wet floor in main corridor without barriers', 'desc' => 'Water leaking from overhead pipe creating a continuous wet surface in the main pedestrian corridor. No cones, signs, or barriers placed.', 'category' => 'Wet/Slippery Surfaces', 'severity' => 'High', 'status' => 'In Progress', 'department' => 'Operations', 'location' => 'Building A, Ground Floor Corridor', 'reporter' => 'Elena Mendoza', 'assignee' => 'Roberto Lim', 'days_ago' => 4],
        ['type' => 'Unsafe Condition', 'title' => 'Emergency exit blocked by stored materials', 'desc' => 'Pallets and boxed inventory stacked in front of Fire Exit 4, preventing the door from opening fully. Violation of fire code.', 'category' => 'Blocked Emergency Exit', 'severity' => 'Critical', 'status' => 'Open', 'department' => 'Logistics', 'location' => 'Warehouse A, Fire Exit 4', 'reporter' => 'Miguel Bautista', 'assignee' => 'Maria Santos', 'days_ago' => 1],
        ['type' => 'Unsafe Condition', 'title' => 'Inadequate lighting in stairwell B', 'desc' => 'Two of six light fixtures in Stairwell B are non-functional. Illumination level below minimum required for safe egress.', 'category' => 'Poor Lighting', 'severity' => 'Medium', 'status' => 'Corrective Action In Progress', 'department' => 'Operations', 'location' => 'Building B, Stairwell B', 'reporter' => 'Juan Dela Cruz', 'assignee' => 'Carlos Garcia', 'days_ago' => 6],
        ['type' => 'Unsafe Condition', 'title' => 'Defective overhead crane hook', 'desc' => 'Safety latch on overhead crane hook in Bay 5 is broken. Hook could slip off slings during lifting operations.', 'category' => 'Defective Equipment', 'severity' => 'High', 'status' => 'Open', 'department' => 'Maintenance', 'location' => 'Workshop, Bay 5 Crane', 'reporter' => 'Roberto Lim', 'assignee' => 'Rafael Cruz', 'days_ago' => 2],
        ['type' => 'Unsafe Condition', 'title' => 'Missing guardrail on mezzanine edge', 'desc' => 'Section of guardrail (approx 3m) removed for maintenance access and not reinstated. Fall hazard to workers below and above.', 'category' => 'Fall Hazard', 'severity' => 'High', 'status' => 'Under Investigation', 'department' => 'Engineering', 'location' => 'Production Line 1, Mezzanine', 'reporter' => 'Carlos Garcia', 'assignee' => 'Maria Santos', 'days_ago' => 3],
        ['type' => 'Unsafe Condition', 'title' => 'Chemical drum stored near ignition source', 'desc' => 'Flammable solvent drum stored within 2 meters of an electrical panel that produces sparks during switching operations.', 'category' => 'Fire/Explosion Risk', 'severity' => 'Critical', 'status' => 'Open', 'department' => 'Operations', 'location' => 'Chemical Store, Bay 3', 'reporter' => 'Sofia Tan', 'assignee' => 'Rafael Cruz', 'days_ago' => 1],
        ['type' => 'Unsafe Condition', 'title' => 'Noise levels exceeding PEL in grinding area', 'desc' => 'Sound level measurement recorded 98 dBA TWA in grinding area. OSHA PEL is 90 dBA. Engineering controls not in place.', 'category' => 'Noise Exposure', 'severity' => 'High', 'status' => 'In Progress', 'department' => 'Engineering', 'location' => 'Workshop, Grinding Bay', 'reporter' => 'Ana Reyes', 'assignee' => 'Carlos Garcia', 'days_ago' => 7],
        ['type' => 'Unsafe Condition', 'title' => 'Poor ventilation in paint booth', 'desc' => 'Exhaust fan running at reduced speed. VOC readings above acceptable limits. Workers experiencing headaches and eye irritation.', 'category' => 'Poor Ventilation', 'severity' => 'High', 'status' => 'Open', 'department' => 'Maintenance', 'location' => 'Paint Shop, Booth 2', 'reporter' => 'Elena Mendoza', 'assignee' => 'Roberto Lim', 'days_ago' => 2],
        ['type' => 'Unsafe Condition', 'title' => 'Damaged scaffolding planks', 'desc' => 'Several scaffold planks in supported scaffold are cracked or split. Load capacity compromised. Workers currently using scaffold.', 'category' => 'Structural Deficiency', 'severity' => 'Critical', 'status' => 'Closed', 'department' => 'Engineering', 'location' => 'Plant 3, Exterior Wall', 'reporter' => 'Carlos Garcia', 'assignee' => 'Maria Santos', 'days_ago' => 18, 'root_cause' => 'Planks not inspected before reuse. No scaffold inspection checklist in use.', 'corrective_action' => 'All damaged planks replaced. Scaffold inspection protocol implemented. Weekly inspection by competent person mandated.'],
    ];

    foreach ($reports as $i => $r) {
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$r['days_ago']} days"));
        $dateObserved = date('Y-m-d', strtotime("-{$r['days_ago']} days"));
        $uid = $userMap[$r['reporter']] ?? null;
        $reportNumber = sprintf('USAC-2026-%04d', $i + 1);

        $stmt = $db->prepare('INSERT INTO reports (report_number, report_type, title, description, category, severity, status, department, location, date_observed, reported_by, assigned_to, reassignment_count, root_cause, corrective_action, user_id, created_at, updated_at) VALUES (:rn, :rt, :ti, :de, :ca, :se, :st, :dp, :lo, :do, :rb, :at, :rc, :rca, :coa, :ui, :cat, :uat)');
        $stmt->bindValue(':rn', $reportNumber, SQLITE3_TEXT);
        $stmt->bindValue(':rt', $r['type'], SQLITE3_TEXT);
        $stmt->bindValue(':ti', $r['title'], SQLITE3_TEXT);
        $stmt->bindValue(':de', $r['desc'], SQLITE3_TEXT);
        $stmt->bindValue(':ca', $r['category'], SQLITE3_TEXT);
        $stmt->bindValue(':se', $r['severity'], SQLITE3_TEXT);
        $stmt->bindValue(':st', $r['status'], SQLITE3_TEXT);
        $stmt->bindValue(':dp', $r['department'], SQLITE3_TEXT);
        $stmt->bindValue(':lo', $r['location'], SQLITE3_TEXT);
        $stmt->bindValue(':do', $dateObserved, SQLITE3_TEXT);
        $stmt->bindValue(':rb', $r['reporter'], SQLITE3_TEXT);
        $stmt->bindValue(':at', $r['assignee'], SQLITE3_TEXT);
        $stmt->bindValue(':rc', rand(0, 3), SQLITE3_INTEGER);
        $stmt->bindValue(':rca', $r['root_cause'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':coa', $r['corrective_action'] ?? null, SQLITE3_TEXT);
        $stmt->bindValue(':ui', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':cat', $createdAt, SQLITE3_TEXT);
        $stmt->bindValue(':uat', $createdAt, SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "Reports seeded: " . count($reports) . "\n";
}

echo "\n--- LOGIN CREDENTIALS ---\n";
echo "Admin:          admin / admin123\n";
echo "Safety Officer: msantos / pass123\n";
echo "Safety Officer: rcruz / pass123\n";
echo "Employee:       jdelacruz / pass123\n";
echo "Employee:       areyes / pass123\n";
echo "Employee:       cgarcia / pass123\n";
echo "Employee:       emendoza / pass123\n";
echo "Employee:       rlim / pass123\n";
echo "Employee:       stan / pass123\n";
echo "Employee:       mbautista / pass123\n";
echo "Pending:        paquino / pass123\n";
echo "Pending:        dramos / pass123\n";
