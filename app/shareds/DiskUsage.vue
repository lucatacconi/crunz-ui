<template>
    <div>
        <v-card>
            <v-card-title>Log's disk or partition usage</v-card-title>
            <v-card-text id="usage-data-area-container">
                <template v-if="calcStatExecuted">
                    <div>
                        <p>
                            Occupancy percentage of disk or disk's partition where log folder resides:
                        </p>

                        <v-progress-linear
                            :value="diskUsageData['used-space-percentage']"
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
                                        <strong>{{ diskUsageData['partition-used-space'] }}</strong>
                                    </span>

                                    <span class="text-h5 text--primary">
                                       <strong>{{ diskUsageData['unit'] }} used </strong>
                                    </span>

                                    <span class="text-h4 text--gray">
                                        /
                                    </span>

                                    <span class="text-h4 text--gray">
                                        <strong>{{ diskUsageData['total-partition-size'] }}</strong>
                                    </span>

                                    <span class="text-h5 text--gray">
                                       <strong>{{ diskUsageData['unit'] }} tot.space</strong>
                                    </span>


                                    <div class="ml-3 text-h6 text--gray">

                                        (
                                            <strong>{{ diskUsageData['total-log-space-yesterday'] }}</strong> <span>{{ diskUsageData['unit'] }}</span> average logs per day
                                            <template v-if=" diskUsageData['day-left'] != '' && diskUsageData['day-left'] > 0 && diskUsageData['day-left'] <= 365 ">
                                                , {{ diskUsageData['day-left'] }} day/s left
                                            </template>
                                            <template v-else>
                                                , >365 day/s left
                                            </template>
                                        )
                                    </div>
                                </template>
                            </p>
                        <div>


                        The bar representing the percentage of log disk or disk's log partition occupancy will change color to identify an emergency situation.
                        The daily log size data will allow you to calculate the time available to fill the available space.
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
                    "unit": "AUTO"
                }

                Utils.apiCall("get", "/task-stat/log-partition-usage",params, options)
                .then(function (response) {
                    self.calcStatExecuted = true;
                    self.diskUsageData = response.data;

                    self.diskUsageData['unit'] = self.diskUsageData['unit'].toLowerCase();
                    self.diskUsageData['unit'] = self.diskUsageData['unit'][0].toUpperCase() + self.diskUsageData['unit'].slice(1);
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
