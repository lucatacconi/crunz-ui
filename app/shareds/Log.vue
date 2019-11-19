<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
            >
                <v-toolbar-title>
                    {{modalTitle}}
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

            <v-card-text class="pt-5">
                <v-form>
                    <v-container class="pt-0">
                        <v-row>
                            <v-col class="pa-0" cols="12">
                                <span class="subtitle-1">
                                    Path: {{logdata.task_path}}
                                </span>
                                <br>
                                <span class="subtitle-1">
                                    Execution: {{logdata.execution}}
                                </span>
                                <br>
                                <span class="subtitle-1">
                                    Duration: {{logdata.duration}}
                                </span>
                                <br>
                                <span class="subtitle-1">
                                    Execution outcome: {{logdata.outcome}}
                                </span>
                                <v-card>
                                    <strong>Crunz log</strong>
                                    <div id="crunz-log"></div>
                                    <v-card-actions>
                                        <v-spacer></v-spacer>
                                        <v-btn
                                            color="blue"
                                            dark
                                            x-small
                                            @click="copyToClipboard('crunz-log')"
                                        >
                                            Copy to clipboard
                                        </v-btn>
                                    </v-card-actions>
                                </v-card>
                            </v-col>
                        </v-row>
                        <v-row v-if="logdata.customLog_content!=''">
                            <v-col cols="12">
                                <v-card>
                                    <strong>Custom log</strong>
                                    <div id="custom-log"></div>
                                    <v-card-actions>
                                        <v-spacer></v-spacer>
                                        <v-btn
                                            color="blue"
                                            dark
                                            x-small
                                            @click="copyToClipboard('custom-log')"
                                        >
                                            Copy to clipboard
                                        </v-btn>
                                    </v-card-actions>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-container>
                <v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            modalTitle:"Task execution log",
            logdata: {
                path:"",
                execution:"",
                duration:"",
                outcome:"",
                crunzLog_content : "",
                customLog_content : ""
            },
            crunzLog : null,
            customLog : null
        }
    },

    props: ['rowdata'],

    created:function() {
        if(this.rowdata){
            this.readData()
        }
    },

    mounted: function () {
        var self = this;


        // self.crunzLog = ace.edit("crunz-log");
        // // self.crunzLog.setTheme("ace/theme/eclipse");
        // self.crunzLog.getSession().setMode("ace/mode/text");

        // self.crunzLog.setOptions({
        //     showPrintMargin: false,
        //     fontSize: 14
        // });

        // // self.crunzLog.session.setValue("self.crunzLog");


        // self.customLog = ace.edit("custom-log");
        // // self.customLog.setTheme("ace/theme/eclipse");
        // self.customLog.getSession().setMode("ace/mode/text");

        // self.customLog.setOptions({
        //     showPrintMargin: false,
        //     fontSize: 14
        // });

        // self.customLog.session.setValue("self.customLog");

    },


    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-edit-modal');
        },
        initEditor:function(editor){
            var ed=""
            var content=""
            if(editor=="crunz-log"){
                content=this.logdata.crunzLog_content
            }
            if(editor=="custom-log"){
                content=this.logdata.customLog_content
            }
            ed = ace.edit(editor);
            // ed.setTheme("ace/theme/eclipse");
            ed.getSession().setMode("ace/mode/text");

            ed.setOptions({
                showPrintMargin: false,
                fontSize: 14
            });

            ed.session.setValue(content);

            if(editor=="crunz-log"){
                this.crunzLog=ed
            }
            if(editor=="custom-log"){
                this.customLog=ed
            }
        },
        copyToClipboard:function(editor){
            var ed=""
            if(editor=="crunz-log"){
                ed=this.crunzLog
            }
            if(editor=="custom-log"){
                ed=this.customLog
            }
            if(ed!=""){
                var sel = ed.selection.toJSON();
                ed.selectAll();
                ed.focus();
                // console.log(ed.session.getTextRange(ed.getSelectionRange()))
                document.execCommand('copy');
                ed.selection.fromJSON(sel);
            }
        },
        readData:function(){
            var self=this
            if(self.rowdata.task_path!=undefined){
                var params={
                    TASK_PATH:self.rowdata.task_path
                }
                Utils.apiCall("get", "/task/exec-outcome",params)
                .then(function (response) {
                    response.data.task_path=self.logdata.path
                    response.data.task_start=self.logdata.execution
                    response.data.task_stop=self.logdata.duration
                    response.data.outcome=self.logdata.outcome
                    if(response.data.log_content!=""){
                        self.logdata.crunzLog_content=window.atob(response.data.log_content)
                        self.initEditor('crunz-log')
                    }
                    if(response.data.custom_log_content!=""){
                        self.logdata.customLog_content=window.atob(response.data.custom_log_content)
                        setTimeout(function(){
                            self.initEditor('custom-log')
                        }, 200);
                    }
                });
            }else{
                self.logdata.crunzLog_content=window.atob(self.rowdata)
                setTimeout(function(){
                    self.initEditor('crunz-log')
                }, 200);
            }
        },
    },
}
</script>

<style>
    #crunz-log {
        height: 300px;
    }
    #custom-log {
        height: 300px;
    }

</style>
