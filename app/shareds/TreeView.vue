<template>
    <div>
        <!-- Folder name modal -->
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
                        autofocus="true"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn outlined
                        small
                        color="button"
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

        <!-- Tree view -->
        <span class="subtitle-1">Destination path</span>
        <v-treeview
            item-disabled="disabled"
            color="blue"
            :items="items"
            item-key="path"
            :active="selectFolder==null ? ['/'] : selectFolder"
            :open="openNodes"
            v-if="ready"
            activatable
            @update:active="checkFolder($event)"
        >
            <template v-slot:prepend="{ item, open }">
                <v-icon v-if="!item.file">
                    {{ open ? 'mdi-folder-open-outline' : 'mdi-folder-outline' }}
                </v-icon>
                <v-icon v-else>
                    {{ file_icons[item.file] }}
                </v-icon>
            </template>
            <template v-slot:label="{ item }">
                {{item.subdir}}
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
                    icon @click="removeFolder(item)"
                    v-if="item.path!='/'"
                >
                    <v-icon color="red">
                        mdi-trash-can-outline
                    </v-icon>
                </v-btn>
            </template>
        </v-treeview>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            items: [],
            file_icons: {
                html: 'mdi-language-html5',
                js: 'mdi-nodejs',
                json: 'mdi-json',
                md: 'mdi-markdown',
                pdf: 'mdi-file-pdf',
                png: 'mdi-file-image',
                txt: 'mdi-file-document-outline',
                xls: 'mdi-file-excel',
            },
            selectFolder:null,
            add_folder:false,
            new_folder_name:'',
            temp_item:null,
            openNodes:[],
            ready:false
        }
    },
    props:['pathFolder'],
    methods: {
        checkFolder:function(event) {
            if(event.length!=0){
                this.selectFolder=event
            }else{
                this.selectFolder=['/']
            }
            this.$emit('select-folder',this.selectFolder[0])
        },
        openNewFolderModal:function(item){
            this.temp_item=item
            this.add_folder=true
            this.new_folder_name=''
        },
        addFolder:function(){
            var self=this

            if(this.new_folder_name==null||this.new_folder_name==''){
                Utils.showAlertDialog('ERROR',"Directory name is empty",'error');
                return
            }
            var regex = /[^a-zA-Z0-9_-]/g
            if(regex.test(this.new_folder_name)){
                Utils.showAlertDialog('ERROR',"Directory being added contains not allowed characters (Only a-z, A-Z, 0-9, -, _ characters allowed)",'error');
                return
            }

            var path=this.temp_item.path=='/' ? this.temp_item.path+this.new_folder_name : this.temp_item.path+"/"+this.new_folder_name
            var params={
                path:path
            }
            Utils.apiCall("post", "task-container/dir",params)
            .then(function (response) {
                if(response.data.result){
                    self.readTree(path)
                    Utils.showAlertDialog('Folder created',response.data.result_msg,'success',{},()=>{
                        self.add_folder=false
                    });
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        },
        removeFolder:function(item){
            var self = this;
            Utils.showAlertDialog(
                'Delete folder',
                'Are you sure you want to delete folder?',
                'warning',{
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                },()=>{
                var params = {
                    path:item.path
                }
                Utils.apiCall("delete", "task-container/dir",params)
                .then(function (response) {
                    if(response.data.result){
                        self.readTree()
                        Utils.showAlertDialog('Folder deleted',response.data.result_msg,'success');
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });
            });
        },
        searchChildren:function(tree, value, key){ //search the VALUE of the KEY in the TREE
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
        readTree:function(selectFolder=null, openNodes=[]){
            var self = this;
            Utils.apiCall("get", "/task-container/tree/display")
            .then(function (response) {
                self.items = [response.data];
                if(selectFolder != null){
                    self.selectFolder = [selectFolder];
                }
                if(openNodes.length > 0){
                    self.openNodes = openNodes;
                }
                self.ready = true;
            });
        }
    },
    created:function() {
        var selectFolder = null;
        var openNodes = [];
        if(this.pathFolder){
            var countSlash = 0;
            var chars = this.pathFolder.split("");
            for(var indx in chars){
                if(chars[indx] == '/') countSlash++;
            }
            selectFolder = "/";
            if(countSlash >1 ){
                removedLastSlash = this.pathFolder.substring(0,this.pathFolder.length-1);
                selectFolder = removedLastSlash;
            }

            if(countSlash > 1){
                var split = selectFolder.split("/");
                for(var i = 0; i < split.length-1; i++){
                    if(i == 0){
                        openNodes.push("/");
                    }else{
                        var concat = '';
                        for(var k = 1; k <= i; k++){
                            concat += "/" + split[k];
                        }
                        openNodes.push(concat);
                    }
                }
            }
        }
        this.readTree(selectFolder,openNodes);
    }
}
</script>
