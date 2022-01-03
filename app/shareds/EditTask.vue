<template>
    <v-dialog :value="true" persistent max-width="1185px" height="500px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="blue-grey"
            >
                <v-toolbar-title>
                    Edit task
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

            <v-card-text class="pt-0 pb-0">
                <v-form>
                    <v-container>

                        <v-row>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Path:"
                                    :value="taskFile.task_path"
                                    readonly
                                    hide-details
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col class="py-0 pt-5" cols="12">
                                <editor v-on:editor="getEditor($event)" :content="taskFile.taskEdit_content"></editor>
                            </v-col>
                        </v-row>

                    </v-container>
                <v-form>
            </v-card-text>

            <v-card-actions class="pt-0 pr-9 pb-3">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(false)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save
                </v-btn>

                <v-btn
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(true)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save & close
                </v-btn>
                <v-btn
                    v-if="origin!='archived'"
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(true,true)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save & Archive
                </v-btn>
                <v-btn
                    v-else
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(true,true)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save & Restore
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            taskFile: {
                filename:"",
                task_path:"",
                taskEdit_content : ""
            },
            editor:null,
        }
    },

    props: ['rowdata','origin'],

    mounted:function() {
        if(this.rowdata){

            var self = this;
            var params = {
                "return_task_cont": "Y",
                "unique_id": self.rowdata.event_unique_key
            }

            Utils.apiCall("get", this.origin=='archived' ? "/task-archive/" : "/task/",params)
            .then(function (response) {
                if(response.data.length!=0){
                    task_detail = response.data[0];
                    self.rowdata.task_content = task_detail.task_content

                    if(self.rowdata.task_content!=''){
                        self.taskFile.filename = self.rowdata.filename;
                        self.taskFile.task_path = self.rowdata.task_path;
                        self.taskFile.taskEdit_content=atob(self.rowdata.task_content)
                    }

                }
            });
        }
    },

    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal', result);
        },
        getEditor:function(editor){
            this.editor=editor
        },
        saveFile: function (edit_modal_close,restore_or_archived=false) {
            var self=this;

            if(this.editor==undefined) return
            if(this.editor==null) return

            if(this.editor.getValue().trim()==""){
                Utils.showAlertDialog('ERROR','Task content is empty','error');
                return
            }

            var apiParams = {
                "task_file_path": this.rowdata.task_path,
                "task_content": btoa(this.editor.getValue())
            }
            Utils.apiCall("post", "/task/", apiParams)
            .then(function (response) {
                if(response.data.result){
                    var msg="Task updated";
                    if(restore_or_archived){
                        var params = {
                            "arch_path": self.rowdata.task_path,
                            "task_path": self.rowdata.task_path
                        }
                        if(self.origin=="archived"){
                            msg+=" and restored";
                            Utils.apiCall("post", "/task-archive/de-archive",params)
                            .then(function (response) {
                                if(response.data.result){
                                    Utils.showAlertDialog(msg,response.data.result_msg,'success',{},
                                        ()=>{
                                        if(edit_modal_close){
                                            self.closeModal(true);
                                        }
                                    });
                                }else{
                                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                                }
                            });
                        }else{
                            msg+=" and archived";
                            Utils.apiCall("post", "/task-archive/archive",params)
                            .then(function (response) {
                                if(response.data.result){
                                    Utils.showAlertDialog(msg,response.data.result_msg,'success',{},
                                        ()=>{
                                        if(edit_modal_close){
                                            self.closeModal(true);
                                        }
                                    });
                                }else{
                                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                                }
                            });
                        }
                    }else{
                        Utils.showAlertDialog(msg,response.data.result_msg,'success',{},
                            ()=>{
                            if(edit_modal_close){
                                self.closeModal(true);
                            }
                        });
                    }
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        }
    },
    components:{
        'editor': httpVueLoader('./Editor.vue' + '?v=' + new Date().getTime())
    }
}
</script>
