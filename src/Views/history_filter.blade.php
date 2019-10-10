<div>
    <div class="row">
        <div class="col-lg-4">
            <div class="input-group">
                <select id="api_list" name="interface_id" class="selectpicker" data-style="btn-info" data-live-search="true"></select>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                <span class="input-group-addon">开始日期</span>
                <input type="text" class="form-control" id="start_date" name="start_date" placeholder="" value="<?= !empty($params['start_date']) ? $params['start_date'] : '' ?>" onfocus="WdatePicker({startDcreated_atate: '%y-%M-%d', dateFmt: 'yyyy-MM-dd', alwaysUseStartDate: true, readOnly: true})" aria-describedby="basic-addon1">
            </div>
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                <span class="input-group-addon">结束日期</span>
                <input type="text" class="form-control" id="end_date" name="end_date" placeholder="" value="<?= !empty($params['end_date']) ? $params['end_date'] : '' ?> " onfocus="WdatePicker({startDcreated_atate: '%y-%M-%d', dateFmt: 'yyyy-MM-dd', alwaysUseStartDate: true, readOnly: true})" aria-describedby="basic-addon1">
            </div>
        </div>
        <div class="col-lg-4">
            <button type="button" id="search" class="btn btn-danger">搜 索</button>
        </div>
    </div>
</div>
<br>

<script type="text/javascript" src="{{ URL::asset('/vendor/stats/js/bootstrap-select.js') }}"></script>
<script type="text/javascript" src="{{ URL::asset('/vendor/stats/js/My97DatePicker/WdatePicker.js') }}"></script>
<link rel="stylesheet" href="{{ URL::asset('/vendor/stats/css/bootstrap-select.css') }}">
<script type="text/javascript">
    $(document).ready(function(){
        $('#api_list').selectpicker('refresh');
        var apiList = JSON.parse('<?= $apiList ?>');
        var interface_id = '<?= $params['interface_id'] ?>';

        $('#api_list').on('loaded.bs.select', function () {
            for (var id in apiList) {
                $("#api_list").append("<option value='"+id+"'>"+id + " : " + apiList[id]+"</option>");
            }
            $('#api_list').selectpicker('val', interface_id);
            $('#api_list').selectpicker('refresh');
        });
    });

    $('#search').unbind('click').click(function () {
        location = window.location.pathname + "?interface_id=" + $('#api_list').val() + "&end_date=" + $('#end_date').val() + "&start_date=" + $('#start_date').val();
    });
</script>