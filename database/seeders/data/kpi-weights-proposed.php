<?php

// The KPI catalog revised from the regional comments: 60 / 30 / 10 becomes
// 60 / 25 / 15, with 30 KPIs. data/kpis.php keeps the official PSTD matrix as
// drafted (39 KPIs) and is what the "Original" catalog version is built from.
//
// Only the KPIs listed here are in the revised catalog. The other nine are
// removed (soft-deleted, so their history is kept and restoring the Original
// brings them back): the seven indicators the comments dropped from scoring —
// firms graduated, firms that became exporters, value of SETUP, GIA and CEST
// projects funded, SETUP refunded amount, % delinquent SETUP projects — and
// the two supporting inputs that only fed % delinquent SETUP.
//
// Used by the seeder (the live catalog starts on this) and by the migration
// that saves it as the "Proposed" catalog version.

return [
    'label' => 'Proposed (regional comments) - 60/25/15',
    'note'  => 'Revised from the regional comments: 30 KPIs - Core 60% (18), Strategic 25% (4), Support 15% (8). '
             . 'Firms graduated, firms that became exporters, value of SETUP, GIA and CEST projects funded, '
             . 'SETUP refunded amount and % delinquent SETUP projects are removed.',

    // KPI code => weight (fraction of the total score)
    'weights' => [
        // ── Core: 60% ─────────────────────────────────────────────────────
        'core_rd_proposals_funded'       => 0.02,  // 3.1  reduced from 3%
        'core_collab_rd_implemented'     => 0.02,  // 3.2  reduced from 3%
        'core_dost_tech_transferred'     => 0.03,  // 3.3  raised from 2%
        'core_dost_tech_adopted'         => 0.03,  // 3.4  raised from 2%
        'core_tech_adoptors'             => 0.02,  // 3.5  retained
        'core_st_interventions'          => 0.05,  // 3.6  raised from 2%
        'core_startups_assisted'         => 0.02,  // 3.7  retained
        'core_customers_assisted'        => 0.03,  // 3.8  raised from 2%
        'core_msmes_assisted'            => 0.04,  // 3.9  raised; absorbs the graduated-firm count
        'core_employment_generated'      => 0.02,  // 3.10 retained
        'core_gross_sales_generated'     => 0.01,  // 3.11 reduced from 2% (client-reported)
        'core_innovation_hubs'           => 0.02,  // 3.14 retained
        'core_communities_assisted'      => 0.05,  // 3.15 raised from 3%
        'core_tech_deployed_communities' => 0.05,  // 3.16 raised from 3%
        'core_setup_projects_funded'     => 0.05,  // 3.17 raised; absorbs 3.18's 3%
        'core_setup_refund_rate'         => 0.04,  // 3.19 raised from 3%
        'core_gia_proposals_funded'      => 0.05,  // 3.21 raised; absorbs 3.22's 3%
        'core_cest_proposals_funded'     => 0.05,  // 3.23 raised; absorbs 3.24's 3%

        // ── Strategic: 25% ────────────────────────────────────────────────
        'func_linkages_established'      => 0.08,  // 4.1  raised from 7.5%
        'func_external_funds_sourced'    => 0.08,  // 4.2  raised from 5%
        'func_drrm_collaborations'       => 0.04,  // 4.3  reduced from 7.5%
        'func_drr_measures'              => 0.05,  // 4.4  reduced from 10%

        // ── Support: 15% ──────────────────────────────────────────────────
        'supp_st_promo_activities'          => 0.03,   // 5.1 raised from 1%
        'supp_samples_referred'             => 0.01,   // 5.2 retained
        'supp_testing_calibration_referred' => 0.01,   // 5.3 reduced from 2%
        'supp_pct_programs_risk_analysis'   => 0.02,   // 5.4 raised from 1%
        'supp_work_process_innovations'     => 0.03,   // 5.5 raised from 1.5%
        'supp_customer_satisfaction'        => 0.03,   // 5.6 raised from 1.5%
        'supp_facebook_posts'               => 0.01,   // 5.7 retained
        'supp_press_releases'               => 0.01,   // 5.8 retained
    ],
];
