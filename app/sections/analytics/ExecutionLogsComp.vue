<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title :class="getTheme ? 'grey darken-3' : 'brown lighten-4'">
                Logs size:
            </v-card-title>
            <v-card-text>
                <br>
                <p>
                    <template v-if="logs['logs-size'] === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <template v-if="logs['logs-size'] > 1000">
                                <strong>{{ (logs['logs-size'] / 1000).toFixed(2) }}</strong>
                            </template>
                            <template v-else>
                                <strong>{{ logs['logs-size'] }}</strong>
                            </template>
                        </span>
                        <span class="text-h5 text--gray">
                            <span v-if="logs['logs-size'] > 1000">Gb</span>
                            <span v-else>Mb</span>
                            <template v-if="logs['num-files'] > 1000">
                                (<strong>{{ (logs['num-files'] / 1000).toFixed(2) }}</strong>K num. logs)
                            </template>
                            <template v-else>
                                (<strong>{{ logs['num-files'] }}</strong> num. logs)
                            </template>
                        </span>
                    </template>
                </p>
                <div>
                    Calcualated log size in megabytes/gigabytest, based on the size of all log files, and number of log present in the logs directory.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "logs" : {
                    "logs-size": false,
                    "num-files": false
                }
            }
        },

        computed:{
            getTheme:function(){
                var self = this;
                return self.$vuetify.theme.dark
            }
        },

        methods: {
            logInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/logs",params, options)
                .then(function (response) {
                    self['logs'] = response.data;
                });
            }
        },

        mounted:function() {
            this.logInfo();
        }
    }
</script>

<style>
</style>
