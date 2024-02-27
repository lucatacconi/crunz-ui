<template>
    <div>
        <v-card>
            <v-card-title>Log's directory usage</v-card-title>
            <v-card-text id="usage-data-area-container">
                <template v-if="calcStatExecuted">
                    <div>
                        <p>
                            Occupancy percentage of disk's partition where log folder resides.
                        </p>

                        <v-progress-linear
                            v-model="diskUsageData['used-space-percentage']"
                            height="30"
                            :color="progressBarColor(diskUsageData['used-space-percentage'])"
                        >
                            <strong>{{ Math.ceil(diskUsageData['used-space-percentage']) }}%</strong>
                        </v-progress-linear>

                        </div>
                            <br><br>
                            <p>
                                <template>
                                    <span class="text-h2 text--primary">
                                        <strong>{{ diskUsageData['partition-used-space'] }} {{ diskUsageData['unit'] }}.</strong>
                                    </span>
                                    <span class="text-h5 text--gray">
                                        /{{ diskUsageData['total-partition-size'] }} {{ diskUsageData['unit'] }}
                                    </span>
                                    <span class="text-h6 text--gray">
                                        <br>
                                        (
                                            <strong>{{ diskUsageData['total-log-space-yesterday'] }}</strong> {{ diskUsageData['unit'] }} amount of logs collected yesterday
                                            <template v-if=" diskUsageData['day-left'] != '' && diskUsageData['day-left'] > 0 && diskUsageData['day-left'] <= 365 ">
                                                , {{ diskUsageData['day-left'] }} day/s left.
                                            </template>
                                        )
                                    </span>
                                </template>
                            </p>
                        <div>

                        The bar representing the percentage of disk occupancy will change color to identify an emergency situation.
                        <br>
                        The daily log size data will allow you to calculate the time available to fill the disk
                    </div>
                </template>

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
                calcStatExecuted: true
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
                    "unit": "AUTO"
                }

                Utils.apiCall("get", "/task-stat/log-partition-usage",params, options)
                .then(function (response) {
                    self.calcStatExecuted = true;
                    self.diskUsageData = response.data;
                });
            },

            progressBarColor(percentage) {
                if (percentage > 90) {
                    return 'red';
                } else if (percentage > 80) {
                    return 'orange';
                } else {
                    return 'primary';
                }
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
