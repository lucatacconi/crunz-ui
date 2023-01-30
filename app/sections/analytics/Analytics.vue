<template>
    <div>
        <v-container>
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
            return {}
        },

        components: {
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
