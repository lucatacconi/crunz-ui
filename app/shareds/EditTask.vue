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
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Path:"
                                    :value="logdata.path"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="logdata.execution"
                                    readonly
                                    dense
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
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Execution outcome:"
                                    :value="( logdata.outcome == 'OK' ? 'Success' : 'Failed')"
                                    readonly
                                    dense
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
            ed.getSession().setMode("ace/mode/text");

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
            var ed=""
            if(editor == "task-edit"){
                ed = this.taskEditEditor;
            }
            if(ed!=""){
                var sel = ed.selection.toJSON();
                ed.selectAll();
                ed.focus();
                document.execCommand('copy');
                ed.selection.fromJSON(sel);
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
