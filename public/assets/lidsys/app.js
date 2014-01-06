var app =  angular.module('app', ['ngRoute']);

app.config(['$injector', '$routeProvider', function ($injector, $routeProvider) {
    $routeProvider
        .when('/',
        {
            template: "Main",
            controller: "AppCtrl"
        })
        .when('/user/login',
        {
            templateUrl: "/app/template/login/index.html",
            controller: "LoginCtrl"
        })
        .when('/football/schedule/:year?/:week?',
        {
            templateUrl: "/app/template/football/schedule.html",
            controller: "LidsysFootballScheduleCtrl",
            resolve: $injector.get('lidsysFootballWeekSensitiveRouteResolver')
        })
        .otherwise({
            template: "This doesn't exist!"
        });
}])

app.factory('active', [function() {
    return new ActiveService()
}])

app.directive('ldsAuthenticated', ['$rootScope', 'active', function ($rootScope, active) {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            var wasOriginallyDisplayed = element.css('display')
            $rootScope.$watch(
                function (scope) {
                    var expected = (attrs.ldsAuthenticated !== "false")
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

app.directive('ldsAuthorized', ['$rootScope', 'active', function ($rootScope, active) {
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

app.run(['$rootScope', 'active', function ($rootScope, active) {
    active.setUser(new User())
}])

app.controller('AppCtrl', ['$scope', '$http', function ($scope, $http) {
}])

app.controller('LoginCtrl', ['$scope', '$location', '$http', 'active', function ($scope, $location, $http, active) {
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
                    active.setUser((new User()).setFromApi(data.authenticated_user))
                    login.error.form = 'Success!!';
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






app.constant('lidsysFootballWeekSensitiveRouteResolver', {
    resolveValidWeek: ['$location', '$q', '$route', 'lidsysFootballSchedule', function ($location, $q, $route, footballSchedule) {
        var year = $route.current.params.year,
            week = $route.current.params.week
        return footballSchedule.load(year, week)
            .catch(function (message) {
                if (message.year && message.week) {
                    $location.path(
                        $route.current.originalPath
                            .replace(":year?", message.year)
                            .replace(":week?", message.week)
                    ).replace()
                }

                return $q.reject(message)
            })
    }],
    resolveTeams: ['lidsysFootballTeam', function (footballTeam) {
        return footballTeam.load()
    }],
})

app.factory('lidsysFootballSchedule', ['$http', '$q', function($http, $q) {
    return new FootballScheduleService($http, $q)
}])

app.factory('lidsysFootballTeam', ['$http', '$q', function($http, $q) {
    return new FootballTeamService($http, $q)
}])

app.directive('ldsFootballWeekSelector', [function () {
    return {
        restrict: "E",
        controller: ['$location', '$route', '$scope', 'lidsysFootballSchedule', function ($location,  $route, $scope, footballSchedule) {
            var season = footballSchedule.getSelectedSeason(),
                week   = footballSchedule.getSelectedWeek()
            $scope.week_selector = {
                season:  season,
                week:    week,
                seasons: footballSchedule.getSeasons(),
                weeks:   footballSchedule.getWeeksArray(season.year)
            };
            $scope.changeSelectedWeek = function() {
                $location.path(
                    $route.current.originalPath
                        .replace(":year?", $scope.week_selector.season.year)
                        .replace(":week?", $scope.week_selector.week.week_number)
                )
            }
        }],
        templateUrl: "/app/template/football/week-selector.html"
    }
}])

app.controller('LidsysFootballScheduleCtrl', ['$scope', 'lidsysFootballSchedule', 'lidsysFootballTeam', function ($scope, footballSchedule, footballTeam) {
    var teams   = footballTeam.getTeams(),
        games   = footballSchedule.getGames(),
        game    = null,
        game_id = null
    for (game_id in games) {
        game = games[game_id]

        if (game.away_team_id && !game.away_team) {
            game.away_team = teams[game.away_team_id]
            game.home_team = teams[game.home_team_id]
        }
    }
    $scope.games        = games
    $scope.prevGameTime = null
    $scope.headerExists = function (game) {
        if ($scope.prevGameTime === game.start_time) {
            return false
        }

        $scope.prevGameTime = game.start_time
        return true
    }
}])