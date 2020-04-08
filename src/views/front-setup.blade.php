@if(config('status-pkg.DEV'))
    <?php $status_pkg_prefix = '/packages/abs/status-pkg/src';?>
@else
    <?php $status_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var status_voucher_list_template_url = "{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/status.html')}}";
</script>
<script type="text/javascript" src="{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/controller.js')}}"></script>
