<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title :class="getTheme ? 'grey darken-3' : 'brown lighten-4'">
                Executed tasks with execution error:
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
                            <template v-if="executedTasksError['num-daily-with-errors'] > 1000">
                                <strong>{{ (executedTasksError['num-daily-with-errors'] / 1000).toFixed(2) }}</strong>
                            </template>
                            <template v-else>
                                <strong>{{ executedTasksError['num-daily-with-errors'] }}</strong>
                            </template>
                        </span>
                        <span class="text-h5 text--gray">

                            <template v-if="executedTasksError['num-daily-with-errors'] > 1000">
                                K
                            </template>
                            daily
                            <template v-if="executedTasksError['num-monthly-with-errors'] > 1000">
                                (<strong>{{ (executedTasksError['num-monthly-with-errors'] / 1000).toFixed(2) }}</strong>K monthly)
                            </template>
                            <template v-else>
                                (<strong>{{ executedTasksError['num-monthly-with-errors'] }}</strong> monthly)
                            </template>
                        </span>
                    </template>
                </p>
                <div>
                    Analysis of tasks performed with execution error in the current month.
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
                "executedTasksError" : {
                    "num-daily-with-errors": false,
                    "num-monthly-with-errors": false,
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
            executedTasksErrorInfo:function(){

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
                    if(response.data && typeof response.data['num-period-with-errors'] !== 'undefined'){
                        self['executedTasksError']['num-daily-with-errors'] = response.data['num-period-with-errors'];
                    }
                });

                var params = {}

                Utils.apiCall("get", "/task-stat/executed-tasks",params, options)
                .then(function (response) {
                    if(response.data && typeof response.data['num-period-with-errors'] !== 'undefined'){
                        self['executedTasksError']['num-monthly-with-errors'] = response.data['num-period-with-errors'];
                        self.calculated = true;
                    }
                });
            }
        },

        mounted:function() {
            this.executedTasksErrorInfo();
        }
    }
</script>

<style>
</style>
