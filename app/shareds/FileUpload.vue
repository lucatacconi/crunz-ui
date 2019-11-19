<template>
    <v-dialog :value="true" persistent max-width="600px" @on-close="closeModal(false)">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
            >
                <v-toolbar-title>
                    Task file upload
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
            <v-card-text class="px-8 pb-0">
                <v-select
                    dense
                    label="Select folder"
                    v-model="formData.path"
                    :items="items"
                ></v-select>
                <v-file-input
                    accept=".php"
                    label="Select file"
                    prepend-icon=""
                    append-icon="mdi-folder"
                    v-model="formData.file"
                ></v-file-input>
                <v-switch class="pt-0" v-model="formData.rewrite" inset :label="`Rewrite task file if present in destination path`"></v-switch>
            </v-card-text>
            <v-card-actions class="pt-0 pb-5 pr-5">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    dark
                    dense
                    color="#607d8b"
                    @click="uploadFile"
                >
                    Upload file
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
                file:null,
                path:"/",
                rewrite:true
            },
            files: {
                html: 'mdi-language-html5',
                js: 'mdi-nodejs',
                json: 'mdi-json',
                md: 'mdi-markdown',
                pdf: 'mdi-file-pdf',
                png: 'mdi-file-image',
                txt: 'mdi-file-document-outline',
                xls: 'mdi-file-excel',
            },
            items: [],
        }
    },
    methods: {
        closeModal: function (result) {
            var self = this;
            self.$emit('on-close-edit-modal',result);
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
        },
        searchChildren:function(tree, value, key){ //cerco il valore di una determinata chiave nell'array tree
            if (tree) {
                for (var i = 0; i < tree.length; i++) {
                    if (tree[i][key] == value) {
                        return tree[i];
                    }
                    var found = this.searchChildren(tree[i].children, value, key);
                    if (found) return found;
                }
            }
        },
        getChildren:function(data,result){
            if(data.children!=undefined){
                for(var i=0;i<data.children.length;i++){
                    this.getChildren(data.children[i],result)
                    if(!data.children[i].disabled){
                        result.push(data.children[i].subdir)
                    }
                }
            }
        }
    },
    created:function() {
        var self=this
        Utils.apiCall("get", "/task/group")
        .then(function (response) {
            self.items.push('/')
            if(response.data.length==1){
                self.getChildren(response.data[0],self.items)
            }
        });
    },
}
</script>
