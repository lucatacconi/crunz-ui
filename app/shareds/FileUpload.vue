<template>
    <v-dialog :value="true" persistent max-width="600px" @on-close="closeModal()">
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
                        @click="closeModal()"
                    >
                        <v-icon>
                            close
                        </v-icon>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>
            <v-card-title>Select folder</v-card-title>
            <v-treeview
                dense
                item-disabled="disabled"
                color="blue"
                :items="items"
                item-key="description"
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
            <v-card-title>Select file</v-card-title>
                <v-file-input
                    class="pl-4 pr-4"
                    solo
                    accept=".php"
                    prepend-icon=""
                    append-icon="mdi-folder"
                    v-model="formData.file"
                ></v-file-input>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn
                    dark
                    color="blue"
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
            },
            selectFolder:false,
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
    watch: {
        'formData.file':function(){
            console.log(this.formData.file)
        }
    },
    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-edit-modal');
        },
        checkFolder:function(event) {
            if(event.length!=0){
                this.selectFolder=event[0]
            }else{
                this.selectFolder=false
            }

        },
        uploadFile:function(){
            console.log(this.formData.file)
            console.log(this.selectFolder)
            if(this.selectFolder&&this.formData.file!=null){

                // var text=new FormData();
                // text.append("file", this.formData.file, this.formData.name);

                var formData = new FormData();
                var imagefile = this.formData.file
                formData.append("image", this.formData.file);
                Utils.apiCall("post", "/task/upload",formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data'
                    }
                })
                .then(function (response) {
                    console.log(response)
                });

                var formData = new FormData();
                var imagefile = this.formData.file
                formData.append("image", this.formData.file);
                axios.post('http://localhost/sviluppo/crunz-ui(luca)/routes/task/upload', formData, {
                    headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NjgzODY1MTgsImV4cCI6MTU2ODM5MzcxOCwianRpIjoiNXJOMjhUekZKdEVzVmFFWUpmVHRkbCIsInVzZXJuYW1lIjoiYWRtaW4iLCJuYW1lIjoiQWRtaW4gVXNlciIsInVzZXJUeXBlIjoiYWRtaW4ifQ.3_dccmC8y3DkM7MNY3B2Qdp2AANQ4a-S6l951qfFOHM'
                    }
                })

            }else{
                var txt=""
                if(!this.selectFolder){
                    txt+="Folder not selected<br>"
                }
                if(this.formData.file==null){
                    txt+="File not selected"
                }
                Swal.fire({
                    title:"Upload error",
                    html:txt,
                    type:"error"
                })
            }
        },
        test:function(){
            var treeDataSource = [
                {
                    id: 1,
                    Name: "Test1",
                    items: [
                        {
                            id: 2,
                            Name: "Test2",
                            items: [
                                {
                                    id: 3,
                                    Name: "Test3"
                                }
                            ]
                        }
                    ]
                }
            ];

            var getSubMenuItem = function (subMenuItems, id) {
                if (subMenuItems) {
                    for (var i = 0; i < subMenuItems.length; i++) {
                        if (subMenuItems[i].id == id) {
                            return subMenuItems[i];
                        }
                        var found = getSubMenuItem(subMenuItems[i].items, id);
                        if (found) return found;
                    }
                }
            };

            var searchedItem = getSubMenuItem(treeDataSource, 3);
            alert(searchedItem.id);
        },

        getChildren:function(tree, description){
            if (tree) {
                for (var i = 0; i < tree.length; i++) {
                    if (tree[i].description == description) {
                        return tree[i];
                    }
                    var found = this.getChildren(tree[i].children, description);
                    if (found) return found;
                }
            }
        }
    },
    created:function() {


        var self=this
        Utils.apiCall("get", "/task/group")
        .then(function (response) {
            console.log(response)
            self.items=response.data
            var prova = self.getChildren(self.items,'SubGroup 2')
            alert(prova.description);
        });
    },
}
</script>
