<template>
    <v-dialog :value="true" persistent max-width="1185px" height="500px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="blue-grey"
            >
                <v-toolbar-title>
                    Move/rename task
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

            <v-card-text class="pt-2 pb-0">
                <v-container>

                    <v-row class="mb-3">
                        <v-col cols="12" class="py-0">
                            <v-text-field
                                label="Path:"
                                :value="taskPath"
                                readonly
                                hide-details
                            ></v-text-field>
                        </v-col>
                    </v-row>

                    <tree-view v-if="showTree" v-on:select-folder="taskDestinationPath=$event" :path-folder="pathFolder"></tree-view>

                    <v-layout row wrap class="pt-4 pb-4">
                        <v-flex xs4 class="pl-3">
                            <v-text-field
                                hide-details
                                v-model="taskName"
                                label="Task file name"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs2 class="pl-1 pt-8">
                            {{suffix}}
                        </v-flex>
                    </v-layout>

                </v-container>
            </v-card-text>

            <v-card-actions class="pt-4 pr-9 pb-3">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="button"
                    @click="saveFile(false)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save
                </v-btn>

                <v-btn
                    small
                    outlined
                    color="button"
                    @click="saveFile(true)"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
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
            taskName:null,
            suffix:null,
            pathFolder:null,
            taskPath:null,
            taskDestinationPath:null,
            showTree:false,
        }
    },

    props: ['rowdata','origin'],

    mounted:function() {
        var self = this;

        if(self.rowdata){

            Utils.apiCall("get", "/environment/crunz-config")
            .then(function (response) {
                if(response.data.suffix){
                    self.suffix=response.data.suffix;

                    var params = {
                        "return_task_cont": "Y",
                        "unique_id": self.rowdata.event_unique_key,
                        "task_path": self.rowdata.task_path
                    }

                    let dest = '/task/draft';
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
                            self.taskName = task_detail.filename.substring(0,task_detail.filename.indexOf(self.suffix));
                            self.taskPath = task_detail.task_path;
                            self.pathFolder = task_detail.subdir;
                        }
                        self.showTree = true;
                    });
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        }
    },

    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal', result);
        },
        saveFile: function (edit_modal_close){
            var self=this;

            if(this.taskName==null||this.taskName==''){
                Utils.showAlertDialog('ERROR',"Task file name is empty",'error');
                return;
            }
            var suffix=this.taskName.slice((this.taskName.length -  this.suffix.length), this.taskName.length);
            if(suffix.toLowerCase()==this.suffix.toLowerCase()){
                this.taskName = this.taskName.slice(0, (this.taskName.length -  this.suffix.length));
            }
            var regex = /[^a-zA-Z0-9_-]/g
            if(regex.test(this.taskName)){
                Utils.showAlertDialog('ERROR',"Task file name being added contains not allowed characters (Only a-z, A-Z, 0-9, -, _ characters allowed)",'error');
                return;
            }

            var apiParams = {
                "TASKS_SOURCE_FILE": self.taskPath,
                "TASKS_DESTINATION_PATH": self.taskDestinationPath,
                "NEW_TASK_FILENAME": self.taskName+self.suffix
            }
            Utils.apiCall("post", "/task/move-rename", apiParams)
            .then(function (response) {
                if(response.data.result){
                    Utils.showAlertDialog('Task moved/renamed',response.data.result_msg,'success',{},()=>{
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
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime())
    }
}
</script>
