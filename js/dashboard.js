(function ($) {
    'use strict';

    var dashboardState = {
        conveyor: null,
        replen: null
    };

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function formatNumber(value, decimals) {
        var parsed = parseFloat(value);
        if (isNaN(parsed)) {
            return '&mdash;';
        }
        return parsed.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatDate(value) {
        if (!value) {
            return 'Unavailable';
        }
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric'});
    }

    function setText(selector, value) {
        $(selector).html(value);
    }

    function renderConveyor(payload) {
        var totals = payload.totals || {};
        var impact = payload.impact || {};
        var capacity = parseFloat(totals.capacity) || 0;
        var plannedOpen = parseFloat(totals.planned_open) || 0;
        var reserve = capacity > 0 ? (plannedOpen / capacity) * 100 : 0;

        setText('[data-conveyor-stat="model-date"]', escapeHtml(formatDate(payload.model_as_of)));
        setText('[data-conveyor-stat="total-actions"]', formatNumber(totals.total_actions, 0));
        setText('[data-conveyor-stat="move-in"]', formatNumber(totals.move_in, 0));
        setText('[data-conveyor-stat="move-out"]', formatNumber(totals.move_out, 0));
        setText('[data-conveyor-stat="walk-day"]', formatNumber(impact.daily_walk_reduction_meters, 1));
        setText('[data-conveyor-stat="annual-replens"]', formatNumber(impact.annual_replen_reduction, 0));
        setText('[data-conveyor-stat="planned-open"]', formatNumber(totals.planned_open, 0));
        setText('[data-conveyor-stat="reserve"]', formatNumber(reserve, 1) + '% capacity reserve');
        $('#dashboard-conveyor-card').attr('aria-busy', 'false');

        dashboardState.conveyor = payload;
        renderCombinedHero();
    }

    function renderReplen(payload) {
        var summary = payload.summary || {};
        var health = payload.health || {};
        var healthType = health.planning_available ? 'ready' : 'warning';
        var healthIcon = health.planning_available ? 'check-circle' : 'exclamation-triangle';
        var healthText = health.planning_available
            ? 'Exact-location planning is ready.'
            : (health.message || 'Opportunities are available for review only.');

        setText('[data-replen-stat="model-date"]', escapeHtml(formatDate(health.model_as_of)));
        setText('[data-replen-stat="annual-hours"]', formatNumber(summary.annual_labor_hours, 1));
        setText('[data-replen-stat="annual-replens"]', formatNumber(summary.annual_replens_avoided, 0));
        setText('[data-replen-stat="physical-reslots"]', formatNumber(summary.physical_reslots, 0));
        setText('[data-replen-stat="ready-now"]', formatNumber(summary.ready_now, 0));
        setText('[data-replen-stat="adjustments"]', formatNumber(summary.adjustments, 0));
        $('#dashboard-replen-health').attr('data-health', healthType)
            .html('<i class="fa fa-' + healthIcon + '" aria-hidden="true"></i><span>' + escapeHtml(healthText) + '</span>');
        $('#dashboard-replen-card').attr('aria-busy', 'false');

        dashboardState.replen = payload;
        renderCombinedHero();
    }

    function renderCombinedHero() {
        var conveyor = dashboardState.conveyor;
        var replen = dashboardState.replen;

        if (conveyor) {
            setText('[data-dashboard-stat="conveyor-actions"]', formatNumber(conveyor.totals.total_actions, 0));
        }
        if (replen) {
            setText('[data-dashboard-stat="replen-hours"]', formatNumber(replen.summary.annual_labor_hours, 1));
        }
        if (!conveyor || !replen) {
            return;
        }

        var conveyorDate = conveyor.model_as_of ? new Date(String(conveyor.model_as_of).replace(' ', 'T')) : null;
        var replenDate = replen.health.model_as_of ? new Date(String(replen.health.model_as_of).replace(' ', 'T')) : null;
        var modelDate = conveyorDate && replenDate
            ? (conveyorDate < replenDate ? conveyor.model_as_of : replen.health.model_as_of)
            : (conveyor.model_as_of || replen.health.model_as_of);
        var ready = !!replen.health.planning_available;

        setText('[data-dashboard-stat="model-date"]', escapeHtml(formatDate(modelDate)));
        setText('[data-dashboard-stat="planning-state"]', ready ? 'Ready to build floor plans' : 'Opportunity review available');
        $('.slotting-dashboard-status').attr('data-dashboard-status', ready ? 'ready' : 'warning');
    }

    function showModuleError(moduleName, message) {
        var safeMessage = escapeHtml(message || 'Summary data is temporarily unavailable.');
        $('#dashboard-' + moduleName + '-card').attr('aria-busy', 'false');
        $('#dashboard-' + moduleName + '-error').removeClass('hidden')
            .html('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + safeMessage);
        $('.slotting-dashboard-status').attr('data-dashboard-status', 'warning');
        setText('[data-dashboard-stat="planning-state"]', 'One module needs attention');
    }

    $(function () {
        $.getJSON('globaldata/conveyor_reslot_data.php', {view: 'summary'})
            .done(function (payload) {
                if (payload.success) {
                    renderConveyor(payload);
                } else {
                    showModuleError('conveyor', payload.message);
                }
            })
            .fail(function (xhr) {
                showModuleError('conveyor', xhr.responseJSON && xhr.responseJSON.message);
            });

        $.getJSON('globaldata/replen_reslot_data.php', {view: 'summary'})
            .done(function (payload) {
                if (payload.success) {
                    renderReplen(payload);
                } else {
                    showModuleError('replen', payload.message);
                }
            })
            .fail(function (xhr) {
                showModuleError('replen', xhr.responseJSON && xhr.responseJSON.message);
            });
    });
}(jQuery));
