<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title :class="getTheme ? 'grey darken-3' : 'brown lighten-4'">
                Executed tasks:
            </v-card-title>
            <v-card-text>
                <br>
                <p>
                    <template v-if="calculated === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <template v-if="executedTasks['num-daily'] > 1000">
                                <strong>{{ (executedTasks['num-daily'] / 1000).toFixed(2) }}</strong>
                            </template>
                            <template v-else>
                                <strong>{{ executedTasks['num-daily'] }}</strong>
                            </template>
                        </span>
                        <span class="text-h5 text--gray">

                            <template v-if="executedTasks['num-daily'] > 1000">
                                K
                            </template>
                            daily
                            <template v-if="executedTasks['num-monthly'] > 1000">
                                (<strong>{{ (executedTasks['num-monthly'] / 1000).toFixed(2) }}</strong>K monthly)
                            </template>
                            <template v-else>
                                (<strong>{{ executedTasks['num-monthly'] }}</strong> monthly)
                            </template>
                        </span>
                    </template>
                </p>
                <div>
                    Analysis of tasks performed in the current month.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "calculated": false,
                "executedTasks" : {
                    "num-daily": false,
                    "num-monthly": false,
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
            executedTasksInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {
                    "interval_from": dayjs().subtract(1, 'day').format('YYYY-MM-DD'),
                    "interval_to": dayjs().subtract(1, 'day').format('YYYY-MM-DD')
                }

                Utils.apiCall("get", "/task-stat/executed-tasks",params, options)
                .then(function (response) {
                    if(response.data && typeof response.data['num-period'] !== 'undefined'){
                        self['executedTasks']['num-daily'] = response.data['num-period'];
                    }
                });

                var params = {}

                Utils.apiCall("get", "/task-stat/executed-tasks",params, options)
                .then(function (response) {
                    if(response.data && typeof response.data['num-period'] !== 'undefined'){
                        self['executedTasks']['num-monthly'] = response.data['num-period'];
                        self.calculated = true;
                    }
                });
            }
        },

        mounted:function() {
            this.executedTasksInfo();
        }
    }
</script>

<style>
</style>
