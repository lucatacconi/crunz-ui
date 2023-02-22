<template>
    <v-dialog :value="true" persistent max-width="1185px" height="500px" @on-close="closeModal(false)">
        <v-card>
            <v-toolbar
                dense
                dark
                color="blue-grey"
            >
                <v-toolbar-title>
                    {{modalTitle}}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn
                        icon
                        @click="closeModal(false)"
                    >
                        <v-icon>
                            close
                        </v-icon>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text class="pt-2">

                <tree-view v-on:select-folder="formData.path=$event" v-if="showTree" :path-folder="pathFolder"></tree-view>

                <v-layout row wrap class="pt-4 pb-4">
                    <v-flex xs4 class="pl-3">
                        <v-text-field
                            hide-details
                            v-model="formData.task_name"
                            label="Task file name"
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs2 class="pl-1 pt-8">
                        {{suffix}}
                    </v-flex>
                </v-layout>

                <editor v-on:editor="getEditor($event)" class="mt-4" :action-button="true" :content="task_content"></editor>

            </v-card-text>

            <v-card-actions class="pt-0 pr-6 pb-3">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="button"
                    @click="saveFile(true)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save
                </v-btn>
            </v-card-actions>

        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            formData:{
                task_name:null,
                path:null,
            },
            modalTitle:"New task",
            pathFolder:null,
            showTree:false,
            editor:null,
            suffix:null,
            task_content:
            `<?php

use Crunz\\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function() {

    //Insert your code here

});

$task

//Insert description here
->description("");

//Insert schedulation here (ex: ->daily())


return $schedule;`
        }
    },
    props:['oldTaskContent','origin'],
    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal',result);
        },
        getEditor:function(editor){
            this.editor=editor;
        },
        saveFile: function (edit_modal_close) {
            var self=this;

            if(this.editor==undefined) return;
            if(this.editor==null) return;
            if(this.formData.task_name==null||this.formData.task_name==''){
                Utils.showAlertDialog('ERROR',"Task file name is empty",'error');
                return;
            }
            var suffix=this.formData.task_name.slice((this.formData.task_name.length -  this.suffix.length), this.formData.task_name.length);
            if(suffix.toLowerCase()==this.suffix.toLowerCase()){
                this.formData.task_name = this.formData.task_name.slice(0, (this.formData.task_name.length -  this.suffix.length));
            }
            var regex = /[^a-zA-Z0-9_-]/g
            if(regex.test(this.formData.task_name)){
                Utils.showAlertDialog('ERROR',"Task file name being added contains not allowed characters (Only a-z, A-Z, 0-9, -, _ characters allowed)",'error');
                return;
            }
            if(this.editor.getValue().trim()==""){
                Utils.showAlertDialog('ERROR',"Task content is empty",'error');
                return;
            }

            var apiParams = {
                "task_file_path": this.formData.path=='/' ? this.formData.path+this.formData.task_name+this.suffix : this.formData.path+"/"+this.formData.task_name+this.suffix,
                "task_content": btoa(this.editor.getValue()),
                "new_file":'Y'
            }
            Utils.apiCall("post", "/task/", apiParams)
            .then(function (response) {
                if(response.data.result){
                    Utils.showAlertDialog('Task created',response.data.result_msg,'success',{},()=>{
                        if(edit_modal_close){
                            self.closeModal(true);
                        }
                    });
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        },
    },
    created:function() {
        var self=this;

        Utils.apiCall("get", "/environment/crunz-config")
        .then(function (response) {
            if(response.data.suffix){
                self.suffix=response.data.suffix;
                if(self.oldTaskContent){
                    var params = {
                        "return_task_cont": "Y",
                        "unique_id": self.oldTaskContent.event_unique_key,
                        "task_path": self.oldTaskContent.task_path
                    };

                    let dest = '/task/';
                    if(self.origin=='archived'){
                        dest = '/task-archive/';
                    }
                    if(self.origin=='linted'){
                        dest = '/task/draft';
                    }

                    Utils.apiCall("get", dest, params)
                    .then(function (responseTask) {
                        if(responseTask.data.length!=0){
                            task_detail = responseTask.data[0];
                            self.task_content = atob(task_detail.task_content);
                            if(self.origin=='archived'){
                                var split = task_detail.filename.split(".");
                                task_detail.filename=split[0]+".php";
                                self.formData.task_name = task_detail.filename.substring(0,task_detail.filename.indexOf(self.suffix));
                            }else{
                                self.formData.task_name = task_detail.filename.substring(0,task_detail.filename.indexOf(self.suffix));
                            }
                            self.pathFolder = task_detail.subdir;
                        }
                        self.showTree = true;
                    });
                }else{
                    self.showTree = true;
                }
            }else{
                Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
            }
        });
    },
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime()),
        'editor': httpVueLoader('./Editor.vue' + '?v=' + new Date().getTime()),
    }
}
</script>
