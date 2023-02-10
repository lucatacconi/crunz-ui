<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title class="grey lighten-4">
                System Uptime:
            </v-card-title>
            <v-card-text>
                <br>
                <p>
                    <template v-if="uptime['uptime-days'] === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <strong>{{ uptime['uptime-days'] }}</strong>
                        </span>
                        <span class="text-h5 text--gray">
                            day/s
                            <strong>({{ uptime['uptime-date'] }})</strong>
                        </span>
                    </template>
                </p>
                <div>
                    Days of system uptime calculated based on the date the first unarchived task was loaded and activated.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "uptime" : {
                    "uptime-date": false,
                    "uptime-days": false
                }
            }
        },

        methods: {
            uptimeInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/uptime",params, options)
                .then(function (response) {
                    self.uptime = response.data;
                });
            },
        },

        mounted:function() {
            this.uptimeInfo();
        }
    }
</script>

<style>
</style>
