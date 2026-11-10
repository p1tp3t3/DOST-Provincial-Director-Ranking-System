<?php

// Field names as people see them on the forms, used in validation messages
// ("The Employee No. has already been taken." instead of "The dost employee
// id …"). Laravel's own message wording still applies; this file only adds
// names, merged over the framework's defaults.

return [
    'attributes' => [
        'identifier'             => 'employee no. or email',
        'dost_employee_id'       => 'Employee No.',
        'admin_employee_id'      => 'Employee No.',
        'admin_email'            => 'email',
        'admin_password'         => 'password',
        'confirm_admin_password' => 'password confirmation',
        'province_id'            => 'province',
        'category_id'            => 'KPI category',
        'num_plantilla_employees'=> 'number of plantilla employees',
        'num_municipalities'     => 'number of municipalities',
        'num_cities'             => 'number of cities',
        'csv_file'               => 'file',
        'picture'                => 'photo',
        'is_published'           => 'published',
        'open_until'             => 'reopen-until date',
        'post_url'               => 'post link',
        'changes.*.name'         => 'KPI name',
        'changes.*.category_id'  => 'KPI category',
        'changes.*.weight'       => 'KPI weight',
        'changes.*.rollup_rule'  => 'roll-up rule',
        'rows.*.name'            => 'KPI name',
        'rows.*.category_id'     => 'KPI category',
        'rows.*.weight'          => 'KPI weight',
        'targets.*'              => 'annual target',
        'quarters.*.*'           => 'quarterly figure',
        'deadlines.*'            => 'deadline',
        'education.*'            => 'education entry',
    ],
];
