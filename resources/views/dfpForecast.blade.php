<html>
<head>
    <title>Forecast Available Impressions</title>

    <link href="/css/lib/bootstrap3.3.7.min.css" rel="stylesheet">
    <link href="/css/lib/jquery-ui-1.12.1.custom/jquery-ui.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">

</head>
<body>
<div class="container">
    <div class="jumbotron">
        <h2>Forecast Available Impressions</h2>
        Estimates number of impressions for a given date range.
    </div>

    <form id="forecastForm" action="/dfpforecast" method="post">
        <div class="form-inline text-center">
            {{ csrf_field() }}
            <div class="form-group">
                <input type="text" id="startdate" name="startdate" required class="form-control"
                       value="{{ request('startdate') ?: '' }}" placeholder="start date">
            </div>

            <div class="form-group">
                <input type="text" id="enddate" name="enddate" required class="form-control"
                       value="{{ request('enddate') ?: '' }}" placeholder="end date">
            </div>
        </div>

        <div class="form-inline text-center">
            <div class="checkbox">
                <label>
                    <input id="esChartCheckbox" type="checkbox" name="domain[]" value="es"
                           {{ App\Http\Controllers\DfpForecastController::domainCheckboxSelected('es') }}
                    > Evening Sun

                </label>
            </div>

            <div class="checkbox">
                <label>
                    <input id="psChartCheckbox" type="checkbox" name="domain[]" value="ps"
                            {{ App\Http\Controllers\DfpForecastController::domainCheckboxSelected("ps") }}
                    > Pennysaver
                </label>
            </div>

            <div class="checkbox">
                <label>
                    <input id="s4aChartCheckbox" type="checkbox" name="domain[]" value="s4a"
                            {{ App\Http\Controllers\DfpForecastController::domainCheckboxSelected("s4a") }}
                    > Shop4Autos
                </label>
            </div>
            <button id='submit' type="submit" class="btn btn-default">Find</button>
        </div>
    </form>

    <div id="divLoading"></div>

    <div class="alert-success">{{ isset($time) ? "This report took ". number_format($time, 2). " seconds to retrieve from Google DFP": ''}}</div>

    @if($errors)
        <div class="alert-danger">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
        </div>
    @endif

    <hr>


    {{-- Set Canvas for ChartJS --}}
    @foreach (['esChart', 'psChart', 's4aChart'] as $chart)
    <div class="col-md-10 col-md-offset-1 margin-top">
        <canvas id="{{ $chart }}"></canvas>
    </div>
    @endforeach

</div>

<script src="/js/lib/jquery-3.2.1.min.js"></script>
<script src="/js/lib/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
<script src="/js/lib/bootstrap-3.3.7.min.js"></script>
<script src="/js/lib/ChartJs.2.7.bundle.min.js"></script>

<script type="text/javascript">

    // Initial Page Setup

    $("#startdate").datepicker({minDate: 0});

    if ($("#enddate").val == '') {
        $("#enddate").attr('disabled', 'disabled');
    }

    var startdate;
    $("#startdate").change(function () {
        startdate = $("#startdate").val();

        $("#enddate").prop('disabled', function (i, v) {
            return false;
        });
        $("#enddate").datepicker({minDate: startdate});
    });

    $('#submit').click(function () {
        if ($('#startdate').val() != ''
            && $('#enddate').val() != ''
        ) {
            $("div#divLoading").addClass('show');
        }
    });

    charts = ['esChart', 'psChart', 's4aChart'];
    toggleByCheckbow(charts);


    // Start Chart Data
    var data = <?= isset($data) ? json_encode($data) : "{}"?>;
    console.log(data);

    if (data.s4a) {
        buildChart(data.s4a, 's4a', 'Shop4Autos');
    }

    if (data.es) {
        buildChart(data.es, 'es', 'The Evening Sun');
    }

    if (data.ps) {
        buildChart(data.ps, 'ps', 'Pennysaver');
    }

    function buildChart(data, domain, title) {
        var availability = new Array();
        var labels = new Array();
        for (var placement in data) {
            labels.push(placement);
            availability.push(data[placement].available);
        }


        domainData = {
            datasets: [{
                label: title + ' Data',
                data: availability,
                backgroundColor: [
                    'rgba(1,1,256, 0.3)',
                    'rgba(255,0,0, 0.5)',
                    'rgba(1,256,1, 0.3)',
                    'rgba(255,255,1, 0.3)',
                    'rgba(1,1,256, 0.3)',
                    'rgba(255,0,0, 0.5)'
                ]
            }],

            // These labels appear in the legend and in the tooltips when hovering different arcs
            labels: labels
        };

        var chartEle = document.getElementById(domain + "Chart");
        var Chart = new window.Chart(chartEle, {
            type: 'bar',
            data: domainData,
            options: {
                title: {
                    display: true,
                    text: 'Domain: ' + title
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (tick) {
                                return tick.toLocaleString();
                            }
                        }
                    }]
                },
            }
        });
    }

        // End Chart Data

    function toggleByCheckbow(charts) {
        for (var chart in charts) {
            var box = '#' + charts[chart] + 'Checkbox';
            if (!$(box).is(':checked')) {
                var chartName = '#' + box.substr(1, box.indexOf('Checkbox') - 1)
                $(chartName).hide();
            }
        }
    }

</script>

</body>
</html>

