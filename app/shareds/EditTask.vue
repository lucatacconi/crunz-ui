<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal()">
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
                                    :value="logdata.task_path"
                                    readonly
                                    hide-details
                                ></v-text-field>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col class="py-0 pt-5" cols="12">
                                <editor v-on:editor="getEditor($event)" :content="logdata.taskEdit_content"></editor>
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
            logdata: {
                filename:"",
                task_path:"",
                taskEdit_content : ""
            },
            editor:null,
        }
    },

    props: ['rowdata'],

    mounted:function() {
        if(this.rowdata){
            if(this.rowdata.task_content!=''){
                this.logdata.filename = this.rowdata.filename;
                this.logdata.task_path = this.rowdata.task_path;
                this.logdata.taskEdit_content=atob(this.rowdata.task_content)
            }
        }
    },

    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-modal');
        },
        getEditor:function(editor){
            this.editor=editor
        },
        saveFile: function (edit_modal_close) {
            var self=this;

            if(this.editor==undefined) return
            if(this.editor==null) return

            var apiParams = {
                "task_file_path": this.rowdata.task_path,
                "task_content": btoa(this.editor.getValue())
            }
            Utils.apiCall("post", "/task/", apiParams)
            .then(function (response) {
                if(response.data.result){
                    Swal.fire({
                        title: 'Task updated',
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
        }
    },
    components:{
        'editor': httpVueLoader('./Editor.vue' + '?v=' + new Date().getTime())
    }
}
</script>
