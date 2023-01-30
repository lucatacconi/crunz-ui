<template>
    <v-col xl="4" lg="6" md="6" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title >
                Executed tasks with execution error:
            </v-card-title>
            <v-card-text>
                <p>
                    <span class="text-h2 text--primary">
                        <strong>{{ executedTasks['num-daily-with-errors'] === false ? "--" : executedTasks['num-daily-with-errors'] }}</strong>
                    </span>
                    <span class="text-h5 text--gray">
                        /day
                        (<strong>{{ executedTasks['num-monthly-with-errors'] === false  ? "--" : executedTasks['num-monthly-with-errors'] }}</strong>/month)
                    </span>
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
                "executedTasks" : {
                    "num-daily": false,
                    "num-monthly": false,
                    "num-daily-with-errors": false,
                    "num-monthly-with-errors": false
                }
            }
        },

        methods: {
            executedTasksInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/executed-tasks",params, options)
                .then(function (response) {
                    self['executedTasks'] = response.data;
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
