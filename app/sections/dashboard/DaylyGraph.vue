<template>
    <div>
        <v-card>
            <v-card-title>Daily task's prospect</v-card-title>
            <v-card-text>
                <canvas id="graph-area-daily" height="100"></canvas>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                date: moment().format("YYYY-MM-DD"),
                planned: 0,
                executed: 0,
                withErrors: 0
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadGraph: function(){

                var self = this;
                var params = {
                    "return_task_cont": "N",
                    "outcome_executed_task_lst": "Y",
                    "interval_from": moment().format("YYYY-MM-DD"),
                    "interval_to": moment().format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task/",params)
                .then(function (response) {
                    if(response.data.length != 0){
                        for (i = 0; i < response.data.length; i++) {
                            task_data = response.data[i];

                            for (var task_data_start in task_data.interval_run_lst) {
                                if(task_data_start.substring(0, 10) == moment().format("YYYY-MM-DD")){
                                    self.planned += 1;
                                }
                            }

                            for (var task_data_exec in task_data.outcome_executed_task_lst) {
                                if(task_data_exec.substring(0, 10) == moment().format("YYYY-MM-DD")){
                                    self.executed += 1;

                                    var task_out = task_data.outcome_executed_task_lst[task_data_exec];
                                    if(task_out != "OK"){
                                        self.withErrors += 1;
                                    }
                                }
                            }
                        }
                    }


                    var config_graph_daily = {
                        type: 'pie',
                        data: {
                            labels: [ 'Planned', 'Executed', 'With errors' ],
                            datasets: [{
                                data: [ self.planned, self.executed, self.withErrors ],
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

                    var graph_container_daily = document.getElementById('graph-area-daily');
                    graphDaily = new Chart(graph_container_daily, config_graph_daily);

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
