(function ($) {
    'use strict';

    function historicalChartOptions(config) {
        return {
            chart: {
                marginTop: 50,
                marginBottom: 135,
                renderTo: config.renderTo,
                type: 'spline'
            },
            credits: {
                enabled: false
            },
            plotOptions: {
                spline: {
                    marker: {
                        enabled: false
                    }
                },
                series: {
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: config.pointClick || function () {}
                        }
                    }
                }
            },
            title: {
                text: ' '
            },
            xAxis: {
                categories: [],
                labels: {
                    rotation: -90,
                    y: 25,
                    align: 'right',
                    step: 5,
                    style: {
                        fontSize: '12px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                },
                legend: {
                    y: '10',
                    x: '5'
                }
            },
            yAxis: {
                title: {
                    text: config.yAxisTitle
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }],
                opposite: true,
                min: config.minimum
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + this.series.name + '</b><br/>' +
                        this.x + ': ' + Highcharts.numberFormat(this.y, config.decimals);
                }
            },
            series: []
        };
    }

    function showChartError(renderTo) {
        var $container = $('#' + renderTo);
        if (!$container.length) {
            return;
        }
        $container.html('<div class="slotting-history-error"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Historical data is temporarily unavailable.</div>');
    }

    function loadHistoricalChart(config, userid) {
        var options = historicalChartOptions(config);

        $.ajax({
            url: config.url,
            data: {userid: userid},
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                var index;
                if (!json || !json[0] || !json[0].data) {
                    showChartError(config.renderTo);
                    return;
                }

                options.xAxis.categories = json[0].data;
                for (index = 1; index <= config.seriesCount; index += 1) {
                    if (json[index]) {
                        options.series.push(json[index]);
                    }
                }

                if (!options.series.length) {
                    showChartError(config.renderTo);
                    return;
                }

                new Highcharts.Chart(options);
                $(window).resize();
            },
            error: function () {
                showChartError(config.renderTo);
            }
        });
    }

    function initializeCapacityGauges() {
        var polarToCartesian;
        var svgCircleArcPath;
        var animateArc;

        if (typeof Snap === 'undefined') {
            return;
        }

        polarToCartesian = function (cx, cy, radius, angle) {
            var radians = (angle - 90) * Math.PI / 180.0;
            return [
                Math.round((cx + (radius * Math.cos(radians))) * 100) / 100,
                Math.round((cy + (radius * Math.sin(radians))) * 100) / 100
            ];
        };

        svgCircleArcPath = function (x, y, radius, startAngle, endAngle) {
            var startXY = polarToCartesian(x, y, radius, endAngle);
            var endXY = polarToCartesian(x, y, radius, startAngle);
            return 'M ' + startXY[0] + ' ' + startXY[1] + ' A ' + radius + ' ' + radius + ' 0 0 0 ' + endXY[0] + ' ' + endXY[1];
        };

        animateArc = function (ratio, svg, percentage) {
            var arc = svg.path('');
            return Snap.animate(0, ratio, function (value) {
                var path;
                arc.remove();
                path = svgCircleArcPath(500, 500, 450, -90, value * 180.0 - 90);
                arc = svg.path(path);
                arc.attr({class: 'data-arc'});
                percentage.text(Math.round(value * 100) + '%');
            }, Math.round(2000 * ratio), mina.easeinout);
        };

        $('.metric.infogauge').each(function () {
            var ratio = parseFloat($(this).data('ratio'));
            var svg = Snap($(this).find('svg')[0]);
            var percentage = $(this).find('text.percentage');
            animateArc(isNaN(ratio) ? 0 : ratio, svg, percentage);
        });
    }

    function reflowHistoricalCharts() {
        if (typeof Highcharts === 'undefined' || !Highcharts.charts) {
            return;
        }
        $.each(Highcharts.charts, function (index, chart) {
            if (chart && chart.reflow) {
                chart.reflow();
            }
        });
    }

    $(document).on('click', '.slotting-history-panel .clicktotoggle-chevron', function () {
        var $button = $(this);
        var $icon = $button.find('i');
        var $body = $button.closest('.slotting-history-panel').find('.panel-body:first');

        $icon.toggleClass('fa-chevron-down fa-chevron-up');
        $body.slideToggle(function () {
            $button.attr('aria-expanded', $body.is(':visible') ? 'true' : 'false');
            reflowHistoricalCharts();
        });
    });

    $(document).on('click', '.slotting-history-panel .closehidden', function () {
        $(this).closest('.hidewrapper').hide('slow');
    });

    $(function () {
        var userid = $.trim($('#userid').text());

        if (typeof Highcharts !== 'undefined') {
            loadHistoricalChart({
                renderTo: 'container_scores',
                url: 'globaldata/dashboardgraph_scores.php',
                yAxisTitle: 'Historical Scores',
                decimals: 1,
                minimum: 0,
                seriesCount: 4
            }, userid);

            loadHistoricalChart({
                renderTo: 'container_replens',
                url: 'globaldata/dashboardgraph_replens.php',
                yAxisTitle: 'Replen Reduction',
                decimals: 0,
                minimum: 0,
                seriesCount: 2
            }, userid);

            loadHistoricalChart({
                renderTo: 'container_fpp',
                url: 'globaldata/dashboardgraph_fpp.php',
                yAxisTitle: 'Average Meters per Pick',
                decimals: 1,
                minimum: null,
                seriesCount: 1,
                pointClick: function () {
                    location.href = 'picksbybay.php?date=' + this.category + '&type=' + this.series.name + '&formSubmit=Submit';
                }
            }, userid);
        } else {
            showChartError('container_scores');
            showChartError('container_replens');
            showChartError('container_fpp');
        }

        initializeCapacityGauges();
    });
}(jQuery));
