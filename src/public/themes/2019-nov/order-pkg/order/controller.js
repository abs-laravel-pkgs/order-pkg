app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/orders', {
        template: '<orders></orders>',
        title: 'Orders',
    });
}]);

app.component('orders', {
    templateUrl: order_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http({
            url: laravel_routes['getOrders'],
            method: 'GET',
        }).then(function(response) {
            self.orders = response.data.orders;
            $rootScope.loading = false;
        });
        $rootScope.loading = false;
    }
});