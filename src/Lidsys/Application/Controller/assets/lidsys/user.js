var module =  angular.module('ldsUser', ['ngRoute']);

module.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/user/login',
        {
            templateUrl: "/app/template/login/index.html",
            controller: "UserLoginCtrl",
            navigationLabel: "Login"
        })
        .when('/user/logout',
        {
            template: "Logging you out...",
            controller: "UserLogoutCtrl",
            navigationLabel: "Logout"
        })
        .when('/user/password',
        {
            templateUrl: "/app/template/password/index.html",
            controller: "UserPasswordCtrl",
            navigationLabel: "Edit Profile"
        })
        .when('/user/profile',
        {
            templateUrl: "/app/template/profile/index.html",
            controller: "UserProfileCtrl",
            navigationLabel: "Edit Profile"
        })
}])

module.factory('active', [function() {
    return new ActiveService()
}])

module.directive('ldsAuthenticated', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    var expected = scope.$eval(attrs.ldsAuthenticated)
                    return expected === active.isLoggedIn()
                },
                function (isAsExpected, wasAsExpected, scope) {
                    if (!isAsExpected) {
                        element.css('display', 'none')
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

module.directive('ldsAuthorized', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    return active.getUser().isAuthorized(attrs.ldsAuthorized)
                },
                function (isAuthorized, wasAuthorized, scope) {
                    if (!isAuthorized) {
                        element.css('display', 'none');
                    }
                    else {
                        element.css('display', wasOriginallyDisplayed)
                    }
                }
            )
        }
    }
}])

module.directive('ldsUserInfo', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            scope.user = active.getUser()
        }
    }
}])

module.directive('ldsUserColorChooser', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "E",
        templateUrl: '/app/template/profile/color-chooser.html',
        controller: function ($scope, $element) {
            var html, r, g, b, colorRows, colors
            colorRows = []
            for (r = 0; r <= 255; r += 51) {
                colors = []
                colorRows.push(colors)
                for (g = 0; g <= 255; g += 51) {
                    for (b = 0; b <= 255; b += 51) {
                        colors.push({r: r, g: g, b: b})
                    }
                }
            }

            $scope.colorRows = colorRows

            $scope.ldsUserChooseColor = function($event) {
                var callback = $scope.$eval($element.attr('on-select'))
                callback($($event.target))
            }
        }
    }
}])

module.controller('UserLoginCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $scope.formChanged = function ($event) {
        var login = $scope.login;

        if (login.username != login.submittedUsername ||
            login.password != login.submittedPassword
        ) {
            login.error = '';
        }
    }
    $scope.processLogin = function ($event) {
        var login = $scope.login;

        login.error             = {};
        login.submittedUsername = login.username;
        login.submittedPassword = login.password;

        if (!login.username) {
            login.error.hasError = true;
            login.error.username = 'Please enter your username.';
        }
        if (!login.password) {
            login.error.hasError = true;
            login.error.password = 'Please enter your password.';
        }

        if (login.error.hasError) {
            return false;
        }

        var postData = {
            username: login.username,
            password: login.password
        }

        $http.post("/app/user/login/", postData)
            .success(function (data) {
                if (data.authenticated_user) {
                    active.getUser().setFromApi(data.authenticated_user)
                    login.error.form = 'Success!!';
                    $window.history.back()
                }
                else {
                    login.error.form = 'The provided username/password are incorrect.';
                }
            })
            .error(function (data) {
                login.error.form = 'There was an error processing your login request. Please contact an administrator.';
            })

        return false;
    }

    $scope.login = {
        submitEnabled: false,
        error: {},
        username: '',
        password: '',
        previousUsername: '',
        previousPassword: ''
    }
}])

module.controller('UserLogoutCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $http.post("/app/user/logout/")
        .success(function (data) {
            if (data.logged_out) {
                active.getUser().setFromApi(null)
            }
            $window.history.back()
        })
        .error(function (data) {
        })
}])

module.controller('UserPasswordCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $scope.form = {
        error: {},
        success: {},
        passwordChange: new UserPasswordChange,
        shadowPasswordChange: new UserPasswordChange
    }

    $scope.processPasswordChange = function ($event) {
        var form           = $scope.form
        var passwordChange = form.passwordChange

        form.error = {}

        if (!passwordChange.currentPassword) {
            form.error.hasError = true;
            form.error.currentPassword = 'Please enter your current password.';
        }
        if (!passwordChange.newPassword) {
            form.error.hasError = true;
            form.error.newPassword = 'Please enter your new password.';
        }
        if (!passwordChange.confirmPassword) {
            form.error.hasError = true;
            form.error.confirmPassword = 'Please confirm your new password.';
        }
        if (passwordChange.newPassword != passwordChange.confirmPassword) {
            form.error.hasError = true;
            form.error.confirmPassword = 'The new passwords do not match.';
        }

        if (form.error.hasError) {
            return false
        }

        var postData = {
            currentPassword: passwordChange.currentPassword,
            newPassword:     passwordChange.newPassword
        }

        $http.post("/app/user/password/", postData)
            .success(function (data) {
                if (data.success) {
                    form.success.form = data.success
                }
                else {
                    form.error.form = data.error
                }
            })
            .error(function (data) {
                form.error.form = 'There was an error processing your password change request. Please contact an administrator.';
            })

        return false;
    }
}])

module.controller('UserProfileCtrl', ['$scope', '$location', '$http', '$window', 'active', function ($scope, $location, $http, $window, active) {
    $scope.form = {
        error: {},
        success: {}
    }

    $scope.dec2hex = function(dec, minDigits) {
        var hex = dec.toString(16)
        if (!minDigits) {
            minDigits = 0
        }
        for (var i = hex.length; i < minDigits; i++) {
            hex = '0' + hex
        }
        return hex
    }

    $scope.selectColor = function($element)
    {
        var user = active.getUser(),
            color = $element.data('color'),
            form = $scope.form,
            hex,
            postData

        hex = $scope.dec2hex(color.r, 2)
            + $scope.dec2hex(color.g, 2)
            + $scope.dec2hex(color.b, 2)

        postData = {
            background_color: hex
        }

        $http.post("/app/user/user-profile/color/", postData)
            .success(function (data) {
                if (data.success) {
                    user.backgroundColor = hex
                    form.success.form = data.success
                }
                else {
                    form.error.form = data.error
                }
            })
            .error(function (data) {
                form.error.form = 'There was an error processing your profile change request. Please contact an administrator.';
            })
    }
}])
