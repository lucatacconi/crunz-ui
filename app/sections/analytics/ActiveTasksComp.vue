<template>
    <v-col xl="4" lg="6" md="12" sm="12" xs="12">
        <v-card class="fill-height">
            <v-card-title :class="getTheme ? 'grey darken-3' : 'brown lighten-4'">
                Active tasks:
            </v-card-title>
            <v-card-text>
                <br>
                <p>
                    <template v-if="activeTasks['num-files'] === false">
                        <v-progress-circular
                        :size="50"
                        :width="7"
                        color="blue-grey"
                        indeterminate
                        ></v-progress-circular>
                    </template>

                    <template v-else>
                        <span class="text-h2 text--primary">
                            <strong>{{ activeTasks['num-files'] }}</strong>
                        </span>
                        <span class="text-h5 text--gray">
                            (<strong>{{ activeTasks['files-size'] }}</strong> Kb)
                        </span>
                    </template>
                </p>
                <div>
                    Number of active tasks present and total size of task files.
                </div>
            </v-card-text>
        </v-card>
    </v-col>
</template>

<script>
    module.exports = {
        data:function(){
            return{
                "activeTasks" : {
                    "files-size": false,
                    "num-files": false
                }
            }
        },

        computed:{
            getTheme:function(){
                var self = this;
                return self.$vuetify.theme.dark
            }
        },

        methods: {
            activeTasksInfo:function(){

                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {}

                Utils.apiCall("get", "/task-stat/active-tasks",params, options)
                .then(function (response) {
                    self['activeTasks'] = response.data;
                });
            }
        },

        mounted:function() {
            this.activeTasksInfo();
        }
    }
</script>

<style>
</style>
