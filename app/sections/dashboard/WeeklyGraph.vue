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

                stats: [],

                graphDaysLabel: [],
                graphPlanned: [],
                graphExecuted: [],
                graphWhitErrors: []
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadGraph: function(){

                var self = this;

                var moves = this.dayBack + this.dayFront + 1;
                var day_start = moment().subtract(this.dayBack + 1, 'days');

                for(i = 0; i < moves; i++){

                    day_focus_calc = day_start.add(1, 'days');

                    this.stats[i] = {
                        id_day: i,
                        date_ref: day_focus_calc.format("YYYY-MM-DD"),
                        planned: 0,
                        executed: 0,
                        with_errors: 0
                    }

                    day_label = day_focus_calc.format("ddd MM-DD");
                    this.graphDaysLabel.push(day_label);
                }

                var options = {
                    showLoading: false
                };

                var params = {
                    "return_task_cont": "N",
                    "outcome_executed_task_lst": "Y",
                    "interval_from": moment().subtract(this.dayBack, 'days').format("YYYY-MM-DD"),
                    "interval_to": moment().add(this.dayFront, 'days').format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task/",params, options)
                .then(function (response) {
                    if(response.data.length != 0){

                        for (i = 0; i < response.data.length; i++) {
                            task_data = response.data[i];

                            for (let task_data_start in task_data.interval_run_lst) {
                                for (key = 0; key < self.stats.length; key++) {
                                    if(task_data_start.substring(0, 10) == self.stats[key].date_ref){
                                        self.stats[key].planned += 1;
                                    }
                                }
                            }

                            for (let task_data_exec in task_data.outcome_executed_task_lst) {
                                for (key = 0; key < self.stats.length; key++) {
                                    if(task_data_exec.substring(0, 10) == self.stats[key].date_ref){
                                        self.stats[key].executed += 1;

                                        let task_out = task_data.outcome_executed_task_lst[task_data_exec];
                                        if(task_out != "OK"){
                                            self.stats[key].with_errors += 1;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    for (key = 0; key < self.stats.length; key++) {
                        self.graphPlanned.push(self.stats[key].planned);
                        self.graphExecuted.push(self.stats[key].executed);
                        self.graphWhitErrors.push(self.stats[key].with_errors);
                    }

                    var config_graph_weekly = {
                        type: 'bar',
                        data: {
                            labels: self.graphDaysLabel,

                            datasets: [{
                                label: 'Planned',
                                backgroundColor: "#6DCEE8",
                                borderColor: "#9199FE",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.graphPlanned
                            }, {
                                label: 'Executed',
                                backgroundColor: "#A7E683",
                                borderColor: "#5C9476",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.graphExecuted
                            }, {
                                label: 'With errors',
                                backgroundColor: "#FFA182",
                                borderColor: "#FF5074",
                                borderWidth: 1,
                                stack: 'Stack 0',
                                data: self.graphWhitErrors
                            }]
                        },
                        options: {
                            responsive: true,
                            legend: {
                                position: 'right'
                            }
                        }
                    };

                    let graph_container_weekly = document.getElementById('graph-area-weekly');
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
