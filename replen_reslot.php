<!DOCTYPE html>
<html lang="en">
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    include_once 'globalfunctions/replen_reslot_functions.php';
    $replenCsrf = replen_csrf_token();
    $replenUser = isset($_SESSION['MYUSER']) ? strtoupper($_SESSION['MYUSER']) : '';
    ?>
    <head>
        <title>OSS - Replenishment Reslot Planner</title>
        <?php include_once 'headerincludes.php'; ?>
        <link href="osscss/replen_reslot.css" rel="stylesheet" type="text/css"/>
        <meta name="replen-reslot-csrf" content="<?php echo htmlspecialchars($replenCsrf, ENT_QUOTES, 'UTF-8'); ?>"/>
    </head>
    <body data-replen-user="<?php echo htmlspecialchars($replenUser, ENT_QUOTES, 'UTF-8'); ?>">
        <?php include_once 'horizontalnav.php'; ?>
        <?php include_once 'verticalnav.php'; ?>

        <section id="content">
            <section class="main padder replen-page">
                <header class="replen-hero">
                    <div class="replen-hero-copy">
                        <span class="replen-eyebrow">LABOR-OPTIMIZED SLOTTING</span>
                        <h1>Replenishment Reslot Planner</h1>
                        <p>Prioritize pick-face changes by net labor value, assign exact compatible locations, and manage the floor work through completion.</p>
                    </div>
                    <div class="replen-hero-actions">
                        <div class="replen-model-date">Model: <strong data-replen-model-date>Loading&hellip;</strong></div>
                        <button id="replen-create-plan" type="button" class="btn btn-primary" disabled data-toggle="modal" data-target="#replen-plan-modal">
                            <i class="fa fa-clipboard" aria-hidden="true"></i> Create Action Plan
                        </button>
                        <a class="btn btn-default" href="dashboard.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
                    </div>
                </header>

                <div id="replen-health-alert" class="alert alert-info replen-health" role="status">
                    <i class="fa fa-circle-o-notch fa-spin"></i> Checking model and occupancy health&hellip;
                </div>

                <section id="replen-kpis" class="replen-kpi-grid" aria-live="polite">
                    <article class="replen-kpi replen-kpi-primary"><span>Annual Labor Opportunity</span><strong data-replen-kpi="annual_labor_hours">&mdash;</strong><small>modeled hours</small></article>
                    <article class="replen-kpi"><span>Replens Avoided</span><strong data-replen-kpi="annual_replens_avoided">&mdash;</strong><small>modeled annual moves</small></article>
                    <article class="replen-kpi"><span>Direct Reslots Ready</span><strong data-replen-kpi="ready_now">&mdash;</strong><small>compatible open targets</small></article>
                    <article class="replen-kpi"><span>No-Move Adjustments</span><strong data-replen-kpi="adjustments">&mdash;</strong><small>max/min opportunities</small></article>
                    <article class="replen-kpi"><span>Needs Review</span><strong data-replen-kpi="review_required">&mdash;</strong><small>history, tradeoff, or fit</small></article>
                </section>

                <ul class="nav nav-tabs replen-tabs" role="tablist">
                    <li class="active"><a href="#replen-opportunities-pane" role="tab" data-toggle="tab"><i class="fa fa-bolt"></i> Opportunities</a></li>
                    <li><a href="#replen-plans-pane" role="tab" data-toggle="tab"><i class="fa fa-tasks"></i> Saved Plans <span id="replen-plan-count" class="badge">0</span></a></li>
                </ul>

                <div class="tab-content replen-tab-content">
                    <section id="replen-opportunities-pane" class="tab-pane active">
                        <nav class="replen-queue-switch" aria-label="Opportunity queues">
                            <button type="button" class="active" data-replen-queue="physical" data-filter="^(DIRECT_RESLOT|DEPENDENCY_RESLOT|REVIEW_REQUIRED)$">
                                <i class="fa fa-exchange"></i><span>Physical Reslots</span><strong data-replen-queue-count="physical_reslots">&mdash;</strong>
                            </button>
                            <button type="button" data-replen-queue="adjustments" data-filter="^MAX_MIN_ADJUSTMENT$">
                                <i class="fa fa-sliders"></i><span>No-Move Max/Min</span><strong data-replen-queue-count="adjustments">&mdash;</strong>
                            </button>
                            <button type="button" data-replen-queue="all" data-filter="">
                                <i class="fa fa-list"></i><span>All Opportunities</span><strong data-replen-queue-count="opportunity_count">&mdash;</strong>
                            </button>
                        </nav>
                        <div class="replen-toolbar">
                            <div class="replen-filters">
                                <label>Action
                                    <select id="replen-action-filter" class="form-control input-sm">
                                        <option value="">All actions</option>
                                        <option value="DIRECT_RESLOT">Direct reslots</option>
                                        <option value="DEPENDENCY_RESLOT">Dependency reslots</option>
                                        <option value="MAX_MIN_ADJUSTMENT">No-move adjustments</option>
                                        <option value="REVIEW_REQUIRED">Review required</option>
                                    </select>
                                </label>
                                <label>Level
                                    <select id="replen-level-filter" class="form-control input-sm">
                                        <option value="">All levels</option>
                                        <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                                    </select>
                                </label>
                                <label>Confidence
                                    <select id="replen-confidence-filter" class="form-control input-sm">
                                        <option value="">All confidence</option>
                                        <option value="STANDARD">Standard</option>
                                        <option value="REVIEW">Review</option>
                                    </select>
                                </label>
                            </div>
                            <div class="replen-selection-tools">
                                <span><strong id="replen-selected-count">0</strong> selected</span>
                                <button type="button" class="btn btn-default btn-sm" data-replen-select-top="25">Top 25 ready</button>
                                <button type="button" class="btn btn-default btn-sm" data-replen-select-top="50">Top 50 ready</button>
                                <button id="replen-clear-selection" type="button" class="btn btn-link btn-sm">Clear</button>
                            </div>
                        </div>

                        <div id="replen-loading" class="replen-loading"><i class="fa fa-circle-o-notch fa-spin"></i> Building replenishment opportunity list&hellip;</div>
                        <div id="replen-error" class="alert alert-danger hidden"></div>
                        <div id="replen-opportunity-wrap" class="hidden">
                            <table id="replen-opportunity-table" class="table table-striped table-hover" width="100%">
                                <thead><tr>
                                    <th class="text-center">Plan</th><th>Priority</th><th>Action</th><th>Level</th><th>Item</th>
                                    <th>Current Location</th><th>Current Profile</th><th>Suggested Profile</th>
                                    <th>Current Repl/Day</th><th>Suggested Repl/Day</th><th>Replens Avoided/Year</th>
                                    <th>Walk Change m/Day</th><th>Labor Hours/Year</th><th>History</th><th>Target Capacity</th><th>Decision</th>
                                </tr></thead>
                            </table>
                        </div>
                    </section>

                    <section id="replen-plans-pane" class="tab-pane">
                        <div class="replen-plans-heading">
                            <div><h2>Saved Floor Plans</h2><p>Persistent, assigned work with exact locations and execution history.</p></div>
                            <button id="replen-refresh-plans" class="btn btn-default"><i class="fa fa-refresh"></i> Refresh</button>
                        </div>
                        <div class="replen-plan-filters" aria-label="Saved plan filters">
                            <div class="form-group">
                                <label for="replen-plan-search">Find a plan</label>
                                <div class="input-group"><span class="input-group-addon"><i class="fa fa-search" aria-hidden="true"></i></span><input id="replen-plan-search" class="form-control" type="search" placeholder="Name, owner, or plan number"/></div>
                            </div>
                            <div class="form-group">
                                <label for="replen-plan-status-filter">Status</label>
                                <select id="replen-plan-status-filter" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="READY">Ready</option>
                                    <option value="IN_PROGRESS">In progress</option>
                                    <option value="COMPLETED">Completed</option>
                                    <option value="CANCELLED">Cancelled</option>
                                </select>
                            </div>
                            <label class="replen-my-plans"><input id="replen-my-plans" type="checkbox"/> My plans only</label>
                            <span id="replen-plan-results" class="replen-plan-results" role="status" aria-live="polite"></span>
                        </div>
                        <div id="replen-plans-loading" class="replen-loading"><i class="fa fa-circle-o-notch fa-spin"></i> Loading plans&hellip;</div>
                        <div id="replen-plans-list" class="replen-plan-list"></div>
                    </section>
                </div>
            </section>
        </section>

        <div id="replen-plan-modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                <div class="modal-header replen-modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <span class="replen-eyebrow">PLAN CREATION</span><h2 class="modal-title">Create Replenishment Action Plan</h2>
                    <p>Exact locations are reserved only after the server revalidates occupancy and move dependencies.</p>
                </div>
                <div class="modal-body">
                    <div id="replen-plan-message" class="alert hidden"></div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label for="replen-plan-name">Plan name</label><input id="replen-plan-name" class="form-control" maxlength="100" placeholder="Example: Week 30 L04 right-sizing"/></div></div>
                        <div class="col-md-3"><div class="form-group"><label for="replen-plan-date">Planned date</label><input id="replen-plan-date" class="form-control" type="date" value="<?php echo date('Y-m-d'); ?>"/></div></div>
                        <div class="col-md-3"><div class="form-group"><label for="replen-plan-assignee">Default assignee</label><select id="replen-plan-assignee" class="form-control"></select></div></div>
                    </div>
                    <div class="form-group"><label for="replen-plan-notes">Plan notes</label><textarea id="replen-plan-notes" class="form-control" rows="2" maxlength="2000"></textarea></div>
                    <div id="replen-plan-impact" class="replen-plan-impact"></div>
                    <div id="replen-override-list"></div>
                    <div class="replen-plan-preview-wrap"><table class="table table-condensed" id="replen-plan-preview"><thead><tr><th>Item</th><th>Action</th><th>From</th><th>Target profile</th><th>Replens/year</th><th>Labor hours/year</th></tr></thead><tbody></tbody></table></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button id="replen-submit-plan" type="button" class="btn btn-primary"><i class="fa fa-lock"></i> Validate Locations &amp; Create</button></div>
            </div></div>
        </div>

        <div id="replen-detail-modal" class="modal fade replen-detail-modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
                <div class="modal-header replen-modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><span class="replen-eyebrow">FLOOR EXECUTION</span><h2 id="replen-detail-title" class="modal-title">Plan Detail</h2><p id="replen-detail-subtitle"></p></div>
                <div class="modal-body">
                    <div id="replen-detail-message" class="alert hidden"></div>
                    <div id="replen-detail-summary" class="replen-detail-summary"></div>
                    <div id="replen-detail-notes" class="replen-detail-notes hidden"></div>
                    <div class="replen-detail-toolbar"><div id="replen-detail-owner-tools"></div><span>Complete actions in sequence; dependency targets open only after their enabling move.</span></div>
                    <table id="replen-plan-items-table" class="table table-striped table-hover" width="100%"><thead><tr><th>Seq</th><th>Status</th><th>Action</th><th>Item</th><th>From</th><th>To</th><th>New Max/Min</th><th>Replens/Year</th><th>Labor Hrs/Year</th><th>Assigned</th><th>Dependency</th><th>Controls</th></tr></thead></table>
                    <section class="replen-event-section"><h3>Plan Activity</h3><div id="replen-event-list"></div></section>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
            </div></div>
        </div>

        <div id="replen-edit-plan-modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="replen-edit-plan-title">
            <div class="modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header replen-modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <span class="replen-eyebrow">PLAN DETAILS</span><h2 id="replen-edit-plan-title" class="modal-title">Edit Saved Plan</h2>
                    <p>Keep the floor team aligned without rebuilding or losing the plan history.</p>
                </div>
                <div class="modal-body">
                    <div id="replen-edit-plan-message" class="alert hidden"></div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label for="replen-edit-plan-name">Plan name</label><input id="replen-edit-plan-name" class="form-control" maxlength="100"/></div></div>
                        <div class="col-sm-3"><div class="form-group"><label for="replen-edit-plan-date">Planned date</label><input id="replen-edit-plan-date" class="form-control" type="date"/></div></div>
                        <div class="col-sm-3"><div class="form-group"><label for="replen-edit-plan-status">Plan status</label><select id="replen-edit-plan-status" class="form-control"><option value="READY">Ready</option><option value="IN_PROGRESS">In progress</option><option value="COMPLETED">Completed</option><option value="CANCELLED">Cancelled</option></select></div></div>
                    </div>
                    <div id="replen-edit-status-help" class="replen-status-help" role="note"></div>
                    <div id="replen-edit-status-note-field" class="form-group hidden"><label for="replen-edit-status-note">Reason for status change</label><textarea id="replen-edit-status-note" class="form-control" rows="2" maxlength="1000" placeholder="Required for the activity history"></textarea></div>
                    <div class="form-group"><label for="replen-edit-plan-notes">Plan notes</label><textarea id="replen-edit-plan-notes" class="form-control" rows="4" maxlength="2000" placeholder="Shift instructions, staging details, or follow-up context"></textarea></div>
                    <div id="replen-edit-assignment-section" class="replen-edit-assignment">
                        <label><input id="replen-edit-reassign" type="checkbox"/> Reassign every unfinished action</label>
                        <select id="replen-edit-plan-assignee" class="form-control" disabled></select>
                        <p class="help-block">Completed and skipped actions keep their original assignee and history.</p>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button id="replen-edit-plan-submit" type="button" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button></div>
            </div></div>
        </div>

        <div id="replen-action-modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h3 id="replen-action-title" class="modal-title">Update Action</h3></div>
                <div class="modal-body"><div id="replen-action-message" class="alert hidden"></div><div id="replen-action-context" class="replen-action-context"></div>
                    <div id="replen-assignee-field" class="form-group hidden"><label for="replen-action-assignee">Assign to</label><select id="replen-action-assignee" class="form-control"></select></div>
                    <div id="replen-completion-fields" class="hidden"><div class="form-group"><label for="replen-actual-location">Actual location</label><input id="replen-actual-location" class="form-control" maxlength="15"/></div><div class="row"><div class="col-xs-6"><div class="form-group"><label for="replen-actual-max">Actual max</label><input id="replen-actual-max" class="form-control" type="number" min="1"/></div></div><div class="col-xs-6"><div class="form-group"><label for="replen-actual-min">Actual min</label><input id="replen-actual-min" class="form-control" type="number" min="0"/></div></div></div></div>
                    <div id="replen-note-field" class="form-group hidden"><label for="replen-action-note">Reason / completion note</label><textarea id="replen-action-note" class="form-control" rows="3" maxlength="1000"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button id="replen-action-submit" type="button" class="btn btn-primary">Save</button></div>
            </div></div>
        </div>

        <script src="js/replen_reslot.js" type="text/javascript"></script>
        <script>$('#reports').addClass('active');</script>
    </body>
</html>
