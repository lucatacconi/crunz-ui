<template>
    <div>
        <v-card class="mt-2">
            <v-card-title>
                Environment settings and directories check
                <v-spacer></v-spacer>
                <v-btn
                    class="mx-2"
                    small
                    @click="readData()"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text>
                <v-form>
                    <v-container>
                        <v-row>
                            <v-col cols="12" class="py-0 pb-2">
                                <v-text-field
                                    label="Crunz directory position:"
                                    :value="env_check.TASK_POSITION_EMBEDDED_DESCR"
                                    readonly
                                    :hide-details="env_check.TASK_POSITION_EMBEDDED"
                                    persistent-hint
                                    hint="In Crunz-ui configuration with custom Crunz directory not embedded, remember to copy crunz-ui.sh and TasksTreeReader.php files into custom directory and use it in crontab instead of crunz.sh"
                                    :error-messages="!env_check.TASK_POSITION_EMBEDDED && !(env_check.CRUNZ_SH_PRESENCE && env_check.TREEREADER_PRESENCE) ? 'The following files crunz-ui.sh and TasksTreeReader.php missing in Crunz base directory. Copy crunz-ui.sh and TasksTreeReader.php files into your Crunz custom directory.' : '' "
                                >
                                    <v-icon v-if="env_check.CRUNZ_SH_PRESENCE && env_check.TREEREADER_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                    <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                </v-text-field>
                            </v-col>

                            <v-col cols="12" class=" pb-2">
                                <v-text-field
                                    label="Capability to perform tasks via web interface (Web server configuration):"
                                    :value=" (env_check.SHELL_EXEC_CAPABILITY) ? 'Web interface can run tasks.' : 'Web interface cannot run tasks. Tasks can only be scheduled and not manually executed.' "
                                    readonly
                                    :hide-details=" (env_check.SHELL_EXEC_CAPABILITY) "
                                    :error-messages="!(env_check.SHELL_EXEC_CAPABILITY) ? 'If, for safety reasons, the bash execution capability is disabled, tasks can only be scheduled.' : '' "
                                >
                                    <v-icon v-if="env_check.SHELL_EXEC_CAPABILITY" slot="append" color="green">mdi-check-bold</v-icon>
                                    <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                </v-text-field>
                            </v-col>

                            <v-col cols="6" class="py-0 pb-2">
                                <v-text-field
                                    label="Crunz YAML configuration file presence:"
                                    :value=" (env_check.YAML_CONFIG_PRESENCE) ? 'Crunz YAML configuration file present.' : 'Crunz YAML configuration file missing.' "
                                    readonly
                                    :hide-details=" (env_check.YAML_CONFIG_PRESENCE) "
                                    :error-messages="!(env_check.YAML_CONFIG_PRESENCE) ? 'Crunz Yaml configuration missing. To create a new copy of the configuration file use ./vendor/bin/crunz publish:config and follow the instructions.' : '' "
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
                                    :hide-details=" (env_check.YAML_CONFIG_CORRECTNESS) "
                                    :error-messages="!(env_check.YAML_CONFIG_PRESENCE) ? 'Crunz Yaml configuration error. Check crunz.yml file or recreate it using ./vendor/bin/crunz publish:config' : '' "
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
                                    :hide-details=" (env_check.YAML_CONFIG_SOURCE_PRESENCE) "
                                    :error-messages="!(env_check.YAML_CONFIG_SOURCE_PRESENCE) ? 'Check Crunz Yaml configuration file. Check task\'s container directory in source in crunz.yml.' : '' "
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
                                    :hide-details=" (env_check.YAML_CONFIG_SUFFIX_PRESENCE) "
                                    :error-messages="!(env_check.YAML_CONFIG_SUFFIX_PRESENCE) ? 'Check Crunz Yaml configuration file. Check suffix in crunz.yml.' : '' "
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
                                    :hide-details=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) "
                                    :error-messages="!(env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? 'Check Crunz Yaml configuration file. Check timezone in crunz.yml.' : '' "
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
                                    :hide-details=" (env_check.YAML_CONFIG_TIMEZONE_PRESENCE) "
                                    :error-messages="!(env_check.YAML_CONFIG_TIMEZONE_PRESENCE) ? 'Configure timezone in crunz.yml.' : '' "
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-2" v-if="!env_check.TASK_POSITION_EMBEDDED">
                                <v-text-field
                                    label="Tasks directory position:"
                                    :value="env_check.TASKS_DIR"
                                    readonly
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-2" v-if="!env_check.TASK_POSITION_EMBEDDED">
                                <v-text-field
                                    :label="'Crunz-ui.sh prensence in '+env_check.TASK_POSITION_EMBEDDED_DESCR +':'"
                                    :value="env_check.TASKS_DIR"
                                    readonly
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-2">
                                <v-text-field
                                    label="Tasks dir is present:"
                                    :value=" (env_check.TASKS_DIR_PRESENCE) ? 'Task\'s dir is present.' : 'Tasks dir missing.' "
                                    readonly
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
                                    :hide-details=" (env_check.TASKS_DIR_WRITABLE) "
                                    :error-messages="!(env_check.TASKS_DIR_WRITABLE) ? 'Check task\'s dir. If not writable check permitions.' : '' "
                                >
                                    <v-icon v-if="env_check.TASKS_DIR_WRITABLE" slot="append" color="green">mdi-check-bold</v-icon>
                                    <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                </v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0 pb-2" v-if="!env_check.TASK_POSITION_EMBEDDED">
                                <v-text-field
                                    label="Logs directory position:"
                                    :value="env_check.LOGS_DIR"
                                    readonly
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-2">
                                <v-text-field
                                    label="Logs path configured in Crunz-ui environment configuration file:"
                                    :value=" (env_check.LOGS_DIR_CONFIG_PRESENCE) ? 'Logs path correctly configured.' : 'Logs path configuration missing. Check LOGS_DIR in ./env file.' "
                                    readonly
                                    :hide-details=" (env_check.LOGS_DIR_CONFIG_PRESENCE) "
                                    :error-messages="!(env_check.LOGS_DIR_CONFIG_PRESENCE) ? 'Check Logs path dir. If not present Crunz-ui will not check log and execution output during execution.' : '' "
                                >
                                    <v-icon v-if="env_check.LOGS_DIR_CONFIG_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                    <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                </v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-2">
                                <v-text-field
                                    label="Logs dir is present:"
                                    :value=" (env_check.LOGS_DIR_PRESENCE) ? 'Logs path is present.' : 'Logs path missing.' "
                                    readonly
                                    :hide-details=" (env_check.LOGS_DIR_PRESENCE) "
                                    :error-messages="!(env_check.LOGS_DIR_PRESENCE) ? 'Check Logs\'s dir. If missing create it.' : '' "
                                >
                                    <v-icon v-if="env_check.LOGS_DIR_PRESENCE" slot="append" color="green">mdi-check-bold</v-icon>
                                    <v-icon v-else slot="append" color="red">mdi-alert-circle</v-icon>
                                </v-text-field>
                            </v-col>

                            <v-col cols="6" class="py-0 pb-2">
                                <v-text-field
                                    label="Logs dir is writable:"
                                    :value=" (env_check.LOGS_DIR_WRITABLE) ? 'Logs path is writable.' : 'Logs path is not writable.' "
                                    readonly
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
                env_check: {}
            }
        },

        methods: {
            readData:function(){
                var self = this;

                var options = {
                    showLoading: false
                };

                var params = {};
                self.message = "Loading environment check data";

                Utils.apiCall("get", "/environment/check", params, options)
                .then(function (response) {
                    if(response.data.length != 0){
                        self.env_check = response.data;

                        if(self.env_check.TASK_POSITION_EMBEDDED){
                            self.env_check.TASK_POSITION_EMBEDDED_DESCR = "Embedded";
                        }else{
                            self.env_check.TASK_POSITION_EMBEDDED_DESCR = self.env_check.TASK_DIR + " (Custom directory)";
                        }

                        self.$emit('environment-check', self.env_check);

                        localStorage.setItem("taskExecutionEnabled", self.env_check.SHELL_EXEC_CAPABILITY);

                    }else{
                        self.message = "Error reading environment check data";
                    }
                });
            }
        },

        mounted:function() {
            this.readData();
        }
    }
</script>

<style>
</style>
