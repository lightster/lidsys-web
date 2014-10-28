window.FootballTeam = class FootballTeam
    @constructor: ->
        @abbreviation     = null
        @background_color = null
        @border_color     = null
        @conference       = null
        @division         = null
        @font_color       = null
        @location         = null
        @mascot           = null
        @team_id          = null

    setFromApi: (options) ->
        @abbreviation     = options.abbreviation
        @background_color = options.background_color
        @border_color     = options.border_color
        @conference       = options.conference
        @division         = options.division
        @font_color       = options.font_color
        @location         = options.location
        @mascot           = options.mascot
        @team_id          = options.team_id
        @



window.FootballSeason = class FootballSeason
    constructor: (season_data) ->
        @year = null
        @setFromApi(season_data) if season_data

    getYear: ->
        @year

    setFromApi: (options) ->
        @year = options.year



window.FootballWeek = class FootballWeek
    constructor: (week_data) ->
        @end_date     = null
        @game_count   = null
        @games_played = null
        @season_id    = null
        @start_date   = null
        @week_number  = null
        @win_weight   = null
        @year         = null
        @setFromApi(week_data) if week_data

    setFromApi: (options) ->
        @end_date     = options.end_date
        @game_count   = options.game_count
        @games_played = options.games_played
        @season_id    = options.season_id
        @start_date   = options.start_date
        @week_number  = options.week_number
        @win_weight   = options.win_weight
        @year         = options.year




window.FootballScheduleService = class FootballScheduleService
    constructor: (@$http, @$q, @teamService) ->
        @seasons = null
        @weeks   = {}
        @games   = {}

        @selectedSeason = null
        @selectedWeek   = null



    setSelectedWeek: (selectedYear, selectedWeekNumber) ->
        @load selectedYear, selectedWeekNumber
    getSelectedSeason: -> @selectedSeason
    getSelectedWeek:   -> @selectedWeek



    load: (requestedYear, requestedWeek) ->
        year    = requestedYear
        week    = requestedWeek
        @$q.when(@loadSeasons())
            .then((response) =>
                seasons = @getSeasons()
                if not seasons[year]?
                    for own a_year of seasons
                        year = a_year

                @$q.when(@loadWeeks(year))
            )
            .then((response) =>
                today = moment().format('YYYY-MM-DD')
                weeks = @getWeeks(year)
                if not weeks[week]?
                    week = 0
                    for own week_num, a_week of weeks
                        week = week_num if a_week.start_date <= today or not week

                if requestedYear isnt year or requestedWeek isnt week
                    @$q.reject({
                        year,
                        week
                    })
                else
                    @selectedSeason = @getSeason year
                    @selectedWeek   = @getWeek year, week
                    @$q.when(@loadGames(year, week))
            )


    loadSeasons: ->
        return @seasons if @seasons?
        @$http.get("/api/v1.0/football/seasons")
            .success((response) =>
                @seasons = {}
                for own year, season_data of response.seasons
                    season = new FootballSeason(season_data)
                    @seasons[year] = season
            )


    loadWeeks: (year) ->
        return @weeks[year] if @weeks[year]?
        @$http.get("/api/v1.0/football/weeks/#{year}")
            .success((response) =>
                @weeks[year] = {}
                for own week_num, week_data of response.weeks
                    week = new FootballWeek(week_data)
                    @weeks[year][week_num] = week
        )


    loadGames: (year, week) ->
        return @games[year][week] if @games[year]? and @games[year][week]?
        @$http.get("/api/v1.0/football/schedule/#{year}/#{week}")
            .success((response) =>
                teams      = @teamService.getTeams()
                games_data = response.games
                games      = {}
                for own game_id, game_data of games_data
                    game_data.away_team = teams[game_data.away_team_id]
                    game_data.home_team = teams[game_data.home_team_id]
                    game = new FootballGame game_data
                    games[game_id] = game

                @games[year]       = {}
                @games[year][week] = games
            )


    getSeason: (year) ->
        @getSeasons()[year]


    getSeasons: ->
        throw "Seasons not yet loaded using 'loadSeasons'" if not @seasons?
        @seasons


    getWeek: (year, week_num) ->
        @getWeeks(year)[week_num]


    getWeeks: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        @weeks[year]


    getWeeksArray: (year) ->
        throw "Weeks not yet loaded using 'loadWeeks' for year #{year}" if not @weeks[year]?
        for own week_num, week of @weeks[year]
            week


    getGames: (year, week_num) ->
        if not year?
            year     = @selectedSeason.year 
            week_num = null
        if not week_num?
            week_num = @selectedWeek.week_number 

        throw "Games not yet loaded using 'loadGames' for year #{year} week #{week_num}" if not @games[year]? or not @games[year][week_num]?
        @games[year][week_num]



window.FootballGame = class FootballGame
    constructor: (game_data) ->
        @game_id      = game_data.game_id
        @away_team_id = game_data.away_team_id
        @home_team_id = game_data.home_team_id
        @away_team    = game_data.away_team
        @home_team    = game_data.home_team
        @away_score   = game_data.away_score
        @home_score   = game_data.home_score
        @start_time   = game_data.start_time
        @away =
            team:  @away_team
            score: @away_score
        @home =
            team:  @home_team
            score: @home_score

    isStarted: ->
        moment().isAfter(@start_time)

    isFinal: ->
        @away_score != null || @home_score != null



