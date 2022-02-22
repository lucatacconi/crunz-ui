<template>
    <v-dialog :value="true" persistent max-width="1185px" height="500px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="blue-grey"
            >
                <v-toolbar-title>
                    Task execution log
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn
                        icon
                        @click="closeModal()"
                    >
                        <v-icon>
                            close
                        </v-icon>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text class="pt-0">
                <v-container>

                    <v-row>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Path:"
                                :value="logdata.path"
                                readonly
                                hide-details
                            ></v-text-field>
                        </v-col>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Execution date and time:"
                                :value="logdata.execution"
                                readonly
                                hide-details
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Duration (minutes):"
                                :value="( logdata.duration == 0 ? '&lt;1' : logdata.duration )"
                                readonly
                                hide-details
                            ></v-text-field>
                        </v-col>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Execution outcome:"
                                :value="( logdata.outcome == 'OK' ? 'Success' : 'Failed')"
                                readonly
                                hide-details
                                :error="( logdata.outcome == 'OK' ? false : true)"
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col class="py-0 pt-5" cols="12">
                            <v-card
                                outlined
                            >
                                <v-toolbar
                                    dense
                                    flat
                                    tile
                                >
                                    <v-toolbar-title>Standard log content</v-toolbar-title>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        v-if="ifClipboardEnabled"
                                        icon
                                        @click="copyToClipboard('crunz-log')"
                                    >
                                        <v-icon>mdi-content-duplicate</v-icon>
                                    </v-btn>
                                </v-toolbar>

                                <v-card-text class="pa-0">
                                    <div id="crunz-log"></div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row v-if="logdata.customLog_content!=''">
                        <v-col cols="12">
                            <v-card
                                outlined
                            >
                                <v-toolbar
                                    dense
                                    flat
                                    tile
                                >
                                    <v-toolbar-title>Custom log content</v-toolbar-title>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        v-if="ifClipboardEnabled"
                                        icon
                                        @click="copyToClipboard('custom-log')"
                                    >
                                        <v-icon>mdi-content-duplicate</v-icon>
                                    </v-btn>
                                </v-toolbar>

                                <v-card-text class="pa-0">
                                    <div id="custom-log"></div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            logdata: {
                path:"",
                execution:"",
                duration:"",
                outcome:"",
                crunzLog_content : "",
                customLog_content : ""
            },

            crunzLogEditor : null,
            customLogEditor : null
        }
    },

    props: ['rowdata'],

    mounted:function() {
        if(this.rowdata){
            this.readData()
        }
    },

    computed: {
        ifClipboardEnabled: function () {
            return Utils.ifClipboardEnabled();
        }
    },

    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-modal');
        },

        initEditor:function(editor){
            var ed = "";
            var content = "";

            if(editor=="crunz-log"){
                content = this.logdata.crunzLog_content;
            }
            if(editor=="custom-log"){
                content = this.logdata.customLog_content;
            }

            ed = ace.edit(editor);
            if(this.$vuetify.theme.dark){
                ed.setTheme("ace/theme/twilight");
            }
            ed.getSession().setMode("ace/mode/text");

            ed.setOptions({
                showPrintMargin: false,
                fontSize: 14
            });

            ed.session.setValue(content);

            if(editor=="crunz-log"){
                this.crunzLogEditor = ed;
            }
            if(editor=="custom-log"){
                this.customLogEditor = ed;
            }
        },

        copyToClipboard:function(editor){
            var ed=""
            if(editor == "crunz-log"){
                ed = this.crunzLogEditor;
            }
            if(editor == "custom-log"){
                ed = this.customLogEditor;
            }
            if(ed != ""){
                navigator.clipboard.writeText(ed.getValue());
            }
        },

        readData:function(){
            var self=this;
            var event_unique_key;

            if(typeof self.rowdata.event_unique_key !== 'undefined' && self.rowdata.event_unique_key != '' ){
                event_unique_key = self.rowdata.event_unique_key;
            }else if(typeof self.rowdata.data.event_unique_key !== 'undefined' && self.rowdata.data.event_unique_key != '' ){
                event_unique_key = self.rowdata.data.event_unique_key;
            }else{
                Utils.showConnError();
            }

            if(self.rowdata.start != ''){
                var apiParams = {
                    "event_unique_key": event_unique_key,
                    "datetime_ref": self.rowdata.start
                }
            }else{
                var apiParams = {
                    "event_unique_key": event_unique_key
                }
            }

            Utils.apiCall("get", "/task/exec-outcome", apiParams)
            .then(function (response) {

                if( typeof response === 'undefined' || response === null ){
                    Utils.showConnError();
                }else{
                    self.logdata.path = response.data.task_path;
                    self.logdata.execution = response.data.task_start;
                    self.logdata.duration = response.data.duration;
                    self.logdata.outcome = response.data.outcome;

                    if(response.data.log_content != ""){
                        self.logdata.crunzLog_content = window.atob(response.data.log_content);
                        setTimeout(function(){
                            self.initEditor('crunz-log');
                        }, 200);
                    }
                    if(response.data.custom_log_content != ""){
                        self.logdata.customLog_content = window.atob(response.data.custom_log_content);
                        setTimeout(function(){
                            self.initEditor('custom-log')
                        }, 200);
                    }
                }
            });
        },
    },
}
</script>

<style>
    #crunz-log {
        height: 500px;
    }
    #custom-log {
        height: 500px;
    }

</style>
