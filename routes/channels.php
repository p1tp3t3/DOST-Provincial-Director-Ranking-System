<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Every authenticated user may listen. The KPI matrix is shared reference data:
 * its category names, KPI names and weights are already rendered to every role
 * on their dashboard, and to anyone who opens a province profile — so the event
 * (action, kpi_id, kpi_name, category_id) discloses nothing a listener can't
 * already read. Only Super Admin and Sub Admin can *change* the catalog; that is
 * enforced on the routes, not here.
 *
 * Listening is what keeps an open dashboard from showing weights and scores
 * that the matrix no longer uses. See useKpiCatalogSync() on the front end.
 */
Broadcast::channel('kpi-catalog', function ($user) {
    return $user !== null;
});
