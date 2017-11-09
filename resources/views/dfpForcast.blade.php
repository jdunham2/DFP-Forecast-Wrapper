
<link href="https://fonts.googleapis.com/css?family=Indie+Flower" rel="stylesheet">
<style>
    .note {
        font-family: 'Indie Flower', cursive;
    }

    .margin-top {
        margin-top: 50px;
    }

    .center {
        text-align: center;
    }
</style>

<h3>Available Ad Impression Lookup</h3>
<p class="text-muted note">Note: Try clicking an ad type (above the chart) to filter your results.</p>


<form action="/reporting/forcast">
    <div class="form-inline">
        <div class="form-group">
            <input type="text" id="startdate" name="startdate" required class="form-control"
                   value="<?= (isset($_GET['startdate']) ? $_GET['startdate'] : ''); ?>" placeholder="start date">
        </div>

        <div class="form-group">
            <input type="text" id="enddate" name="enddate" required class="form-control"
                   value="<?= (isset($_GET['enddate']) ? $_GET['enddate'] : ''); ?>" placeholder="end date">
        </div>

        <button type="submit" class="btn btn-default">Find</button>
    </div>
</form>

<div class="col-lg-12 margin-top">
    <canvas id="esChart"></canvas>
</div>

<div class="col-lg-12 margin-top">
    <canvas id="psChart"></canvas>
</div>

<script>
    $(function () {
        $("#startdate").datepicker({minDate: 0});
    });

    var startdate;
    $("#startdate").change(function () {
        startdate = $("#startdate").val();
        console.log(startdate);
        $("#enddate").datepicker({minDate: startdate});
    });
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js"></script>
<script type="text/javascript">
    var esChart = document.getElementById("esChart");
    var psChart = document.getElementById("psChart");

    var es_sky = {{ old('es_sky') ?: 0 }};
    var es_ldrbrd = <?= (isset($_GET['es_ldrbrd']) ? $_GET['es_ldrbrd'] : 0); ?>;
    var es_lrgblk = <?= (isset($_GET['es_lrgblk']) ? $_GET['es_lrgblk'] : 0); ?>;
    var es_med = <?= (isset($_GET['es_med']) ? $_GET['es_med'] : 0); ?>;

    var ps_sky = <?= (isset($_GET['ps_sky']) ? $_GET['ps_sky'] : 0); ?>;
    var ps_ldrbrd = <?= (isset($_GET['ps_ldrbrd']) ? $_GET['ps_ldrbrd'] : 0); ?>;
    var ps_lrgblk = <?= (isset($_GET['ps_lrgblk']) ? $_GET['ps_lrgblk'] : 0); ?>;
    var ps_med = <?= (isset($_GET['ps_med']) ? $_GET['ps_med'] : 0); ?>;

    es_data = {
        datasets: [{
            label: 'ES Data',
            data: [es_sky, es_ldrbrd, es_lrgblk, es_med],
            backgroundColor: [
                'rgba(1,1,256, 0.3)',
                'rgba(255,0,0, 0.5)',
                'rgba(1,256,1, 0.3)',
                'rgba(255,255,1, 0.3)'
            ]
        }],

        // These labels appear in the legend and in the tooltips when hovering different arcs
        labels: [
            'SkyScraper',
            'Leader Board',
            'Large Block',
            'Med Rectangle'
        ]
    };

    ps_data = {
        datasets: [{
            label: 'PS Data',
            data: [ps_sky, ps_ldrbrd, ps_lrgblk, ps_med],
            backgroundColor: [
                'rgba(1,1,256, 0.3)',
                'rgba(255,0,0, 0.5)',
                'rgba(1,256,1, 0.3)',
                'rgba(255,255,1, 0.3)'
            ]
        }],

        // These labels appear in the legend and in the tooltips when hovering different arcs
        labels: [
            'SkyScraper',
            'Leader Board',
            'Large Block',
            'Med Rectangle'
        ]
    };

    var ESDoughnutChart = new Chart(esChart, {
        type: 'bar',
        data: es_data,
        options: {
            title: {
                display: true,
                text: 'Domain: Evening Sun'
            }
        }
    });

    var PSDoughnutChart = new Chart(psChart, {
        type: 'bar',
        data: ps_data,
        options: {
            title: {
                display: true,
                text: 'Domain: Pennysaver'
            }
        }
    });


</script>