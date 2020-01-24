<template>
    <div>
        <v-card>
            <v-card-text class="pt-5">

                <v-form>
                    <v-container>

                        <v-row>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Task position:"
                                    :value=" taskPositionInfo "
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Path:"
                                    :value="env_check.YAML_CONFIG_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                    append-icon="close"
                                >
                                    <v-icon slot="append" color="green">mdi-minus</v-icon>
                                </v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.YAML_CONFIG_CORRECTNESS"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.YAML_CONFIG_SOURCE_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.YAML_CONFIG_SUFFIX_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.YAML_CONFIG_TIMEZONE_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.TIMEZONE_CONFIG"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.TASKS_DIR_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.TASKS_DIR_WRITABLE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.LOGS_DIR_CONFIG_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.LOGS_DIR_PRESENCE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="env_check.LOGS_DIR_WRITABLE"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
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
                    }else{
                        self.message = "Error reading environment check data";
                    }
                });
            }
        },

        computed: {
            taskPositionInfo: function () {




                // return this.message.split('').reverse().join('')

                return "Ciao";
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
