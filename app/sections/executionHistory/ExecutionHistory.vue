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

        <!-- New task modal -->
        <new-task
            v-if="showNewTaskModal"
            :old-task-content="oldTaskContent"
            @on-close-modal="closeNewTaskModal($event)"
        ></new-task>

        <!-- Picker modal -->
        <picker-modal @result="closePicker($event)" ref="picker"></picker-modal>

        <v-card class="mb-16">
            <v-card-title >
                Tasks' execution list
            </v-card-title>
            <validationobserver ref="observer">
                <v-form>
                    <v-layout row wrap class="ma-0 mr-4 ml-4">
                        <v-flex xs12 md6>
                            <v-autocomplete
                                v-model="search_params.taskPath"
                                ref="selTaskPath"
                                :items="task_path_lovs"
                                label="Task path"
                                hide-details
                                class="mt-0 mr-md-2"
                                append-icon="mdi-filter-remove-outline"
                                @click:append="search_params.taskPath = null"
                                no-data-text="No task files with this path. Check the filter used."
                            ></v-autocomplete>
                        </v-flex>
                        <v-flex xs12 md6>
                            <validationprovider name="Event univoque id" rules="length:32" v-slot="{ errors }">
                                <v-text-field
                                    v-model="search_params.eventUniqueId"
                                    label="Event ID"
                                    single-line
                                    hide-details="auto"
                                    class="mt-0"
                                    :error-messages="errors[0]"
                                    maxlength="32"
                                    append-icon="mdi-filter-remove-outline"
                                    @click:append="search_params.eventUniqueId = null"
                                ></v-text-field>
                            </validationprovider>
                        </v-flex>
                        <v-flex xs12 md6>
                            <validationprovider name="Execution internal from" rules="date_format" v-slot="{ errors }">
                                <v-text-field
                                    id="execution_interval_from"
                                    v-model="search_params.executionIntervalFrom"
                                    label="Execution internal from"
                                    hide-details="auto"
                                    single-line
                                    class="mt-3 mr-md-2"
                                    :error-messages="errors[0]"
                                    maxlength="16"
                                    append-icon="mdi-calendar"
                                    @click:append="openPicker('executionIntervalFrom','execution_interval_from')"
                                    append-outer-icon="mdi-filter-remove-outline"
                                    @click:append-outer="search_params.executionIntervalFrom = null"
                                ></v-text-field>
                            </validationprovider>
                        </v-flex>
                        <v-flex xs12 md6>
                            <validationprovider name="Execution internal to" rules="date_format|confirm_to_date:@Execution internal from" v-slot="{ errors }">
                                <v-text-field
                                    id="execution_interval_to"
                                    v-model="search_params.executionIntervalTo"
                                    label="Execution internal to"
                                    hide-details="auto"
                                    single-line
                                    class="mt-3"
                                    :error-messages="errors[0]"
                                    maxlength="16"
                                    append-icon="mdi-calendar"
                                    @click:append="openPicker('executionIntervalTo','execution_interval_to')"
                                    append-outer-icon="mdi-filter-remove-outline"
                                    @click:append-outer="search_params.executionIntervalTo = null"
                                ></v-text-field>
                            </validationprovider>
                        </v-flex>
                        <v-flex xs12 md6>
                            <v-select
                                v-model="search_params.amountLogs"
                                label="Amount of logs"
                                hide-details
                                class="mt-3 mr-md-2"
                                :items="['50','100','200','300','All']"
                            ></v-select>
                        </v-flex>
                        <v-flex xs12 md6>
                            <v-btn
                                class="mt-5 mr-md-2"
                                outlined
                                color="red"
                                block
                                @click="launchSearch()"
                            >
                                <v-icon left>
                                    mdi-magnify
                                </v-icon>
                                Search
                            </v-btn>
                        </v-flex>
                        <v-flex xs12 md6 offset-md6 class="text-right">
                            <v-text-field
                                v-model="search"
                                append-icon="biotech"
                                label="Search within the search result (+ to concatenate criteria)"
                                single-line
                                hide-details
                            >
                                <template v-slot:append-outer>
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
                                </template>
                            </v-text-field>
                        </v-flex>
                    </v-layout>
                </v-form>
            </validationobserver>

            <v-data-table
                :headers="headers"
                :items="search.length > 0 ? searchResult : tasksExecutions"
                :sort-desc.sync="sortDesc"
                :sort-by.sync="sortBy"
                :custom-sort="customSort"
                :items-per-page="10"
                :footer-props='{ "items-per-page-options": [10, 30, 50, -1]}'
                class="mt-3"
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
                                                Executed task Action Menu
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
                                                <v-list-item @click="openLastLogModal(item, i)">
                                                    <v-list-item-icon><v-icon>mdi-comment-check</v-icon></v-list-item-icon>
                                                    <v-list-item-title>View last log</v-list-item-title>
                                                </v-list-item>
                                            </v-list-item-group>
                                        </v-list>
                                    </v-menu>
                                </div>
                            </td>
                            <td>
                                <div>
                                    {{ item.task_path }}
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
                                                @click="copyToClipboad(item.event_unique_key)"
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
                                {{ dayjs(item.execution_datatime).format('YY-MM-DD HH:mm') }}
                            </td>
                            <td class="text-center">
                                {{ item.duration == 0 ? "&lt;1" : item.duration }} min.
                            </td>
                            <td class="text-center" >
                                <v-icon v-if="item.outcome=='OK'" color="green darken-2" @click="openLogModal(item,i)" small>mdi-comment-check</v-icon>
                                <v-icon v-else-if="item.outcome=='KO'" color="red" @click="openLogModal(item,i)" small>mdi-comment-alert</v-icon>
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
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            task_path_lovs:[],
            search_params:{
                taskPath:null,
                eventUniqueId:null,
                executionIntervalFrom:null,
                executionIntervalTo:null,
                amountLogs:'50'
            },
            pointer:null,
            sortBy:'execution_datatime',
            sortDesc:true,
            search: '',
            showEditModal: false,
            showNewTaskModal:false,
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
                { text: 'Execution', value: 'expression', sortable: false },
                { text: 'Exec. date and time', value: 'execution_datatime', align: 'center' },
                { text: 'Duration', value: 'duration', align: 'center' },
                { text: 'Outcome', value: 'outcome', align: 'center' }
            ],
            tasksExecutions: [],
            searchResult: [],
            caseSensitive:false,
            oldTaskContent:null,
            editData: false,
            uploadData: false,
            logData: false,
            message: 'No tasks execution log selected by criteria. Modify your search criteria and press Search button.',

            clipboad_enabled: false
        }
    },
    methods: {
        openPicker:function(key,fieldId){
            document.getElementById(fieldId).focus();
            this.pointer = key;
            this.$refs.picker.showDatePickerModal(this.search_params[this.pointer], true);
        },
        closePicker:function(value){
            var self=this
            this.search_params[this.pointer] = value;
            this.pointer = null;
        },

        copyToClipboad:function(value){
            navigator.clipboard.writeText(value);
        },

        launchSearch:function(){
            var self = this;

            this.$refs.observer.validate()
            .then(form_valid => {

                var params = {
                    unique_id:self.search_params.eventUniqueId,
                    task_path:self.search_params.taskPath,
                    interval_from:self.search_params.executionIntervalFrom,
                    interval_to:self.search_params.executionIntervalTo
                };

                if(!isNaN(self.search_params.amountLogs)){
                    params.lst_length = self.search_params.amountLogs;
                }

                if(form_valid){
                    Utils.apiCall("get", "/task/exec-history",params)
                    .then(function (response) {
                        self.tasksExecutions = response.data;
                        if(response.data.length==0){
                            self.message = "No tasks execution log selected by criteria. Modify your search criteria and press Search button.";
                        }
                    });
                }

            }, failure => {
                failure;
                return false;
            })
        },

        readData:function(options = {}){
            var self = this;
            var params = {
                lst_length:self.search_params.amountLogs
            };
            self.message = "Loading execution log";
            Utils.apiCall("get", "/task/exec-history",params, options)
            .then(function (response) {
                self.tasksExecutions = response.data;
                if(self.search.length > 0) self.customSearch(self.search);
                if(response.data.length == 0){
                    self.message = "No tasks execution log selected by criteria. Modify your search criteria and press Search button.";
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

            for(var k=0;k<this.tasksExecutions.length;k++){
                count=0;
                var find=[];
                for(var i=0;i<searchInProperties.length;i++){
                    if(this.tasksExecutions[k][searchInProperties[i]] == undefined || this.tasksExecutions[k][searchInProperties[i]] == '' || typeof this.tasksExecutions[k][searchInProperties[i]] == 'boolean' || this.tasksExecutions[k][searchInProperties[i]] == 'object') continue;

                    var valSearchProperties=String(this.tasksExecutions[k][searchInProperties[i]]);
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
                    res.push(this.tasksExecutions[k]);
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
        readLovs:function(options={}){
            var self = this;
            self.search_params.taskPath = null
            self.search_params.eventUniqueId = null
            self.search_params.executionIntervalFrom = null
            self.search_params.executionIntervalTo = null
            self.search_params.amountLogs = '50'
            Utils.apiCall("get", "/task/filename",{},options)
            .then(function (response) {
                self.task_path_lovs=[]
                for(var i=0; i<response.data.length; i++){
                    self.task_path_lovs.push(response.data[i].task_path)
                }
            });

            self.search_params.executionIntervalFrom = dayjs().subtract(1, 'days').format('YYYY-MM-DD HH:mm');
            self.search_params.executionIntervalTo = dayjs().format('YYYY-MM-DD HH:mm');
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
        }
    },

    watch: {
        search: function (val) {
            this.customSearch(val);
        }
    },

    computed: {
        ifClipboardEnabled: function () {
            return Utils.ifClipboardEnabled();
        }
    },

    created:function() {

        VeeValidate.extend('date_format', value => {

            var regex_date_or_datetime_second = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$|^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_date_or_datetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d)$|^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlydatetime_second = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$/gm);
            var regex_onlydatetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d)$/gm);
            var regex_onlydate = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlytime_second = new RegExp(/(?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)/gm);
            var regex_onlytime = new RegExp(/(?:[01]\d|2[0123]):(?:[012345]\d)/gm);

            if(regex_date_or_datetime.test(value)) return true;

            return 'The date of {_field_} is invalid';
        });

        VeeValidate.extend('confirm_to_date', {
            params: ['target'],
            validate(value, { target }) {
                if(target == undefined ||target == null || target == '') return false;
                if(value == target) return true;
                if(dayjs(value).isAfter(target)){
                    return true;
                }
                return false;
            },
            message: 'Date range is incorrect'
        });
    },

    mounted:function(){
        this.readLovs();
    },

    components:{
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime()),
        'task-edit': httpVueLoader('../../shareds/EditTask.vue' + '?v=' + new Date().getTime()),
        'picker-modal': httpVueLoader('../../shareds/PickerModal.vue' + '?v=' + new Date().getTime())
    }
}
</script>
