<?php

// Configuration for the PSTD Ranking Matrix evaluation system.
// Adjust these values to tune the ranking behaviour without touching service code.
return [

    // Performer bucket cutoffs by RANK within each province CSTC tier
    // (micro / small / medium / large / cstc). Per Ma'am Grace's directive, ranking is
    // rank-based not score-based: the top 20% by rank get the Top label, etc.
    'buckets' => [
        'top_pct'   => 0.20,
        'under_pct' => 0.20,
    ],

    // If a CSTC tier has fewer than this many provinces with data for the year, skip
    // bucket labels entirely (the rank is still shown). Keeps tiny groups from getting
    // meaningless "Top of 2" / "Under of 1" labels.
    'min_group_size_for_buckets' => 5,

    // Adjective bands per the PSTD Ranking Matrix scoring guide. Each entry is
    // [min_percentage, score]. The first row whose min_percentage is <= actual
    // wins. Order matters — list highest cutoff first.
    'score_bands_standard' => [
        ['min' => 111, 'score' => 1.0, 'label' => 'Outstanding'],
        ['min' => 100, 'score' => 0.8, 'label' => 'Very Satisfactory'],
        ['min' => 76,  'score' => 0.6, 'label' => 'Satisfactory'],
        ['min' => 26,  'score' => 0.4, 'label' => 'Average'],
        ['min' => 11,  'score' => 0.2, 'label' => 'Unsatisfactory'],
        ['min' => 0,   'score' => 0.0, 'label' => 'Poor'],
    ],

    // Inverse bands — used by the "% Delinquent SETUP" KPI where lower is better.
    // Each entry is [max_percentage, score]. The first row whose max_percentage
    // is >= actual wins.
    'score_bands_inverse' => [
        ['max' => 0,   'score' => 1.0, 'label' => 'Outstanding'],
        ['max' => 24,  'score' => 0.8, 'label' => 'Very Satisfactory'],
        ['max' => 49,  'score' => 0.6, 'label' => 'Satisfactory'],
        ['max' => 75,  'score' => 0.4, 'label' => 'Average'],
        ['max' => 99,  'score' => 0.2, 'label' => 'Unsatisfactory'],
        ['max' => 100, 'score' => 0.0, 'label' => 'Poor'],
    ],
];
