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
                :headers="headers"
                :items="files"
                :sort-by="headers"
                :sort-desc="[false, true]"
            >
                <template v-if="files.length!=0" v-slot:body="{ items }">
                    <tbody>
                        <tr v-for="(item,i) in items" :key="i">
                            <td>

                                <div class="text-center">
                                    <v-menu offset-y>
                                        <template v-slot:activator="{ on }">
                                            <v-icon v-on="on" small>fa fa-ellipsis-h</v-icon>
                                        </template>
                                        <v-list subheader dense>
                                            <v-subheader class="pl-4 blue-grey white--text font-weight-bold white">
                                                TASK ACTION MENU
                                            </v-subheader>
                                            <v-list-item-group color="primary">
                                                <v-list-item @click="executeItem(item,i)">
                                                    <v-list-item-icon><v-icon small>fa fa-exclamation-circle</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Execute task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item class="d-none">
                                                    <v-list-item-icon><v-icon small>fa fa-folder-open</v-icon></v-list-item-icon>
                                                    <v-list-item-title>View task execution result</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="opendEditModal(item,i)" class="d-none">
                                                    <v-list-item-icon><v-icon small>fa fa-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task configuration</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="deleteItem(item,i)">
                                                    <v-list-item-icon><v-icon small>fa fa-trash</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Delete task</v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
                            </td>
                            <td class="text-center">
                                {{ item.event_launch_id }}
                            </td>
                            <td>
                                {{ item.task_path }}
                            </td>
                            <td>
                                {{ item.task_description }}
                            </td>
                            <td>
                                {{ cronstrue.toString(item.expression) }}
                            </td>
                            <td>
                                {{ item.next_run }}
                            </td>
                            <td class="text-center">
                                {{ item.average_duration }}
                            </td>
                            <td>
                                {{ item.last_run }}
                            </td>
                            <td :class="item.last_outcome.toUpperCase()=='OK' ? 'green--text' : 'red--text'" >
                                {{ item.last_outcome }}
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    TASKS NOT FOUND
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
                    text: '',
                    sortable: false,
                    value: ''
                },
                { text: '#', value: 'event_launch_id', align: 'center' },
                { text: 'Task', value: 'task_path' },
                { text: 'Description', value: 'task_description' },
                { text: 'Execution', value: 'expression' },
                { text: 'Next execution', value: 'next_run' },
                { text: 'Average duration(min.)', value: 'average_duration', align: 'center' },
                { text: 'Last execution', value: 'last_run' },
                { text: 'Last execution status', value: 'last_outcome', align: 'center' },
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
                    self.files=JSON.parse(JSON.stringify(response.data))
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
        executeItem: function (rowdata) {
            var self = this;
            Swal.fire({
                title: 'Execute task',
                text: "Do you want execute task?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'EXECUTE',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var self=this
                    Utils.apiCall("post", "/task/execute")
                    .then(function (response) {
                        if(response.statusText=='OK'){
                            Swal.fire({
                                title: 'Task EXECUTED',
                                text: "Task EXECUTED",
                                type: 'success'
                            })
                        }else{
                            Swal.fire({
                                title: 'Error executing task',
                                text: "Error executing task",
                                type: 'error'
                            })
                        }
                    });
                }
            });
        }
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
