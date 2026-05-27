<?php

function clms_default_education_flow_rows() {
    return [
        ['Skilled', 'B.Tech', 'Electrical Engineer'],
        ['Skilled', 'B.Tech', 'Mechanical Engineer'],
        ['Skilled', 'B.Tech', 'Structural Engineer'],
        ['Skilled', 'B.Tech', 'IT Engineer'],
        ['Skilled', 'B.Tech', 'Civil Engineer'],
        ['Skilled', 'B.Tech', 'Electronics Engineer'],
        ['Semi-Skilled', 'Diploma', 'Electrical Technician'],
        ['Semi-Skilled', 'Diploma', 'Draftsman'],
        ['Semi-Skilled', 'Diploma', 'Civil'],
        ['Semi-Skilled', 'Diploma', 'Structural'],
        ['Semi-Skilled', 'Diploma', 'IT'],
        ['Semi-Skilled', 'Diploma', 'Electronics'],
        ['Semi-Skilled', 'ITI Certification', 'Painter'],
        ['Semi-Skilled', 'ITI Certification', 'Welder'],
        ['Semi-Skilled', 'ITI Certification', 'Fitter'],
        ['Semi-Skilled', 'ITI Certification', 'Carpenter'],
        ['Semi-Skilled', 'ITI Certification', 'Fitter - Pipe'],
        ['Semi-Skilled', 'ITI Certification', 'Plumber'],
        ['Semi-Skilled', 'Class 10th or equivalent', 'Rigger'],
        ['Semi-Skilled', 'Class 10th or equivalent', 'Blaster'],
        ['Unskilled', 'Below Class 10th', 'Helper'],
    ];
}

function clms_normalize_flow_skill($skill) {
    $value = trim((string)$skill);
    $normalized = strtolower(str_replace(['_', '-'], ' ', $value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    if ($normalized === 'semi skilled') return 'Semi-Skilled';
    if ($normalized === 'skilled') return 'Skilled';
    if ($normalized === 'unskilled' || $normalized === 'un skilled') return 'Unskilled';

    return $value;
}

function clms_flow_skill_for_workmen($skill) {
    $flowSkill = clms_normalize_flow_skill($skill);
    return $flowSkill === 'Semi-Skilled' ? 'Semi Skilled' : $flowSkill;
}

function clms_ensure_education_flow_table($conn) {
    static $done = false;
    if ($done) return;

    $conn->query("
        CREATE TABLE IF NOT EXISTS education_job_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            skill_category VARCHAR(50) NOT NULL,
            qualification VARCHAR(150) NOT NULL,
            job_profile VARCHAR(150) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_education_job_profile (skill_category, qualification, job_profile),
            KEY idx_education_job_profiles_active (is_active, skill_category, qualification)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $count = db_count($conn, "SELECT COUNT(*) FROM education_job_profiles");
    if ($count === 0) {
        $sort = 10;
        foreach (clms_default_education_flow_rows() as $row) {
            db_execute(
                $conn,
                "INSERT IGNORE INTO education_job_profiles (skill_category, qualification, job_profile, sort_order, is_active)
                 VALUES (?, ?, ?, ?, 1)",
                'sssi',
                [$row[0], $row[1], $row[2], $sort]
            );
            $sort += 10;
        }
    }

    $done = true;
}

function clms_get_education_flow_rows($conn, $includeInactive = false) {
    clms_ensure_education_flow_table($conn);
    $where = $includeInactive ? '' : 'WHERE is_active = 1';
    return db_fetch_all(
        $conn,
        "SELECT id, skill_category, qualification, job_profile, sort_order, is_active
         FROM education_job_profiles
         $where
         ORDER BY FIELD(skill_category, 'Skilled', 'Semi-Skilled', 'Unskilled'), sort_order, qualification, job_profile"
    );
}

function clms_get_education_flow($conn) {
    $flow = [
        'Skilled' => ['qualifications' => []],
        'Semi-Skilled' => ['qualifications' => []],
        'Unskilled' => ['qualifications' => []],
    ];

    foreach (clms_get_education_flow_rows($conn) as $row) {
        $category = clms_normalize_flow_skill($row['skill_category'] ?? '');
        if ($category === '') continue;
        if (!isset($flow[$category])) {
            $flow[$category] = ['qualifications' => []];
        }

        $qualification = trim((string)($row['qualification'] ?? ''));
        $jobProfile = trim((string)($row['job_profile'] ?? ''));
        if ($qualification === '' || $jobProfile === '') continue;

        if (!isset($flow[$category]['qualifications'][$qualification])) {
            $flow[$category]['qualifications'][$qualification] = [];
        }
        if (!in_array($jobProfile, $flow[$category]['qualifications'][$qualification], true)) {
            $flow[$category]['qualifications'][$qualification][] = $jobProfile;
        }
    }

    return array_filter($flow, function ($category) {
        return !empty($category['qualifications']);
    });
}

function clms_get_education_options($conn) {
    $options = [];
    foreach (clms_get_education_flow($conn) as $category => $data) {
        foreach (($data['qualifications'] ?? []) as $qualification => $jobs) {
            if (!isset($options[$qualification])) {
                $options[$qualification] = ['skill' => $category, 'jobs' => []];
            }
            foreach ($jobs as $job) {
                if (!in_array($job, $options[$qualification]['jobs'], true)) {
                    $options[$qualification]['jobs'][] = $job;
                }
            }
        }
    }
    return $options;
}
