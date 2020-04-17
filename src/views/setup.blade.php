@if(config('status-pkg.DEV'))
    <?php $status_pkg_prefix = '/packages/abs/status-pkg/src';?>
@else
    <?php $status_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //STATUS
	    when('/status-pkg/status/list', {
	        template: '<status-list></status-list>',
	        title: 'Statuses',
	    }).
	    when('/status-pkg/status/add', {
	        template: '<status-form></status-form>',
	        title: 'Add Status',
	    }).
	    when('/status-pkg/status/edit/:id', {
	        template: '<status-form></status-form>',
	        title: 'Edit Status',
	    }).
	     when('/status-pkg/status/card-list', {
	        template: '<status-card-list></status-card-list>',
	        title: 'Statuses Card List',
	    });
	}]);

	//STATUSS
    var status_list_template_url = "{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/list.html')}}";
    var status_form_template_url = "{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/form.html')}}";
    var status_attchment_url = "{{asset('/storage/app/public/status/attachments')}}";
    var status_card_list_template_url = "{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/card-list.html')}}";
    var status_modal_form_template_url = "{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/partials/status-modal-form.html')}}";


</script>
<script type="text/javascript" src="{{asset($status_pkg_prefix.'/public/themes/'.$theme.'/status-pkg/status/controller.js')}}"></script>
