<template>
    <div>
        <v-card>
            <v-card-title>Daily task's prospect</v-card-title>
            <v-card-text id="graph-area-daily-container">
                <canvas v-show="calcStatExecuted" id="graph-area-daily"></canvas>
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
                date: dayjs().format("YYYY-MM-DD"),
                planned: 0,
                executed: 0,
                withErrors: 0,
                calcStatExecuted: false
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadGraph: function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {
                    "interval_from": dayjs().format("YYYY-MM-DD"),
                    "interval_to": dayjs().format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task-stat/period",params, options)
                .then(function (response) {

                    self.calcStatExecuted = true;

                    if((typeof(response.data[dayjs().format("YYYY-MM-DD")]) !== 'undefined') && response.data[dayjs().format("YYYY-MM-DD")] != 0){

                        let day_stat = response.data[dayjs().format("YYYY-MM-DD")];

                        let planned_calc = day_stat.planned -( day_stat.succesfull + day_stat.error );

                        self.planned = planned_calc;
                        self.executed = day_stat.succesfull;
                        self.withErrors = day_stat.error;
                        self.succesfullNotPlanned = day_stat.succesfull_not_planned;
                        self.errorNotPlanned = day_stat.error_not_planned;
                        self.syntaxErrorTask = day_stat.syntax_error_task;
                    }

                    var config_graph_daily = {
                        type: 'pie',
                        data: {
                            labels: [ 'Planned', 'Executed', 'With errors', 'Executed not planned', 'Error in not planned', 'Syntax error in task file' ],
                            datasets: [{
                                data: [ self.planned, self.executed, self.withErrors, self.succesfullNotPlanned, self.errorNotPlanned, self.syntaxErrorTask ],
                                label: 'Daily task\'s distribution',
                                backgroundColor: [ "#6DCEE8", "#A7E683", "#FFA182", "#FFD149", "#EA9EFF", "#FF3333" ],
                                borderColor: [ "#9199FE", "#5C9476", "#FF5074", "#FF9D00", "#D84FFF", "#990000" ],
                                borderWidth: 1
                            }]
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

                    var graph_container_daily = document.getElementById('graph-area-daily');
                    graphDaily = new Chart(graph_container_daily, config_graph_daily);

                    if(self.syntaxErrorTask != 0){
                        Utils.showAlertDialog('Syntax error in task file','Check \'Tasks code inspector\' to fix issue','error');
                        return;
                    }
                });
            }
        },

        mounted:function() {
            this.loadGraph();
        }
    }
</script>

<style>

    #graph-area-daily {
        max-height: 300px;
    }
    #graph-area-daily-container {
        height: 320px;
    }

</style>
