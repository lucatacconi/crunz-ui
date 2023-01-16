<template>
    <div>
        <v-container>
            <v-row class="pa-0">

                <v-col lg="4" md="6" sm="12" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            System Uptime:
                        </v-card-title>
                        <v-card-text>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>{{ uptime['uptime-days'] === false ? "--" : uptime['uptime-days'] }}</strong>
                                </span>
                                <span class="text-h4 text--gray">
                                    day/s
                                    <strong>({{ uptime['uptime-date'] === false  ? "--" : uptime['uptime-date'] }})</strong>
                                </span>
                            </p>
                            <div>
                                Days of system uptime calculated based on the date the first unarchived task was loaded and activated.
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col lg="4" md="6" sm="12" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Active tasks:
                        </v-card-title>
                        <v-card-text>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>{{ activeTasks['num-files'] === false ? "--" : activeTasks['num-files'] }}</strong>
                                </span>
                                <span class="text-h4 text--gray">

                                    (<strong>{{ activeTasks['files-size'] === false  ? "--" : activeTasks['files-size'] }}</strong> Kb)
                                </span>
                            </p>
                            <div>
                                Number of active tasks present and total size of task files.
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col lg="4" md="6" sm="12" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Archived tasks:
                        </v-card-title>
                        <v-card-text>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>{{ archivedTasks['num-files'] === false ? "--" : archivedTasks['num-files'] }}</strong>
                                </span>
                                <span class="text-h4 text--gray">

                                    (<strong>{{ archivedTasks['files-size'] === false  ? "--" : archivedTasks['files-size'] }}</strong> Kb)
                                </span>
                            </p>
                            <div>
                                Number of archived tasks present and total size of task files.
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col lg="4" md="6" sm="12" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Logs size:
                        </v-card-title>
                        <v-card-text>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>{{ logs['logs-size'] === false ? "--" : logs['logs-size'] }}</strong>
                                </span>
                                <span class="text-h4 text--gray">
                                    Mb
                                    (<strong>{{ logs['num-files'] === false  ? "--" : logs['num-files'] }}</strong> num. tasks)
                                </span>
                            </p>
                            <div>
                                Calcualated log size in megabytes, based on the size of all log files, and number of log present in the logs directory.
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

            </v-row>
        </v-container>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            "uptime" : {
                "uptime-date": false,
                "uptime-days": false
            },
            "logs" : {
                "logs-size": false,
                "num-files": false
            },
            "activeTasks" : {
                "files-size": false,
                "num-files": false
            },
            "archivedTasks" : {
                "files-size": false,
                "num-files": false
            }
        }
    },
    methods: {

        readUptime:function(){

            var self = this;

            var options = {
                showLoading: false
            };

            var params = {}

            Utils.apiCall("get", "/task-stat/uptime",params, options)
            .then(function (response) {
                self.uptime = response.data;
            });
        },

        readLogInfo:function(){

            var self = this;

            var options = {
                showLoading: false
            };

            var params = {}

            Utils.apiCall("get", "/task-stat/logs",params, options)
            .then(function (response) {
                self['logs'] = response.data;
            });
        },

        activeTasksInfo:function(){

            var self = this;

            var options = {
                showLoading: false
            };

            var params = {}

            Utils.apiCall("get", "/task-stat/active-tasks",params, options)
            .then(function (response) {
                self['activeTasks'] = response.data;
            });
        },

        archivedTasksInfo:function(){

            var self = this;

            var options = {
                showLoading: false
            };

            var params = {}

            Utils.apiCall("get", "/task-stat/archived-tasks",params, options)
            .then(function (response) {
                self['archivedTasks'] = response.data;
            });
        },
    },

    computed: {

    },

    mounted:function(){
        this.readUptime();
        this.readLogInfo();
        this.activeTasksInfo();
        this.archivedTasksInfo();
    },

    components:{ }
}
</script>
