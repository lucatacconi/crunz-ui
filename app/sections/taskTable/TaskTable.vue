<template>
    <div>

        <!-- New task modal -->
        <new-task
            v-if="showNewTaskModal"
            @on-close-modal="closeNewTaskModal($event)"
        ></new-task>

        <!-- Upload file modal -->
        <task-upload
            v-if="showUploadModal"
            @on-close-modal="closeUploadModal($event)"
        ></task-upload>

        <!-- Log modal -->
        <task-log
            v-if="showLogModal"
            @on-close-modal="closeLogModal"
            :rowdata="logData"
        ></task-log>

        <!-- Edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-modal="closeEditModal"
            :rowdata="logData"
        ></task-edit>

        <v-card class="mb-16">
            <v-card-title >
                Task list
                <v-spacer></v-spacer>
                <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Search"
                    single-line
                    hide-details
                    class="mt-0"
                ></v-text-field>
            </v-card-title>

            <v-data-table
                :headers="headers"
                :items="files"
                :sort-by="headers"
                :sort-desc="[false, true]"
                :custom-sort="customSort"
                :search="search"
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
                                                Task Action Menu
                                            </v-subheader>
                                            <v-list-item-group color="primary">
                                                <v-list-item @click="executeItem(item, false)" class="pt-2">
                                                    <v-list-item-icon><v-icon small color="orange">fa fa-play</v-icon></v-list-item-icon>
                                                    <v-list-item-title> <span class="orange--text">Execute</span> </v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="executeItem(item, true)">
                                                    <v-list-item-icon><v-icon small color="orange">fas fa-file-alt</v-icon></v-list-item-icon>
                                                    <v-list-item-title> <span class="orange--text">Execute and wait log</span> </v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openLogModal(item, i)" :class="item.last_outcome=='OK'||item.last_outcome=='KO' ? '' : 'd-none'">
                                                    <v-list-item-icon><v-icon small>fa fa-folder-open</v-icon></v-list-item-icon>
                                                    <v-list-item-title>View last log</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="downloadTask(item,i)">
                                                    <v-list-item-icon><v-icon small>fa-download </v-icon></v-list-item-icon>
                                                    <v-list-item-title>Download task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openEditModal(item, i)">
                                                    <v-list-item-icon><v-icon small>fa fa-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="archiveItem(item, i)">
                                                    <v-list-item-icon><v-icon small color="red">fas fa-archive</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">Archive task</span> </v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="deleteItem(item, i)">
                                                    <v-list-item-icon><v-icon small color="red">fa fa-trash</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">Delete task</span> </v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
                            </td>
                            <td class="text-center">
                                {{ item.event_launch_id }}

                                <template v-if="item.high_frequency == true">
                                    <v-tooltip bottom>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-icon
                                                small
                                                color="orange"
                                                v-bind="attrs"
                                                v-on="on"
                                            >
                                                mdi-clock-fast
                                            </v-icon>
                                        </template>
                                        <span>High frequency task</span>
                                    </v-tooltip>
                                </template>
                            </td>
                            <td>
                                {{ item.task_path }}
                            </td>
                            <td>
                                {{ item.task_description == "" ? "--" : item.task_description }}
                            </td>
                            <td>
                                {{item.expression_readable}}
                            </td>
                            <td class="text-center">
                                {{ item.next_run == "" ? "Expired" : moment(item.next_run).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                {{ item.last_run == "" ? "" : moment(item.last_run).format('YY-MM-DD HH:mm') }}

                                <template v-if="item.last_run_actually_executed != true">
                                    <v-tooltip bottom>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-icon
                                                small
                                                color="red"
                                                v-bind="attrs"
                                                v-on="on"
                                            >
                                                mdi-clock-alert-outline
                                            </v-icon>
                                        </template>
                                        <span>The last scheduled task was not executed</span>
                                    </v-tooltip>
                                </template>
                            </td>
                            <td class="text-center">
                                <span v-if="item.last_outcome != ''">
                                    {{ item.last_duration == 0 ? "&lt;1" : item.last_duration }}
                                    min.
                                </span>
                                <span v-else>--</span>
                            </td>
                            <td class="text-center" >
                                <v-icon v-if="item.last_outcome=='OK'" color="green darken-2" @click="openLogModal(item,i)" small>fas fa-folder-open</v-icon>
                                <v-icon v-else-if="item.last_outcome=='KO'" color="red" @click="openLogModal(item,i)" small>fas fa-folder-open</v-icon>
                                <span v-else>--</span>
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    {{ message }}
                </template>

            </v-data-table>

        </v-card>

        <!-- Actions buttons -->
        <actions-buttons v-on:read-data="readData()" v-on:edit-modal="opendEditModal()" v-on:new-task-modal="openNewTaskModal()" v-on:upload-modal="openUploadModal()"></actions-buttons>

    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            search: '',
            showNewTaskModal:false,
            showUploadModal: false,
            showEditModal: false,
            showLogModal: false,
            showEditModal: false,
            headers: [
                {
                    text: 'Actions',
                    sortable: false,
                    value: '',
                    align: 'center'
                },
                { text: 'Task num.', value: 'event_launch_id', align: 'center' },
                { text: 'Task', value: 'task_path' },
                { text: 'Description', value: 'task_description', sortable: false },
                { text: 'Execution', value: 'expression', sortable: false },
                { text: 'Next execution', value: 'next_run', align: 'center' },
                { text: 'Last execution', value: 'last_run', align: 'center' },
                { text: 'Last duration', value: 'last_duration', align: 'center' },
                { text: 'Last exec. outcome', value: 'last_outcome', align: 'center' },
            ],
            files: [],
            editData: false,
            uploadData: false,
            logData: false,
            message: 'No tasks found on server. Eventually check tasks directory path.',
            reloadIntervalObj: false,
            reloadTime: 60000
        }
    },
    methods: {
        readData:function(options = {}){
            var self = this;
            var params = {
                "return_task_cont": "Y"
            }
            self.message = "Loading tasks";
            Utils.apiCall("get", "/task/",params, options)
            .then(function (response) {
                if(response.data.length!=0){
                    self.files = response.data;
                }else{
                    self.message = "No tasks found on server. Eventually check tasks directory path."
                }
            });
        },

        customSort(items, index, isDesc) {

            items.sort((a, b) => {

                if (index[0] === "expression") {

                    a_split = a[index[0]].split(" ");
                    b_split = b[index[0]].split(" ");

                    const zeroPad = (num, places) => String(num).padStart(places, '0');

                    a_m = "00";
                    if(!isNaN(a_split[0])) a_m = zeroPad(parseInt(a_split[0], 10), 2);

                    a_h = "00";
                    if(!isNaN(a_split[1])) a_h = zeroPad(parseInt(a_split[1], 10), 2);

                    b_m = "00";
                    if(!isNaN(b_split[0])) b_m = zeroPad(parseInt(b_split[0], 10), 2);

                    b_h = "00";
                    if(!isNaN(b_split[1])) b_h = zeroPad(parseInt(b_split[1], 10), 2);

                    console.log(a_h + a_m);
                    console.log(b_h + b_m);

                    if (!isDesc) {
                        return (a_h + a_m) < (b_h + b_m) ? -1 : 1;
                    } else {
                        return (b_h + b_m) < (a_h + a_m) ? -1 : 1;
                    }

                }else{
                    if (!(isNaN(a[index[0]]))) {
                        if (!isDesc[0]) {
                            return (a[index[0]] - b[index[0]]);
                        } else {
                            return (b[index[0]] - a[index[0]]);
                        }

                    } else {
                        if (!isDesc[0]) {
                            return (a[index[0]] < b[index[0]]) ? -1 : 1;
                        } else {
                            return (b[index[0]] < a[index[0]]) ? -1 : 1;
                        }
                    }
                }
            });
            return items;
        },

        downloadTask:function(rowdata){
            if(rowdata.task_content != '' && rowdata.filename != ''){
                if(rowdata.task_content == ''){
                    Swal.fire({
                        title: 'Task content empty',
                        text: "Task content is empty",
                        type: 'error'
                    })
                    return;
                }
                if(rowdata.filename == ''){
                    Swal.fire({
                        title: 'Filename empty',
                        text: "Filename is empty",
                        type: 'error'
                    })
                    return;
                }
                var dec = atob(rowdata.task_content);
                Utils.downloadFile(dec,rowdata.filename);
            }
        },

        openUploadModal: function () {
            this.showUploadModal = true;
        },
        closeUploadModal: function (result) {
            this.showUploadModal = false;
            if(typeof result !== 'undefined' && result){
                this.readData();
            }
        },

        openNewTaskModal: function () {
            this.showNewTaskModal = true;
        },
        closeNewTaskModal: function (result) {
            this.showNewTaskModal = false;
            if(typeof result !== 'undefined' && result){
                this.readData();
            }
        },

        openLogModal: function (rowdata) {
            this.showLogModal = true;
            this.logData = rowdata != undefined ? rowdata : false;
        },
        closeLogModal: function () {
            this.showLogModal = false;
        },

        openEditModal: function (rowdata) {
            this.showEditModal = true;
            this.logData = rowdata != undefined ? rowdata : false;
        },
        closeEditModal: function (result) {
            this.showEditModal = false;
            if(typeof result !== 'undefined' && result){
                this.readData();
            }
        },

        archiveItem: function (rowdata) {
            var self = this;
            Swal.fire({
                title: 'Archive task',
                text: "Are you sure you want to archive task? The task file will be renamed and the task will no longer be visible in the dashboard.",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'Archive',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var params = {
                        "task_path": rowdata.task_path
                    }
                    Utils.apiCall("post", "/task/archive",params)
                    .then(function (response) {
                        if(response.data.result){
                            Swal.fire({
                                title: 'Task archived',
                                text: response.data.result_msg,
                                type: 'success'
                            })
                            self.readData();
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

        deleteItem: function (rowdata) {
            var self = this;
            Swal.fire({
                title: 'Delete task',
                text: "Are you sure you want to delete task?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var params = {
                        "task_path": rowdata.task_path
                    }
                    Utils.apiCall("delete", "/task/",params)
                    .then(function (response) {
                        if(response.data.result){
                            Swal.fire({
                                title: 'Task deleted',
                                text: response.data.result_msg,
                                type: 'success'
                            })
                            self.readData();
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

        executeItem: function (item, wait) {
            var self = this;
            var params = {
                "event_unique_key": item.event_unique_key,
                "exec_and_wait": wait ? 'Y' : 'N'
            }

            Utils.apiCall("post", "/task/execute", params)
            .then(function (response) {
                if(response.data.result){
                    if(wait){
                        self.openLogModal(response.data);
                        self.readData();
                    }else{
                        Swal.fire({
                            title: 'Task launched. Execution in progress.',
                            text: response.data.result_msg,
                            type: 'success'
                        })
                    }
                }else{
                    Swal.fire({
                        title: 'ERROR',
                        text: response.data.result_msg,
                        type: 'error'
                    })
                }
            });
        },

        scheduleReload: function () {
            var self = this;
            if(router.currentRoute.fullPath >= "/taskTable/TaskTable"){

                var options = {
                    showLoading: false
                };

                this.readData(options);
                this.reloadIntervalObj = setTimeout(function(){
                    self.scheduleReload();
                }, self.reloadTime);
            }
        }
    },

    created:function() {
        this.readData();
    },

    mounted:function(){
        var self = this;

        if(this.reloadIntervalObj) clearTimeout(this.reloadIntervalObj);

        this.reloadIntervalObj = setTimeout(function(){
            self.scheduleReload();
        }, self.reloadTime);
    },

    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue' + '?v=' + new Date().getTime()),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue' + '?v=' + new Date().getTime()),
        'new-task': httpVueLoader('../../shareds/NewTask.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime()),
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
