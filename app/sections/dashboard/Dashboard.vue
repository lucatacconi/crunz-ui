<template>
    <div>
        <v-container>
            <v-row class="pa-0" v-if="environmentStatus.ALL_CHECK">
                <v-col lg="6" md="12" class="pl-lg-0 pr-lg-1 mx-md-0 px-md-0">
                    <dailygraph :environmentStatus="environmentStatus"></dailygraph>
                </v-col>
                <v-col lg="6" md="12" class="pr-lg-0 pl-lg-1 mx-md-0 px-md-0">
                    <weeklygraph :environmentStatus="environmentStatus"></weeklygraph>
                </v-col>
            </v-row>

            <v-row>
                <environmentcheck v-on:environment-check="environmentStatus = $event"></environmentcheck>
            </v-row>
        </v-container>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                environmentStatus: {}
            }
        },

        components: {
            'dailygraph': httpVueLoader('../../../app/sections/dashboard/DailyGraph.vue' + '?v=' + new Date().getTime()),
            'weeklygraph': httpVueLoader('../../../app/sections/dashboard/WeeklyGraph.vue' + '?v=' + new Date().getTime()),
            'environmentcheck': httpVueLoader('../../../app/sections/dashboard/EnvironmentCheck.vue' + '?v=' + new Date().getTime())
        }
    }
</script>

<style>
</style>
