<template>
    <div>

        <!-- New task modal -->
        <new-task
            v-if="showNewTaskModal"
            :old-task-content="oldTaskContent"
            @on-close-modal="closeNewTaskModal($event)"
            origin="linted"
        ></new-task>

        <!-- Edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-modal="closeEditModal"
            :rowdata="logData"
            origin="linted"
        ></task-edit>

        <v-card class="mb-16">
            <v-card-title >
                Verified task files
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
                                                <v-list-item @click="downloadTask(item,i)">
                                                    <v-list-item-icon><v-icon>mdi-file-download</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Download task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openEditModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-file-edit</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Edit task</v-list-item-title>
                                                </v-list-item>
                                                <v-list-item @click="openNewTaskModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-content-duplicate</v-icon></v-list-item-icon>
                                                    <v-list-item-title>Clone task</v-list-item-title>
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
                                {{ item.task_path }}
                            </td>
                            <td class="text-center">
                                {{ item.modification_date }}
                            </td>
                            <td class="text-center">
                                <v-icon
                                    :color="item.syntax_check ? 'green' : 'red'"
                                    small
                                >
                                    {{ item.syntax_check ? 'mdi-check-circle' : 'mdi-alert-circle' }}
                                </v-icon>
                            </td>
                            <td>
                                {{ item.error_detected == "" ? "--" : item.error_detected }}
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
            showEditModal: false,
            showNewTaskModal:false,
            headers: [
                {
                    text: 'Actions',
                    sortable: false,
                    value: '',
                    align: 'center'
                },
                { text: 'Task path', value: 'task_path' },
                { text: 'Last modification', value: 'modification_date', align: 'center', sortable: false },
                { text: 'Syntax check', value: 'syntax_check', align: 'center', sortable: true },
                { text: 'Error detected', value: 'error_detected', align: 'left', sortable: false },
            ],
            files: [],
            searchResult: [],
            caseSensitive:false,
            oldTaskContent:null,
            editData: false,
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
            Utils.apiCall("get", "/task/lint",params, options)
            .then(function (response) {
                self.files = response.data;
                if(self.search.length > 0) self.customSearch(self.search);
                if(response.data.length == 0){
                    self.message = "No tasks found on server. Eventually check tasks directory path."
                }
            });
        },

        customSearch: function (val){
            var res=[];
            var searchInProperties=[];
            var extraSearchInProperties=[
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
                "task_path": rowdata.task_path
            }

            Utils.apiCall("get", "/task/draft",params, {})
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
                        return;
                    }
                }else{
                    Utils.showAlertDialog('ERROR',error_dwl_msg,'error');
                    return;
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

        deleteItem: function (rowdata) {
            var self = this;
            Utils.showAlertDialog(
                'Delete task',
                'Are you sure you want to delete task?',
                'warning',{
                    showCancelButton: true,
                    confirmButtonColor: '#f86c6b',
                    cancelButtonColor: '#20a8d8',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Back'
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

        scheduleReload: function () {
            var self = this;
            if(router.currentRoute.fullPath >= "/taskLinter/TaskLinter"){

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
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime()),
        'new-task': httpVueLoader('../../shareds/NewTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
