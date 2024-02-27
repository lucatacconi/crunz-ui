<template>
    <div>
        <v-card>
            <v-card-title>Log's directory usage</v-card-title>
            <v-card-text id="usage-data-area-container">
                <template v-if="calcStatExecuted">
                    <div>
                        <p>
                            Occupancy percentage of the partition where log folder resides.
                        </p>

                        <!-- Need to change color when over 80 (orange) and 90 (red) -->
                        <v-progress-linear
                            v-model="diskUsageData['used-space-percentage']"
                            height="30"
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
                                        <strong class="pl-3">({{ diskUsageData['total-log-size-yesterday'] }}</strong> {{ diskUsageData['unit'] }} - amount of logs collected yesterday)
                                    </span>
                                </template>
                            </p>
                        <div>

                        <!-- Need to be completed -->
                        lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum
                        lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum
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
