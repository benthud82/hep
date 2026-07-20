(function ($) {
    'use strict';

    var readEndpoint = 'globaldata/replen_reslot_data.php';
    var writeEndpoint = 'formpost/replen_reslot_plan.php';
    var csrfToken = $('meta[name="replen-reslot-csrf"]').attr('content') || '';
    var currentUser = String($('body').attr('data-replen-user') || '').toUpperCase();
    var opportunityPayload = null;
    var opportunityTable = null;
    var selectedKeys = {};
    var userList = [];
    var savedPlans = [];
    var currentPlanPayload = null;
    var currentEditingPlan = null;
    var reopenPlanAfterEdit = false;
    var planItemsTable = null;
    var pendingAction = null;

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function number(value, decimals) {
        var parsed = parseFloat(value);
        if (isNaN(parsed)) {
            parsed = 0;
        }
        return parsed.toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
    }

    function signedNumber(value, decimals) {
        var parsed = parseFloat(value) || 0;
        return (parsed > 0 ? '+' : '') + number(parsed, decimals);
    }

    function formatDate(value) {
        if (!value) {
            return 'Unavailable';
        }
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString();
    }

    function actionLabel(action) {
        var labels = {
            DIRECT_RESLOT: 'Direct Reslot',
            DEPENDENCY_RESLOT: 'Dependency Reslot',
            MAX_MIN_ADJUSTMENT: 'Max/Min Only',
            REVIEW_REQUIRED: 'Review Required'
        };
        var classes = {
            DIRECT_RESLOT: 'replen-action-direct',
            DEPENDENCY_RESLOT: 'replen-action-dependency',
            MAX_MIN_ADJUSTMENT: 'replen-action-adjust',
            REVIEW_REQUIRED: 'replen-action-review'
        };
        return '<span class="replen-action-badge ' + (classes[action] || '') + '">' + escapeHtml(labels[action] || action) + '</span>';
    }

    function statusLabel(status) {
        return '<span class="replen-status replen-status-' + String(status || '').toLowerCase() + '">' + escapeHtml(String(status || '').replace(/_/g, ' ')) + '</span>';
    }

    function showMessage(selector, type, message) {
        $(selector).removeClass('hidden alert-info alert-success alert-warning alert-danger')
            .addClass('alert-' + type).html(message);
    }

    function hideMessage(selector) {
        $(selector).addClass('hidden').empty();
    }

    function renderHealth(health, config) {
        var type = health.planning_available ? 'success' : (health.occupancy_available ? 'warning' : 'danger');
        var icon = health.planning_available ? 'check-circle' : 'exclamation-triangle';
        var heading = health.planning_available ? 'Planning data is ready.' : 'Plan creation is currently protected.';
        var details = health.message || 'The current model is available.';
        var assumptions = config ? ' Ranking assumptions: ' + number(config.replens_per_hour, 1) +
            ' replens/hour, ' + number(config.walk_meters_per_second, 1) + ' m/s walking, ' +
            number(config.operating_days, 0) + ' operating days.' : '';
        $('#replen-health-alert').removeClass('alert-info alert-success alert-warning alert-danger')
            .addClass('alert-' + type)
            .html('<i class="fa fa-' + icon + '"></i> <strong>' + escapeHtml(heading) + '</strong> ' + escapeHtml(details + assumptions));
        $('[data-replen-model-date]').text(formatDate(health.model_as_of));
    }

    function renderSummary(payload, rootSelector) {
        var summary = payload.summary || {};
        var $root = $(rootSelector || document);
        $.each(summary, function (key, value) {
            var decimals = key === 'annual_labor_hours' || key === 'daily_net_minutes' ? 1 : 0;
            $root.find('[data-replen-kpi="' + key + '"]').text(number(value, decimals));
        });
        $root.find('[data-replen-queue-count]').each(function () {
            $(this).text(number(summary[$(this).attr('data-replen-queue-count')], 0));
        });
        $root.find('[data-replen-model-date]').text(formatDate(payload.health.model_as_of));
        $root.find('[data-replen-planning-state]').text(payload.health.planning_available ? 'Ready for exact-location planning' : payload.health.message);
    }

    function renderDashboard(payload) {
        renderSummary(payload, '#replen-dashboard-summary');
        var summary = payload.summary || {};
        var html = '<div class="replen-dashboard-mini-grid">' +
            '<div><span>Annual labor opportunity</span><strong>' + number(summary.annual_labor_hours, 1) + ' hrs</strong></div>' +
            '<div><span>Modeled replens avoided</span><strong>' + number(summary.annual_replens_avoided, 0) + '</strong></div>' +
            '<div><span>Direct reslots ready</span><strong>' + number(summary.ready_now, 0) + '</strong></div>' +
            '<div><span>No-move adjustments</span><strong>' + number(summary.adjustments, 0) + '</strong></div>' +
        '</div>';
        $('#replen-dashboard-content').html(html);
        $('#replen-dashboard-loading').addClass('hidden');
    }

    function selectionAllowed(row) {
        return row.planning_eligible || row.override_allowed;
    }

    function updateSelectionUi() {
        var count = Object.keys(selectedKeys).length;
        $('#replen-selected-count').text(count);
        $('#replen-create-plan').prop('disabled', count === 0 || !opportunityPayload || !opportunityPayload.health.planning_available);
        if (opportunityTable) {
            $('#replen-opportunity-table .replen-select-row').each(function () {
                $(this).prop('checked', !!selectedKeys[$(this).attr('data-key')]);
            });
        }
    }

    function buildOpportunityTable(rows) {
        opportunityTable = $('#replen-opportunity-table').DataTable({
            data: rows,
            destroy: true,
            deferRender: true,
            responsive: true,
            fixedHeader: true,
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            order: [[12, 'desc']],
            dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                {extend: 'copyHtml5', text: 'Copy'},
                {extend: 'csvHtml5', text: 'CSV', title: 'Replenishment_Reslot_Opportunities', exportOptions: {columns: ':not(:first-child)'}},
                {extend: 'excelHtml5', text: 'Excel', title: 'Replenishment_Reslot_Opportunities', exportOptions: {columns: ':not(:first-child)'}},
                {extend: 'print', text: 'Print', title: 'Replenishment Reslot Opportunities', exportOptions: {columns: ':not(:first-child)'}}
            ],
            columns: [
                {data: null, orderable: false, searchable: false, render: function (data, type, row) {
                    if (type !== 'display') { return selectionAllowed(row) ? 1 : 0; }
                    if (!selectionAllowed(row)) { return '<i class="fa fa-lock text-muted" title="Review-only"></i>'; }
                    return '<input type="checkbox" class="replen-select-row" data-key="' + escapeHtml(row.key) + '" aria-label="Select item ' + escapeHtml(row.item_number) + '"/>';
                }},
                {data: 'priority'},
                {data: 'action_type', render: function (data, type) { return type === 'display' ? actionLabel(data) : data; }},
                {data: 'level'},
                {data: 'item_number', render: function (data, type) {
                    return type === 'display' ? '<a href="itemquery.php?itemnum=' + encodeURIComponent(data) + '" target="_blank" rel="noopener">' + escapeHtml(data) + '</a>' : data;
                }},
                {data: 'current_location'},
                {data: null, render: function (data, type, row) { var value = row.current_tier + ' / ' + row.current_grid + ' / ' + row.current_depth; return type === 'display' ? escapeHtml(value) : value; }},
                {data: null, render: function (data, type, row) { var value = row.suggested_tier + ' / ' + row.suggested_grid + ' / ' + row.suggested_depth; return type === 'display' ? escapeHtml(value) : value; }},
                {data: 'current_replens_day', render: function (data, type) { return type === 'display' ? number(data, 3) : data; }},
                {data: 'suggested_replens_day', render: function (data, type) { return type === 'display' ? number(data, 3) : data; }},
                {data: 'annual_replens_avoided', render: function (data, type) { return type === 'display' ? number(data, 0) : data; }},
                {data: 'walk_reduction_meters_day', render: function (data, type) { return type === 'display' ? '<span class="' + (parseFloat(data) < 0 ? 'text-danger' : 'text-success') + '">' + signedNumber(data, 1) + '</span>' : data; }},
                {data: 'annual_labor_hours', render: function (data, type) { return type === 'display' ? '<strong class="' + (parseFloat(data) <= 0 ? 'text-danger' : '') + '">' + signedNumber(data, 1) + '</strong>' : data; }},
                {data: null, render: function (data, type, row) { var value = row.shipment_occurrences + ' shipments / ' + row.days_since_sale + ' days'; return type === 'display' ? escapeHtml(value) : value; }},
                {data: null, render: function (data, type, row) {
                    if (row.action_type === 'MAX_MIN_ADJUSTMENT') { return type === 'display' ? '<span class="text-success">No location needed</span>' : 'No location needed'; }
                    var value = row.vacant_slots === null ? 'Occupancy unavailable' : row.vacant_slots + ' open / ' + row.compatible_slots + ' compatible';
                    return type === 'display' ? escapeHtml(value) : value;
                }},
                {data: null, render: function (data, type, row) {
                    var label = row.planning_eligible ? 'Ready' : (row.override_allowed ? 'Review + override' : 'Review only');
                    if (type !== 'display') { return row.confidence + ' ' + label + ' ' + row.reason; }
                    return '<span class="replen-confidence ' + (row.planning_eligible ? 'is-ready' : 'is-review') + '" title="' + escapeHtml(row.reason) + '">' + escapeHtml(label) + '</span>';
                }}
            ],
            columnDefs: [
                {className: 'text-center', targets: [0, 1, 3, 8, 9, 10, 11, 12]},
                {responsivePriority: 1, targets: [0, 2, 4, 5, 12]},
                {responsivePriority: 2, targets: [7, 10, 15]}
            ],
            createdRow: function (row, data) {
                $(row).toggleClass('replen-row-review', !data.planning_eligible);
                $(row).attr('data-opportunity-key', data.key);
            },
            drawCallback: updateSelectionUi,
            language: {emptyTable: 'No positive replenishment opportunities are available.', search: 'Search items and locations:'}
        });
        opportunityTable.column(2).search('^(DIRECT_RESLOT|DEPENDENCY_RESLOT|REVIEW_REQUIRED)$', true, false).draw();
        $('#replen-loading').addClass('hidden');
        $('#replen-opportunity-wrap').removeClass('hidden');
    }

    function populateUsers(users) {
        userList = users || [];
        var html = '';
        $.each(userList, function (index, user) {
            var selected = String(user.user_id).toUpperCase() === currentUser ? ' selected' : '';
            html += '<option value="' + escapeHtml(user.user_id) + '"' + selected + '>' + escapeHtml(user.user_name) + '</option>';
        });
        $('#replen-plan-assignee, #replen-action-assignee, #replen-edit-plan-assignee').html(html);
    }

    function loadOpportunities() {
        $.getJSON(readEndpoint, {view: 'opportunities'}).done(function (payload) {
            if (!payload.success) { throw new Error(payload.message || 'Opportunity load failed.'); }
            opportunityPayload = payload;
            csrfToken = payload.csrf_token || csrfToken;
            currentUser = String(payload.current_user || currentUser).toUpperCase();
            renderHealth(payload.health, payload.config);
            renderSummary(payload);
            populateUsers(payload.users);
            buildOpportunityTable(payload.rows || []);
            updateSelectionUi();
        }).fail(function (xhr) {
            $('#replen-loading').addClass('hidden');
            $('#replen-error').removeClass('hidden').text((xhr.responseJSON && xhr.responseJSON.message) || 'The opportunity list could not be loaded.');
        });
    }

    function opportunityByKey(key) {
        var match = null;
        $.each((opportunityPayload && opportunityPayload.rows) || [], function (index, row) {
            if (row.key === key) { match = row; return false; }
        });
        return match;
    }

    function renderPlanPreview() {
        var totals = {moves: 0, adjustments: 0, replens: 0, hours: 0};
        var body = '';
        var overrides = '';
        $.each(Object.keys(selectedKeys), function (index, key) {
            var row = opportunityByKey(key);
            if (!row) { return; }
            if (row.action_type === 'MAX_MIN_ADJUSTMENT') { totals.adjustments++; } else { totals.moves++; }
            totals.replens += parseFloat(row.annual_replens_avoided) || 0;
            totals.hours += parseFloat(row.annual_labor_hours) || 0;
            body += '<tr><td>' + escapeHtml(row.item_number) + '<small>' + escapeHtml(row.package_type) + ' / ' + escapeHtml(row.package_unit) + '</small></td>' +
                '<td>' + actionLabel(row.action_type) + '</td><td>' + escapeHtml(row.current_location) + '</td>' +
                '<td>' + escapeHtml(row.suggested_tier + ' / ' + row.suggested_grid + ' / ' + row.suggested_depth) + '</td>' +
                '<td>' + number(row.annual_replens_avoided, 0) + '</td><td>' + number(row.annual_labor_hours, 1) + '</td></tr>';
            if (row.override_allowed && !row.planning_eligible) {
                overrides += '<div class="form-group replen-override"><label>Override reason for item ' + escapeHtml(row.item_number) + '</label>' +
                    '<input class="form-control replen-override-reason" data-key="' + escapeHtml(row.key) + '" maxlength="255" placeholder="Required: explain why low-history work is approved"/></div>';
            }
        });
        $('#replen-plan-preview tbody').html(body);
        $('#replen-override-list').html(overrides);
        $('#replen-plan-impact').html('<div><span>Physical moves</span><strong>' + totals.moves + '</strong></div>' +
            '<div><span>No-move adjustments</span><strong>' + totals.adjustments + '</strong></div>' +
            '<div><span>Modeled replens avoided/year</span><strong>' + number(totals.replens, 0) + '</strong></div>' +
            '<div><span>Net labor hours/year</span><strong>' + number(totals.hours, 1) + '</strong></div>');
        hideMessage('#replen-plan-message');
    }

    function postAction(payload) {
        payload.csrf_token = csrfToken;
        return $.ajax({url: writeEndpoint, method: 'POST', data: JSON.stringify(payload), contentType: 'application/json; charset=utf-8', dataType: 'json'});
    }

    function submitPlan() {
        var overrides = {};
        var missingOverride = false;
        $('.replen-override-reason').each(function () {
            var reason = $.trim($(this).val());
            if (!reason) { missingOverride = true; $(this).closest('.form-group').addClass('has-error'); }
            overrides[$(this).attr('data-key')] = reason;
        });
        if (missingOverride) {
            showMessage('#replen-plan-message', 'danger', '<strong>Override reason required.</strong> Explain every low-history selection.');
            return;
        }
        var payload = {
            action: 'create_plan', plan_name: $.trim($('#replen-plan-name').val()), planned_date: $('#replen-plan-date').val(),
            assigned_to: $('#replen-plan-assignee').val(), notes: $.trim($('#replen-plan-notes').val()),
            opportunity_keys: Object.keys(selectedKeys), override_reasons: overrides
        };
        $('#replen-submit-plan').prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin"></i> Validating exact locations');
        postAction(payload).done(function (response) {
            $('#replen-plan-modal').modal('hide');
            selectedKeys = {};
            updateSelectionUi();
            loadPlans();
            openPlan(response.plan_id);
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            var blocked = response.blocked_items && response.blocked_items.length ? '<br><strong>Blocked items:</strong> ' + response.blocked_items.join(', ') : '';
            showMessage('#replen-plan-message', 'danger', '<strong>Plan not created.</strong> ' + escapeHtml(response.message || 'Validation failed.') + blocked);
        }).always(function () {
            $('#replen-submit-plan').prop('disabled', false).html('<i class="fa fa-lock"></i> Validate Locations &amp; Create');
        });
    }

    function planById(planId) {
        var match = null;
        $.each(savedPlans, function (index, plan) {
            if (String(plan.plan_id) === String(planId)) { match = plan; return false; }
        });
        return match;
    }

    function renderPlans() {
        var query = $.trim($('#replen-plan-search').val()).toLowerCase();
        var status = $('#replen-plan-status-filter').val();
        var mineOnly = $('#replen-my-plans').is(':checked');
        var visiblePlans = $.grep(savedPlans, function (plan) {
            var searchText = [plan.plan_id, plan.plan_name, plan.owner_user, plan.notes].join(' ').toLowerCase();
            return (!query || searchText.indexOf(query) !== -1)
                && (!status || plan.plan_status === status)
                && (!mineOnly || String(plan.owner_user).toUpperCase() === currentUser);
        });
        var html = '';
        $.each(visiblePlans, function (index, plan) {
            var percent = parseInt(plan.action_count, 10) > 0 ? (parseInt(plan.completed_actions, 10) / parseInt(plan.action_count, 10)) * 100 : 0;
            var planNote = String(plan.notes || '');
            if (planNote.length > 180) { planNote = planNote.substring(0, 177) + '...'; }
            var actions = '<button class="btn btn-primary btn-sm replen-open-plan" data-plan-id="' + plan.plan_id + '">Open Plan</button>';
            if (plan.can_manage) {
                actions += '<button class="btn btn-default btn-sm replen-edit-plan" data-plan-id="' + plan.plan_id + '" title="Edit plan details"><i class="fa fa-pencil"></i> Edit</button>';
            }
            if (plan.can_delete) {
                actions += '<button class="btn btn-link btn-sm text-danger replen-delete-plan" data-plan-id="' + plan.plan_id + '" title="Permanently delete this plan"><i class="fa fa-trash"></i> Delete</button>';
            }
            html += '<article class="replen-plan-card"><div class="replen-plan-card-main"><div>' + statusLabel(plan.plan_status) +
                '<h3>' + escapeHtml(plan.plan_name) + '</h3><p>Plan #' + escapeHtml(plan.plan_id) + ' &middot; ' + escapeHtml(plan.planned_date) + ' &middot; Owner ' + escapeHtml(plan.owner_user) + '</p></div>' +
                '<div class="replen-plan-card-actions">' + actions + '</div></div>' +
                (planNote ? '<p class="replen-plan-card-note">' + escapeHtml(planNote) + '</p>' : '') +
                '<div class="replen-plan-card-metrics"><div><strong>' + number(plan.action_count, 0) + '</strong><span>Actions</span></div><div><strong>' + number(plan.completed_actions, 0) + '</strong><span>Complete</span></div><div><strong>' + number(plan.blocked_actions, 0) + '</strong><span>Blocked</span></div><div><strong>' + number(plan.expected_annual_labor_hours, 1) + '</strong><span>Labor hrs/year</span></div></div>' +
                '<div class="replen-plan-progress"><span style="width:' + Math.min(100, percent) + '%"></span></div></article>';
        });
        if (!html && savedPlans.length === 0) {
            html = '<div class="replen-empty-state"><i class="fa fa-clipboard"></i><h3>No saved plans yet</h3><p>Select ready opportunities and create the first tracked floor plan.</p></div>';
        } else if (!html) {
            html = '<div class="replen-empty-state"><i class="fa fa-search"></i><h3>No plans match these filters</h3><p>Clear the search, status, or ownership filter to see more plans.</p><button id="replen-clear-plan-filters" class="btn btn-default btn-sm">Clear filters</button></div>';
        }
        $('#replen-plan-results').text('Showing ' + visiblePlans.length + ' of ' + savedPlans.length);
        $('#replen-plans-list').html(html);
    }

    function loadPlans() {
        $('#replen-plans-loading').removeClass('hidden');
        $.getJSON(readEndpoint, {view: 'plans'}).done(function (payload) {
            savedPlans = payload.plans || [];
            csrfToken = payload.csrf_token || csrfToken;
            currentUser = String(payload.current_user || currentUser).toUpperCase();
            if (payload.users) { populateUsers(payload.users); }
            $('#replen-plan-count').text(savedPlans.length);
            renderPlans();
            $('#replen-plans-loading').addClass('hidden');
        }).fail(function (xhr) {
            $('#replen-plans-loading').addClass('hidden');
            $('#replen-plan-results').empty();
            $('#replen-plans-list').html('<div class="alert alert-danger">' + escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'Saved plans could not be loaded.') + '</div>');
        });
    }

    function planControlButtons(row, plan) {
        if (row.item_status === 'COMPLETED' || row.item_status === 'SKIPPED'
            || plan.plan.plan_status === 'COMPLETED' || plan.plan.plan_status === 'CANCELLED') { return ''; }
        var canManage = plan.permissions.can_manage_actions;
        var canUpdate = canManage || String(row.assigned_to || '').toUpperCase() === currentUser;
        if (!canUpdate) { return '<span class="text-muted">Assigned work</span>'; }
        var html = '<div class="btn-group btn-group-xs replen-action-buttons">';
        if (row.item_status === 'READY' || row.item_status === 'BLOCKED') { html += '<button class="btn btn-default" data-action="start_action" title="Start"><i class="fa fa-play"></i></button>'; }
        if (row.item_status === 'READY' || row.item_status === 'IN_PROGRESS' || row.item_status === 'BLOCKED') { html += '<button class="btn btn-success" data-action="complete_action" title="Complete"><i class="fa fa-check"></i></button>'; }
        if (row.item_status === 'READY' || row.item_status === 'IN_PROGRESS') { html += '<button class="btn btn-warning" data-action="block_action" title="Block"><i class="fa fa-pause"></i></button>'; }
        html += '<button class="btn btn-danger" data-action="skip_action" title="Skip"><i class="fa fa-forward"></i></button>';
        if (canManage) { html += '<button class="btn btn-info" data-action="assign_action" title="Assign"><i class="fa fa-user"></i></button>'; }
        return html + '</div>';
    }

    function renderPlanDetail(payload) {
        currentPlanPayload = payload;
        var plan = payload.plan;
        $('#replen-detail-title').text(plan.plan_name);
        $('#replen-detail-subtitle').text('Plan #' + plan.plan_id + ' · ' + plan.planned_date + ' · Owner ' + plan.owner_user + ' · Model ' + formatDate(plan.model_as_of));
        $('#replen-detail-summary').html('<div><span>Status</span><strong>' + statusLabel(plan.plan_status) + '</strong></div>' +
            '<div><span>Actions</span><strong>' + number(plan.action_count, 0) + '</strong></div>' +
            '<div><span>Replens avoided/day</span><strong>' + number(plan.expected_replens_day, 2) + '</strong></div>' +
            '<div><span>Walk change m/day</span><strong>' + signedNumber(plan.expected_walk_meters_day, 1) + '</strong></div>' +
            '<div><span>Net labor min/day</span><strong>' + signedNumber(plan.expected_net_minutes_day, 1) + '</strong></div>' +
            '<div><span>Annual labor hours</span><strong>' + number(plan.expected_annual_labor_hours, 1) + '</strong></div>');
        $('#replen-detail-notes').toggleClass('hidden', !plan.notes).text(plan.notes ? 'Plan notes: ' + plan.notes : '');
        var ownerTools = '';
        if (payload.permissions.can_manage) {
            ownerTools += '<button class="btn btn-default btn-sm replen-edit-plan" data-plan-id="' + plan.plan_id + '"><i class="fa fa-pencil"></i> Edit Plan</button>';
            if (plan.plan_status !== 'COMPLETED' && plan.plan_status !== 'CANCELLED') {
                ownerTools += '<button id="replen-cancel-plan" class="btn btn-warning btn-sm" data-plan-id="' + plan.plan_id + '"><i class="fa fa-times"></i> Cancel Plan</button>';
            }
            if (payload.permissions.can_delete) {
                ownerTools += '<button class="btn btn-danger btn-sm replen-delete-plan" data-plan-id="' + plan.plan_id + '"><i class="fa fa-trash"></i> Delete Plan</button>';
            } else if (payload.permissions.delete_reason) {
                ownerTools += '<span class="replen-retention-note"><i class="fa fa-lock"></i> Execution history retained</span>';
            }
        }
        $('#replen-detail-owner-tools').html(ownerTools);

        var sequenceById = {};
        $.each(payload.items, function (index, row) { sequenceById[row.plan_item_id] = row.sequence_no; });
        if ($.fn.dataTable.isDataTable('#replen-plan-items-table')) { $('#replen-plan-items-table').DataTable().destroy(); }
        planItemsTable = $('#replen-plan-items-table').DataTable({
            data: payload.items || [], destroy: true, responsive: true, paging: false, ordering: false,
            dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>tr",
            buttons: [
                {extend: 'excelHtml5', text: 'Excel Floor Sheet', title: 'Replenishment_Plan_' + plan.plan_id, exportOptions: {columns: ':not(:last-child)'}},
                {extend: 'csvHtml5', text: 'CSV', title: 'Replenishment_Plan_' + plan.plan_id, exportOptions: {columns: ':not(:last-child)'}},
                {extend: 'pdfHtml5', text: 'PDF', title: 'Replenishment Plan #' + plan.plan_id, orientation: 'landscape', pageSize: 'A3', exportOptions: {columns: ':not(:last-child)'}},
                {extend: 'print', text: 'Print', title: 'Replenishment Plan #' + plan.plan_id, exportOptions: {columns: ':not(:last-child)'}}
            ],
            columns: [
                {data: 'sequence_no'},
                {data: 'item_status', render: function (data, type) { return type === 'display' ? statusLabel(data) : data; }},
                {data: 'action_type', render: function (data, type) { return type === 'display' ? actionLabel(data) : data; }},
                {data: 'item_number', render: function (data, type, row) { var value = data + ' / ' + row.package_type + ' / ' + row.package_unit; return type === 'display' ? '<strong>' + escapeHtml(data) + '</strong><small>' + escapeHtml(row.package_type + ' / ' + row.package_unit) + '</small>' : value; }},
                {data: 'from_location'}, {data: 'target_location'},
                {data: null, render: function (data, type, row) { var value = row.suggested_max + ' / ' + row.suggested_min; return type === 'display' ? escapeHtml(value) : value; }},
                {data: 'replen_reduction_day', render: function (data, type) { var value = parseFloat(data) * parseInt(plan.operating_days, 10); return type === 'display' ? number(value, 0) : value; }},
                {data: 'annual_labor_hours', render: function (data, type) { return type === 'display' ? number(data, 1) : data; }},
                {data: 'assigned_to'},
                {data: 'dependency_item_id', defaultContent: '', render: function (data, type) { var value = data ? 'Complete #' + sequenceById[data] + ' first' : ''; return type === 'display' ? escapeHtml(value) : value; }},
                {data: null, orderable: false, searchable: false, render: function (data, type, row) { return type === 'display' ? planControlButtons(row, payload) : ''; }}
            ],
            createdRow: function (row, data) { $(row).attr('data-plan-item-id', data.plan_item_id).toggleClass('replen-row-blocked', data.item_status === 'BLOCKED'); }
        });
        var events = '';
        $.each(payload.events || [], function (index, event) {
            events += '<div class="replen-event"><i class="fa fa-circle"></i><div><strong>' + escapeHtml(event.event_type.replace(/_/g, ' ')) + '</strong><span>' + escapeHtml(event.event_user) + ' · ' + escapeHtml(formatDate(event.created_at)) + '</span>' + (event.event_note ? '<p>' + escapeHtml(event.event_note) + '</p>' : '') + '</div></div>';
        });
        $('#replen-event-list').html(events || '<p class="text-muted">No activity recorded.</p>');
        hideMessage('#replen-detail-message');
        $('#replen-detail-modal').modal('show');
    }

    function openPlan(planId) {
        $.getJSON(readEndpoint, {view: 'plan_detail', plan_id: planId}).done(renderPlanDetail).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'The plan could not be opened.');
        });
    }

    function updateEditStatusUi() {
        if (!currentEditingPlan) { return; }
        var originalStatus = currentEditingPlan.plan.plan_status;
        var selectedStatus = $('#replen-edit-plan-status').val();
        var statusChanged = selectedStatus !== originalStatus;
        var terminal = selectedStatus === 'COMPLETED' || selectedStatus === 'CANCELLED';
        var originalTerminal = originalStatus === 'COMPLETED' || originalStatus === 'CANCELLED';
        var unfinishedCount = $.grep(currentEditingPlan.items || [], function (item) {
            return item.item_status === 'READY' || item.item_status === 'IN_PROGRESS' || item.item_status === 'BLOCKED';
        }).length;
        var help = 'Status changes are manual and recorded in Plan Activity.';
        if (statusChanged && terminal) {
            help = 'Closing this plan releases its outstanding location reservations. The action rows remain available if you reopen it later.';
        } else if (statusChanged && originalTerminal && !terminal) {
            help = 'Reopening reclaims target reservations for unfinished actions. The save is blocked only if another saved plan now owns a target.';
        } else if (selectedStatus === 'IN_PROGRESS') {
            help = 'Use In Progress when floor work has started, even if the morning data has not refreshed yet.';
        } else if (selectedStatus === 'READY') {
            help = 'Use Ready when the plan is approved and waiting for floor work.';
        }
        $('#replen-edit-status-help').toggleClass('is-terminal', terminal).text(help);
        $('#replen-edit-status-note-field').toggleClass('hidden', !statusChanged);
        if (!statusChanged) { $('#replen-edit-status-note').val(''); }
        var canReassign = !terminal && unfinishedCount > 0;
        $('#replen-edit-reassign').prop('disabled', !canReassign);
        if (!canReassign) { $('#replen-edit-reassign').prop('checked', false); }
        $('#replen-edit-plan-assignee').prop('disabled', !canReassign || !$('#replen-edit-reassign').is(':checked'));
        $('#replen-edit-assignment-section').toggleClass('is-disabled', !canReassign);
    }

    function openEditPlan(planId) {
        $.getJSON(readEndpoint, {view: 'plan_detail', plan_id: planId}).done(function (payload) {
            if (!payload.permissions.can_manage) {
                alert('Only the plan owner can edit this plan.');
                return;
            }
            currentEditingPlan = payload;
            var plan = payload.plan;
            var unfinished = $.grep(payload.items || [], function (item) {
                return item.item_status === 'READY' || item.item_status === 'IN_PROGRESS' || item.item_status === 'BLOCKED';
            });
            $('#replen-edit-plan-name').val(plan.plan_name);
            $('#replen-edit-plan-date').val(plan.planned_date);
            $('#replen-edit-plan-status').val(plan.plan_status);
            $('#replen-edit-status-note').val('');
            $('#replen-edit-plan-notes').val(plan.notes || '');
            $('#replen-edit-reassign').prop('checked', false);
            $('#replen-edit-plan-assignee').prop('disabled', true);
            if (unfinished.length > 0) { $('#replen-edit-plan-assignee').val(unfinished[0].assigned_to || currentUser); }
            updateEditStatusUi();
            hideMessage('#replen-edit-plan-message');
            reopenPlanAfterEdit = $('#replen-detail-modal').hasClass('in');
            if (reopenPlanAfterEdit) {
                $('#replen-detail-modal').one('hidden.bs.modal', function () { $('#replen-edit-plan-modal').modal('show'); }).modal('hide');
            } else {
                $('#replen-edit-plan-modal').modal('show');
            }
        }).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'The plan could not be loaded for editing.');
        });
    }

    function submitPlanEdit() {
        if (!currentEditingPlan) { return; }
        var statusChanged = $('#replen-edit-plan-status').val() !== currentEditingPlan.plan.plan_status;
        var statusNote = $.trim($('#replen-edit-status-note').val());
        if (statusChanged && !statusNote) {
            showMessage('#replen-edit-plan-message', 'danger', 'Enter a reason for the manual status change.');
            return;
        }
        var payload = {
            action: 'update_plan',
            plan_id: currentEditingPlan.plan.plan_id,
            row_version: currentEditingPlan.plan.row_version,
            plan_name: $.trim($('#replen-edit-plan-name').val()),
            planned_date: $('#replen-edit-plan-date').val(),
            plan_status: $('#replen-edit-plan-status').val(),
            status_note: statusNote,
            notes: $.trim($('#replen-edit-plan-notes').val()),
            reassign_remaining: $('#replen-edit-reassign').is(':checked'),
            assigned_to: $('#replen-edit-plan-assignee').val()
        };
        $('#replen-edit-plan-submit').prop('disabled', true).html('<i class="fa fa-circle-o-notch fa-spin"></i> Saving');
        postAction(payload).done(function (response) {
            $('#replen-edit-plan-modal').modal('hide');
            loadPlans();
        }).fail(function (xhr) {
            showMessage('#replen-edit-plan-message', 'danger', escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'The plan could not be updated.'));
        }).always(function () {
            $('#replen-edit-plan-submit').prop('disabled', false).html('<i class="fa fa-save"></i> Save Changes');
        });
    }

    function deletePlan(planId) {
        var detailIsOpen = $('#replen-detail-modal').hasClass('in');
        var plan = detailIsOpen && currentPlanPayload && String(currentPlanPayload.plan.plan_id) === String(planId)
            ? currentPlanPayload.plan : planById(planId);
        if (!plan) { alert('Refresh the saved plans before deleting.'); return; }
        if (!window.confirm('Permanently delete "' + plan.plan_name + '"? This removes every action, reservation, and activity-history entry and cannot be undone.')) { return; }
        postAction({action: 'delete_plan', plan_id: plan.plan_id, row_version: plan.row_version}).done(function () {
            if ($('#replen-detail-modal').hasClass('in')) { $('#replen-detail-modal').modal('hide'); }
            currentPlanPayload = null;
            loadPlans();
        }).fail(function (xhr) {
            var message = escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'The plan could not be deleted.');
            if ($('#replen-detail-modal').hasClass('in')) { showMessage('#replen-detail-message', 'danger', message); } else { alert($('<div>').html(message).text()); }
        });
    }

    function planItemById(id) {
        var match = null;
        $.each((currentPlanPayload && currentPlanPayload.items) || [], function (index, row) { if (String(row.plan_item_id) === String(id)) { match = row; return false; } });
        return match;
    }

    function openActionModal(action, row) {
        pendingAction = {action: action, row: row};
        var titles = {assign_action: 'Reassign Floor Action', start_action: 'Start Floor Action', complete_action: 'Complete Floor Action', block_action: 'Block Floor Action', skip_action: 'Skip Floor Action'};
        $('#replen-action-title').text(titles[action] || 'Update Action');
        $('#replen-action-context').html('<strong>Item ' + escapeHtml(row.item_number) + '</strong><span>' + escapeHtml(row.from_location) + ' → ' + escapeHtml(row.target_location) + '</span>');
        $('#replen-assignee-field, #replen-completion-fields, #replen-note-field').addClass('hidden');
        $('#replen-action-note').val('');
        if (action === 'assign_action') { $('#replen-assignee-field').removeClass('hidden'); $('#replen-action-assignee').val(row.assigned_to); }
        if (action === 'complete_action') {
            $('#replen-completion-fields, #replen-note-field').removeClass('hidden');
            $('#replen-actual-location').val(row.target_location); $('#replen-actual-max').val(row.suggested_max); $('#replen-actual-min').val(row.suggested_min);
        }
        if (action === 'block_action' || action === 'skip_action') { $('#replen-note-field').removeClass('hidden'); }
        var buttonLabels = {assign_action: 'Assign', start_action: 'Start Action', complete_action: 'Complete Action', block_action: 'Block Action', skip_action: 'Skip Action'};
        $('#replen-action-submit').text(buttonLabels[action] || 'Save').toggleClass('btn-danger', action === 'skip_action').toggleClass('btn-success', action === 'complete_action').toggleClass('btn-primary', action !== 'skip_action' && action !== 'complete_action');
        hideMessage('#replen-action-message');
        $('#replen-action-modal').modal('show');
    }

    function submitItemAction() {
        if (!pendingAction) { return; }
        var payload = {action: pendingAction.action, plan_item_id: pendingAction.row.plan_item_id};
        if (pendingAction.action === 'assign_action') { payload.assigned_to = $('#replen-action-assignee').val(); }
        if (pendingAction.action === 'complete_action') { payload.actual_location = $.trim($('#replen-actual-location').val()); payload.actual_max = $('#replen-actual-max').val(); payload.actual_min = $('#replen-actual-min').val(); payload.note = $.trim($('#replen-action-note').val()); }
        if (pendingAction.action === 'block_action' || pendingAction.action === 'skip_action') { payload.note = $.trim($('#replen-action-note').val()); }
        $('#replen-action-submit').prop('disabled', true);
        postAction(payload).done(function () {
            $('#replen-action-modal').modal('hide');
            openPlan(currentPlanPayload.plan.plan_id);
            loadPlans();
        }).fail(function (xhr) {
            showMessage('#replen-action-message', 'danger', escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'The action could not be updated.'));
        }).always(function () { $('#replen-action-submit').prop('disabled', false); });
    }

    $(document).on('change', '.replen-select-row', function () {
        var key = $(this).attr('data-key');
        if ($(this).is(':checked')) { selectedKeys[key] = true; } else { delete selectedKeys[key]; }
        updateSelectionUi();
    });

    $(document).on('click', '[data-replen-select-top]', function () {
        if (!opportunityTable) { return; }
        var limit = parseInt($(this).attr('data-replen-select-top'), 10);
        var added = 0;
        opportunityTable.rows({search: 'applied', order: 'applied'}).every(function () {
            var row = this.data();
            if (added < limit && row.planning_eligible && (row.action_type === 'DIRECT_RESLOT' || row.action_type === 'MAX_MIN_ADJUSTMENT')) { selectedKeys[row.key] = true; added++; }
        });
        updateSelectionUi();
    });

    $('#replen-clear-selection').on('click', function () { selectedKeys = {}; updateSelectionUi(); });
    $(document).on('click', '[data-replen-queue]', function () {
        if (!opportunityTable) { return; }
        $('[data-replen-queue]').removeClass('active');
        $(this).addClass('active');
        $('#replen-action-filter').val('');
        opportunityTable.column(2).search($(this).attr('data-filter'), true, false).draw();
    });
    $('#replen-action-filter').on('change', function () {
        if (opportunityTable) {
            $('[data-replen-queue]').removeClass('active');
            $('[data-replen-queue="all"]').addClass('active');
            opportunityTable.column(2).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
        }
    });
    $('#replen-level-filter').on('change', function () { if (opportunityTable) { opportunityTable.column(3).search(this.value ? '^' + this.value + '$' : '', true, false).draw(); } });
    $('#replen-confidence-filter').on('change', function () { if (opportunityTable) { opportunityTable.column(15).search(this.value, false, false).draw(); } });
    $('#replen-plan-modal').on('show.bs.modal', renderPlanPreview);
    $('#replen-submit-plan').on('click', submitPlan);
    $('#replen-refresh-plans').on('click', loadPlans);
    $('#replen-plan-search').on('input', renderPlans);
    $('#replen-plan-status-filter, #replen-my-plans').on('change', renderPlans);
    $(document).on('click', '#replen-clear-plan-filters', function () {
        $('#replen-plan-search, #replen-plan-status-filter').val('');
        $('#replen-my-plans').prop('checked', false);
        renderPlans();
    });
    $(document).on('click', '.replen-open-plan', function () { openPlan($(this).attr('data-plan-id')); });
    $(document).on('click', '.replen-edit-plan', function () { openEditPlan($(this).attr('data-plan-id')); });
    $(document).on('click', '.replen-delete-plan', function () { deletePlan($(this).attr('data-plan-id')); });
    $('#replen-edit-plan-status').on('change', updateEditStatusUi);
    $('#replen-edit-reassign').on('change', function () { $('#replen-edit-plan-assignee').prop('disabled', !this.checked); });
    $('#replen-edit-plan-submit').on('click', submitPlanEdit);
    $('#replen-edit-plan-modal').on('hidden.bs.modal', function () {
        var planId = currentEditingPlan ? currentEditingPlan.plan.plan_id : null;
        var shouldReopen = reopenPlanAfterEdit;
        currentEditingPlan = null;
        reopenPlanAfterEdit = false;
        if (shouldReopen && planId) { openPlan(planId); }
    });
    $(document).on('click', '#replen-plan-items-table [data-action]', function () { var row = planItemById($(this).closest('tr').attr('data-plan-item-id')); if (row) { openActionModal($(this).attr('data-action'), row); } });
    $('#replen-action-submit').on('click', submitItemAction);
    $(document).on('click', '#replen-cancel-plan', function () {
        if (!window.confirm('Cancel this entire plan and release all remaining location reservations?')) { return; }
        postAction({action: 'cancel_plan', plan_id: $(this).attr('data-plan-id'), note: 'Cancelled by plan owner.'}).done(function () { $('#replen-detail-modal').modal('hide'); currentPlanPayload = null; loadPlans(); }).fail(function (xhr) { showMessage('#replen-detail-message', 'danger', escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'The plan could not be cancelled.')); });
    });

    $(function () {
        if ($('#replen-opportunity-table').length) { loadOpportunities(); loadPlans(); }
        if ($('#replen-dashboard-summary').length) {
            $.getJSON(readEndpoint, {view: 'summary'}).done(renderDashboard).fail(function (xhr) { $('#replen-dashboard-loading').html('<span class="text-danger">' + escapeHtml((xhr.responseJSON && xhr.responseJSON.message) || 'Summary unavailable.') + '</span>'); });
        }
    });
}(jQuery));
