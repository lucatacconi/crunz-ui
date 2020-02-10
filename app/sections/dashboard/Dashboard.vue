<template>
    <div>
        <v-container>
            <v-row class="pa-0" v-if="environmentStatus.ALL_CHECK">
                <v-col md="6" sm="12" class="pl-md-0 pr-md-1 mx-sm-0 px-sm-0">
                    <dailygraph :environmentStatus="environmentStatus"></dailygraph>
                </v-col>
                <v-col md="6" sm="12" class="pr-md-0 pl-md-1 mx-sm-0 px-sm-0">
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
            'dailygraph': httpVueLoader('../../../app/sections/dashboard/DaylyGraph.vue' + '?v=' + new Date().getTime()),
            'weeklygraph': httpVueLoader('../../../app/sections/dashboard/WeeklyGraph.vue' + '?v=' + new Date().getTime()),
            'environmentcheck': httpVueLoader('../../../app/sections/dashboard/EnvironmentCheck.vue' + '?v=' + new Date().getTime())
        }
    }
</script>

<style>
</style>
