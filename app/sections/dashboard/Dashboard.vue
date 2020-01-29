<template>
    <div>
        <v-container>
            <v-row v-if="env_check.ALL_CHECK" class="pa-0">
                <v-col md="6" sm="12" class="pl-md-0 pr-md-1 mx-sm-0 px-sm-0">
                    <v-card>
                        <v-card-title>Daily task's prospect</v-card-title>
                        <v-card-text>
                            <canvas id="graph-area-1" height="100"></canvas>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col md="6" sm="12" class="pr-md-0 pl-md-1 mx-sm-0 px-sm-0">
                    <v-card>
                        <v-card-title>Weekly task's prospect</v-card-title>
                        <v-card-text>
                            <canvas id="graph-area-2" height="100"></canvas>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-row>
                <v-card class="mt-2">
                    <v-card-title>Environment settings and directoris check</v-card-title>
                    <v-card-text>
                        <v-form>
                            <v-container>
                                <v-row>
                                    <v-col cols="12" class="py-0 pb-2">
                                        <v-text-field
                                            label="Task position:"
                                            :value="env_check.TASK_POSITION_EMBEDDED_DESCR"
                                            readonly
                                            dense
                                            hide-details
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Crunz YAML configuration file presence:"
                                            :value=" (env_check.YAML_CONFIG_PRESENCE) ? 'Crunz YAML configuration file present.' : 'Crunz YAML configuration file missing.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_PRESENCE) "
                                            :error-messages="!(env_check.YAML_CONFIG_PRESENCE) ? 'Crunz Yaml configuration missing. To create a new copy of the configuration file use <strong>./vendor/bin/crunz publish:config</strong> and follow the instructions.' : '' "
                                        >
                                            <v-icon v-if="env_check.YAML_CONFIG_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Crunz YAML configuration file correctness:"
                                            :value=" (env_check.YAML_CONFIG_CORRECTNESS) ? 'Crunz YAML correctly configured.' : 'Errors present in Crunz YAML configuration file.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_CORRECTNESS) "
                                            :error-messages="!(env_check.YAML_CONFIG_PRESENCE) ? 'Crunz Yaml configuration error. Check crunz.yml file or recreate it using <strong>./vendor/bin/crunz publish:config</strong>.' : '' "
                                        >
                                            <v-icon v-if="env_check.YAML_CONFIG_CORRECTNESS" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                    </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Task path configured in Crunz YAML configuration file:"
                                            :value=" (env_check.YAML_CONFIG_SOURCE_PRESENCE) ? 'Task path correctly configured (./'+env_check.YAML_CONFIG_SOURCE+').' : 'Errors in task\'s path configuration in Crunz YAML configuration file.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_SOURCE_PRESENCE) "
                                            :error-messages="!(env_check.YAML_CONFIG_SOURCE_PRESENCE) ? 'Check Crunz Yaml configuration file. Check task\'s container directory in <strong>source</strong> in crunz.yml.' : '' "
                                        >
                                            <v-icon v-if="env_check.YAML_CONFIG_SOURCE_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Task suffix configured in Crunz YAML configuration file:"
                                            :value=" (env_check.YAML_CONFIG_SUFFIX_PRESENCE) ? 'Suffix correctly configured ('+env_check.YAML_CONFIG_SUFFIX+').' : 'Errors in task\'s suffix configuration in Crunz YAML configuration file.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_SUFFIX_PRESENCE) "
                                            :error-messages="!(env_check.YAML_CONFIG_SUFFIX_PRESENCE) ? 'Check Crunz Yaml configuration file. Check <strong>suffix</strong> in crunz.yml.' : '' "
                                        >
                                            <v-icon v-if="env_check.YAML_CONFIG_SUFFIX_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Time zone configured in Crunz YAML configuration file:"
                                            :value=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? 'Time zone correctly configured.' : 'Errors in time zone configuration in Crunz YAML configuration file.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) "
                                            :error-messages="!(env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? 'Check Crunz Yaml configuration file. Check <strong>timezone</strong> in crunz.yml.' : '' "
                                        >
                                            <v-icon v-if="env_check.YAML_CONFIG_TIMEZONE_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Time zone configured:"
                                            :value=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? env_check.TIMEZONE_CONFIG : '--' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) "
                                            :error-messages="!(env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? 'Configure <strong>timezone</strong> in crunz.yml.' : '' "
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Tasks dir is present:"
                                            :value=" (env_check.TASKS_DIR_PRESENCE) ? 'Task\'s dir is present.' : 'Tasks dir missing.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.TASKS_DIR_PRESENCE) "
                                            :error-messages="!(env_check.TASKS_DIR_PRESENCE) ? 'Check task\'s dir. If missing create it.' : '' "
                                        >
                                            <v-icon v-if="env_check.TASKS_DIR_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Tasks dir is writable:"
                                            :value=" (env_check.TASKS_DIR_WRITABLE) ? 'Task\'s dir is writable.' : 'Tasks dir is not writable.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.TASKS_DIR_WRITABLE) "
                                            :error-messages="!(env_check.TASKS_DIR_WRITABLE) ? 'Check task\'s dir. If not writable check permitions.' : '' "
                                        >
                                            <v-icon v-if="env_check.TASKS_DIR_WRITABLE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0 pb-2">
                                        <v-text-field
                                            label="Logs path configured in Crunz-ui environment configuration file:"
                                            :value=" (env_check.LOGS_DIR_CONFIG_PRESENCE) ? 'Logs path correctly configured.' : 'Logs path configuration missing. Check <strong>LOGS_DIR</strong> in ./env file.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.LOGS_DIR_CONFIG_PRESENCE) "
                                            :error-messages="!(env_check.LOGS_DIR_CONFIG_PRESENCE) ? 'Check Logs path dir. If not present Crunz-ui will not check log and execution output during execution.' : '' "
                                        >
                                            <v-icon v-if="env_check.LOGS_DIR_CONFIG_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                    <v-col cols="6" class="py-0  pb-2">
                                        <v-text-field
                                            label="Logs dir is present:"
                                            :value=" (env_check.LOGS_DIR_PRESENCE) ? 'Logs path is present.' : 'Logs path missing.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.LOGS_DIR_PRESENCE) "
                                            :error-messages="!(env_check.LOGS_DIR_PRESENCE) ? 'Check Logs\'s dir. If missing create it.' : '' "
                                        >
                                            <v-icon v-if="env_check.LOGS_DIR_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>

                                    <v-col cols="6" class="py-0">
                                        <v-text-field
                                            label="Logs dir is writable:"
                                            :value=" (env_check.LOGS_DIR_WRITABLE) ? 'Logs path is writable.' : 'Logs path is not writable.' "
                                            readonly
                                            dense
                                            :hide-details=" (env_check.LOGS_DIR_WRITABLE) "
                                            :error-messages="!(env_check.LOGS_DIR_WRITABLE) ? 'Check log\'s dir. If not writable check permitions.' : '' "
                                        >
                                            <v-icon v-if="env_check.LOGS_DIR_WRITABLE" slot="append" color="green">mdi-check-bold</v-icon>
                                            <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                        </v-text-field>
                                    </v-col>
                                </v-row>
                            </v-container>
                        <v-form>
                    </v-card-text>
                </v-card>
            </v-row>
        </v-container>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                "env_check": {},
                "message": "",
                "stats": {
                    "daily":{
                        "planned": 0,
                        "executed": 0,
                        "failed": 0
                    }
                }
            }
        },
        methods: {
            readData:function(){
                var self = this;
                var params = {};
                self.message = "Loading environment check data";

                Utils.apiCall("get", "/environment/check",params)
                .then(function (response) {
                    if(response.data.length != 0){
                        self.env_check = response.data;

                        if(self.env_check.TASK_POSITION_EMBEDDED){
                            self.env_check.TASK_POSITION_EMBEDDED_DESCR = "Embedded";
                        }else{
                            self.env_check.TASK_POSITION_EMBEDDED_DESCR = "Custom directory ("+this.env_check.TASK_DIR+")";
                        }

                        if(self.env_check.ALL_CHECK){


                            setTimeout(
                                function(){
                                    self.stat();
                                    // self.graphWeekly();
                                }, 200
                            );




                            // setTimeout(
                            //     function(){
                            //         self.graphDaily();
                            //         self.graphWeekly();
                            //     }, 200
                            // );
                        }

                    }else{
                        self.message = "Error reading environment check data";
                    }
                });
            },

            stat:function(){
                var self = this;
                var params = {
                    "return_task_cont": "N",
                    "calc_run_lst": "Y",
                    "show_past_planned_task": "Y",
                    "interval_from": moment().subtract(3, 'days').format("YYYY-MM-DD"),
                    "interval_to": moment().add(3, 'days').format("YYYY-MM-DD")
                }

                Utils.apiCall("get", "/task/",params)
                .then(function (response) {
                    if(response.data.length != 0){
                        for (i = 0; i < response.data.length; i++) {
                            task_data = response.data[i];

                            console.log(JSON.stringify(task_data));

                            for (var task_data_start in task_data.interval_run_lst) {
                                if(task_data_start.substring(0, 10) == moment().format("YYYY-MM-DD")){
                                    self.stats.daily.planned += 1;
                                }
                            }

                            for (var task_data_exec in task_data.executed_task_lst) {
                                if(task_data_exec.substring(0, 10) == moment().format("YYYY-MM-DD")){
                                    self.stats.daily.executed += 1;
                                }
                            }
                        }

                    }else{

                    }




                    console.log(JSON.stringify(self.stats));



                    // console.log(JSON.stringify(response));
                    //  console.log(JSON.stringify(params));
                });


            },

            graphDaily:function(){
                var self = this;
                var config_graph_daily = {
                    type: 'pie',
                    data: {
                        labels: [ 'Planned', 'Executed', 'Errors' ],
                        datasets: [{
                            data: [ 100, 200, 300 ],
                            label: 'Daily task\'s distribution',
                            backgroundColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'right'
                        }
                    }
                };

                var graph_container_daily = document.getElementById('graph-area-1');
                graphDaily = new Chart(graph_container_daily, config_graph_daily);
            },

            graphWeekly:function(){
                var self = this;
                var config_graph_weekly = {
                    type: 'bar',
                    data: {
                        labels: [ 'Planned', 'Executed', 'Errors' ],
                        datasets: [{
                            data: [ 100, 200, 300 ],
                            label: 'Daily task\'s distribution',
                            backgroundColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderColor: [ "#AAAAAA", "#BBBBBB", "#CCCCCC" ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'right'
                        }
                    }
                };

                var graph_container_weekly = document.getElementById('graph-area-2');
                graphWeekly = new Chart(graph_container_weekly, config_graph_weekly);
            },
        },

        created:function() {
            this.readData();
        },
        mounted:function(){
            var self = this;
            setInterval(function(){ self.readData(); }, 60000);
        }
    }
</script>

<style>
</style>
