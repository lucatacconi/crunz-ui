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

        <!-- Log modal -->
        <task-log
            v-if="showLogModal"
            @on-close-edit-modal="closeLogModal"
            :rowdata="logData"
        ></task-log>

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
                                                    <v-list-item-icon><v-icon small>fa fa-play</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Execute task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openLogModal(item,i)" :class="item.last_outcome=='OK'||item.last_outcome=='KO' ? '' : 'd-none'">
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
                                {{ item.task_description == "" ? "--" : item.task_description }}
                            </td>
                            <td>
                                {{ item !=undefined && item.expression != undefined && item.expression!='' ? cronstrue.toString(item.expression) : '' }}
                            </td>
                            <td class="text-center">
                                {{ moment(item.next_run).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                {{ item.last_run == "" ? "--" : moment(item.last_run).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                <span v-if="item.last_outcome != ''">
                                    {{ item.last_duration == 0 ? "&lt;1" : item.last_duration }}
                                    min.
                                </span>
                                <span v-else>--</span>
                            </td>
                            <td class="text-center" >
                                <v-icon v-if="item.last_outcome=='OK'" color="green darken-2">fas fa-check-circle</v-icon>
                                <v-icon v-else-if="item.last_outcome=='KO'" color="red">fas fa-exclamation-triangle</v-icon>
                                <span v-else>--</span>
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    {{ message }}
                </template>

            </v-data-table>
            <v-card-actions style="padding-top:50px;"></v-card-actions>

        </v-card>

        <!-- Actions buttons -->
        <actions-buttons v-on:read-data="readData()" v-on:edit-modal="opendEditModal()" v-on:upload-modal="openUploadModal()"></actions-buttons>

    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            showUploadModal:false,
            showEditModal:false,
            showLogModal:false,
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
                { text: 'Next execution', value: 'next_run', align: 'center' },
                { text: 'Last execution', value: 'last_run', align: 'center' },
                { text: 'Last duration', value: 'last_duration', align: 'center' },
                { text: 'Last execution status', value: 'last_outcome', align: 'center' },
            ],
            files: [],
            editData:false,
            uploadData:false,
            logData:false,
            message:'LOADING TASKS'
        }
    },
    methods: {
        readData:function(){
            var self=this
            self.message="LOADING TASKS"
            Utils.apiCall("get", "/task/")
            .then(function (response) {
                if(response.data.length!=0){
                    self.files=JSON.parse(JSON.stringify(response.data))
                }else{
                    self.message="TASKS NOT FOUND"
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
        openLogModal: function (rowdata) {
            this.showLogModal = true;
            // this.editData = rowdata!=undefined ? rowdata : false;
        },
        closeLogModal: function () {
            this.showLogModal = false;
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
                    var params={
                        TASK_PATH:rowdata.task_path
                    }
                    Utils.apiCall("delete", "/task/",params)
                    .then(function (response) {
                        if(response.data.result){
                            Swal.fire({
                                title: 'Task DELETED',
                                text: response.data.result_msg,
                                type: 'success'
                            })
                            self.readData()
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
                    var params={
                        TASK_PATH:rowdata.task_path
                    }
                    Utils.apiCall("post", "/task/execute",params)
                    .then(function (response) {
                        if(response.data.result){
                            Swal.fire({
                                title: 'Task EXECUTED',
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
        }
    },
    created:function() {
        this.readData()
    },
    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue'),
        'task-edit': httpVueLoader('../../shareds/TaskEdit.vue'),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue'),
        'task-log': httpVueLoader('../../shareds/Log.vue')
    }
}
</script>
