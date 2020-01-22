<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
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

            <v-card-text class="pt-0">
                <v-form>
                    <v-container>

                        <v-row>
                            <v-col cols="12" class="py-0">
                                <v-text-field
                                    label="Path:"
                                    :value="logdata.task_path"
                                    readonly
                                    dense
                                    hide-details
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
                                        <v-toolbar-title>Task content</v-toolbar-title>
                                        <v-spacer></v-spacer>
                                        <v-btn
                                            icon
                                            @click="copyToClipboard('task-edit')"
                                        >
                                            <v-icon>mdi-content-duplicate</v-icon>
                                        </v-btn>
                                    </v-toolbar>

                                    <v-card-text class="pa-0">
                                        <div id="task-edit"></div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                    </v-container>
                <v-form>
            </v-card-text>
            <v-card-actions class="pt-0 pb-3 pr-3">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    dense
                    dark
                    color="#607d8b"
                    @click="saveFile(false)"
                >
                    <v-icon left>mdi-content-save</v-icon>
                    Save
                </v-btn>

                <v-btn
                    small
                    dense
                    dark
                    color="#607d8b"
                    @click="saveFile(true)"
                >
                    <v-icon left>mdi-content-save</v-icon>
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

            taskEditEditor : null,
        }
    },

    props: ['rowdata'],

    mounted:function() {
        var self=this
        if(this.rowdata){
            if(this.rowdata.task_content!=''){
                this.logdata.filename = this.rowdata.filename;
                this.logdata.task_path = this.rowdata.task_path;
                this.logdata.taskEdit_content=atob(this.rowdata.task_content)

                setTimeout(function(){
                    self.initEditor('task-edit');
                }, 200);
            }
        }
    },

    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-edit-modal');
        },

        initEditor:function(editor){
            var ed = "";
            var content = "";

            if(editor=="task-edit"){
                content = this.logdata.taskEdit_content;
            }

            ed = ace.edit(editor);
            ed.getSession().setMode("ace/mode/php");

            ed.setOptions({
                showPrintMargin: false,
                fontSize: 14
            });

            ed.session.setValue(content);

            if(editor=="task-edit"){
                this.taskEditEditor = ed;
            }
        },

        copyToClipboard:function(editor){
            var ed = "";
            if(editor == "task-edit"){
                ed = this.taskEditEditor;
            }
            if(ed != ""){
                var sel = ed.selection.toJSON();
                ed.selectAll();
                ed.focus();
                document.execCommand('copy');
                ed.selection.fromJSON(sel);
            }
        },

        saveFile: function (edit_modal_close) {
            var self=this;

            var ed = this.taskEditEditor;
            if(ed != ""){
                var code = ed.getValue();

                var apiParams = {
                    "task_file_path": self.rowdata.task_path,
                    "task_content": code
                }

                Utils.apiCall("post", "/task/", apiParams)
                .then(function (response) {
                    if(response.data.result){
                        Swal.fire({
                            title: 'Task updated.',
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
        }
    }
}
</script>

<style>
    #task-edit {
        height: 300px;
    }

</style>
