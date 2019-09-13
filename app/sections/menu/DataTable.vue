<template>
    <div>

        <!-- Task edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-edit-modal="closeEditModal"
            :rowdata="editData"
        ></task-edit>

        <!-- Upload file modal -->
        <task-upload
            v-if="showUploadModal"
            @on-close-edit-modal="closeUploadModal"
            :rowdata="uploadData"
        ></task-upload>

        <v-card>

            <v-data-table
                dense
                :headers="headers"
                :items="files"
            >
                <template v-if="files.length!=0" v-slot:body="{ items }">
                    <tbody>
                        <tr v-for="(item,i) in items" :key="i">
                            <td>
                                <center>
                                    <v-icon color="#607d8b" href="#" @click="opendEditModal(item,i)">
                                        edit
                                    </v-icon>
                                    <v-icon color="red" href="#" @click="deleteItem(item,i)">
                                        delete
                                    </v-icon>
                                </center>
                            </td>
                        <td>
                            {{ item.filename }}
                        </td>
                        <td>
                            {{ item.task_description }}
                        </td>
                        <td>
                            <!-- {{ item.execution_frequency }} -->
                        </td>
                        <td>
                            {{ item.average_duration }}
                        </td>
                        <td>
                            {{ item.next_run }}
                        </td>
                        <td>
                        </td>
                        <td :class="item.last_outcome.toUpperCase()=='OK' ? 'green--text' : 'red--text'" >
                            {{ item.last_outcome }}
                        </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    NESSUN DATO
                </template>

            </v-data-table>

        </v-card>

        <v-speed-dial
            absolute
            bottom
            left
            direction="right"
            open-on-hover
            transition="slide-y-reverse-transition"
        >
            <template v-slot:activator>
                <v-btn
                    color="blue darken-2"
                    dark
                    fab
                    small
                >
                    <v-icon>fa fa-cog</v-icon>
                </v-btn>
            </template>
            <v-tooltip bottom>
                <template v-slot:activator="{ on }">
                    <v-btn
                        fab
                        dark
                        small
                        color="green"
                        @click="openUploadModal()"
                        v-on="on"
                    >
                        <v-icon>mdi-upload</v-icon>
                    </v-btn>
                </template>
                <span>Upload file</span>
            </v-tooltip>
            <v-tooltip bottom>
                <template v-slot:activator="{ on }">
                    <v-btn
                        fab
                        dark
                        small
                        color="indigo"
                        @click="opendEditModal()"
                        v-on="on"
                    >
                        <v-icon>mdi-plus</v-icon>
                    </v-btn>
                </template>
                <span>Add new task</span>
            </v-tooltip>
            <v-tooltip bottom>
                <template v-slot:activator="{ on }">
                    <v-btn
                        fab
                        dark
                        small
                        color="indigo"
                        @click="readData()"
                        v-on="on"
                    >
                        <v-icon>mdi-refresh</v-icon>
                    </v-btn>
                </template>
                <span>Refresh</span>
            </v-tooltip>
        </v-speed-dial>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            showUploadModal:false,
            showEditModal:false,
            headers: [
                {
                    text: 'Operations',
                    sortable: false,
                    value: ''
                },
                { text: 'File', value: 'filename' },
                { text: 'Description', value: 'task_description' },
                { text: 'Execution frequency', value: 'execution_frequency' },
                { text: 'Execution time', value: 'execution_time' },
                { text: 'Next execution', value: 'next_run' },
                { text: 'Last execution status', value: 'last_execution_status' },
            ],
            files: [],
            editData:false,
            uploadData:false,
        }
    },
    methods: {
        readData:function(){
            var self=this
            Utils.apiCall("get", "/task/")
            .then(function (response) {
                if(response.data.length!=0){
                    self.files=response.data
                }else{
                    Swal.fire({
                        title: 'Tasks not found',
                        text: "Tasks not found",
                        type: 'warning'
                    })
                }
            });
        },
        opendEditModal: function (rowdata) {
            this.showEditModal = true;
            this.editData = rowdata!=undefined ? rowdata : false;
        },
        closeEditModal: function () {
            this.showEditModal = false;
            // this.form. = false;
            // this.readData();
        },
         openUploadModal: function (rowdata) {
            this.showUploadModal = true;
            // this.editData = rowdata!=undefined ? rowdata : false;
        },
        closeUploadModal: function () {
            this.showUploadModal = false;
            // this.form. = false;
            // this.readData();
        },
        deleteItem: function (rowdata) {
            var self = this;
            Swal.fire({
                title: 'Delete task',
                text: "Do you want delete task?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'DELETE',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var self=this
                    Utils.apiCall("delete", "/task/")
                    .then(function (response) {
                        if(response.statusText=='OK'){
                            Swal.fire({
                                title: 'Task deleted',
                                text: "Task deleted",
                                type: 'success'
                            })
                        }else{
                            Swal.fire({
                                title: 'Error deleted task',
                                text: "Error deleted task",
                                type: 'error'
                            })
                        }
                    });
                }
            });
        },
    },
    created:function() {
        this.readData()
    },
    components:{
        'task-edit': httpVueLoader('../../shareds/TaskEdit.vue'),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue')
    }
}
</script>
