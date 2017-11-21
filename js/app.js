"use strict";

// Main module
angular.module("app", [])
// Base controller
.controller("BaseController", [
    "$scope",
    "$filter",
    "$http",
    function BaseController($scope, $filter, $http) {

        var settings = {
            cssClass: {
                paymentStatus: "payment-status"
            }
        };

        $scope.orders = [];

        $scope.users = [];

        $scope.newOrder = getEmptyOrder();

        $scope.newPayment = getEmptyPayment();

        $scope.getUserById = function(id) {
            return $filter('filter')($scope.users, { id: parseInt(id) }, true)[0].name;
        };

        $scope.addPayment = function() {
            var newPayment = $scope.newPayment;
            if (!newPayment.user_id || !newPayment.cost || !newPayment.what) {
                return;
            }
            $scope.newOrder.payments.push(newPayment);
            $scope.newPayment = getEmptyPayment();
        };

        $scope.deleteTempPayment = function(idx) {
            $scope.newOrder.payments.splice(idx, 1);
        };

        $scope.updatePayment = function(payment) {
            payment['action'] = 'updatePayment';
            $http({
                method: "POST",
                url: "./api.php",
                data: { data: payment },
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            }).then(
                function(response) {
                    $scope.orders = response.data.orders;
                    $scope.users = response.data.users;
                },
                function(response) {
                    console.log("error", response);
                }
            )
        }

        $scope.addOrder = function(orderForm) {
            if (orderForm.$valid && $scope.newOrder.payments.length > 0) {
                var newOrder = $scope.newOrder;
                newOrder.action = "addOrder";
                postNewOrder(newOrder, clearOrder);
            }
        };

        $scope.removeOrder = function(orderId) {
            var order = {
                id: orderId,
                action: "removeOrder"
            };
            $http({
                method: "POST",
                url: "./api.php",
                data: { data: order },
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            }).then(
                function(response) {
                    $scope.orders = response.data.orders;
                    $scope.users = response.data.users;
                },
                function(response) {
                    console.log("error", response);
                }
            );
        };

        $scope.getPaymentCssClass = function(status) {
            return status ? settings.cssClass.paymentStatus : "";
        };

        function getEmptyOrder() {
            return { action: "", date_time: "", order_from: "", user_id: null, payments: [] };
        }

        function getEmptyPayment() {
            return { action: "", user_id: null, cost: 0, what: "", status: 0 };
        }

        function clearOrder() {
            $scope.newOrder = getEmptyOrder();
        }

        function postNewOrder(newOrder, callback) {
            $http({
                method: "POST",
                url: "./api.php",
                data: { data: newOrder },
                headers: { "Content-Type": "application/x-www-form-urlencoded" }
            }).then(
                function(response) {
                    $scope.orders = response.data.orders;
                    $scope.users = response.data.users;
                    callback();
                },
                function(response) {
                    console.log("error", response);
                }
            )
        }

        function getAllOrders() {
            $http({
                method: "GET",
                url: "./api.php"
            }).then(
                function(response) {
                    $scope.orders = response.data.orders;
                    $scope.users = response.data.users;
                },
                function(response) {
                    console.log("error", response);
                }
            )
        }

        function init() {
            getAllOrders();
        }

        return init();

    }
]);
