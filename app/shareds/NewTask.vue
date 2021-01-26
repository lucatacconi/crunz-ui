<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal(false)">
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

            <v-card-text class="pt-3">

                <tree-view v-on:select-folder="formData.path=$event"></tree-view>

                <v-layout row wrap class="pt-2 pb-2">
                    <v-flex xs10 class="pl-3">
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

                <editor v-on:editor="getEditor($event)" :action-button="true" :content="task_content"></editor>

            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(true)"
                >
                    <v-icon left>far fa-save</v-icon>
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
    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal',result);
        },
        getEditor:function(editor){
            this.editor=editor
        },
        saveFile: function (edit_modal_close) {
            var self=this;

            if(this.editor==undefined) return
            if(this.editor==null) return
            if(this.formData.task_name==null||this.formData.task_name==''){
                Swal.fire({
                    title: 'ERROR',
                    text: "Task name is empty",
                    type: 'error'
                })
                return
            }
            var regex = /[^a-zA-Z0-9_-]/g
            if(regex.test(this.formData.task_name)){
                Swal.fire({
                    title: 'ERROR',
                    text: "Task name being added contains not allowed characters (Only a-z, A-Z, 0-9, -, _ characters allowed)",
                    type: 'error'
                })
                return
            }

            var apiParams = {
                "task_file_path": this.formData.path=='/' ? this.formData.path+this.formData.task_name+this.suffix : this.formData.path+"/"+this.formData.task_name+this.suffix,
                "task_content": btoa(this.editor.getValue()),
                "new_file":'Y'
            }
            Utils.apiCall("post", "/task/", apiParams)
            .then(function (response) {
                if(response.data.result){
                    Swal.fire({
                        title: 'Task created',
                        text: response.data.result_msg,
                        type: 'success',
                        onClose: () => {
                            if(edit_modal_close){
                                self.closeModal(true);
                            }
                        }
                    })
                }else{
                    Swal.fire({
                        title: 'ERROR',
                        text: response.data.result_msg,
                        type: 'error'
                    })
                }
            });
        },
    },
    created:function() {
        var self=this
        Utils.apiCall("get", "/environment/crunz-config")
        .then(function (response) {
            if(response.data.suffix){
                self.suffix=response.data.suffix
            }else{
                Swal.fire({
                    title: 'ERROR',
                    text: response.data.result_msg,
                    type: 'error'
                })
            }
        });
    },
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime()),
        'editor': httpVueLoader('./Editor.vue' + '?v=' + new Date().getTime()),
    }
}
</script>
