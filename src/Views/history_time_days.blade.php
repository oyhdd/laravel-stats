<div id="compare_time_days" style="height: 300px"></div>

<script type="text/javascript" src="{{ URL::asset('/vendor/stats/js/echarts.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function(){
        // 每日请求量对比
        var title = [];
        var seriesData = [];

        var yData = JSON.parse('<?= $yData ?>');
        var xData = JSON.parse('<?= $xData ?>');
        for (var name in yData) {
            title.push(name);
            seriesData.push({
                name: name,
                type: 'line',
                data: yData[name]
            });
        }

        var echart = echarts.init(document.getElementById('compare_time_days'));
        var option = {
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: title,
                // selectedMode : "single",
                right: '5%'
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: xData
            },
            yAxis: {
                type: 'value'
            },
            series: seriesData
        };

        echart.setOption(option);
    });

</script>