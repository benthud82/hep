<!DOCTYPE html>
<html lang="en">
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    ?>
    <head>
        <title>OSS - Conveyor Reslot Plan</title>
        <?php include_once 'headerincludes.php'; ?>
        <link href="osscss/conveyor_reslot.css" rel="stylesheet" type="text/css"/>
    </head>

    <body>
        <?php include_once 'horizontalnav.php'; ?>
        <?php include_once 'verticalnav.php'; ?>

        <section id="content">
            <section class="main padder conveyor-reslot-page">
                <div class="conveyor-hero">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="conveyor-eyebrow">OPERATIONS PLAN</div>
                            <h1>Conveyor Reslot Recommendations</h1>
                            <p>Items recommended to enter or leave the L02 conveyor area on levels A, B, and C.</p>
                        </div>
                        <div class="col-md-4 conveyor-hero-meta">
                            <div class="conveyor-asof-label">Recommendation model date</div>
                            <div class="conveyor-asof conveyor-model-date">Loading&hellip;</div>
                            <div class="conveyor-hero-actions">
                                <button id="conveyor-plan-launch" class="btn btn-primary" type="button" data-toggle="modal" data-target="#conveyor-plan-modal" disabled>
                                    <i class="fa fa-clipboard" aria-hidden="true"></i> Create Floor Plan
                                </button>
                                <a class="btn btn-default" href="dashboard.php">
                                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="conveyor-reslot-summary" class="conveyor-summary" aria-live="polite">
                    <div class="row conveyor-total-row">
                        <div class="col-sm-3">
                            <div class="conveyor-total-card conveyor-total-in">
                                <span class="conveyor-total-label">Move In</span>
                                <strong class="conveyor-total-value" data-summary-total="move_in">&mdash;</strong>
                                <span class="conveyor-total-help">items entering conveyor</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="conveyor-total-card conveyor-total-out">
                                <span class="conveyor-total-label">Move Out</span>
                                <strong class="conveyor-total-value" data-summary-total="move_out">&mdash;</strong>
                                <span class="conveyor-total-help">items leaving conveyor</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="conveyor-total-card">
                                <span class="conveyor-total-label">Recommended Occupancy</span>
                                <strong class="conveyor-total-value" data-summary-total="recommended">&mdash;</strong>
                                <span class="conveyor-total-help"><span data-summary-total="capacity">&mdash;</span> physical positions</span>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="conveyor-total-card">
                                <span class="conveyor-total-label">Planned Open</span>
                                <strong class="conveyor-total-value" data-summary-total="planned_open">&mdash;</strong>
                                <span class="conveyor-total-help">positions after reslotting</span>
                            </div>
                        </div>
                    </div>
                    <div class="row conveyor-summary-levels"></div>
                </div>

                <div class="conveyor-guidance" role="note">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <div>
                        <strong>This is a placement guide, not a location assignment.</strong>
                        Use the suggested tier, grid/depth, walk distance in meters, and eligible band to choose an actual open position. Positive walk and replenishment changes indicate a reduction; negative values indicate a tradeoff.
                    </div>
                </div>

                <section class="panel conveyor-report-panel">
                    <header class="panel-heading conveyor-report-heading">
                        <div>
                            <h2>Action List</h2>
                            <p>Move-out work is shown first so capacity can be opened before move-in work begins.</p>
                        </div>
                        <div class="conveyor-visible-count" aria-live="polite">
                            <span id="conveyor-visible-rows">0</span> visible items
                        </div>
                    </header>

                    <div class="panel-body">
                        <div class="conveyor-filter-bar" aria-label="Recommendation filters">
                            <div class="conveyor-filter-group">
                                <span class="conveyor-filter-label">Action</span>
                                <div class="btn-group" role="group" aria-label="Filter by action">
                                    <button type="button" class="btn btn-default active conveyor-action-filter" data-action="">All</button>
                                    <button type="button" class="btn btn-default conveyor-action-filter" data-action="MOVE_OUT">
                                        <i class="fa fa-sign-out" aria-hidden="true"></i> Move Out
                                        <span class="badge" data-action-count="MOVE_OUT">0</span>
                                    </button>
                                    <button type="button" class="btn btn-default conveyor-action-filter" data-action="MOVE_IN">
                                        <i class="fa fa-sign-in" aria-hidden="true"></i> Move In
                                        <span class="badge" data-action-count="MOVE_IN">0</span>
                                    </button>
                                </div>
                            </div>
                            <div class="conveyor-filter-group">
                                <label class="conveyor-filter-label" for="conveyor-level-filter">Level</label>
                                <select id="conveyor-level-filter" class="form-control input-sm">
                                    <option value="">All levels</option>
                                    <option value="A">Level A</option>
                                    <option value="B">Level B</option>
                                    <option value="C">Level C</option>
                                </select>
                            </div>
                        </div>

                        <div id="conveyor-report-loading" class="conveyor-loading" role="status">
                            <i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>
                            Loading conveyor recommendations&hellip;
                        </div>
                        <div id="conveyor-report-error" class="alert alert-danger hidden" role="alert"></div>

                        <div id="conveyor-table-wrap" class="hidden">
                            <table id="conveyor-reslot-table" class="table table-striped table-hover" width="100%">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Priority</th>
                                        <th>Level</th>
                                        <th>Item</th>
                                        <th>Current Location</th>
                                        <th>Current Band</th>
                                        <th>APD</th>
                                        <th>Current Tier</th>
                                        <th>Current Grid / Depth</th>
                                        <th>Current Walk (m/pick)</th>
                                        <th>Suggested Tier</th>
                                        <th>Suggested Grid / Depth</th>
                                        <th>Suggested Walk (m/pick)</th>
                                        <th>Eligible Bands</th>
                                        <th>Walk Reduction (m/day)</th>
                                        <th>Replen Change / Day</th>
                                        <th>Why</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </section>
            </section>
        </section>

        <div id="conveyor-plan-modal" class="modal fade conveyor-plan-modal" tabindex="-1" role="dialog" aria-labelledby="conveyor-plan-title" data-backdrop="static" data-plan-user="<?php echo isset($_SESSION['MYUSER']) ? htmlspecialchars($_SESSION['MYUSER'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header conveyor-plan-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <div class="conveyor-plan-kicker">FLOOR EXECUTION</div>
                        <h2 class="modal-title" id="conveyor-plan-title">Create Conveyor Reslot Plan</h2>
                        <p>Build a capacity-aware action list from the current recommendation model.</p>
                    </div>

                    <ol class="conveyor-plan-progress" aria-label="Plan creation progress">
                        <li class="active" data-plan-progress="1"><span>1</span><strong>Workload</strong></li>
                        <li data-plan-progress="2"><span>2</span><strong>Output</strong></li>
                        <li data-plan-progress="3"><span>3</span><strong>Columns</strong></li>
                        <li data-plan-progress="4"><span>4</span><strong>Review</strong></li>
                    </ol>

                    <div class="modal-body conveyor-plan-body">
                        <section id="conveyor-plan-savings" class="conveyor-plan-savings" aria-labelledby="conveyor-plan-savings-title" aria-live="polite">
                            <div class="conveyor-plan-savings-heading">
                                <div>
                                    <span class="conveyor-plan-savings-kicker">PROJECTED NET SAVINGS</span>
                                    <h3 id="conveyor-plan-savings-title">Operational impact of this plan</h3>
                                </div>
                                <span class="conveyor-plan-savings-scope" data-plan-savings-scope>No valid plan selected</span>
                            </div>
                            <div class="conveyor-plan-savings-grid">
                                <div class="conveyor-plan-savings-card savings-walk">
                                    <span>Walk saved / day</span>
                                    <strong data-plan-savings="walk-daily">&mdash;</strong>
                                    <small>net meters</small>
                                </div>
                                <div class="conveyor-plan-savings-card savings-replen">
                                    <span>Replen moves avoided / day</span>
                                    <strong data-plan-savings="replen-daily">&mdash;</strong>
                                    <small>net moves</small>
                                </div>
                                <div class="conveyor-plan-savings-card">
                                    <span>Annual walk reduction</span>
                                    <strong data-plan-savings="walk-annual">&mdash;</strong>
                                    <small>kilometers / 253 days</small>
                                </div>
                                <div class="conveyor-plan-savings-card">
                                    <span>Annual replen reduction</span>
                                    <strong data-plan-savings="replen-annual">&mdash;</strong>
                                    <small>moves / 253 days</small>
                                </div>
                            </div>
                            <p class="conveyor-plan-savings-note">Net totals include any negative item-level tradeoffs. Walk values are meters; annual estimates use 253 operating days.</p>
                        </section>

                        <div id="conveyor-plan-message" class="alert hidden" role="alert" aria-live="assertive"></div>

                        <section class="conveyor-plan-step" data-plan-step="1" aria-labelledby="conveyor-plan-step-1-title">
                            <div class="conveyor-plan-step-heading">
                                <div>
                                    <span class="conveyor-plan-step-number">STEP 1 OF 4</span>
                                    <h3 id="conveyor-plan-step-1-title">Define the floor workload</h3>
                                    <p>The requested total includes every move-out and move-in action.</p>
                                </div>
                                <div class="conveyor-plan-available"><strong id="conveyor-plan-max-moves">0</strong><span>available actions</span></div>
                            </div>

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="conveyor-plan-field-card">
                                        <label for="conveyor-plan-total">How many total floor moves do you want to complete?</label>
                                        <div class="input-group conveyor-plan-quantity-input">
                                            <span class="input-group-addon"><i class="fa fa-arrows" aria-hidden="true"></i></span>
                                            <input id="conveyor-plan-total" class="form-control input-lg" type="number" min="1" step="1" inputmode="numeric" aria-describedby="conveyor-plan-total-help"/>
                                            <span class="input-group-addon">moves</span>
                                        </div>
                                        <p id="conveyor-plan-total-help" class="help-block">Open positions are used first. Move-outs are added only when capacity is needed.</p>
                                        <div class="conveyor-plan-quick-values" aria-label="Quick workload choices">
                                            <button type="button" class="btn btn-default btn-sm" data-plan-quantity="25">25</button>
                                            <button type="button" class="btn btn-default btn-sm" data-plan-quantity="50">50</button>
                                            <button type="button" class="btn btn-default btn-sm" data-plan-quantity="100">100</button>
                                            <button type="button" class="btn btn-default btn-sm" data-plan-quantity="all">All recommendations</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <fieldset class="conveyor-plan-field-card conveyor-plan-level-fieldset">
                                        <legend>Which levels should be included?</legend>
                                        <p>Work is balanced automatically across the selected levels.</p>
                                        <div class="conveyor-plan-level-options">
                                            <label class="conveyor-plan-level-option">
                                                <input type="checkbox" class="conveyor-plan-level" value="A" checked/>
                                                <span><strong>Level A</strong><small data-plan-level-available="A">Loading&hellip;</small></span>
                                            </label>
                                            <label class="conveyor-plan-level-option">
                                                <input type="checkbox" class="conveyor-plan-level" value="B" checked/>
                                                <span><strong>Level B</strong><small data-plan-level-available="B">Loading&hellip;</small></span>
                                            </label>
                                            <label class="conveyor-plan-level-option">
                                                <input type="checkbox" class="conveyor-plan-level" value="C" checked/>
                                                <span><strong>Level C</strong><small data-plan-level-available="C">Loading&hellip;</small></span>
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div id="conveyor-plan-workload-summary" class="conveyor-plan-workload-summary" aria-live="polite"></div>
                        </section>

                        <section class="conveyor-plan-step hidden" data-plan-step="2" aria-labelledby="conveyor-plan-step-2-title">
                            <div class="conveyor-plan-step-heading">
                                <div>
                                    <span class="conveyor-plan-step-number">STEP 2 OF 4</span>
                                    <h3 id="conveyor-plan-step-2-title">Choose the plan output</h3>
                                    <p>Generate one format at a time. The wizard remains open so another format can be created.</p>
                                </div>
                            </div>

                            <fieldset class="conveyor-plan-output-fieldset">
                                <legend class="sr-only">Output format</legend>
                                <div class="row">
                                    <div class="col-sm-6 col-md-3">
                                        <label class="conveyor-plan-output-option">
                                            <input type="radio" name="conveyor-plan-format" value="pdf" checked/>
                                            <span class="conveyor-plan-output-card"><i class="fa fa-file-pdf-o" aria-hidden="true"></i><strong>PDF</strong><small>Floor-ready document</small></span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="conveyor-plan-output-option">
                                            <input type="radio" name="conveyor-plan-format" value="xlsx"/>
                                            <span class="conveyor-plan-output-card"><i class="fa fa-file-excel-o" aria-hidden="true"></i><strong>Excel</strong><small>Editable .xlsx tracker</small></span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="conveyor-plan-output-option">
                                            <input type="radio" name="conveyor-plan-format" value="csv"/>
                                            <span class="conveyor-plan-output-card"><i class="fa fa-file-text-o" aria-hidden="true"></i><strong>CSV</strong><small>Simple data extract</small></span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <label class="conveyor-plan-output-option">
                                            <input type="radio" name="conveyor-plan-format" value="print"/>
                                            <span class="conveyor-plan-output-card"><i class="fa fa-print" aria-hidden="true"></i><strong>Print</strong><small>Browser print view</small></span>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                        </section>

                        <section class="conveyor-plan-step hidden" data-plan-step="3" aria-labelledby="conveyor-plan-step-3-title">
                            <div class="conveyor-plan-step-heading">
                                <div>
                                    <span class="conveyor-plan-step-number">STEP 3 OF 4</span>
                                    <h3 id="conveyor-plan-step-3-title">Select the information to include</h3>
                                    <p>Essential identifiers remain locked. Add or remove operational and analytical detail.</p>
                                </div>
                                <div class="conveyor-plan-column-count"><strong id="conveyor-plan-column-count">0</strong><span>columns selected</span></div>
                            </div>

                            <div class="conveyor-plan-presets" role="group" aria-label="Column presets">
                                <button type="button" class="btn btn-primary btn-sm" data-plan-preset="floor">Floor Essentials</button>
                                <button type="button" class="btn btn-default btn-sm" data-plan-preset="full">Full Analytics</button>
                                <button type="button" class="btn btn-default btn-sm" data-plan-preset="required">Required Only</button>
                            </div>

                            <div class="row conveyor-plan-column-groups">
                                <div class="col-md-4">
                                    <fieldset class="conveyor-plan-column-group">
                                        <legend>Required</legend>
                                        <label><input type="checkbox" data-plan-column="sequence" checked disabled/> Sequence</label>
                                        <label><input type="checkbox" data-plan-column="action" checked disabled/> Action</label>
                                        <label><input type="checkbox" data-plan-column="level" checked disabled/> Level</label>
                                        <label><input type="checkbox" data-plan-column="item_number" checked disabled/> Item</label>
                                        <label><input type="checkbox" data-plan-column="current_location" checked disabled/> Current Location</label>
                                    </fieldset>
                                </div>
                                <div class="col-md-4">
                                    <fieldset class="conveyor-plan-column-group">
                                        <legend>Floor Guidance</legend>
                                        <label><input type="checkbox" data-plan-column="avg_daily_picks" checked/> APD</label>
                                        <label><input type="checkbox" data-plan-column="suggested_tier" checked/> Suggested Tier</label>
                                        <label><input type="checkbox" data-plan-column="suggested_grid_depth" checked/> Suggested Grid / Depth</label>
                                        <label><input type="checkbox" data-plan-column="suggested_walk_distance" checked/> Suggested Walk (m/pick)</label>
                                        <label><input type="checkbox" data-plan-column="eligible_bands" checked/> Eligible Bands</label>
                                        <label><input type="checkbox" data-plan-column="reason" checked/> Why</label>
                                    </fieldset>
                                    <fieldset class="conveyor-plan-column-group conveyor-plan-tracking-group">
                                        <legend>Completion Tracking</legend>
                                        <label><input type="checkbox" data-plan-column="actual_location" checked/> Actual Location</label>
                                        <label><input type="checkbox" data-plan-column="completed_by" checked/> Completed By</label>
                                        <label><input type="checkbox" data-plan-column="completed_date" checked/> Completed Date</label>
                                    </fieldset>
                                </div>
                                <div class="col-md-4">
                                    <fieldset class="conveyor-plan-column-group">
                                        <legend>Additional Analytics</legend>
                                        <label><input type="checkbox" data-plan-column="package_unit"/> Package Unit</label>
                                        <label><input type="checkbox" data-plan-column="package_type"/> Package Type</label>
                                        <label><input type="checkbox" data-plan-column="current_band"/> Current Band</label>
                                        <label><input type="checkbox" data-plan-column="current_tier"/> Current Tier</label>
                                        <label><input type="checkbox" data-plan-column="current_grid_depth"/> Current Grid / Depth</label>
                                        <label><input type="checkbox" data-plan-column="current_walk_distance"/> Current Walk (m/pick)</label>
                                        <label><input type="checkbox" data-plan-column="daily_walk_reduction"/> Walk Reduction (m/day)</label>
                                        <label><input type="checkbox" data-plan-column="daily_replen_reduction"/> Replen Change / Day</label>
                                    </fieldset>
                                </div>
                            </div>
                            <div id="conveyor-plan-pdf-warning" class="alert alert-warning hidden" role="status">
                                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                                Wide PDFs may use small text. Consider Floor Essentials or Excel for detailed plans.
                            </div>
                        </section>

                        <section class="conveyor-plan-step hidden" data-plan-step="4" aria-labelledby="conveyor-plan-step-4-title">
                            <div class="conveyor-plan-step-heading">
                                <div>
                                    <span class="conveyor-plan-step-number">STEP 4 OF 4</span>
                                    <h3 id="conveyor-plan-step-4-title">Review the execution plan</h3>
                                    <p>Move-outs are sequenced before move-ins so the floor team opens capacity first.</p>
                                </div>
                            </div>

                            <div id="conveyor-plan-review-summary" class="conveyor-plan-review-summary"></div>

                            <div class="conveyor-plan-instruction" role="note">
                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                <div><strong>Placement guidance only.</strong> The plan identifies a suggested tier, grid/depth, walk distance in meters per pick, and eligible band. The floor team selects and records the actual open location.</div>
                            </div>

                            <div class="conveyor-plan-preview-heading">
                                <h4>Plan preview</h4>
                                <span>First 10 actions</span>
                            </div>
                            <div id="conveyor-plan-preview" class="table-responsive conveyor-plan-preview"></div>
                        </section>
                    </div>

                    <div class="modal-footer conveyor-plan-footer">
                        <button id="conveyor-plan-reset" type="button" class="btn btn-link pull-left">Reset</button>
                        <button id="conveyor-plan-close" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button id="conveyor-plan-back" type="button" class="btn btn-default hidden"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button>
                        <button id="conveyor-plan-next" type="button" class="btn btn-primary">Continue <i class="fa fa-arrow-right" aria-hidden="true"></i></button>
                        <button id="conveyor-plan-generate" type="button" class="btn btn-success hidden"><i class="fa fa-download" aria-hidden="true"></i> Generate Plan</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="conveyor-plan-export-host" class="conveyor-plan-export-host" aria-hidden="true"></div>

        <script src="js/conveyor_reslot.js" type="text/javascript"></script>
        <script>
            $(function () {
                $('#reports').addClass('active');
            });
        </script>
    </body>
</html>
