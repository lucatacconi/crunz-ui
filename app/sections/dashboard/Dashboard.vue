<template>
    <div>
        <v-card>
            <v-card-text class="pt-5">

                {{ env_check.YAML_CONFIG_PRESENCE }}
                {{ env_check.YAML_CONFIG_CORRECTNESS }}
                {{ env_check.YAML_CONFIG_SOURCE_PRESENCE }}
                {{ env_check.YAML_CONFIG_SUFFIX_PRESENCE }}
                {{ env_check.YAML_CONFIG_TIMEZONE_PRESENCE }}
                {{ env_check.TIMEZONE_CONFIG }}
                {{ env_check.TASKS_DIR_PRESENCE }}
                {{ env_check.TASKS_DIR_WRITABLE }}
                {{ env_check.LOGS_DIR_CONFIG_PRESENCE }}
                {{ env_check.LOGS_DIR_PRESENCE }}
                {{ env_check.LOGS_DIR_WRITABLE }}

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
