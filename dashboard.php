<!DOCTYPE html>
<html lang="en">
    <?php
    include 'sessioninclude.php';
    include_once 'connection/connection_details.php';
    include_once 'globaldata/l04capacity.php';
    $dashboardUser = isset($_SESSION['MYUSER']) ? strtoupper($_SESSION['MYUSER']) : '';
    ?>
    <head>
        <title>OSS - HEP Slotting Dashboard</title>
        <?php include_once 'headerincludes.php'; ?>
        <link href="osscss/dashboard.css" rel="stylesheet" type="text/css"/>
        <script src="../Snap.svg-0.4.1/dist/snap.svg.js" type="text/javascript"></script>
    </head>

    <body class="slotting-dashboard-page">
        <?php include_once 'horizontalnav.php'; ?>
        <?php include_once 'verticalnav.php'; ?>

        <section id="content">
            <main class="main padder slotting-dashboard-shell">
                <section class="slotting-dashboard-hero" aria-labelledby="dashboard-title">
                    <div class="slotting-dashboard-hero-copy">
                        <span class="slotting-dashboard-eyebrow">HEPPENHEIM &middot; SLOTTING CONTROL CENTER</span>
                        <h1 id="dashboard-title">Put the highest-value slotting work first.</h1>
                        <p>One current model, two focused planning paths. Use this page to size the opportunity, then open the right module to build and execute the floor plan.</p>
                        <div class="slotting-dashboard-hero-meta">
                            <span><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Model <strong data-dashboard-stat="model-date">Loading&hellip;</strong></span>
                            <span><i class="fa fa-user" aria-hidden="true"></i> Signed in as <strong><?php echo htmlspecialchars($dashboardUser, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        </div>
                    </div>

                    <div class="slotting-dashboard-hero-signal" aria-live="polite">
                        <div class="slotting-dashboard-status" data-dashboard-status="loading">
                            <span class="slotting-dashboard-status-dot"></span>
                            <div><small>PLANNING STATUS</small><strong data-dashboard-stat="planning-state">Checking model readiness</strong></div>
                        </div>
                        <div class="slotting-dashboard-hero-stats">
                            <div><strong data-dashboard-stat="conveyor-actions">&mdash;</strong><span>Conveyor actions</span></div>
                            <div><strong data-dashboard-stat="replen-hours">&mdash;</strong><span>Replen labor hrs/year</span></div>
                        </div>
                    </div>
                </section>

                <header class="slotting-dashboard-section-heading">
                    <div>
                        <span>OPTIMIZATION MODULES</span>
                        <h2>Choose the operational problem to solve</h2>
                    </div>
                    <p>The dashboard stays high level. Recommendations, item detail, exact locations, and execution controls live inside each module.</p>
                </header>

                <section class="slotting-dashboard-module-grid" aria-label="Slotting optimization modules">
                    <article id="dashboard-conveyor-card" class="slotting-module-card slotting-module-conveyor" aria-busy="true">
                        <div class="slotting-module-accent"></div>
                        <header class="slotting-module-header">
                            <div class="slotting-module-icon"><i class="fa fa-random" aria-hidden="true"></i></div>
                            <div>
                                <span class="slotting-module-kicker">WALK DISTANCE &amp; CAPACITY</span>
                                <h2>Conveyor Slotting</h2>
                                <p>Balance conveyor positions while reducing travel and protecting working capacity.</p>
                            </div>
                            <span class="slotting-module-model">Model <strong data-conveyor-stat="model-date">&mdash;</strong></span>
                        </header>

                        <div class="slotting-module-primary-stat">
                            <div><span>PRIORITIZED ACTIONS</span><strong data-conveyor-stat="total-actions">&mdash;</strong></div>
                            <small><strong data-conveyor-stat="move-in">&mdash;</strong> move in <b>&middot;</b> <strong data-conveyor-stat="move-out">&mdash;</strong> move out</small>
                        </div>

                        <div class="slotting-module-stat-grid">
                            <div><span>Walk saved/day</span><strong data-conveyor-stat="walk-day">&mdash;</strong><small>meters</small></div>
                            <div><span>Annual replens avoided</span><strong data-conveyor-stat="annual-replens">&mdash;</strong><small>modeled moves</small></div>
                            <div><span>Planned open</span><strong data-conveyor-stat="planned-open">&mdash;</strong><small data-conveyor-stat="reserve">capacity reserve</small></div>
                        </div>

                        <div id="dashboard-conveyor-error" class="slotting-module-error hidden" role="alert"></div>
                        <footer class="slotting-module-footer">
                            <p><i class="fa fa-compass" aria-hidden="true"></i> Best for deciding which items should enter or leave conveyor positions.</p>
                            <a class="btn slotting-module-button" href="conveyor_reslot.php">Open Conveyor Planner <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        </footer>
                    </article>

                    <article id="dashboard-replen-card" class="slotting-module-card slotting-module-replen" aria-busy="true">
                        <div class="slotting-module-accent"></div>
                        <header class="slotting-module-header">
                            <div class="slotting-module-icon"><i class="fa fa-refresh" aria-hidden="true"></i></div>
                            <div>
                                <span class="slotting-module-kicker">REPLENISHMENT LABOR</span>
                                <h2>Replenishment Reslotting</h2>
                                <p>Right-size pick faces and build assigned, exact-location floor plans.</p>
                            </div>
                            <span class="slotting-module-model">Model <strong data-replen-stat="model-date">&mdash;</strong></span>
                        </header>

                        <div class="slotting-module-primary-stat">
                            <div><span>ANNUAL LABOR OPPORTUNITY</span><strong data-replen-stat="annual-hours">&mdash;</strong></div>
                            <small>modeled hours at 15 replens/hour</small>
                        </div>

                        <div class="slotting-module-stat-grid">
                            <div><span>Replens avoided/year</span><strong data-replen-stat="annual-replens">&mdash;</strong><small>net-positive moves</small></div>
                            <div><span>Physical reslots</span><strong data-replen-stat="physical-reslots">&mdash;</strong><small><span data-replen-stat="ready-now">&mdash;</span> direct-ready</small></div>
                            <div><span>No-move adjustments</span><strong data-replen-stat="adjustments">&mdash;</strong><small>max/min actions</small></div>
                        </div>

                        <div id="dashboard-replen-health" class="slotting-module-health" data-health="loading">
                            <i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i><span>Checking exact-location planning readiness&hellip;</span>
                        </div>
                        <div id="dashboard-replen-error" class="slotting-module-error hidden" role="alert"></div>
                        <footer class="slotting-module-footer">
                            <p><i class="fa fa-clipboard" aria-hidden="true"></i> Best for reducing replenishment work and managing floor execution.</p>
                            <a class="btn slotting-module-button" href="replen_reslot.php">Open Replenishment Planner <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                        </footer>
                    </article>
                </section>

                <p class="slotting-dashboard-footnote"><i class="fa fa-info-circle" aria-hidden="true"></i> Opportunity values are modeled decision support. Confirm item restrictions and floor conditions in the selected module before execution.</p>

                <header class="slotting-dashboard-section-heading slotting-dashboard-history-heading">
                    <div>
                        <span>HISTORICAL PERFORMANCE</span>
                        <h2>Track whether slotting changes sustain improvement</h2>
                    </div>
                    <p>The original dashboard trends and capacity gauges remain available below the two planning modules.</p>
                </header>

                <section class="panel hidewrapper slotting-history-panel" id="graph_fpp">
                    <header class="panel-heading slotting-history-panel-heading">
                        <div>
                            <span>TRAVEL EFFICIENCY</span>
                            <h3>Historical Meters Per Pick</h3>
                        </div>
                        <div class="slotting-history-panel-actions">
                            <button type="button" class="slotting-history-icon clicktotoggle-chevron" aria-label="Collapse historical meters per pick"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>
                            <button type="button" class="slotting-history-icon closehidden" id="close_fpp" aria-label="Hide historical meters per pick"><i class="fa fa-close" aria-hidden="true"></i></button>
                        </div>
                    </header>
                    <div id="historicalfpp" class="panel-body slotting-history-panel-body">
                        <div id="chartpage_fpp" class="page-break slotting-history-chart-page">
                            <div class="row slotting-history-guidance">
                                <div class="col-md-6">
                                    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button><i class="fa fa-info-circle fa-lg"></i><span> On average, how many aisle meters are walked per item picked.</span></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-success"><button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button><i class="fa fa-arrow-down fa-lg"></i><span> Positive improvement is indicated by a <strong>downward</strong> trend.</span></div>
                                </div>
                            </div>
                            <div id="container_fpp" class="dashboardstyle printrotate slotting-history-chart"></div>
                        </div>
                    </div>
                </section>

                <section class="panel hidewrapper hidden-xs slotting-history-panel" id="graph_historicalscores">
                    <header class="panel-heading slotting-history-panel-heading">
                        <div>
                            <span>SLOTTING QUALITY</span>
                            <h3>Historical Scores</h3>
                        </div>
                        <div class="slotting-history-panel-actions">
                            <button type="button" class="slotting-history-icon clicktotoggle-chevron" aria-label="Collapse historical scores"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>
                            <button type="button" class="slotting-history-icon closehidden" id="close_scoresgraph" aria-label="Hide historical scores"><i class="fa fa-close" aria-hidden="true"></i></button>
                        </div>
                    </header>
                    <div id="historicalscores" class="panel-body slotting-history-panel-body">
                        <div id="chartpage_scores" class="page-break slotting-history-chart-page">
                            <div class="row slotting-history-guidance">
                                <div class="col-md-6">
                                    <div class="alert alert-success"><button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button><i class="fa fa-arrow-up fa-lg"></i><span> Positive improvement is indicated by an <strong>upward</strong> trend.</span></div>
                                </div>
                            </div>
                            <div id="container_scores" class="dashboardstyle printrotate slotting-history-chart"></div>
                        </div>
                    </div>
                </section>

                <section class="panel hidewrapper slotting-history-panel" id="graph_historicalreplens">
                    <header class="panel-heading slotting-history-panel-heading">
                        <div>
                            <span>REPLENISHMENT OPPORTUNITY</span>
                            <h3>Historical Replen Reduction Opportunity</h3>
                        </div>
                        <div class="slotting-history-panel-actions">
                            <button type="button" class="slotting-history-icon clicktotoggle-chevron" aria-label="Collapse historical replenishment opportunity"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>
                            <button type="button" class="slotting-history-icon closehidden" id="close_replengraph" aria-label="Hide historical replenishment opportunity"><i class="fa fa-close" aria-hidden="true"></i></button>
                        </div>
                    </header>
                    <div id="historicalreplens" class="panel-body slotting-history-panel-body">
                        <div id="chartpage_replen" class="page-break slotting-history-chart-page">
                            <div class="row slotting-history-guidance">
                                <div class="col-md-6">
                                    <div class="alert alert-info"><button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button><i class="fa fa-info-circle fa-lg"></i><span> Potential replenishment reduction <strong>per day</strong> if slotted in the optimal location.</span></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-success"><button type="button" class="close" data-dismiss="alert"><i class="fa fa-times"></i></button><i class="fa fa-arrow-down fa-lg"></i><span> Positive improvement is indicated by a <strong>downward</strong> trend.</span></div>
                                </div>
                            </div>
                            <div id="container_replens" class="dashboardstyle printrotate slotting-history-chart"></div>
                        </div>
                    </div>
                </section>

                <section class="panel hidewrapper slotting-history-panel slotting-capacity-panel" id="graph_capacity">
                    <header class="panel-heading slotting-history-panel-heading">
                        <div>
                            <span>L04 UTILIZATION</span>
                            <h3>Capacity Gauges</h3>
                        </div>
                        <div class="slotting-history-panel-actions">
                            <button type="button" class="slotting-history-icon clicktotoggle-chevron" aria-label="Collapse capacity gauges"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>
                            <button type="button" class="slotting-history-icon closehidden" id="close_capacity" aria-label="Hide capacity gauges"><i class="fa fa-close" aria-hidden="true"></i></button>
                        </div>
                    </header>
                    <div class="panel-body slotting-history-panel-body">
                        <div class="row slotting-capacity-grid">
                            <div class="col-md-4 col-sm-12">
                                <div class="metric infogauge" data-ratio="<?php echo $l04capacity_A; ?>">
                                    <svg viewBox="0 0 1000 500">
                                        <path d="M 950 500 A 450 450 0 0 0 50 500"></path>
                                        <text class="percentage" text-anchor="middle" alignment-baseline="middle" x="500" y="300" font-size="140" font-weight="bold">0%</text>
                                        <text class="title" text-anchor="middle" alignment-baseline="middle" x="500" y="450" font-size="90" font-weight="normal">L04 Level A Cap</text>
                                    </svg>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="metric infogauge" data-ratio="<?php echo $l04capacity_B; ?>">
                                    <svg viewBox="0 0 1000 500">
                                        <path d="M 950 500 A 450 450 0 0 0 50 500"></path>
                                        <text class="percentage" text-anchor="middle" alignment-baseline="middle" x="500" y="300" font-size="140" font-weight="bold">0%</text>
                                        <text class="title" text-anchor="middle" alignment-baseline="middle" x="500" y="450" font-size="90" font-weight="normal">L04 Level B Cap</text>
                                    </svg>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="metric infogauge" data-ratio="<?php echo $l04capacity_C; ?>">
                                    <svg viewBox="0 0 1000 500">
                                        <path d="M 950 500 A 450 450 0 0 0 50 500"></path>
                                        <text class="percentage" text-anchor="middle" alignment-baseline="middle" x="500" y="300" font-size="140" font-weight="bold">0%</text>
                                        <text class="title" text-anchor="middle" alignment-baseline="middle" x="500" y="450" font-size="90" font-weight="normal">L04 Level C Cap</text>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </section>

        <script src="js/dashboard.js" type="text/javascript"></script>
        <script src="js/dashboard_history.js" type="text/javascript"></script>
        <script>$('#dash').addClass('active');</script>
    </body>
</html>
