<template>
    <div>
        <v-container>

            <v-row class="pa-0">
                <v-col lg="6" md="12" class="pl-lg-0 pr-lg-1 mx-md-0 px-md-0">
                    <dailygraph :environmentStatus="environmentStatus"></dailygraph>
                </v-col>
                <v-col lg="6" md="12" class="pr-lg-0 pl-lg-1 mx-md-0 px-md-0">
                    <weeklygraph :environmentStatus="environmentStatus"></weeklygraph>
                </v-col>
            </v-row>

            <v-row class="pa-0">
                <systemuptime></systemuptime>
                <sysloadcomp></sysloadcomp>
                <activetaskscomp></activetaskscomp>
                <archivedtaskscomp></archivedtaskscomp>
                <executedtaskscomp></executedtaskscomp>
                <executedtaskserrorcomp></executedtaskserrorcomp>
                <executionlogscomp></executionlogscomp>
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

            'dailygraph': httpVueLoader('../../shareds/DailyGraph.vue' + '?v=' + new Date().getTime()),
            'weeklygraph': httpVueLoader('../../shareds/WeeklyGraph.vue' + '?v=' + new Date().getTime()),

            'systemuptime': httpVueLoader('../../../app/sections/analytics/SystemUptimeComp.vue' + '?v=' + new Date().getTime()),
            'activetaskscomp': httpVueLoader('../../../app/sections/analytics/ActiveTasksComp.vue' + '?v=' + new Date().getTime()),
            'archivedtaskscomp': httpVueLoader('../../../app/sections/analytics/ArchivedTasksComp.vue' + '?v=' + new Date().getTime()),
            'executionlogscomp': httpVueLoader('../../../app/sections/analytics/ExecutionLogsComp.vue' + '?v=' + new Date().getTime()),
            'executedtaskscomp': httpVueLoader('../../../app/sections/analytics/ExecutedTasksComp.vue' + '?v=' + new Date().getTime()),
            'executedtaskserrorcomp': httpVueLoader('../../../app/sections/analytics/ExecutedTasksErrorComp.vue' + '?v=' + new Date().getTime()),
            'sysloadcomp': httpVueLoader('../../../app/sections/analytics/SysLoadComp.vue' + '?v=' + new Date().getTime()),
        }
    }
</script>

<style>
</style>
