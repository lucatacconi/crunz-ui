<template>
    <div>
        <v-container>
            <v-row class="pa-0" v-if="environmentStatus.ALL_CHECK">
                <v-col lg="6" md="12" class="pl-lg-0 pr-lg-1 mx-md-0 px-md-0">
                    <dailygraph :environmentStatus="environmentStatus"></dailygraph>
                </v-col>
                <v-col lg="6" md="12" class="pr-lg-0 pl-lg-1 mx-md-0 px-md-0">
                    <diskusage :environmentStatus="environmentStatus"></diskusage>
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
            'dailygraph': httpVueLoader('../../shareds/DailyGraph.vue' + '?v=' + new Date().getTime()),
            'diskusage': httpVueLoader('../../shareds/DiskUsage.vue' + '?v=' + new Date().getTime()),
            'environmentcheck': httpVueLoader('../../shareds/EnvironmentCheck.vue' + '?v=' + new Date().getTime())
        }
    }
</script>

<style>
</style>
