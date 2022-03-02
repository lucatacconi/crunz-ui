<template>
    <v-dialog :value="true" persistent max-width="600px" @on-close="closeModal(false)">
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

                <v-file-input
                    class="pt-5"
                    label="Select file"
                    accept=".php"
                    prepend-icon=""
                    hide-details
                    append-icon="mdi-folder"
                    :multiple="formData.multipleUpload"
                    v-model="formData.files"
                ></v-file-input>
            </v-card-text>

            <v-card-actions class="pt-0 pl-7 pr-6">
                <v-switch
                    class="pt-0 mt-0"
                    v-model="formData.rewrite"
                    inset
                    label="Rewrite task file if present in destination path"
                    hide-details
                ></v-switch>
                <v-spacer></v-spacer>
                <v-btn
                    outlined
                    small
                    color="button"
                    @click="uploadFile"
                >
                    <v-icon left>mdi-file-upload-outline</v-icon>
                    Upload
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
                files: [],
                path:null,
                rewrite:false,
                multipleUpload:true
            },
            modalTitle:"Tasks upload"
        }
    },
    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal',result);
        },
        uploadFile:function(){
            var self=this

            var error=''
            if(this.formData.files==null){
                error+='<br>Task file/s not selected'
            }else if(this.formData.files.length==0){
                error+='<br>File not selected'
            }else{
                for(var i=0;i<this.formData.files.length;i++){
                    if(this.formData.files[i]==null||this.formData.files[i].type!="application/x-php"){
                        if(this.formData.files[i]==null) error+='<br>Task file/s not selected'
                        if(this.formData.files[i].type!="application/x-php") error+='<br>Wrong task file format'
                        break
                    }
                }
            }
            if(error!=''){
                Utils.showAlertDialog('Upload error',error,'error');
                return
            }

            var formData = new FormData();
            for(var i=0;i<this.formData.files.length;i++){
                formData.append("task_upload_"+Number(i+1), this.formData.files[i]);
            }
            formData.append("tasks_destination_path", this.formData.path);
            formData.append("can_rewrite", this.formData.rewrite ? 'Y' : 'N');
            formData.append("multiple_upload", this.formData.multipleUpload ? 'Y' : 'N');

            Utils.fileUpload("/task/upload", formData)
            .then(function (response) {
                if(response.data.result){
                    Utils.showAlertDialog('Task/s uploaded',response.data.result_msg,'success',{},()=>{
                        self.closeModal(true)
                    });
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        }
    },
    created:function() {
    },
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime())
    }
}
</script>
