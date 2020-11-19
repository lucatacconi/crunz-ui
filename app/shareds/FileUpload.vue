<template>
    <div>

        <!-- folder name modal -->
        <v-dialog :value="add_folder" @keydown.esc="add_folder = false" @keydown.enter="addFolder()" persistent max-width="600px">
            <v-card>
                <v-toolbar
                    dense
                    dark
                    color="blue-grey"
                >
                    <v-toolbar-title>
                        New folder
                    </v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-toolbar-items>
                        <v-btn
                            icon
                            @click="add_folder = false"
                        >
                            <v-icon>
                                close
                            </v-icon>
                        </v-btn>
                    </v-toolbar-items>
                </v-toolbar>
                <v-card-text class="pt-1 pb-0">
                    <v-text-field
                        hide-details
                        label="Folder name (ENTER to create new folder; ESC to exit)"
                        v-model="new_folder_name"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn outlined
                        small
                        color="grey darken-2"
                        @click="addFolder()"
                    >
                        <v-icon left>
                            mdi-content-save-outline
                        </v-icon>
                        Create
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- upload modal -->
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
                    <span class="subtitle-1">Destination path</span>
                    <v-treeview
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
                                {{ open ? 'mdi-folder-open-outline' : 'mdi-folder-outline' }}
                            </v-icon>
                            <v-icon v-else>
                                {{ files[item.file] }}
                            </v-icon>
                        </template>
                        <template v-slot:label="{ item }">
                            {{item.description}}
                        </template>
                        <template v-slot:append="{ item }">
                            <v-btn
                                icon
                                @click="openNewFolderModal(item)"
                            >
                                <v-icon>
                                    mdi-folder-plus-outline
                                </v-icon>
                            </v-btn>
                            <v-btn
                                icon @click="removeFloder(item)"
                                v-if="item.subdir!='/'"
                            >
                                <v-icon color="red">
                                    mdi-trash-can-outline
                                </v-icon>
                            </v-btn>
                        </template>
                    </v-treeview>
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
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            add_folder:false,
            new_folder_name:'',
            temp_item:null,
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
            self.$emit('on-close-modal',result);
        },
        checkFolder:function(event) {
            if(event.length!=0){
                this.selectFolder=event
            }else{
                this.selectFolder=['/']
            }

        },
        openNewFolderModal:function(item){
            this.temp_item=item
            this.add_folder=true
            this.new_folder_name=''
        },
        addFolder:function(){
            var self=this
            var params={
                path:this.temp_item.subdir+"/"+this.new_folder_name
            }
            Utils.apiCall("post", "task-container/dir",params)
            .then(function (response) {
                if(response.data.result){
                    self.readTree()
                    Swal.fire({
                        title: 'Folder created',
                        text: response.data.result_msg,
                        type: 'success',
                        onClose: () => {
                            self.add_folder=false
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
        removeFloder:function(item){
            var self = this;
            Swal.fire({
                title: 'Delete folder',
                text: "Are you sure you want to delete folder?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var params = {
                        path:item.subdir
                    }
                    Utils.apiCall("delete", "task-container/dir",params)
                    .then(function (response) {
                        if(response.data.result){
                            self.readTree()
                            Swal.fire({
                                title: 'Folder deleted',
                                text: response.data.result_msg,
                                type: 'success'
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
            });
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
        readTree:function(){
            var self=this
            Utils.apiCall("get", "/task-container/tree/display")
            .then(function (response) {
                self.items=[response.data]
            });
        }
    },
    created:function() {
        this.readTree()
    },
}
</script>
