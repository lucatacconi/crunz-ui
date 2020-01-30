<template>
    <div>
        <v-container>
            <v-row class="pa-0" v-if="environmentStatus.ALL_CHECK">
                <v-col md="6" sm="12" class="pl-md-0 pr-md-1 mx-sm-0 px-sm-0">
                    <dailygraph :environmentStatus="environmentStatus"></dailygraph>
                </v-col>
                <v-col md="6" sm="12" class="pr-md-0 pl-md-1 mx-sm-0 px-sm-0">
                    <weeklygraph :environmentStatus="environmentStatus"></weeklygraph>
                </v-col>
            </v-row>

            <v-row>
                <environmentcheck v-on:environment-check="environmentStatus = $event"></environmentcheck>
            </v-row>
        </v-container>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                environmentStatus: {}
            }
        },

        components: {
            'dailygraph': httpVueLoader('../../../app/sections/dashboard/DaylyGraph.vue' + '?v=' + new Date().getTime()),
            'weeklygraph': httpVueLoader('../../../app/sections/dashboard/WeeklyGraph.vue' + '?v=' + new Date().getTime()),
            'environmentcheck': httpVueLoader('../../../app/sections/dashboard/EnvironmentCheck.vue' + '?v=' + new Date().getTime())
        },

        methods: {

            stat:function(){
                var self = this;
                var params = {
                    "return_task_cont": "N",
                    "calc_run_lst": "Y",
                    "show_past_planned_task": "Y",
                    "interval_from": moment().subtract(3, 'days').format("YYYY-MM-DD"),
                    "interval_to": moment().add(3, 'days').format("YYYY-MM-DD")
                }

                // Utils.apiCall("get", "/task/",params)
                // .then(function (response) {
                //     if(response.data.length != 0){
                //         for (i = 0; i < response.data.length; i++) {
                //             task_data = response.data[i];

                //             console.log(JSON.stringify(task_data));

                //             for (var task_data_start in task_data.interval_run_lst) {
                //                 if(task_data_start.substring(0, 10) == moment().format("YYYY-MM-DD")){
                //                     self.stats.daily.planned += 1;
                //                 }
                //             }

                //             for (var task_data_exec in task_data.executed_task_lst) {
                //                 if(task_data_exec.substring(0, 10) == moment().format("YYYY-MM-DD")){
                //                     self.stats.daily.executed += 1;
                //                 }
                //             }
                //         }

                //     }else{

                //     }




                //     console.log(JSON.stringify(self.stats));



                    // console.log(JSON.stringify(response));
                    //  console.log(JSON.stringify(params));
                // });


            },

            graphDaily:function(){
                var self = this;
                var config_graph_daily = {
                    type: 'pie',
                    data: {
                        labels: [ 'Planned', 'Executed', 'Errors' ],
                        datasets: [{
                            data: [ 100, 200, 300 ],
                            label: 'Daily task\'s distribution',
                            backgroundColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'right'
                        }
                    }
                };

                var graph_container_daily = document.getElementById('graph-area-1');
                graphDaily = new Chart(graph_container_daily, config_graph_daily);
            },

            graphWeekly:function(){
                var self = this;
                var config_graph_weekly = {
                    type: 'bar',
                    data: {
                        labels: [ 'Planned', 'Executed', 'Errors' ],
                        datasets: [{
                            data: [ 100, 200, 300 ],
                            label: 'Daily task\'s distribution',
                            backgroundColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'right'
                        }
                    }
                };

                var graph_container_weekly = document.getElementById('graph-area-2');
                graphWeekly = new Chart(graph_container_weekly, config_graph_weekly);
            },
        },

        mounted:function() {

        }
    }
</script>

<style>
</style>
