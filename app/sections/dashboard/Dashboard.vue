<template>
    <div>
        <v-card>
            <v-card-title>Unlimited music now</v-card-title>
            <v-card-text>
            </v-card-text>
        </v-card>

        <v-card class="mt-5">
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
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
                env_check: {},
                message: ""
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

                    }else{
                        self.message = "Error reading environment check data";
                    }
                });
            }
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
