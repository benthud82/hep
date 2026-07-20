(function ($) {
    'use strict';

    var endpoint = 'globaldata/conveyor_reslot_data.php';
    var reportPayload = null;
    var reportTable = null;
    var floorPlannerInitialized = false;
    var currentPlan = null;
    var currentPlanStep = 1;

    var floorColumnDefinitions = [
        {key: 'sequence', label: 'Sequence', required: true},
        {key: 'action', label: 'Action', required: true},
        {key: 'level', label: 'Level', required: true},
        {key: 'item_number', label: 'Item', required: true},
        {key: 'current_location', label: 'Current Location', required: true},
        {key: 'avg_daily_picks', label: 'APD'},
        {key: 'suggested_tier', label: 'Suggested Tier'},
        {key: 'suggested_grid_depth', label: 'Suggested Grid / Depth'},
        {key: 'suggested_walk_distance', label: 'Suggested Walk (m/pick)'},
        {key: 'eligible_bands', label: 'Eligible Bands'},
        {key: 'reason', label: 'Why'},
        {key: 'actual_location', label: 'Actual Location'},
        {key: 'completed_by', label: 'Completed By'},
        {key: 'completed_date', label: 'Completed Date'},
        {key: 'package_unit', label: 'Package Unit'},
        {key: 'package_type', label: 'Package Type'},
        {key: 'current_band', label: 'Current Band'},
        {key: 'current_tier', label: 'Current Tier'},
        {key: 'current_grid_depth', label: 'Current Grid / Depth'},
        {key: 'current_walk_distance', label: 'Current Walk (m/pick)'},
        {key: 'daily_walk_reduction', label: 'Walk Reduction (m/day)'},
        {key: 'daily_replen_reduction', label: 'Replen Change / Day'}
    ];

    var floorPresetColumns = [
        'sequence', 'action', 'level', 'item_number', 'current_location',
        'avg_daily_picks', 'suggested_tier', 'suggested_grid_depth',
        'suggested_walk_distance', 'eligible_bands', 'reason',
        'actual_location', 'completed_by', 'completed_date'
    ];

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : value).html();
    }

    function formatNumber(value, decimals) {
        var numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            numericValue = 0;
        }
        return numericValue.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatSignedNumber(value, decimals) {
        var numericValue = parseFloat(value);
        if (isNaN(numericValue)) {
            numericValue = 0;
        }
        return (numericValue > 0 ? '+' : '') + formatNumber(numericValue, decimals);
    }

    function numericValue(value, decimals) {
        var parsedValue = parseFloat(value);
        if (isNaN(parsedValue)) {
            parsedValue = 0;
        }
        return Number(parsedValue.toFixed(decimals));
    }

    function formatModelDate(value) {
        if (!value) {
            return 'Date unavailable';
        }

        var parts = value.split('-');
        if (parts.length !== 3) {
            return value;
        }

        return parts[1] + '/' + parts[2] + '/' + parts[0];
    }

    function localDateStamp() {
        var date = new Date();
        var month = String(date.getMonth() + 1);
        var day = String(date.getDate());

        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }

        return date.getFullYear() + '-' + month + '-' + day;
    }

    function renderSummary(payload) {
        var $root = $('#conveyor-reslot-summary');
        if (!$root.length) {
            return;
        }

        $.each(payload.totals, function (key, value) {
            $root.find('[data-summary-total="' + key + '"]').text(formatNumber(value, 0));
        });

        var capacity = parseFloat(payload.totals.capacity) || 0;
        var plannedOpen = parseFloat(payload.totals.planned_open) || 0;
        var reservePercent = capacity > 0 ? (plannedOpen / capacity) * 100 : 0;
        $root.find('[data-summary-reserve]').text(formatNumber(reservePercent, 1) + '%');

        var impact = payload.impact || {};
        var impactFormats = {
            current_walk_meters_day: {decimals: 1, signed: false},
            implied_walk_meters_day: {decimals: 1, signed: false},
            daily_walk_reduction_meters: {decimals: 1, signed: true},
            current_replen_day: {decimals: 3, signed: false},
            implied_replen_day: {decimals: 3, signed: false},
            daily_replen_reduction: {decimals: 2, signed: true},
            annual_walk_reduction_km: {decimals: 1, signed: true},
            annual_replen_reduction: {decimals: 0, signed: true}
        };
        $.each(impactFormats, function (key, options) {
            var value = parseFloat(impact[key]);
            if (isNaN(value)) {
                value = 0;
            }
            $root.find('[data-summary-impact="' + key + '"]')
                .text(options.signed ? formatSignedNumber(value, options.decimals) : formatNumber(value, options.decimals))
                .toggleClass('is-negative', options.signed && value < 0);
        });

        var levelHtml = '';
        $.each(payload.summary, function (index, level) {
            if ($root.hasClass('conveyor-summary-dashboard')) {
                var utilization = Math.max(0, Math.min(100, parseFloat(level.utilization_pct) || 0));
                levelHtml += '<div class="col-md-4">' +
                    '<article class="conveyor-dashboard-level-card">' +
                        '<div class="conveyor-dashboard-level-top">' +
                            '<div class="conveyor-dashboard-level-badge">' + escapeHtml(level.level) + '</div>' +
                            '<div class="conveyor-dashboard-level-title"><span>Level ' + escapeHtml(level.level) + '</span><strong>' + formatNumber(level.recommended, 0) + ' of ' + formatNumber(level.capacity, 0) + ' positions</strong></div>' +
                            '<div class="conveyor-dashboard-level-utilization"><strong>' + formatNumber(utilization, 1) + '%</strong><span>target utilized</span></div>' +
                        '</div>' +
                        '<div class="conveyor-dashboard-level-progress" aria-label="Level ' + escapeHtml(level.level) + ' target utilization ' + formatNumber(utilization, 1) + ' percent"><span style="width:' + utilization + '%"></span></div>' +
                        '<div class="conveyor-dashboard-level-metrics">' +
                            '<div class="metric-in"><strong>' + formatNumber(level.move_in, 0) + '</strong><span>Move In</span></div>' +
                            '<div class="metric-out"><strong>' + formatNumber(level.move_out, 0) + '</strong><span>Move Out</span></div>' +
                            '<div><strong>' + formatNumber(level.planned_open, 0) + '</strong><span>Open After</span></div>' +
                        '</div>' +
                    '</article>' +
                '</div>';
            } else {
                levelHtml += '<div class="col-md-4">' +
                    '<div class="conveyor-level-card">' +
                        '<div class="conveyor-level-name">Level <strong>' + escapeHtml(level.level) + '</strong></div>' +
                        '<div class="conveyor-level-metrics">' +
                            '<div class="conveyor-level-metric metric-in"><strong>' + formatNumber(level.move_in, 0) + '</strong><span>Move In</span></div>' +
                            '<div class="conveyor-level-metric metric-out"><strong>' + formatNumber(level.move_out, 0) + '</strong><span>Move Out</span></div>' +
                            '<div class="conveyor-level-metric"><strong>' + formatNumber(level.planned_open, 0) + '</strong><span>Open</span></div>' +
                            '<div class="conveyor-level-metric"><strong>' + formatNumber(level.recommended, 0) + '/' + formatNumber(level.capacity, 0) + '</strong><span>Target</span></div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }
        });

        $root.find('.conveyor-summary-levels').html(levelHtml);
        $('.conveyor-model-date').text(formatModelDate(payload.model_as_of));
    }

    function showSummaryError(message) {
        var $root = $('#conveyor-reslot-summary');
        if (!$root.length) {
            return;
        }
        $root.html('<div class="alert alert-warning" role="alert"><strong>Recommendations unavailable.</strong> ' + escapeHtml(message) + '</div>');
    }

    function actionBadge(action) {
        if (action === 'MOVE_IN') {
            return '<span class="conveyor-action-badge conveyor-action-in"><i class="fa fa-sign-in" aria-hidden="true"></i> Move In</span>';
        }
        return '<span class="conveyor-action-badge conveyor-action-out"><i class="fa fa-sign-out" aria-hidden="true"></i> Move Out</span>';
    }

    function deltaBadge(value) {
        var numericValue = parseFloat(value);
        var className = 'conveyor-delta-neutral';
        var prefix = '';

        if (numericValue > 0) {
            className = 'conveyor-delta-positive';
            prefix = '+';
        } else if (numericValue < 0) {
            className = 'conveyor-delta-negative';
        }

        return '<span class="conveyor-delta ' + className + '">' + prefix + formatNumber(numericValue, 1) + '</span>';
    }

    function levelAvailability(payload) {
        var levels = {};

        $.each(payload.summary || [], function (index, level) {
            var capacity = parseInt(level.capacity, 10) || 0;
            var recommended = parseInt(level.recommended, 10) || 0;
            var moveIn = parseInt(level.move_in, 10) || 0;
            var moveOut = parseInt(level.move_out, 10) || 0;
            var occupied = recommended - moveIn + moveOut;

            levels[level.level] = {
                level: level.level,
                capacity: capacity,
                currentOccupied: Math.max(0, occupied),
                openNow: Math.max(0, capacity - occupied),
                availableMoveIn: moveIn,
                availableMoveOut: moveOut
            };
        });

        return levels;
    }

    function selectedLevels() {
        var levels = [];
        $('.conveyor-plan-level:checked').each(function () {
            levels.push($(this).val());
        });
        levels.sort();
        return levels;
    }

    function maxAvailableActions(rows, levels) {
        var allowed = {};
        var count = 0;

        $.each(levels, function (index, level) {
            allowed[level] = true;
        });
        $.each(rows || [], function (index, row) {
            if (allowed[row.level]) {
                count += 1;
            }
        });

        return count;
    }

    function rowPriority(row) {
        return parseInt(row.priority, 10) || 999999;
    }

    function sortByPriority(left, right) {
        var priorityDifference = rowPriority(left) - rowPriority(right);
        if (priorityDifference !== 0) {
            return priorityDifference;
        }
        return String(left.item_number).localeCompare(String(right.item_number));
    }

    function chooseBalancedLevel(levels, state, queueName, selectedName, originalName, canUse) {
        var candidates = [];

        $.each(levels, function (index, level) {
            var levelState = state[level];
            if (!levelState || !levelState[queueName].length || (canUse && !canUse(levelState))) {
                return;
            }

            candidates.push({
                level: level,
                ratio: levelState[selectedName] / Math.max(1, levelState[originalName]),
                priority: rowPriority(levelState[queueName][0])
            });
        });

        candidates.sort(function (left, right) {
            if (left.ratio !== right.ratio) {
                return left.ratio - right.ratio;
            }
            if (left.priority !== right.priority) {
                return left.priority - right.priority;
            }
            return left.level.localeCompare(right.level);
        });

        return candidates.length ? candidates[0].level : null;
    }

    function buildFloorPlan(rows, summary, requestedMoves, levels) {
        var availability = levelAvailability({summary: summary});
        var state = {};
        var selectedRows = [];
        var remaining = requestedMoves;

        $.each(levels, function (index, level) {
            state[level] = {
                moveInQueue: [],
                moveOutQueue: [],
                selectedMoveIn: 0,
                selectedMoveOut: 0,
                originalMoveIn: 0,
                originalMoveOut: 0,
                openRemaining: availability[level] ? availability[level].openNow : 0
            };
        });

        $.each(rows || [], function (index, row) {
            if (!state[row.level]) {
                return;
            }
            if (row.action === 'MOVE_IN') {
                state[row.level].moveInQueue.push(row);
            } else if (row.action === 'MOVE_OUT') {
                state[row.level].moveOutQueue.push(row);
            }
        });

        $.each(levels, function (index, level) {
            state[level].moveInQueue.sort(sortByPriority);
            state[level].moveOutQueue.sort(sortByPriority);
            state[level].originalMoveIn = state[level].moveInQueue.length;
            state[level].originalMoveOut = state[level].moveOutQueue.length;
        });

        while (remaining > 0) {
            var moveInLevel = chooseBalancedLevel(
                levels,
                state,
                'moveInQueue',
                'selectedMoveIn',
                'originalMoveIn',
                function (levelState) {
                    return levelState.openRemaining > 0 || (remaining >= 2 && levelState.moveOutQueue.length > 0);
                }
            );

            if (!moveInLevel) {
                break;
            }

            if (state[moveInLevel].openRemaining <= 0) {
                selectedRows.push(state[moveInLevel].moveOutQueue.shift());
                state[moveInLevel].selectedMoveOut += 1;
                state[moveInLevel].openRemaining += 1;
                remaining -= 1;
            }

            if (remaining <= 0) {
                break;
            }

            selectedRows.push(state[moveInLevel].moveInQueue.shift());
            state[moveInLevel].selectedMoveIn += 1;
            state[moveInLevel].openRemaining -= 1;
            remaining -= 1;
        }

        while (remaining > 0) {
            var moveOutLevel = chooseBalancedLevel(
                levels,
                state,
                'moveOutQueue',
                'selectedMoveOut',
                'originalMoveOut'
            );

            if (!moveOutLevel) {
                break;
            }

            selectedRows.push(state[moveOutLevel].moveOutQueue.shift());
            state[moveOutLevel].selectedMoveOut += 1;
            state[moveOutLevel].openRemaining += 1;
            remaining -= 1;
        }

        selectedRows.sort(function (left, right) {
            var leftAction = left.action === 'MOVE_OUT' ? 1 : 2;
            var rightAction = right.action === 'MOVE_OUT' ? 1 : 2;

            if (leftAction !== rightAction) {
                return leftAction - rightAction;
            }
            if (left.level !== right.level) {
                return left.level.localeCompare(right.level);
            }
            return sortByPriority(left, right);
        });

        var counts = {
            requested: requestedMoves,
            selected: selectedRows.length,
            moveIn: 0,
            moveOut: 0,
            levels: {}
        };

        $.each(levels, function (index, level) {
            var startOpen = availability[level] ? availability[level].openNow : 0;
            counts.levels[level] = {
                moveIn: state[level].selectedMoveIn,
                moveOut: state[level].selectedMoveOut,
                openNow: startOpen,
                openAfter: startOpen + state[level].selectedMoveOut - state[level].selectedMoveIn
            };
            counts.moveIn += state[level].selectedMoveIn;
            counts.moveOut += state[level].selectedMoveOut;
        });

        $.each(selectedRows, function (index, row) {
            row._planSequence = index + 1;
        });

        return {
            rows: selectedRows,
            counts: counts,
            levels: levels.slice(0),
            complete: selectedRows.length === requestedMoves
        };
    }

    function showPlanMessage(type, message) {
        $('#conveyor-plan-message')
            .removeClass('hidden alert-danger alert-warning alert-success alert-info')
            .addClass('alert-' + type)
            .html(message);
    }

    function clearPlanMessage() {
        $('#conveyor-plan-message').addClass('hidden').empty();
    }

    function selectedColumnDefinitions() {
        var selected = [];

        $.each(floorColumnDefinitions, function (index, definition) {
            var $input = $('[data-plan-column="' + definition.key + '"]');
            if ($input.prop('checked')) {
                selected.push(definition);
            }
        });

        return selected;
    }

    function updateColumnSelection() {
        var count = selectedColumnDefinitions().length;
        var format = $('input[name="conveyor-plan-format"]:checked').val();

        $('#conveyor-plan-column-count').text(count);
        $('#conveyor-plan-pdf-warning').toggleClass('hidden', !(format === 'pdf' && count > 14));
    }

    function applyColumnPreset(preset) {
        var selectedKeys = {};

        if (preset === 'full') {
            $.each(floorColumnDefinitions, function (index, definition) {
                selectedKeys[definition.key] = true;
            });
        } else if (preset === 'floor') {
            $.each(floorPresetColumns, function (index, key) {
                selectedKeys[key] = true;
            });
        } else {
            $.each(floorColumnDefinitions, function (index, definition) {
                if (definition.required) {
                    selectedKeys[definition.key] = true;
                }
            });
        }

        $('[data-plan-column]').each(function () {
            var key = $(this).data('plan-column');
            $(this).prop('checked', !!selectedKeys[key]);
        });

        $('[data-plan-preset]').removeClass('btn-primary').addClass('btn-default');
        $('[data-plan-preset="' + preset + '"]').removeClass('btn-default').addClass('btn-primary');
        updateColumnSelection();
    }

    function updateLevelAvailabilityLabels() {
        if (!reportPayload) {
            return;
        }

        var availability = levelAvailability(reportPayload);
        $.each(availability, function (level, values) {
            $('[data-plan-level-available="' + level + '"]').text(
                values.openNow + ' open now · ' +
                (values.availableMoveIn + values.availableMoveOut) + ' actions available'
            );
        });
    }

    function renderWorkloadSummary(plan) {
        if (!plan) {
            $('#conveyor-plan-workload-summary').html(
                '<div class="conveyor-plan-empty-summary"><i class="fa fa-calculator" aria-hidden="true"></i> Enter a valid workload to see the capacity-aware move mix.</div>'
            );
            return;
        }

        var html = '<div class="conveyor-plan-workload-totals">' +
            '<div><span>Total actions</span><strong>' + plan.counts.selected + '</strong></div>' +
            '<div class="plan-count-out"><span>Move out</span><strong>' + plan.counts.moveOut + '</strong></div>' +
            '<div class="plan-count-in"><span>Move in</span><strong>' + plan.counts.moveIn + '</strong></div>' +
        '</div>' +
        '<div class="table-responsive"><table class="table conveyor-plan-level-table">' +
            '<thead><tr><th>Level</th><th>Open Now</th><th>Move Out</th><th>Move In</th><th>Open After</th></tr></thead><tbody>';

        $.each(plan.levels, function (index, level) {
            var values = plan.counts.levels[level];
            html += '<tr>' +
                '<td><strong>Level ' + escapeHtml(level) + '</strong></td>' +
                '<td>' + values.openNow + '</td>' +
                '<td>' + values.moveOut + '</td>' +
                '<td>' + values.moveIn + '</td>' +
                '<td>' + values.openAfter + '</td>' +
            '</tr>';
        });

        html += '</tbody></table></div>';
        $('#conveyor-plan-workload-summary').html(html);
    }

    function calculatePlanSavings(plan) {
        var workdays = 253;
        var metersPerKilometer = 1000;
        var walkDaily = 0;
        var replenDaily = 0;

        $.each(plan && plan.rows ? plan.rows : [], function (index, row) {
            walkDaily += numericValue(row.daily_walk_reduction, 1);
            replenDaily += numericValue(row.daily_replen_reduction, 2);
        });

        return {
            walkDaily: numericValue(walkDaily, 1),
            replenDaily: numericValue(replenDaily, 2),
            annualWalkKilometers: numericValue((walkDaily * workdays) / metersPerKilometer, 1),
            annualReplenMoves: numericValue(replenDaily * workdays, 0)
        };
    }

    function savingsValueClass(value) {
        if (value > 0) {
            return 'is-positive';
        }
        if (value < 0) {
            return 'is-negative';
        }
        return 'is-neutral';
    }

    function renderPlanSavings(plan) {
        var $summary = $('#conveyor-plan-savings');
        var $values = $summary.find('[data-plan-savings]');

        $values.removeClass('is-positive is-negative is-neutral');

        if (!plan || !plan.complete) {
            $values.text('\u2014').addClass('is-neutral');
            $summary.find('[data-plan-savings-scope]').text('No valid plan selected');
            return;
        }

        var savings = calculatePlanSavings(plan);
        var metrics = {
            'walk-daily': {value: savings.walkDaily, decimals: 1},
            'replen-daily': {value: savings.replenDaily, decimals: 2},
            'walk-annual': {value: savings.annualWalkKilometers, decimals: 1},
            'replen-annual': {value: savings.annualReplenMoves, decimals: 0}
        };

        $.each(metrics, function (key, metric) {
            $summary.find('[data-plan-savings="' + key + '"]')
                .text(formatSignedNumber(metric.value, metric.decimals))
                .addClass(savingsValueClass(metric.value));
        });

        $summary.find('[data-plan-savings-scope]').text(
            plan.counts.selected + ' actions \u00b7 Levels ' + plan.levels.join(', ')
        );
    }

    function validateAndBuildPlan(showErrors) {
        if (!reportPayload) {
            if (showErrors) {
                showPlanMessage('danger', '<strong>Recommendations are still loading.</strong> Please wait and try again.');
            }
            renderPlanSavings(null);
            return null;
        }

        var levels = selectedLevels();
        var rawValue = $.trim($('#conveyor-plan-total').val());
        var requested = parseInt(rawValue, 10);
        var maxActions = maxAvailableActions(reportPayload.rows || [], levels);

        $('#conveyor-plan-total').attr('max', maxActions || 1);
        $('#conveyor-plan-max-moves').text(maxActions);

        if (!levels.length) {
            currentPlan = null;
            renderWorkloadSummary(null);
            renderPlanSavings(null);
            if (showErrors) {
                showPlanMessage('danger', '<strong>Select at least one level.</strong> Choose A, B, or C to build the plan.');
            }
            return null;
        }

        if (!/^\d+$/.test(rawValue) || isNaN(requested) || requested < 1) {
            currentPlan = null;
            renderWorkloadSummary(null);
            renderPlanSavings(null);
            if (showErrors) {
                showPlanMessage('danger', '<strong>Enter a whole number of moves.</strong> The minimum workload is 1.');
            }
            return null;
        }

        if (requested > maxActions) {
            currentPlan = null;
            renderWorkloadSummary(null);
            renderPlanSavings(null);
            if (showErrors) {
                showPlanMessage('danger', '<strong>That workload is larger than the available plan.</strong> Enter ' + maxActions + ' moves or fewer for the selected levels.');
            }
            return null;
        }

        currentPlan = buildFloorPlan(reportPayload.rows || [], reportPayload.summary || [], requested, levels);
        if (!currentPlan.complete) {
            renderWorkloadSummary(null);
            renderPlanSavings(null);
            if (showErrors) {
                showPlanMessage('danger', '<strong>A complete capacity-safe plan could not be created.</strong> Reduce the requested workload or select more levels.');
            }
            return null;
        }

        clearPlanMessage();
        renderWorkloadSummary(currentPlan);
        renderPlanSavings(currentPlan);
        return currentPlan;
    }

    function setPlanStep(step) {
        currentPlanStep = step;
        $('.conveyor-plan-step').addClass('hidden');
        $('.conveyor-plan-step[data-plan-step="' + step + '"]').removeClass('hidden');

        $('[data-plan-progress]').each(function () {
            var progressStep = parseInt($(this).data('plan-progress'), 10);
            $(this)
                .toggleClass('active', progressStep === step)
                .toggleClass('complete', progressStep < step);
        });

        $('#conveyor-plan-back').toggleClass('hidden', step === 1);
        $('#conveyor-plan-next').toggleClass('hidden', step === 4);
        $('#conveyor-plan-generate').toggleClass('hidden', step !== 4);

        var $heading = $('.conveyor-plan-step[data-plan-step="' + step + '"] h3');
        $heading.attr('tabindex', '-1').focus();
    }

    function formatLabel(format) {
        var labels = {pdf: 'PDF', xlsx: 'Excel (.xlsx)', csv: 'CSV', print: 'Print'};
        return labels[format] || format;
    }

    function planValue(row, key) {
        switch (key) {
            case 'sequence':
                return row._planSequence;
            case 'action':
                return row.action === 'MOVE_OUT' ? 'Move Out' : 'Move In';
            case 'level':
                return row.level;
            case 'item_number':
                return row.item_number;
            case 'current_location':
                return row.current_location || '';
            case 'avg_daily_picks':
                return numericValue(row.avg_daily_picks, 2);
            case 'suggested_tier':
                return row.suggested_tier || '';
            case 'suggested_grid_depth':
                return (row.suggested_grid || '') + ' / ' + (row.suggested_depth || '');
            case 'suggested_walk_distance':
                return numericValue(row.suggested_walk_distance, 0);
            case 'eligible_bands':
                return row.eligible_bands || 'Outside conveyor';
            case 'reason':
                return row.reason || '';
            case 'actual_location':
            case 'completed_by':
            case 'completed_date':
                return '';
            case 'package_unit':
                return row.package_unit || '';
            case 'package_type':
                return row.package_type || '';
            case 'current_band':
                return row.current_band || '';
            case 'current_tier':
                return row.current_tier || '';
            case 'current_grid_depth':
                return (row.current_grid || '') + ' / ' + (row.current_depth || '');
            case 'current_walk_distance':
                return numericValue(row.current_walk_distance, 0);
            case 'daily_walk_reduction':
                return numericValue(row.daily_walk_reduction, 1);
            case 'daily_replen_reduction':
                return numericValue(row.daily_replen_reduction, 2);
            default:
                return row[key] || '';
        }
    }

    function planDisplayValue(row, key) {
        var value = planValue(row, key);

        switch (key) {
            case 'avg_daily_picks':
                return formatNumber(value, 2);
            case 'suggested_walk_distance':
            case 'current_walk_distance':
                return formatNumber(value, 0);
            case 'daily_walk_reduction':
                return formatSignedNumber(value, 1);
            case 'daily_replen_reduction':
                return formatSignedNumber(value, 2);
            default:
                return value;
        }
    }

    function renderPlanReview() {
        var plan = validateAndBuildPlan(true);
        if (!plan) {
            setPlanStep(1);
            return false;
        }

        var format = $('input[name="conveyor-plan-format"]:checked').val();
        var columns = selectedColumnDefinitions();
        var user = $('#conveyor-plan-modal').attr('data-plan-user') || 'Current user';
        var generatedAt = new Date().toLocaleString();
        var summaryHtml = '<div class="row">' +
            '<div class="col-sm-3"><div class="conveyor-plan-review-card"><span>Total Actions</span><strong>' + plan.counts.selected + '</strong></div></div>' +
            '<div class="col-sm-3"><div class="conveyor-plan-review-card review-out"><span>Move Out</span><strong>' + plan.counts.moveOut + '</strong></div></div>' +
            '<div class="col-sm-3"><div class="conveyor-plan-review-card review-in"><span>Move In</span><strong>' + plan.counts.moveIn + '</strong></div></div>' +
            '<div class="col-sm-3"><div class="conveyor-plan-review-card"><span>Output</span><strong>' + escapeHtml(formatLabel(format)) + '</strong></div></div>' +
        '</div>' +
        '<div class="conveyor-plan-review-meta">' +
            '<span><strong>Levels:</strong> ' + escapeHtml(plan.levels.join(', ')) + '</span>' +
            '<span><strong>Model date:</strong> ' + escapeHtml(formatModelDate(reportPayload.model_as_of)) + '</span>' +
            '<span><strong>Prepared by:</strong> ' + escapeHtml(user) + '</span>' +
            '<span><strong>Generated:</strong> ' + escapeHtml(generatedAt) + '</span>' +
            '<span><strong>Columns:</strong> ' + columns.length + '</span>' +
        '</div>';

        $('#conveyor-plan-review-summary').html(summaryHtml);

        var previewHtml = '<table class="table table-striped table-bordered"><thead><tr>';
        $.each(columns, function (index, definition) {
            previewHtml += '<th>' + escapeHtml(definition.label) + '</th>';
        });
        previewHtml += '</tr></thead><tbody>';

        $.each(plan.rows.slice(0, 10), function (rowIndex, row) {
            previewHtml += '<tr>';
            $.each(columns, function (columnIndex, definition) {
                previewHtml += '<td>' + escapeHtml(planDisplayValue(row, definition.key)) + '</td>';
            });
            previewHtml += '</tr>';
        });

        previewHtml += '</tbody></table>';
        $('#conveyor-plan-preview').html(previewHtml);
        updateColumnSelection();
        return true;
    }

    function safeExportValue(value) {
        if (typeof value === 'string' && /^[=+\-@]/.test(value)) {
            return "'" + value;
        }
        return value;
    }

    function exportMetadata(plan) {
        var user = $('#conveyor-plan-modal').attr('data-plan-user') || 'Current user';
        var savings = calculatePlanSavings(plan);
        return 'Model date: ' + formatModelDate(reportPayload.model_as_of) +
            ' | Generated: ' + new Date().toLocaleString() +
            ' | Prepared by: ' + user +
            ' | Levels: ' + plan.levels.join(', ') +
            ' | ' + plan.counts.selected + ' total actions (' + plan.counts.moveOut + ' out, ' + plan.counts.moveIn + ' in)' +
            ' | Projected net savings: ' + formatSignedNumber(savings.walkDaily, 1) + ' walk m/day, ' +
            formatSignedNumber(savings.replenDaily, 2) + ' replen moves/day';
    }

    function buildExportRows(plan, columns) {
        var exportRows = [];

        $.each(plan.rows, function (rowIndex, row) {
            var exportRow = {};
            $.each(columns, function (columnIndex, definition) {
                exportRow[definition.key] = safeExportValue(planValue(row, definition.key));
            });
            exportRows.push(exportRow);
        });

        return exportRows;
    }

    function exportButtonConfig(format, plan, columns, fileBase) {
        var extensionMap = {pdf: 'pdfHtml5', xlsx: 'excelHtml5', csv: 'csvHtml5', print: 'print'};
        var config = {
            extend: extensionMap[format],
            title: 'Conveyor Reslot Floor Plan',
            filename: fileBase,
            messageTop: exportMetadata(plan),
            messageBottom: 'Complete move-outs before move-ins. Suggested location information is placement guidance; record the actual location selected by the floor team.',
            exportOptions: {columns: ':visible'}
        };

        if (format === 'pdf') {
            config.orientation = 'landscape';
            config.pageSize = columns.length > 14 ? 'A3' : 'LETTER';
            config.customize = function (document) {
                document.defaultStyle.fontSize = columns.length > 14 ? 6 : 8;
                document.styles.tableHeader.fontSize = columns.length > 14 ? 6 : 8;
                document.styles.title.fontSize = 16;
                document.pageMargins = [24, 28, 24, 28];
                document.footer = function (currentPage, pageCount) {
                    return {
                        text: 'Conveyor Reslot Floor Plan  |  Page ' + currentPage + ' of ' + pageCount,
                        alignment: 'center',
                        fontSize: 7,
                        margin: [0, 8, 0, 0]
                    };
                };
            };
        }

        if (format === 'print') {
            config.autoPrint = true;
            config.customize = function (printWindow) {
                $(printWindow.document.body)
                    .addClass('conveyor-plan-print-document')
                    .css('font-size', columns.length > 14 ? '8px' : '10px');
                $(printWindow.document.body).find('table').addClass('table-condensed').css('width', '100%');
            };
        }

        return config;
    }

    function generatePlanOutput() {
        if (!renderPlanReview()) {
            return;
        }

        var format = $('input[name="conveyor-plan-format"]:checked').val();
        var columns = selectedColumnDefinitions();
        var fileBase = 'Conveyor_Reslot_Plan_' + localDateStamp() + '_' + currentPlan.counts.selected + '_moves';

        if (format === 'xlsx' && typeof window.JSZip === 'undefined') {
            showPlanMessage('danger', '<strong>Excel export is unavailable.</strong> Confirm that the production DataTables asset bundle is loaded.');
            return;
        }
        if (format === 'pdf' && typeof window.pdfMake === 'undefined') {
            showPlanMessage('danger', '<strong>PDF export is unavailable.</strong> Confirm that the production DataTables asset bundle is loaded.');
            return;
        }

        var $host = $('#conveyor-plan-export-host');
        var tableHtml = '<table id="conveyor-plan-export-table"><thead><tr>';
        $.each(columns, function (index, definition) {
            tableHtml += '<th>' + escapeHtml(definition.label) + '</th>';
        });
        tableHtml += '</tr></thead></table>';
        $host.empty().html(tableHtml);

        var exportColumns = [];
        $.each(columns, function (index, definition) {
            exportColumns.push({data: definition.key, title: definition.label, defaultContent: ''});
        });

        var exportTable = $('#conveyor-plan-export-table').DataTable({
            data: buildExportRows(currentPlan, columns),
            columns: exportColumns,
            dom: 'B',
            paging: false,
            searching: false,
            ordering: false,
            info: false,
            buttons: [exportButtonConfig(format, currentPlan, columns, fileBase)]
        });

        try {
            exportTable.button(0).trigger();
            showPlanMessage('success', '<strong>Plan generated.</strong> Your ' + escapeHtml(formatLabel(format)) + ' output contains ' + currentPlan.counts.selected + ' floor actions.');
        } catch (error) {
            showPlanMessage('danger', '<strong>The output could not be generated.</strong> Try another format or refresh the page.');
        }

        window.setTimeout(function () {
            if ($.fn.dataTable.isDataTable('#conveyor-plan-export-table')) {
                exportTable.destroy();
            }
            $host.empty();
        }, 2000);
    }

    function resetFloorPlanner() {
        if (!reportPayload) {
            return;
        }

        $('.conveyor-plan-level').prop('checked', true);
        var maxActions = maxAvailableActions(reportPayload.rows || [], ['A', 'B', 'C']);
        $('#conveyor-plan-total').val(Math.min(50, maxActions));
        $('input[name="conveyor-plan-format"][value="pdf"]').prop('checked', true);
        applyColumnPreset('floor');
        clearPlanMessage();
        validateAndBuildPlan(false);
        setPlanStep(1);
    }

    function initializeFloorPlanner(payload) {
        reportPayload = payload;
        $('#conveyor-plan-launch').prop('disabled', false);
        updateLevelAvailabilityLabels();

        if (floorPlannerInitialized) {
            validateAndBuildPlan(false);
            return;
        }

        floorPlannerInitialized = true;

        $('#conveyor-plan-total').on('input.conveyorPlan change.conveyorPlan', function () {
            clearPlanMessage();
            validateAndBuildPlan(false);
        });

        $('.conveyor-plan-level').on('change.conveyorPlan', function () {
            clearPlanMessage();
            validateAndBuildPlan(false);
        });

        $('[data-plan-quantity]').on('click.conveyorPlan', function () {
            var value = $(this).data('plan-quantity');
            var levels = selectedLevels();
            if (value === 'all') {
                value = maxAvailableActions(reportPayload.rows || [], levels);
            }
            $('#conveyor-plan-total').val(value).trigger('change');
        });

        $('[data-plan-preset]').on('click.conveyorPlan', function () {
            applyColumnPreset($(this).data('plan-preset'));
            clearPlanMessage();
        });

        $('[data-plan-column]').on('change.conveyorPlan', function () {
            $('[data-plan-preset]').removeClass('btn-primary').addClass('btn-default');
            updateColumnSelection();
            clearPlanMessage();
        });

        $('input[name="conveyor-plan-format"]').on('change.conveyorPlan', function () {
            updateColumnSelection();
            clearPlanMessage();
            if (currentPlanStep === 4) {
                renderPlanReview();
            }
        });

        $('#conveyor-plan-next').on('click.conveyorPlan', function () {
            if (currentPlanStep === 1 && !validateAndBuildPlan(true)) {
                return;
            }
            if (currentPlanStep === 3 && !selectedColumnDefinitions().length) {
                showPlanMessage('danger', '<strong>Select at least one output column.</strong>');
                return;
            }

            clearPlanMessage();
            setPlanStep(Math.min(4, currentPlanStep + 1));
            if (currentPlanStep === 4) {
                renderPlanReview();
            }
        });

        $('#conveyor-plan-back').on('click.conveyorPlan', function () {
            clearPlanMessage();
            setPlanStep(Math.max(1, currentPlanStep - 1));
        });

        $('#conveyor-plan-reset').on('click.conveyorPlan', function () {
            resetFloorPlanner();
        });

        $('#conveyor-plan-generate').on('click.conveyorPlan', function () {
            generatePlanOutput();
        });

        $('#conveyor-plan-modal').on('shown.bs.modal', function () {
            if (currentPlanStep === 1) {
                $('#conveyor-plan-total').focus().select();
            }
        });

        resetFloorPlanner();
    }

    function initializeReport(payload) {
        var rows = payload.rows || [];
        var moveInCount = 0;
        var moveOutCount = 0;

        $.each(rows, function (index, row) {
            if (row.action === 'MOVE_IN') {
                moveInCount += 1;
            } else if (row.action === 'MOVE_OUT') {
                moveOutCount += 1;
            }
        });

        $('[data-action-count="MOVE_IN"]').text(moveInCount);
        $('[data-action-count="MOVE_OUT"]').text(moveOutCount);

        reportTable = $('#conveyor-reslot-table').DataTable({
            data: rows,
            destroy: true,
            deferRender: true,
            responsive: true,
            fixedHeader: true,
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            order: [],
            dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                {extend: 'copyHtml5', text: 'Copy'},
                {extend: 'csvHtml5', text: 'CSV', title: 'Conveyor_Reslot_Recommendations'},
                {extend: 'excelHtml5', text: 'Excel', title: 'Conveyor_Reslot_Recommendations'},
                {extend: 'print', text: 'Print', title: 'Conveyor Reslot Recommendations'}
            ],
            columns: [
                {
                    data: 'action',
                    render: function (data, type) {
                        return type === 'display' ? actionBadge(data) : data;
                    }
                },
                {data: 'priority'},
                {data: 'level'},
                {
                    data: 'item_number',
                    render: function (data, type) {
                        if (type !== 'display') {
                            return data;
                        }
                        return '<a class="conveyor-item-link" href="itemquery.php?itemnum=' + encodeURIComponent(data) + '" target="_blank" rel="noopener">' + escapeHtml(data) + '</a>';
                    }
                },
                {data: 'current_location'},
                {data: 'current_band'},
                {
                    data: 'avg_daily_picks',
                    render: function (data, type) {
                        return type === 'display' ? formatNumber(data, 2) : data;
                    }
                },
                {data: 'current_tier'},
                {
                    data: null,
                    render: function (data, type, row) {
                        var value = row.current_grid + ' / ' + row.current_depth;
                        return type === 'display' ? '<span class="conveyor-grid-value">' + escapeHtml(value) + '</span>' : value;
                    }
                },
                {
                    data: 'current_walk_distance',
                    render: function (data, type) {
                        return type === 'display' ? '<span class="conveyor-walk-value">' + formatNumber(data, 0) + '</span>' : data;
                    }
                },
                {data: 'suggested_tier'},
                {
                    data: null,
                    render: function (data, type, row) {
                        var value = row.suggested_grid + ' / ' + row.suggested_depth;
                        return type === 'display' ? '<span class="conveyor-grid-value">' + escapeHtml(value) + '</span>' : value;
                    }
                },
                {
                    data: 'suggested_walk_distance',
                    render: function (data, type) {
                        return type === 'display' ? '<span class="conveyor-walk-value">' + formatNumber(data, 0) + '</span>' : data;
                    }
                },
                {
                    data: 'eligible_bands',
                    defaultContent: '',
                    render: function (data, type) {
                        var value = data || 'Outside conveyor';
                        return type === 'display' ? escapeHtml(value) : value;
                    }
                },
                {
                    data: 'daily_walk_reduction',
                    render: function (data, type) {
                        return type === 'display' ? deltaBadge(data) : data;
                    }
                },
                {
                    data: 'daily_replen_reduction',
                    render: function (data, type) {
                        return type === 'display' ? deltaBadge(data) : data;
                    }
                },
                {
                    data: 'reason',
                    render: function (data, type) {
                        return type === 'display' ? '<span class="conveyor-why">' + escapeHtml(data) + '</span>' : data;
                    }
                }
            ],
            columnDefs: [
                {responsivePriority: 1, targets: [0, 2, 3]},
                {responsivePriority: 2, targets: [4, 10, 11, 12]},
                {responsivePriority: 3, targets: [6, 13]},
                {className: 'text-center', targets: [1, 2, 5, 6, 7, 8, 9, 10, 11, 12, 14, 15]}
            ],
            createdRow: function (row, data) {
                $(row).addClass(data.action === 'MOVE_IN' ? 'conveyor-row-in' : 'conveyor-row-out');
            },
            drawCallback: function () {
                var api = this.api();
                $('#conveyor-visible-rows').text(api.rows({search: 'applied'}).count());
            },
            language: {
                emptyTable: 'No move-in or move-out recommendations are available.',
                search: 'Search items and locations:'
            }
        });

        $('.conveyor-action-filter').on('click', function () {
            $('.conveyor-action-filter').removeClass('active').attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');
            var action = $(this).data('action');
            reportTable.column(0).search(action ? '^' + action + '$' : '', true, false).draw();
        });

        $('#conveyor-level-filter').on('change', function () {
            var level = $(this).val();
            reportTable.column(2).search(level ? '^' + level + '$' : '', true, false).draw();
        });

        $('#conveyor-report-loading').addClass('hidden');
        $('#conveyor-table-wrap').removeClass('hidden');
        reportTable.columns.adjust().responsive.recalc();
        initializeFloorPlanner(payload);
    }

    function loadReport() {
        $.ajax({
            url: endpoint,
            data: {view: 'rows'},
            dataType: 'json',
            cache: false
        }).done(function (payload) {
            if (!payload.success) {
                var message = payload.message || 'The report could not be loaded.';
                $('#conveyor-report-loading').addClass('hidden');
                $('#conveyor-report-error').removeClass('hidden').text(message);
                showSummaryError(message);
                return;
            }
            renderSummary(payload);
            initializeReport(payload);
        }).fail(function (xhr) {
            var message = 'The report could not be loaded. Refresh the page or sign in again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            $('#conveyor-report-loading').addClass('hidden');
            $('#conveyor-report-error').removeClass('hidden').text(message);
            showSummaryError(message);
        });
    }

    function loadDashboardSummary() {
        $.ajax({
            url: endpoint,
            data: {view: 'summary'},
            dataType: 'json',
            cache: false
        }).done(function (payload) {
            if (payload.success) {
                renderSummary(payload);
                $('#conveyor-dashboard-loading').addClass('hidden');
                $('#conveyor-dashboard-content').removeClass('hidden');
            } else {
                showSummaryError(payload.message || 'Summary data could not be loaded.');
            }
        }).fail(function (xhr) {
            var message = 'Summary data could not be loaded.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            $('#conveyor-dashboard-loading').addClass('hidden');
            showSummaryError(message);
        });
    }

    window.ConveyorReslotPlanner = {
        buildPlan: buildFloorPlan,
        levelAvailability: levelAvailability
    };

    $(function () {
        if ($('#conveyor-reslot-table').length) {
            loadReport();
        } else if ($('#conveyor-reslot-summary').length) {
            loadDashboardSummary();
        }
    });
}(jQuery));
