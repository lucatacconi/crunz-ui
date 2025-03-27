<template>
    <div>

        <!-- New task modal -->
        <new-task
            v-if="showNewTaskModal"
            :old-task-content="oldTaskContent"
            @on-close-modal="closeNewTaskModal($event)"
        ></new-task>

        <!-- Upload file modal -->
        <tasks-upload
            v-if="showUploadModal"
            @on-close-modal="closeUploadModal($event)"
        ></tasks-upload>

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

        <!-- Move modal -->
        <task-move
            v-if="showMoveModal"
            @on-close-modal="closeMoveModal"
            :rowdata="logData"
        ></task-move>

        <v-card class="mb-16">
            <v-card-title >
                Task list
                <v-spacer></v-spacer>
                <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Search (Use + to concatenate search criteria)"
                    single-line
                    hide-details
                    class="mt-0"
                ></v-text-field>
                <v-tooltip bottom>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            fab
                            rounded
                            :outlined="!caseSensitive"
                            @click="caseSensitive=!caseSensitive;customSearch(search)"
                            color="green"
                            dark
                            x-small
                            class="mt-2"
                            v-bind="attrs"
                            v-on="on"
                        >
                            <v-icon>
                                mdi-format-letter-case
                            </v-icon>
                        </v-btn>
                    </template>
                    <span>Case sensitive search ON/OFF</span>
                </v-tooltip>
            </v-card-title>

            <v-data-table
                :headers="headers"
                :items="search.length > 0 ? searchResult : files"
                :sort-desc.sync="sortDesc"
                :sort-by.sync="sortBy"
                :custom-sort="customSort"
                :items-per-page="10"
                :footer-props='{ "items-per-page-options": [10, 30, 50, -1]}'
            >
                <template v-slot:body="{ items }">
                    <tbody v-if="items.length!=0">
                        <tr v-for="(item,i) in items" :key="i">
                            <td>
                                <div class="text-center">
                                    <v-menu offset-y>
                                        <template v-slot:activator="{ on }">
                                            <v-icon v-on="on">mdi-dots-horizontal</v-icon>
                                        </template>
                                        <v-list subheader dense>
                                            <v-subheader class="pl-4 blue-grey white--text font-weight-bold white">
                                                Task Action Menu
                                            </v-subheader>
                                            <v-list-item-group color="primary">
                                                <v-list-item @click="executeItem(item, false)" :disabled="localStorage.getItem('taskExecutionEnabled') == 'false'">
                                                    <template v-if="localStorage.getItem('taskExecutionEnabled') == 'true'">
                                                        <v-list-item-icon><v-icon color="orange">mdi-play</v-icon></v-list-item-icon>
                                                        <v-list-item-title> <span class="orange--text">Execute</span> </v-list-item-title>                                                    </template>
                                                    <template v-else>
                                                        <v-list-item-icon><v-icon color="grey lighten-3">mdi-play</v-icon></v-list-item-icon>
                                                        <v-list-item-title> <span>Execute</span> </v-list-item-title>
                                                    </template>
                                                </v-list-item>
                                                <v-list-item @click="executeItem(item, true)" :disabled="localStorage.getItem('taskExecutionEnabled') == 'false'">
                                                    <template v-if="localStorage.getItem('taskExecutionEnabled') == 'true'">
                                                        <v-list-item-icon><v-icon color="orange">mdi-clock</v-icon></v-list-item-icon>
                                                        <v-list-item-title> <span class="orange--text">Execute and wait log</span> </v-list-item-title>                                                    </template>
                                                    <template v-else>
                                                        <v-list-item-icon><v-icon color="grey lighten-3">mdi-clock</v-icon></v-list-item-icon>
                                                        <v-list-item-title> <span>Execute and wait log</span> </v-list-item-title>
                                                    </template>
                                                </v-list-item>
                                                <v-list-item @click="openLogModal(item, i)" :class="item.last_outcome=='OK'||item.last_outcome=='KO' ? '' : 'd-none'">
                                                    <v-list-item-icon><v-icon>mdi-comment-check</v-icon></v-list-item-icon>
                                                    <v-list-item-title>View last log</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="downloadTask(item,i)">
                                                    <v-list-item-icon><v-icon>mdi-file-download</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Download task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openEditModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-file-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openMoveModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-file-move</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Move/rename task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openNewTaskModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-content-duplicate</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Clone task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="archiveItem(item, i)">
                                                    <v-list-item-icon><v-icon color="red">mdi-archive</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">Archive task</span> </v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="deleteItem(item, i)">
                                                    <v-list-item-icon><v-icon color="red">mdi-delete</v-icon></v-list-item-icon>
                                                    <v-list-item-title > <span class="red--text">Delete task</span> </v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
                            </td>
                            <td>
                                <div>
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
                                </div>
                                <template v-if="ifClipboardEnabled">
                                    <v-tooltip bottom>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-chip
                                                outlined
                                                small
                                                link
                                                v-bind="attrs"
                                                v-on="on"
                                                @click="navigator.clipboard.writeText(item.event_unique_key)"
                                                class="caption grey--text mb-2"
                                            >
                                                {{ item.event_unique_key }}
                                            </v-chip>
                                        </template>
                                        <span>Click to copy Event ID</span>
                                    </v-tooltip>
                                </template>
                                <template v-else>
                                    <caption class="grey--text">{{ item.event_unique_key }}</caption>
                                </template>
                            </td>
                            <td>
                                {{ item.task_description == "" ? "--" : item.task_description }}
                            </td>
                            <td>
                                {{item.expression_readable}}
                            </td>
                            <td class="text-center">
                                {{ item.next_run == "" ? "Expired" : dayjs(item.next_run).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                {{ item.last_run == "" ? "" : dayjs(item.last_run).format('YY-MM-DD HH:mm') }}

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
                                <v-icon v-if="item.last_outcome=='OK'" color="green darken-2" @click="openLogModal(item,i)" small>mdi-comment-check</v-icon>
                                <v-icon v-else-if="item.last_outcome=='KO'" color="red" @click="openLogModal(item,i)" small>mdi-comment-alert</v-icon>
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
        <actions-buttons v-on:read-data="readData()" v-on:export-task-list="exportTaskList()" v-on:edit-modal="opendEditModal()" v-on:new-task-modal="openNewTaskModal()" v-on:upload-modal="openUploadModal()"></actions-buttons>

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
            showMoveModal: false,
            showLogModal: false,
            headers: [
                {
                    text: 'Actions',
                    sortable: false,
                    value: '',
                    align: 'center'
                },
                { text: 'Task file / Event ID', value: 'task_path' },
                { text: 'Description', value: 'task_description', sortable: false },
                { text: 'Execution', value: 'expression_readable', sortable: false },
                { text: 'Next execution', value: 'next_run', align: 'center' },
                { text: 'Last execution', value: 'last_run', align: 'center' },
                { text: 'Last duration', value: 'last_duration', align: 'center' },
                { text: 'Last exec. outcome', value: 'last_outcome', align: 'center' },
            ],
            files: [],
            searchResult: [],
            caseSensitive:false,
            oldTaskContent:null,
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
            var params = {}
            self.message = "Loading tasks";
            Utils.apiCall("get", "/task/",params, options)
            .then(function (response) {
                self.files = response.data;
                if(self.search.length > 0) self.customSearch(self.search);
                if(response.data.length == 0){
                    self.message = "No tasks found on server. Eventually check tasks directory path."
                }
            });
        },

        exportTaskList:function(){

            var self = this;
            var params = {}

            Utils.apiCall("get", "/task/export",params, {})
            .then(function (response) {

                error_dwl_msg = "Error exporting task list";

                if(response.data.length != 0 && response.data.content != '' && response.data.filename != ''){
                    var dec = atob(response.data.content);
                    Utils.downloadFile(dec, response.data.filename);
                } else {
                    Utils.showAlertDialog('ERROR', error_dwl_msg, 'error');
                }
            });
        },

        customSearch: function (val){
            var res=[];
            var searchInProperties=[];
            var extraSearchInProperties=[
                "event_unique_key"
            ];
            for(var i=0;i<this.headers.length;i++){
                if(this.headers[i]['value']==undefined || this.headers[i]['value']=='') continue;
                searchInProperties.push(this.headers[i]['value']);
            }
            searchInProperties=searchInProperties.concat(extraSearchInProperties);

            var split=[];

            split=val.split("+");
            var count=0;

            for(var k=0;k<this.files.length;k++){
                count=0;
                var find=[];
                for(var i=0;i<searchInProperties.length;i++){
                    if(this.files[k][searchInProperties[i]] == undefined || this.files[k][searchInProperties[i]] == '' || typeof this.files[k][searchInProperties[i]] == 'boolean' || this.files[k][searchInProperties[i]] == 'object') continue;

                    var valSearchProperties=String(this.files[k][searchInProperties[i]]);
                    var valSearch=val;

                    for(var c=0;c<split.length;c++){
                        valSearch=split[c];
                        if(!this.caseSensitive){
                            valSearchProperties=valSearchProperties.toLowerCase();
                            valSearch=valSearch.toLowerCase();
                        }
                        if(valSearchProperties.includes(valSearch)){
                            if(find.includes(valSearch)) continue;
                            find.push(valSearch);
                            count++;
                        }
                    }
                }
                if(count>=split.length){
                    res.push(this.files[k]);
                }
            }

            this.searchResult=res;
        },

        customSort(items, index, isDesc) {

            items.sort((a, b) => {

                if (index[0] === "last_outcome") {

                    if(a[index[0]] == "OK"){
                        a_conv = 2;
                    }else if(a[index[0]] == "KO"){
                        a_conv = 1;
                    }else{
                        a_conv = 0;
                    }

                    if(b[index[0]] == "OK"){
                        b_conv = 2;
                    }else if(b[index[0]] == "KO"){
                        b_conv = 1;
                    }else{
                        b_conv = 0;
                    }

                    if (isDesc[0]) {
                        return a_conv >= b_conv ? 1 : -1;
                    } else {
                        return a_conv >= b_conv ? -11 : 1;
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
                            Utils.showAlertDialog('Task content empty','Task content is empty','error');
                            return;
                        }
                        if(rowdata.filename == ''){
                            Utils.showAlertDialog('Filename empty','Filename is empty','error');
                            return;
                        }
                        var dec = atob(rowdata.task_content);
                        Utils.downloadFile(dec,rowdata.filename);
                    }else{
                        Utils.showAlertDialog('ERROR',error_dwl_msg,'error');
                    }
                }else{
                    Utils.showAlertDialog('ERROR',error_dwl_msg,'error');
                }
            });
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

        openNewTaskModal: function (item) {
            this.oldTaskContent=null;
            if(item!=undefined){
                this.oldTaskContent = {
                    subdir: item.subdir,
                    real_path: item.real_path,
                    task_path: item.task_path,
                    filename: item.filename,
                    event_unique_key: item.event_unique_key
                }
            };
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

        openMoveModal: function (rowdata) {
            this.showMoveModal = true;
            this.logData = rowdata != undefined ? rowdata : false;
        },
        closeMoveModal: function (result) {
            this.showMoveModal = false;
            if(typeof result !== 'undefined' && result){
                this.readData();
            }
        },

        archiveItem: function (rowdata) {
            var self = this;
            Utils.showAlertDialog(
                'Archive task',
                'Are you sure you want to archive task? The task file will be renamed and the task will no longer be visible in the dashboard.',
                'warning',{
                    showCancelButton: true,
                    confirmButtonText: 'Archive',
                },()=>{
                var params = {
                    "task_path": rowdata.task_path
                }
                Utils.apiCall("post", "/task-archive/archive",params)
                .then(function (response) {
                    if(response.data.result){
                        Utils.showAlertDialog('Task archived',response.data.result_msg,'success');
                        self.readData();
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });
            });
        },

        deleteItem: function (rowdata) {
            var self = this;
            Utils.showAlertDialog(
                'Delete task',
                'Are you sure you want to delete task?',
                'warning',{
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                },()=>{
                var params = {
                    "task_path": rowdata.task_path
                }
                Utils.apiCall("delete", "/task/",params)
                .then(function (response) {
                    if(response.data.result){
                        Utils.showAlertDialog('Task deleted',response.data.result_msg,'success');
                        self.readData();
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });
            });
        },

        executeItem: function (item, wait) {
            var self = this;
            var params = {
                "event_unique_key": item.event_unique_key,
                "exec_and_wait": wait ? 'Y' : 'N'
            }

            if(wait){
                if(this.reloadIntervalObj) clearTimeout(this.reloadIntervalObj);

                Utils.apiCall("post", "/task/execute", params)
                .then(function (response) {
                    if(response.data.result){
                        self.openLogModal(response.data);
                        self.readData();
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });

            }else{
                Utils.apiCall("post", "/task/execute", params)
                .then(function (response) {
                    if(response.data.result){
                        Utils.showAlertDialog('Task launched. Execution in progress',response.data.result_msg,'success');
                    }else{
                        Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                    }
                });
            }
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

    computed: {
        ifClipboardEnabled: function () {
            return Utils.ifClipboardEnabled();
        }
    },

    watch: {
        search: function (val) {
            this.customSearch(val);
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
        'tasks-upload': httpVueLoader('../../shareds/TasksUpload.vue' + '?v=' + new Date().getTime()),
        'new-task': httpVueLoader('../../shareds/NewTask.vue' + '?v=' + new Date().getTime()),
        'task-move': httpVueLoader('../../shareds/MoveTask.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime()),
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
