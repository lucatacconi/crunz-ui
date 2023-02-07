<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title >
                System load average:
            </v-card-title>
            <v-card-text>
                <p>
                    <template v-if="sysload['load-1'] === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <strong>{{ sysload['load-1'] }}</strong>
                        </span>
                        <span class="text-h5 text--gray">
                            /1 min.
                            <br>
                            <strong>({{ sysload['load-5'] }}</strong>/5 min., <strong>{{ sysload['load-15'] }}</strong>/15 min.)
                        </span>
                    </template>
                </p>
                <div>
                    Returns three values representing the average system load (the number of processes in the system run queue) over the last 1, 5 and 15 minutes, respectively.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "sysload" : {
                    "load-1": false,
                    "load-5": false,
                    "load-15": false
                }
            }
        },

        methods: {
            sysloadInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/sys-load",params, options)
                .then(function (response) {
                    self.sysload = response.data;
                });
            },
        },

        mounted:function() {
            this.sysloadInfo();
        }
    }
</script>

<style>
</style>
