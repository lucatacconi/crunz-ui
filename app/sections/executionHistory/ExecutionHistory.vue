<template>
    <div>

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
                Tasks' execution outcome list
            </v-card-title>
            <v-layout row wrap class="ma-0 mr-4 ml-4">
                <v-flex xs12 md6>
                    <v-select
                        v-model="amountLogs"
                        label="Amount of logs"
                        hide-details
                        class="mt-0 mr-2"
                        :items="['50','100','150','150+']"
                        @change="readData"
                    ></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field
                        v-model="search"
                        append-icon="mdi-magnify"
                        label="Search in log interval"
                        single-line
                        hide-details
                        class="mt-0"
                    ></v-text-field>
                </v-flex>
            </v-layout>

            <v-data-table
                :headers="headers"
                :items="tasksExecutions"
                :sort-desc.sync="sortDesc"
                :sort-by.sync="sortBy"
                :custom-sort="customSort"
                :search="search"
            >
                <template v-if="tasksExecutions.length!=0" v-slot:body="{ items }">
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
                                                Executed task Action Menu
                                            </v-subheader>
                                            <v-list-item-group color="primary">
                                                <v-list-item @click="downloadTask(item,i)">
                                                    <v-list-item-icon><v-icon>mdi-file-download</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Download task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openEditModal(item, i)">
                                                    <v-list-item-icon><v-icon>fmdi-file-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openLastLogModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-clipboard-clock</v-icon></v-list-item-icon>
                                                    <v-list-item-title>View last log</v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
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
                                {{ moment(item.execution_datatime).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                {{ item.duration == 0 ? "&lt;1" : item.duration }} min.
                            </td>
                            <td class="text-center" >
                                <v-icon v-if="item.outcome=='OK'" color="green darken-2" @click="openLogModal(item,i)" small>mdi-clipboard-clock</v-icon>
                                <v-icon v-else-if="item.outcome=='KO'" color="red" @click="openLogModal(item,i)" small>mdi-clipboard-clock</v-icon>
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
            sortBy:'execution_datatime',
            sortDesc:true,
            amountLogs:'50',
            search: '',
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
                { text: 'Exec. date and time', value: 'execution_datatime', align: 'center' },
                { text: 'Duration', value: 'duration', align: 'center' },
                { text: 'Outcome', value: 'outcome', align: 'center' }
            ],
            tasksExecutions: [],
            editData: false,
            uploadData: false,
            logData: false,
            message: 'No tasks execution log found on server.',
            reloadIntervalObj: false,
            reloadTime: 60000
        }
    },
    methods: {
        readData:function(options = {}){
            var self = this;
            var params = {
                lst_length:self.amountLogs
            }
            self.message = "Loading execution log";
            Utils.apiCall("get", "/task/exec-history",params, options)
            .then(function (response) {
                if(response.data.length!=0){
                    self.tasksExecutions = response.data;
                }else{
                    self.message = "No tasks execution log found on server."
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

        openLogModal: function (rowdata) {
            this.showLogModal = true;
            this.logData = rowdata != undefined ? rowdata : false;
        },

        openLastLogModal: function (rowdata) {
            this.showLogModal = true;

            if(rowdata != undefined){
                this.logData = JSON.parse(JSON.stringify(rowdata));
                this.logData.start = ''
            }else{
                this.logData = false;
            }
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

        scheduleReload: function () {
            var self = this;
            if(router.currentRoute.fullPath >= "/executionHistory/ExecutionHistory"){

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
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime()),
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
