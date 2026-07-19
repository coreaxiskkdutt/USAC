<?php
session_start();

define('DB_PATH', __DIR__ . '/usac.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD', 16 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['png', 'jpg', 'jpeg', 'gif', 'pdf']);

define('DEPARTMENTS', [
    'Operations', 'Maintenance', 'Engineering', 'HSE', 'Human Resources',
    'Procurement', 'Logistics', 'Quality', 'IT', 'Finance', 'Admin', 'Other'
]);

define('CATEGORIES_ACT', [
    'PPE Violation', 'Unsafe Positioning', 'Improper Lifting', 'Horseplay',
    'Bypassing Safety Devices', 'Improper Use of Tools', 'Failure to Lock Out/Tag Out',
    'Working Without Authorization', 'Ignoring Warning Signs', 'Improper Housekeeping',
    'Fatigue/Impairment', 'Other'
]);

define('CATEGORIES_CONDITION', [
    'Unguarded Machinery', 'Defective Equipment', 'Wet/Slippery Surfaces',
    'Poor Lighting', 'Chemical Exposure', 'Electrical Hazard', 'Fall Hazard',
    'Confined Space', 'Noise Exposure', 'Fire/Explosion Risk',
    'Poor Ventilation', 'Missing Signage', 'Structural Deficiency',
    'Blocked Emergency Exit', 'Improper Storage', 'Other'
]);

define('SEVERITY_LEVELS', ['Low', 'Medium', 'High', 'Critical']);
define('REPORT_TYPES', ['Unsafe Act', 'Unsafe Condition']);
define('STATUS_OPTIONS', ['Open', 'Under Investigation', 'Corrective Action In Progress', 'Closed']);
define('DESIGNATIONS', [
    'Operator', 'Technician', 'Engineer', 'Supervisor', 'Foreman',
    'Manager', 'Coordinator', 'Specialist', 'Inspector', 'Planner',
    'Officer', 'Analyst', 'Director', 'Superintendent', 'Lead', 'Other'
]);
