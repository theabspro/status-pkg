app.component('statusList', {
    templateUrl: status_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location, $mdSelect, $element) {
        $scope.loading = true;
        var self = this;
        $('#search_status').focus();
        self.hasPermission = HelperService.hasPermission;
        // if (!self.hasPermission('statuses')) {
        //     window.location = "#!/page-permission-denied";
        //     return false;
        // }
        $http.get(
            laravel_routes['getStatusFilterData']
        ).then(function(response) {
            // console.log(response.data);
            self.designation_list = response.data.designation_list;
            $rootScope.loading = false;
        });
        self.add_permission = self.hasPermission('add-status');
        var table_scroll;
        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#statuses_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_status').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getStatusList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.code = $('#status_code').val();
                    d.first_name = $('#first_name').val();
                    d.last_name = $('#last_name').val();
                    d.user_name = $('#user_name').val();
                    d.mobile_number = $('#mobile_number').val();
                    d.designation_id = $('#designation_id').val();
                    d.status = $('#status').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'type', name: 't.name' },
                { data: 'name', name: 'statuses.name' },
                { data: 'color', name: 'statuses.color' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $('.refresh_table').on("click", function() {
            $('#statuses_list').DataTable().ajax.reload();
        });

        $scope.clear_search = function() {
            $('#search_status').val('');
            $('#statuses_list').DataTable().search('').draw();
        }

        var dataTables = $('#statuses_list').dataTable();
        $("#search_status").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteStatus = function($id) {
            $('#status_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#status_id').val();
            $http.get(
                laravel_routes['deleteStatus'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Status Deleted Successfully');
                    $('#statuses_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/status-pkg/status/list');
                }
            });
        }

        //FOR FILTER
        self.status = [
            { id: '', name: 'Select Status' },
            { id: '1', name: 'Active' },
            { id: '0', name: 'Inactive' },
        ];
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $('#status_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#first_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#last_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#user_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_number').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.onselectDesignation = function(id) {
            $('#designation_id').val(id);
            dataTables.fnFilter();
        }

        $scope.onSelectedStatus = function(val) {
            $("#status").val(val);
            dataTables.fnFilter();
        }
        $scope.reset_filter = function() {
            $("#status_code").val('');
            $("#first_name").val('');
            $("#last_name").val('');
            $("#user_name").val('');
            $("#mobile_number").val('');
            $("#designation_id").val('');
            $("#status").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('statusForm', {
    templateUrl: status_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-status') || !self.hasPermission('edit-status')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getStatusFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            // console.log(response);
            self.status = response.data.status;
            self.designation_list = response.data.designation_list;
            self.status_attchment_url = status_attchment_url;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.status.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                if (self.status.password_change == 'No') {
                    self.switch_password = 'No';
                    $("#hide_password").hide();
                    $("#password").prop('disabled', true);
                } else {
                    self.switch_password = 'Yes';
                }
                // console.log(response.data.status_attachment);
                // if (response.data.status_attachment.name != '' && response.data.status_attachment.name != 'null') {
                //     self.status_attachment_name = response.data.status_attachment.name;
                // } else {
                //     self.status_attachment_name = '';
                // }
                console.log(response.data.status);

            } else {
                self.switch_value = 'Active';
                $("#hide_password").show();
                $("#password").prop('disabled', false);
                self.switch_password = 'Yes';
                self.status_attachment_name = '';
            }
        });

        $scope.psw_change = function(val) {
            if (val == 'No') {
                $("#hide_password").hide();
                $("#password").prop('disabled', true);
            } else {
                $("#hide_password").show();
                setTimeout(function() {
                    $noty.close();
                }, 1000);
                $("#password").prop('disabled', false);
            }
        }

        $("input:text:visible:first").focus();

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'user[first_name]': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'user[last_name]': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'user[username]': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },
                'alternate_mobile_number': {
                    number: true,
                    maxlength: 12,
                },

                'user[mobile_number]': {
                    number: true,
                    maxlength: 12,
                },
                'user[password]': {
                    required: function(element) {
                        if ($("#password_change").val() == 'Yes') {
                            return true;
                        } else {
                            return false;
                        }
                    },
                    minlength: 5,
                    maxlength: 16,
                },
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveStatus'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/status-pkg/status/list');
                            $scope.$apply();
                        } else {
                            $('#submit').button('reset');
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            custom_noty('error', errors);
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