window.FootballTeamService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teams   = null



    load: ->
        @$q.when(@loadTeams())


    loadTeams: ->
        return @teams if @teams?
        @$http.get("/api/v1.0/football/teams")
            .success((response) =>
                @teams = {}
                for teamId, team of response.teams
                    @teams[teamId] = (new FootballTeam()).setFromApi team
            )


    getTeams: ->
        throw "Teams not yet loaded using 'loadTeams'" if not @teams?
        @teams




window.FootballTeamStandingService = class FootballTeamService
    constructor: (@$http, @$q) ->
        @teamStandings   = {}

        @selectedConference = 'AFC'
        @selectedDivision   = 'North'


    setSelectedConference: (@selectedConference) ->
    getSelectedConference: -> @selectedConference
    setSelectedDivision:   (@selectedDivision) ->
    getSelectedDivision:   -> @selectedDivision


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadTeamStandings(requestedYear, requestedWeek))


    loadTeamStandings: (year, week) ->
        return @teamStandings[year][week] if @teamStandings[year]? and @teamStandings[year][week]?
        @$http.get("/api/v1.0/football/team-standings/#{year}/#{week}")
            .success((response) =>
                @teamStandings[year]       = {}
                @teamStandings[year][week] = response.team_standings
            )


    getTeamStandings: (year, week_num) ->
        throw "Team standings not yet loaded using 'loadTeamStandings' for year #{year} week #{week_num}" if not @teamStandings[year]? or not @teamStandings[year][week_num]?
        @teamStandings[year][week_num]




window.FootballPickService = class FootballPickService
    constructor: (@$http, @$timeout, @$q, @$window) ->
        @picks              = {}
        @queuedPickChanges  = []
        @queueTimeout       = null
        @isSaving           = false
        @errors             = []


    load: (requestedYear, requestedWeek) ->
        @picks = {}
        @$q.when(@loadPicks(requestedYear, requestedWeek))


    loadPicks: (year, week) ->
        return @picks[year][week] if @picks[year]? and @picks[year][week]?
        @$http.get("/api/v1.0/football/fantasy-picks/#{year}/#{week}")
            .success((response) =>
                @picks[year]       = {}
                @picks[year][week] = response.fantasy_picks
            )


    getPicks: (year, week_num) ->
        throw "Picks not yet loaded using 'loadPicks' for year #{year} week #{week_num}" if not @picks[year]? or not @picks[year][week_num]?
        @picks[year][week_num]


    changePick: (game, player, team) ->
        @queuedPickChanges.push
            game_id: game.game_id
            team_id: team.team_id
        @savePicks() if not @isSaving
        true

    savePicks: ->
        @$timeout.cancel(@queueTimeout) if @queueTimeout
        @queueTimeout = @$timeout(
            () =>
                @isSaving = true

                picksHash = {}
                while @queuedPickChanges.length
                    pick = @queuedPickChanges.pop()
                    picksHash[pick.game_id] = pick.team_id

                pickCount = (k for own k of picksHash).length

                @errors.pop while @errors.length
                data   = {fantasy_picks: picksHash}
                @$http.post("/api/v1.0/football/fantasy-picks/", data)
                    .success((response) =>
                        if response.saved_picks.length != pickCount
                            console.log(response.saved_picks.length, pickCount)
                            @$window.location.reload()
                    )
                    .error((response) =>
                        @errors.push("Your picks could not be saved. Please try again.")
                    )
                    .finally(=>
                        @isSaving = false
                        @savePicks() if @queuedPickChanges.length
                        true
                    )
            500,
            true
        )

    isPickSavePending: (game, player) ->
        for queuedPick in @queuedPickChanges
            return true if queuedPick.game_id == game.game_id
        false




window.FootballFantasyPlayerService = class FootballFantasyPlayerService
    constructor: (@$http, @$q) ->
        @players              = {}


    load: (requestedYear, requestedWeek) ->
        @$q.when(@loadPlayers(requestedYear, requestedWeek))


    loadPlayers: (year) ->
        return @players[year] if @players[year]?
        @$http.get("/api/v1.0/football/fantasy-players/#{year}")
            .success((response) =>
                @players[year] = {}
                for playerId, player of response.fantasy_players
                    names = player.name.split(" ")
                    player.displayName = names[0][0] + names[0][names[0].length - 1] + names[1][0]
                    @players[year][playerId] = player
            )


    getPlayers: (year, week_num) ->
        throw "Players not yet loaded using 'loadPlayers' for year #{year}" if not @players[year]?
        @players[year]



window.FootballFantasyStandingService = class FootballFantasyStandingService
    constructor: (@$http, @$q) ->
        @standings              = {}


    load: (requestedYear) ->
        @$q.when(@loadStandings(requestedYear))


    loadStandings: (year) ->
        return @standings[year] if @standings[year]?
        @$http.get("/api/v1.0/football/fantasy-standings/#{year}")
            .success((response) =>
                @standings[year] = response.fantasy_standings
            )


    getStandings: (year) ->
        throw "Standings not yet loaded using 'loadStandings' for year #{year}" if not @standings[year]?
        @standings[year]
