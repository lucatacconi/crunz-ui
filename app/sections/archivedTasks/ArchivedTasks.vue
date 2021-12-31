<template>
    <div>

        <!-- Edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-modal="closeEditModal"
            :rowdata="logData"
            origin="archived"
        ></task-edit>

        <v-card class="mb-16">
            <v-card-title >
                Archived task list
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
                :sort-desc.sync="sortDesc"
                :sort-by.sync="sortBy"
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
                                            <v-icon v-on="on">mdi-dots-horizontal</v-icon>
                                        </template>
                                        <v-list subheader dense>
                                            <v-subheader class="pl-4 blue-grey white--text font-weight-bold white">
                                                Archived Task Action Menu
                                            </v-subheader>
                                            <v-list-item-group color="primary">
                                                <!-- <v-list-item @click="downloadTask(item,i)">
                                                    <v-list-item-icon><v-icon>mdi-file-download </v-icon></v-list-item-icon>
                                                    <v-list-item-title>Download task</v-list-item-title>
                                                </v-list-item> -->
                                                <v-list-item @click="openEditModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-archive-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task</v-list-item-title>
                                                </v-list-item>


                                                <v-list-item @click="dearchiveItem(item, i)">
                                                    <v-list-item-icon><v-icon color="red">mdi-archive-refresh</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">De-archive task</span> </v-list-item-title>
                                                </v-list-item>

                                                <v-list-item @click="deleteItem(item, i)">
                                                    <v-list-item-icon><v-icon color="red">mdi-archive-remove</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">Delete task</span> </v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
                            </td>
                            <td>
                                {{ item.task_path }}
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
                                {{ item.task_description == "" ? "--" : item.task_description }}
                            </td>
                            <td>
                                {{item.expression_readable}}
                            </td>
                            <td class="text-center">
                                {{ moment(item.storage_datetime).format('YY-MM-DD HH:mm') }}
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    {{ message }}
                </template>

            </v-data-table>

        </v-card>

        <v-speed-dial
            absolute
            fixed
            bottom
            right
            direction="left"
            transition="slide-y-reverse-transition"
            style="margin-bottom:30px;"
        >
            <template v-slot:activator>
                <v-btn
                    color="blue darken-2"
                    dark
                    fab
                >
                    <v-icon large>mdi-cog</v-icon>
                </v-btn>
            </template>
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
            sortDesc:false,
            sortBy:'',
            search: '',
            showNewTaskModal:false,
            showUploadModal: false,
            showEditModal: false,
            showLogModal: false,
            headers: [
                {
                    text: 'Actions',
                    sortable: false,
                    value: '',
                    align: 'center'
                },
                { text: 'Task', value: 'task_path' },
                { text: 'Description', value: 'task_description', sortable: false },
                { text: 'Execution', value: 'expression', sortable: false },
                { text: 'Storade date', value: 'storage_datetime', align: 'center' }
            ],
            files: [],
            editData: false,
            uploadData: false,
            logData: false,
            message: 'No archived tasks found on server. Eventually check tasks directory path.',
            reloadIntervalObj: false,
            reloadTime: 60000
        }
    },
    methods: {
        readData:function(options = {}){
            var self = this;
            var params = {}
            self.message = "Loading archived tasks";
            Utils.apiCall("get", "/task-archive/",params, options)
            .then(function (response) {
                self.files = response.data;
                if(response.data.length == 0){
                    self.message = "No archived tasks found on server. Eventually check tasks directory path."
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

            var self = this;
            var params = {
                "return_task_cont": "Y",
                "unique_id": rowdata.event_unique_key
            }

            Utils.apiCall("get", "/task/",params, {})
            .then(function (response) {

                error_dwl_msg = "Error downloading task content";

                if(response.data.length!=0){
                    task_detail = response.data[0];
                    rowdata.task_content = task_detail.task_content

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
                    }else{
                        Swal.fire({
                            title: 'ERROR',
                            text: error_dwl_msg,
                            type: 'error'
                        })
                    }
                }else{
                    Swal.fire({
                        title: 'ERROR',
                        text: error_dwl_msg,
                        type: 'error'
                    })
                }
            });
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

        dearchiveItem: function (rowdata) {
            var self = this;
            Swal.fire({
                title: 'De-archive task',
                text: "Are you sure you want to de-archive task? The task file will be renamed and the task will be visible in the dashboard.",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'De-archive',
                cancelButtonText: 'Back'
            }).then( function (result) {
                if (result.value) {
                    var params = {
                        "arch_path": rowdata.task_path
                    }
                    Utils.apiCall("post", "/task-archive/de-archive",params)
                    .then(function (response) {
                        if(response.data.result){
                            Swal.fire({
                                title: 'Task de-archived',
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
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
