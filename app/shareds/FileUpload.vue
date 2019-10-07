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
            <!-- <v-treeview
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
            </v-treeview> -->
            <v-select
            class="pl-4 pr-4"
                solo
                :items="['folder 1','folder 2','folder 3']"
            ></v-select>
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
            if(this.selectFolder&&this.formData.file!=null&&this.formData.file.type=="application/x-php"){


                var result = this.getChildren(this.items,this.selectFolder)
                console.log(result);

                // var text=new FormData();
                // text.append("file", this.formData.file, this.formData.name);

                //------------CORRECT CODE
                // var formData = new FormData();
                // var imagefile = this.formData.file
                // formData.append("image", this.formData.file);
                // Utils.apiCall("post", "/task/upload",formData, {
                //     headers: {
                //     'Content-Type': 'multipart/form-data'
                //     }
                // })
                // .then(function (response) {
                //     console.log(response)
                // });

                // var formData = new FormData();
                // var imagefile = this.formData.file
                // formData.append("image", this.formData.file);
                // axios.post('http://localhost/sviluppo/crunz-ui(luca)/routes/task/upload', formData, {
                //     headers: {
                //     'Content-Type': 'multipart/form-data',
                //     'Authorization': 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1NjgzODY1MTgsImV4cCI6MTU2ODM5MzcxOCwianRpIjoiNXJOMjhUekZKdEVzVmFFWUpmVHRkbCIsInVzZXJuYW1lIjoiYWRtaW4iLCJuYW1lIjoiQWRtaW4gVXNlciIsInVzZXJUeXBlIjoiYWRtaW4ifQ.3_dccmC8y3DkM7MNY3B2Qdp2AANQ4a-S6l951qfFOHM'
                //     }
                // })

            }else{
                var txt=""
                if(!this.selectFolder){
                    txt+="Folder not selected"
                }
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
        });
    },
}
</script>
