<template>
    <div>
        <v-card>
            <v-card-title>Log's directory usage</v-card-title>
            <v-card-text id="usage-data-area-container">
                <canvas v-show="calcStatExecuted">



                </canvas>
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
                diskUsageData: {},
                calcStatExecuted: false
            }
        },

        props: ['environmentStatus'],

        methods: {
            loadUsageData: function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {
                }

                Utils.apiCall("get", "/task-stat/log-partition-usage",params, options)
                .then(function (response) {

                    self.calcStatExecuted = true;
                    self.diskUsageData = response.data;

                    // if(response.data.length != 0){
                    //     self.graphFree.push(self.diskUsageData['free-space-percentage']);
                    //     self.graphUsed.push(self.diskUsageData['used-space-percentage']);
                    // }

                    // self.graphLabel.push('Percentage occupancy status of the log directory');

                    // var config_graph_disk_usage = {
                    //     type: 'bar',
                    //     data: {
                    //         labels: self.graphLabel,

                    //         datasets: [
                    //             {
                    //                 label: 'Perc. used',
                    //                 backgroundColor: "#FFA182",
                    //                 borderColor: "#FF5074",
                    //                 borderWidth: 1,
                    //                 stack: 'Stack 0',
                    //                 data: self.graphUsed
                    //             }, {
                    //                 label: 'Free space left',
                    //                 backgroundColor: "#6DCEE8",
                    //                 borderColor: "#9199FE",
                    //                 borderWidth: 1,
                    //                 stack: 'Stack 0',
                    //                 data: self.graphFree
                    //             }
                    //         ]
                    //     },
                    //     options: {
                    //         responsive: true,
                    //         plugins: {
                    //             legend: {
                    //                 position: 'right'
                    //             }
                    //         }
                    //     }
                    // };

                    // let graph_container_disk_usage = document.getElementById('graph-area-disk-usage');
                    // graphDiskUsage = new Chart(graph_container_disk_usage, config_graph_disk_usage);
                });
            }
        },

        mounted:function() {
            this.loadUsageData();
        }
    }
</script>

<style>
    #usage-data-area-container {
        height: 320px;
    }
</style>
