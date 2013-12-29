// Generated by CoffeeScript 1.6.3
(function() {
  var ActiveService, User;

  window.ActiveService = ActiveService = (function() {
    function ActiveService() {
      this.user = null;
    }

    ActiveService.prototype.setUser = function(user) {
      this.user = user;
    };

    ActiveService.prototype.getUser = function() {
      return this.user;
    };

    ActiveService.prototype.isLoggedIn = function() {
      if (this.user.userId) {
        return true;
      } else {
        return false;
      }
    };

    return ActiveService;

  })();

  window.User = User = (function() {
    function User() {
      this.userId = null;
      this.permissions = {};
    }

    User.prototype.isAuthorized = function(permission) {
      if ((this.permissions[permission] != null) && this.permissions[permission]) {
        return true;
      } else {
        return false;
      }
    };

    User.prototype.setFromApi = function(options) {
      this.userId = options.user_id;
      this.username = options.username;
      return this;
    };

    return User;

  })();

}).call(this);
