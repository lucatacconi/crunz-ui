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
                    <v-flex xs11 class="pl-3">
                        <v-text-field
                            hide-details
                            v-model="formData.task_name"
                            label="Task name"
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs1 class="pl-1 pt-8">
                        .php
                    </v-flex>
                </v-layout>

                <editor v-on:editor="getEditor($event)" :content="task_content"></editor>

            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(false)"
                >
                    <v-icon left>far fa-save</v-icon>
                    Save
                </v-btn>

                <v-btn
                    small
                    outlined
                    color="grey darken-2"
                    @click="saveFile(true)"
                >
                    <v-icon left>far fa-save</v-icon>
                    Save & close
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
            task_content:
            `<?php

use Crunz\Schedule;

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

            // console.log(this.editor.getValue())

            var apiParams = {
                "task_file_path": this.formData.path=='/' ? this.formData.path+this.formData.task_name+".php" : this.formData.path+"/"+this.formData.task_name+".php",
                "task_content": this.editor.getValue()
            }

            console.log(apiParams)

            // Utils.apiCall("post", "/task/", apiParams)
            // .then(function (response) {
            //     if(response.data.result){
            //         Swal.fire({
            //             title: 'Task updated.',
            //             text: response.data.result_msg,
            //             type: 'success',
            //             // onClose: () => {
            //             //     if(edit_modal_close){
            //             //         self.closeModal(true);
            //             //     }
            //             // }
            //         })
            //     }else{
            //         Swal.fire({
            //             title: 'ERROR',
            //             text: response.data.result_msg,
            //             type: 'error'
            //         })
            //     }
            // });
        },
    },
    created:function() {
    },
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime()),
        'editor': httpVueLoader('./Editor.vue' + '?v=' + new Date().getTime()),
    }
}
</script>
