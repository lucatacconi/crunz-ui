<template>
    <v-dialog :value="true" persistent max-width="600px" @on-close="closeModal(false)">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
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
            <v-card-text class="px-8 pb-0">
                <v-card-title class="pa-0">Destination path</v-card-title>
                <v-treeview
                    dense
                    item-disabled="disabled"
                    color="blue"
                    :items="items"
                    item-key="subdir"
                    :active="selectFolder==null ? ['/'] : selectFolder"
                    activatable
                    @update:active="checkFolder($event)"
                >
                    <template v-slot:prepend="{ item, open }">
                        <v-icon v-if="!item.file">
                            {{ open ? 'mdi-folder-open' : 'mdi-folder' }}
                        </v-icon>
                        <v-icon v-else>
                            {{ files[item.file] }}
                        </v-icon>
                    </template>
                    <template v-slot:label="{ item }">
                        {{item.description}}
                    </template>
                </v-treeview>
                    <v-file-input
                        class="mt-0"
                        label="Select file"
                        accept=".php"
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
                    dense
                    dark
                    color="#607d8b"
                    @click="uploadFile"
                >
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
                file:null,
                path:"",
                rewrite:true
            },
            selectFolder:null,
            modalTitle:"File upload",
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
        checkFolder:function(event) {
            if(event.length!=0){
                this.selectFolder=event
            }else{
                this.selectFolder=['/']
            }

        },
        uploadFile:function(){
            var self=this
            if(this.formData.file!=null&&this.formData.file.type=="application/x-php"){

                // var result = this.searchChildren(this.items,this.selectFolder,'description')

                var formData = new FormData();
                formData.append("task_upload", this.formData.file);
                formData.append("task_destination_path", this.selectFolder==null ? '/' : this.selectFolder[0]);
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
    },
    created:function() {
        var self=this
        Utils.apiCall("get", "/task/group")
        .then(function (response) {
            self.items=response.data
        });
    },
}
</script>
