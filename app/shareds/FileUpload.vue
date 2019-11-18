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
            <v-card-text class="pb-0">
                <v-select
                    solo
                    dense
                    label="Select folder"
                    v-model="formData.path"
                    :items="items"
                ></v-select>
                    <v-file-input
                        solo
                        dense
                        accept=".php"
                        label="Select file"
                        prepend-icon=""
                        append-icon="mdi-folder"
                        v-model="formData.file"
                    ></v-file-input>
                    <v-checkbox
                        class="pt-0 mt-0"
                        v-model="formData.rewrite"
                        label="Rewrite file"
                    ></v-checkbox>
            </v-card-text>
            <v-card-actions class="pt-0">
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
                path:"/",
                rewrite:true
            },
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
        uploadFile:function(){
            console.log(this.formData.path)
            console.log(this.formData.file)
            if(this.formData.file!=null&&this.formData.file.type=="application/x-php"){

                // var text=new FormData();
                // text.append("file", this.formData.file, this.formData.name);

                //------------CORRECT CODE
                var formData = new FormData();
                var imagefile = this.formData.file
                formData.append("TASK_UPLOAD", this.formData.file);
                formData.append("TASK_DESTINATION_PATH", this.formData.path);
                formData.append("CAN_REWRITE", 'N');
                Utils.apiCall("post", "/task/upload",formData, {
                    'Content-Type': 'multipart/form-data'
                })
                .then(function (response) {
                    console.log(response)
                });

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
                    result.push(data.children[i].subdir)
                }
            }
        }
    },
    created:function() {
        var self=this
        Utils.apiCall("get", "/task/group")
        .then(function (response) {
            console.log(response)
            self.items.push('/')
            if(response.data.length==1){
                self.getChildren(response.data[0],self.items)
            }
            console.log(self.items)
        });
    },
}
</script>
