app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/order-pkg/order/list', {
        template: '<order-list></order-list>',
        title: 'Orders',
    }).
    when('/order-pkg/order/add', {
        template: '<order-form></order-form>',
        title: 'Add Order',
    }).
    when('/order-pkg/order/edit/:id', {
        template: '<order-form></order-form>',
        title: 'Edit Order',
    }).

    when('/order-pkg/order/view/:order_id', {
        template: '<order-view></order-view>',
        title: 'View Order',
    })

    ;
}]);

app.component('orderList', {
    templateUrl: order_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var table_scroll;
        table_scroll = $('.page-main-content').height() - 37;
        var dataTable = $('#orders_list').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            stateSave: true,
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getOrderList'],
                data: function(d) {}
            },
            columns: [
                { data: 'id', name: 'orders.id', searchable: true },
                { data: 'date', name: 'orders.created_at', searchable: true },
                { data: 'email', name: 'cb.email', searchable: true },
                { data: 'billing_name', name: 'ba.first_name', searchable: true },
                { data: 'shipping_name', name: 'ba.first_name', searchable: true },
                { data: 'payment_mode', name: 'pm.name', searchable: true },
                { data: 'type', name: 'pm.name', searchable: false },
                { data: 'total', name: 'orders.total', searchable: false },
                { data: 'status', name: 'orders.status_id', searchable: true },
                { data: 'action', name: 'action', class: 'action' },
            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total + '/' + max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            },
            initComplete: function() {
                $('.search label input').focus();
            },
        });
        $('.dataTables_length select').select2();
        $('.page-header-content .display-inline-block .data-table-title').html('Orders <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        $('.add_new_button').html(
            '<a href="#!/order-pkg/order/add" type="button" class="btn btn-secondary" dusk="add-btn">' +
            'Add Order' +
            '</a>'
        );

        $('.btn-add-close').on("click", function() {
            $('#orders_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#orders_list').DataTable().ajax.reload();
        });

        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_order').val('');
            $('#orders_list').DataTable().search('').draw();
        }

        var dataTables = $('#orders_list').dataTable();
        $("#search_order").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteOrder = function($id) {
            $('#order_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#order_id').val();
            $http.get(
                order_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Order Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#orders_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/order-pkg/order/list');
                }
            });
        }

        //FOR FILTER
        $('#order_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#order_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#mobile_no').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#order_name").val('');
            $("#order_code").val('');
            $("#mobile_no").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});

app.component('orderView', {
    templateUrl: order_view_template_url,
    controller: function(HelperService, $rootScope, $scope, $http, $location, $routeParams) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;

        self.order_list_url = laravel_routes['authedAngularPage'] + '/#!/order/list';
        $http({
            url: laravel_routes['viewOrderAdmin'],
            method: 'GET',
            params: {
                id: $routeParams.order_id
            }
        }).then(function(response) {
            if (!response.data.success) {
                alert(response.data.error)
                return;
            }
            self.order = response.data.order;
            self.extras = response.data.extras;
            self.log = {
                notify_customer: false,
                add_comments: false,
            }
            $rootScope.loading = false;
        });
        console.log($location);
        var form_id = '#order-log-form';
        var v = jQuery(form_id).validate({
            ignore: "",
            rules: {
                'log[status_id]': {
                    required: true,
                },
                'log[notify_customer]': {
                    required: true,
                },
                'log[add_comments]': {
                    required: true,
                },
                'log[comments]': {
                    required: function() {
                        return self.log.add_comments == true;
                    },
                },
            },
            messages: {
                // 'batch_qty': {
                //     required: 'Batch Quantity is required',
                // },
            },
            submitHandler: function(form) {
                // $('#modal_edit_batch_po_item_sub_btn').html('Submitting ...');
                // $("#modal_edit_batch_po_item_sub_btn").prop('disabled', true);

                let formData = new FormData($(form_id)[0]);
                $.ajax({
                        url: laravel_routes['addOrderLog'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (!res.success) {
                            // $mdToast.show({
                            //     hideDelay: 0,
                            //     position: 'top right',
                            //     locals: { toastMessage: 'res.errors' },
                            // })

                            alert(res.errors);
                            var errors = '';
                            for (var i in res.errors) {
                                errors += '<li>' + res.errors[i] + '</li>';
                            }
                            if (errors) {
                                custom_noty('error', errors);
                            }
                            // $('#modal_edit_batch_po_item_sub_btn').html('Submit');
                            // $("#modal_edit_batch_po_item_sub_btn").prop('disabled', false);

                        } else {
                            custom_noty('success', res.message);
                            // $location.path('/order/view/' + self.order.id)
                            $location.path('/order-pkg/order/list');
                            $scope.$apply()
                        }
                    })
                    .fail(function(xhr) {
                        //$('#submit').button('reset');
                        custom_noty('error', 'Something went wrong at server.');
                    });
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('orderForm', {
    templateUrl: order_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? laravel_routes['getOrderFormData'] : laravel_routes['getOrderFormData'] + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http({
            url: laravel_routes['getOrderFormData'],
            method: 'GET',
            params: {
                'id': typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
            }
        }).then(function(response) {
            self.order = response.data.order;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.order.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'question': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'answer': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
            },
            invalidHandler: function(event, validator) {
                checkAllTabNoty()
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveOrder'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message)
                            $location.path('/order-pkg/order/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                showErrorNoty(res)
                            } else {
                                $('#submit').button('reset');
                                $location.path('/order-pkg/order/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        showServerErrorNoty()
                    });
            }
        });
    }
});
