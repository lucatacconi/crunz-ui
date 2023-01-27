<template>
    <div>
        <v-card>
            <v-card-title>Weekly task's prospect</v-card-title>
            <v-card-text id="graph-area-weekly-container">
                <canvas v-show="calcStatExecuted" id="graph-area-weekly"></canvas>
                <v-progress-circular
                :size="100"
                :width="7"
                color="blue-grey"
                indeterminate
                v-show="!calcStatExecuted"
                ></v-progress-circular>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                dayBack: 5,
                dayFront: 1,

                stats: [],

                graphDaysLabel: [],
                graphPlanned: [],
                graphExecuted: [],
                graphWhitErrors: [],
                graphExecutedNotPlanned: [],
                graphErrorsNotPlanned: [],
                graphSyntaxError: [],

                calcStatExecuted: false
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadGraph: function(){

                var self = this;

                var moves = this.dayBack + this.dayFront + 1;
                var day_start = dayjs().subtract(this.dayBack + 1, 'days');

                day_focus_calc = day_start;
                for(i = 0; i < moves; i++){

                    day_focus_calc = day_focus_calc.add(1, 'days');

                    this.stats[i] = {
                        id_day: i,
                        date_ref: day_focus_calc.format("YYYY-MM-DD"),
                        planned: 0,
                        executed: 0,
                        with_errors: 0,
                        executed_not_planned: 0,
                        errors_not_planned: 0,
                        syntax_error_task: 0
                    }

                    day_label = day_focus_calc.format("ddd MM-DD");
                    this.graphDaysLabel.push(day_label);
                }

                var options = {
                    showLoading: false
                };

                var params = {
                    "interval_from": dayjs().subtract(this.dayBack, 'days').format("YYYY-MM-DD"),
                    "interval_to": dayjs().add(this.dayFront, 'days').format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task-stat/period",params, options)
                .then(function (response) {

                    self.calcStatExecuted = true;

                    if(response.data.length != 0){

                        for (let  stat_data of self.stats) {
                            let key = stat_data.id_day;
                            let planned_calc = response.data[stat_data.date_ref].planned - (response.data[stat_data.date_ref].succesfull + response.data[stat_data.date_ref].error)

                            self.stats[key].planned = planned_calc;
                            self.stats[key].executed = response.data[stat_data.date_ref].succesfull;
                            self.stats[key].with_errors = response.data[stat_data.date_ref].error;
                            self.stats[key].executed_not_planned = response.data[stat_data.date_ref].succesfull_not_planned;
                            self.stats[key].errors_not_planned = response.data[stat_data.date_ref].error_not_planned;
                            self.stats[key].syntax_error_task = response.data[stat_data.date_ref].syntax_error_task;
                        }
                    }

                    for (key = 0; key < self.stats.length; key++) {
                        self.graphPlanned.push(self.stats[key].planned);
                        self.graphExecuted.push(self.stats[key].executed);
                        self.graphWhitErrors.push(self.stats[key].with_errors);
                        self.graphExecutedNotPlanned.push(self.stats[key].executed_not_planned);
                        self.graphErrorsNotPlanned.push(self.stats[key].errors_not_planned);
                        self.graphSyntaxError.push(self.stats[key].syntax_error_task);
                    }

                    var config_graph_weekly = {
                        type: 'bar',
                        data: {
                            labels: self.graphDaysLabel,

                            datasets: [
                                {
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
                                }, {
                                    label: 'Executed not planned',
                                    backgroundColor: "#FFD149",
                                    borderColor: "#FF9D00",
                                    borderWidth: 1,
                                    stack: 'Stack 0',
                                    data: self.graphExecutedNotPlanned
                                }, {
                                    label: 'Error in not planned',
                                    backgroundColor: "#EA9EFF",
                                    borderColor: "#D84FFF",
                                    borderWidth: 1,
                                    stack: 'Stack 0',
                                    data: self.graphErrorsNotPlanned
                                }, {
                                    label: 'Syntax error in task file',
                                    backgroundColor: "#FF3333",
                                    borderColor: "#990000",
                                    borderWidth: 1,
                                    stack: 'Stack 0',
                                    data: self.graphSyntaxError
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
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

    #graph-area-weekly {
        max-height: 300px;
    }
    #graph-area-weekly-container {
        height: 320px;
    }

</style>
