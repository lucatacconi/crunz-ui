<template>
    <div>
        <v-card>
            <v-card-title>Weekly task's prospect</v-card-title>
            <v-card-text>
                <canvas id="graph-area-weekly" height="100"></canvas>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                dayBack: 3,
                dayFront: 3,

                statPlanned: [],
                statExecuted: [],
                statWithError: [],
                dayList: [],
                stats: {
                    planned: [],
                    executed: [],
                    withErrors: []
                }
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadGraph: function(){

                var self = this;

                var moves = this.dayBack + this.dayFront + 1;
                var day_start = moment().subtract(this.dayBack + 1, 'days');

                for(i = 0; i < moves; i++){
                    $day_focus = day_start.add(1, 'days');
                    this.stats.planned[$day_focus.format("YYYY-MM-DD")] = 0;
                    this.stats.executed[$day_focus.format("YYYY-MM-DD")] = 0;
                    this.stats.withErrors[$day_focus.format("YYYY-MM-DD")] = 0;
                    this.dayList.push($day_focus.format("ddd MM-DD"));
                }



                var params = {
                    "return_task_cont": "N",
                    "outcome_executed_task_lst": "Y",
                    "interval_from": moment().subtract(this.dayBack, 'days').format("YYYY-MM-DD"),
                    "interval_to": moment().add(this.dayFront, 'days').format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task/",params)
                .then(function (response) {
                    if(response.data.length != 0){
                        for (i = 0; i < response.data.length; i++) {
                            task_data = response.data[i];

                            for (var task_data_start in task_data.interval_run_lst) {
                                for (var date_check in self.stats.planned) {
                                    if(task_data_start.substring(0, 10) == date_check){
                                        self.stats.planned[date_check] += 1;
                                    }
                                }
                            }

                            for (var task_data_exec in task_data.outcome_executed_task_lst) {

                                for (var date_check in self.stats.executed) {
                                    if(task_data_exec.substring(0, 10) == date_check){
                                        self.stats.executed[date_check] += 1;

                                        var task_out = task_data.outcome_executed_task_lst[task_data_exec];
                                        if(task_out != "OK"){
                                            self.stats.withErrors[date_check] += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    console.log(JSON.stringify(self.stats));

                    var config_graph_weekly = {
                        type: 'bar',
                        data: {
                            labels: self.dayList,

                            datasets: [{
                                label: 'Planned',
                                backgroundColor: "#AAAAAA",
                                borderColor: "#AAAAAA",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.stats.planned
                            }, {
                                label: 'Executed',
                                backgroundColor: "#BBBBBB",
                                borderColor: "#BBBBBB",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.stats.executed
                            }, {
                                label: 'With errors',
                                backgroundColor: "#CCCCCC",
                                borderColor: "#CCCCCC",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.stats.withErrors
                            }]
                        },
                        options: {
                            responsive: true,
                            legend: {
                                position: 'right'
                            }
                        }
                    };

                    var graph_container_weekly = document.getElementById('graph-area-weekly');
                    graphWeekly = new Chart(graph_container_weekly, config_graph_weekly);

                });

            }
        },

        mounted:function() {

            this.loadGraph();
        }
    }
</script>

<style>
</style>
