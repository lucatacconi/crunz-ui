<template>
    <div>

        <v-card class="mb-16">
            <v-card-title >
                Log directory manager
            </v-card-title>

            <v-card-text>

                <v-container>
                    <v-row>
                        <v-col xl="4" lg="4" md="12" sm="12" xs="12">
                            <v-card class="fill-height">
                                <v-card-title>
                                    Available free space:
                                </v-card-title>
                                <v-card-text>
                                    <br>
                                    <p>
                                        <template>
                                            <span class="text-h2 text--primary">
                                                <strong>{{ diskUsageData['partition-free-space'] }}<span>{{ diskUsageData['unit'] }}</span></strong>
                                            </span>
                                            <span class="text-h5 text--gray">
                                                /{{ diskUsageData['total-partition-size'] }}<span>{{ diskUsageData['unit'] }}</span>
                                            </span>
                                        </template>
                                    </p>
                                    <div>
                                        Represents the amount of total GB available on the disk or partition where the log folder resides.
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col xl="4" lg="4" md="12" sm="12" xs="12">
                            <v-card class="fill-height">
                                <v-card-title>
                                    Logs size:
                                </v-card-title>
                                <v-card-text>
                                    <br>
                                    <p>
                                        <template>
                                            <span class="text-h2 text--primary">
                                                <strong>{{ diskUsageData['partition-used-space'] }}<span>{{ diskUsageData['unit'] }}</span></strong>
                                            </span>
                                        </template>
                                    </p>
                                    <div>
                                        Represents the amount of total GB occupied by the logs generated and saved on the system and system files on the disk or partition where the log folder resides.
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col xl="4" lg="4" md="12" sm="12" xs="12">
                            <v-card class="fill-height">
                                <v-card-title>
                                    Occupied log size per day:
                                </v-card-title>
                                <v-card-text>
                                    <br>
                                    <p>
                                        <template>
                                            <span class="text-h2 text--primary">
                                                <strong>{{ diskUsageData['total-log-space-yesterday'] }}<span>{{ diskUsageData['unit'] }}</span></strong>
                                            </span>
                                            <span class="text-h5 text--gray" v-if="diskUsageData['day-left'] == ''">
                                                /-- day left
                                            </span>
                                            <span class="text-h5 text--gray" v-if="diskUsageData['day-left'] > 0">
                                                /{{ diskUsageData['day-left'] }} day left
                                            </span>
                                        </template>
                                    </p>
                                    <div>
                                        Represents the average amount of logs that are generated daily. It provides useful data to avoid completely occupying the disk or partition used for logs.
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <p>
                                Occupancy percentage of disk or partition where log folder resides:
                            </p>

                            <v-progress-linear
                                :value="diskUsageData['used-space-percentage']"
                                height="30"
                                :color="progressBarColor(diskUsageData['used-space-percentage'])"
                            >
                                <strong>{{ Math.ceil(diskUsageData['used-space-percentage']) }}%</strong>
                            </v-progress-linear>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <strong>
                                Selective removal of log files (older than 3, 6, 9 or 12 months):
                            </strong>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="4">
                            <v-select
                                label="Selective log removal"
                                outlined
                                v-model="obsoleteRange"

                                :items="items"
                                :item-text="'rangeDescr'"
                                :item-value="'rangeId'"

                                required
                            >
                            </v-select>
                        </v-col>
                        <v-col cols="4">
                            <v-btn
                                x-large
                                outlined
                                color="red"
                                @click="deleteLogs()"
                            >
                                Delete obsolete logs
                            </v-btn>
                        </v-col>
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            diskUsageData: {},
            calcStatExecuted: true,
            obsoleteRange: 3,

            items:[
                { rangeId: 3, rangeDescr: "Older than 3 months" },
                { rangeId: 6, rangeDescr: "Older than 6 months" },
                { rangeId: 9, rangeDescr: "Older than 9 months" },
                { rangeId: 12, rangeDescr: "Older than 12 months" }
            ]
        }
    },
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
        },

        deleteLogs: function () {
            var self=this;

            Utils.showAlertDialog(
                'Delete obsolete logs',
                'Are you sure you want to remove obsolete logs? Logs older than ' + self.obsoleteRange + ' months will be deleted.',
                'warning',{
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                },()=>{
                var params = {
                    "older_than": self.obsoleteRange
                }

                Utils.apiCall("delete", "/task-stat/obsolete-logs",params)
                .then(function (response) {
                    if(response.data.result){
                        Utils.showAlertDialog('Obsolete logs deleted',response.data.result_msg,'success');
                        self.readData();
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });
            });
        }
    },

    mounted:function() {
        this.loadUsageData();
    }
}
</script>
