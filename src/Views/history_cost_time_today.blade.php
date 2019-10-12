<div id="cost_time_today" style="height: 300px"></div>

<script type="text/javascript" src="{{ URL::asset('/vendor/stats/js/echarts.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function(){
        var title = [];
        var data = [];
        var costTimeToday = JSON.parse('<?= $costTimeToday ?>');
        for (var i in costTimeToday) {
            title.push(i);
            data.push({name: i, value: costTimeToday[i]});
        }

        var echart = echarts.init(document.getElementById('cost_time_today'));
        var option = {
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c}次 ({d}%)"
            },
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 10,
                top: 20,
                bottom: 20,
                data: title
            },
            series : [
                {
                    name: '请求量',
                    type: 'pie',
                    radius : '55%',
                    center: ['40%', '50%'],
                    data: data,
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        };


        echart.setOption(option);
    });

</script>