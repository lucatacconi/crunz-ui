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

                var options = {
                    showLoading: false
                };

                var params = {
                    "interval_from": moment().format("YYYY-MM-DD"),
                    "interval_to": moment().format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task-stat/period",params, options)
                .then(function (response) {
                    if((typeof(response.data[moment().format("YYYY-MM-DD")]) !== 'undefined') && response.data[moment().format("YYYY-MM-DD")] != 0){

                        let day_stat = response.data[moment().format("YYYY-MM-DD")];

                        let planned_calc = day_stat.planned -( day_stat.succesfull + day_stat.error );

                        self.planned = planned_calc;
                        self.executed = day_stat.succesfull;
                        self.withErrors = day_stat.error;
                        self.succesfullNotPlanned = day_stat.succesfull_not_planned;
                        self.errorNotPlanned = day_stat.error_not_planned;
                    }

                    var config_graph_daily = {
                        type: 'pie',
                        data: {
                            labels: [ 'Planned', 'Executed', 'With errors', 'Executed not planned', 'Error in not planned' ],
                            datasets: [{
                                data: [ self.planned, self.executed, self.withErrors, self.succesfullNotPlanned, self.errorNotPlanned ],
                                label: 'Daily task\'s distribution',
                                backgroundColor: [ "#6DCEE8", "#A7E683", "#FFA182", "#FFD149", "#EA9EFF" ],
                                borderColor: [ "#9199FE", "#5C9476", "#FF5074", "#FF9D00", "#D84FFF" ],
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
                    var graphDaily = new Chart(graph_container_daily, config_graph_daily);

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
