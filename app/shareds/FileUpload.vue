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
                    v-model="formData.file"
                ></v-file-input>
                <v-layout row wrap>
                    <v-flex xs10 class="pl-3">
                        <v-switch
                            v-model="formData.rewrite"
                            inset
                            label="Rewrite task file if present in destination path"
                            hide-details
                        ></v-switch>
                    </v-flex>
                    <v-flex xs2 class="pt-4">
                        <v-btn
                            outlined
                            small
                            color="grey darken-2"
                            @click="uploadFile"
                        >
                            <v-icon left>mdi-file-upload-outline</v-icon>
                            Upload
                        </v-btn>
                    </v-flex>
                </v-layout>
            </v-card-text>

        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            formData:{
                file:null,
                path:null,
                rewrite:true
            },
            modalTitle:"File upload"
        }
    },
    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-modal',result);
        },
        uploadFile:function(){
            var self=this
            if(this.formData.file!=null&&this.formData.file.type=="application/x-php"){

                var formData = new FormData();
                formData.append("task_upload", this.formData.file);
                formData.append("task_destination_path", this.formData.path);
                formData.append("can_rewrite", this.formData.rewrite ? 'Y' : 'N');

                Utils.fileUpload("/task/upload", formData)
                .then(function (response) {
                    if(response.data.result){
                        Swal.fire({
                            title: 'Task uploaded',
                            text: response.data.result_msg,
                            type: 'success',
                            onClose: () => {
                                self.closeModal(true)
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

            }else{
                var txt=""
                if(this.formData.file==null){
                    txt+="<br>File not selected"
                }else if(this.formData.file.type!='application/x-php'){
                    txt+="<br>Type file wrong"
                }
                Swal.fire({
                    title:"Upload error",
                    html:txt,
                    type:"error"
                })
            }
        }
    },
    created:function() {
    },
    components:{
        'tree-view': httpVueLoader('./TreeView.vue' + '?v=' + new Date().getTime())
    }
}
</script>
