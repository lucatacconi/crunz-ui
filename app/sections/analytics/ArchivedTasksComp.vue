<template>
    <v-col xl="4" lg="6" md="6" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title >
                Archived tasks:
            </v-card-title>
            <v-card-text>
                <p>
                    <template v-if="archivedTasks['num-files'] === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <strong>{{ archivedTasks['num-files'] }}</strong>
                        </span>
                        <span class="text-h5 text--gray">
                            (<strong>{{ archivedTasks['files-size'] }}</strong> Kb)
                        </span>
                    </template>
                </p>
                <div>
                    Number of archived tasks present and total size of task files.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "archivedTasks" : {
                    "files-size": false,
                    "num-files": false
                }
            }
        },

        methods: {
            archivedTasksInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/archived-tasks",params, options)
                .then(function (response) {
                    self['archivedTasks'] = response.data;
                });
            }
        },

        mounted:function() {
            this.archivedTasksInfo();
        }
    }
</script>

<style>
</style>
