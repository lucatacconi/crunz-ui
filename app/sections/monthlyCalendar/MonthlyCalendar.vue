<template>
    <div>

        <!-- New task modal -->
        <new-task
            v-if="showNewTaskModal"
            @on-close-modal="closeNewTaskModal($event)"
        ></new-task>

        <!-- Detail modal -->
        <task-detail
            v-if="showDetailModal"
            @on-close-modal="closeDetailModal"
            @on-open-log-modal="openLogModal"
            :rowdata="selectedEvent"
        ></task-detail>

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

        <v-card>
            <v-row class="fill-height">
                <v-col class="py-0">

                    <v-sheet height="64">
                        <v-toolbar flat>
                            <v-btn outlined class="mr-4" @click = "setToday">
                                Today
                            </v-btn>
                            <v-btn fab text small @click = "prev">
                                <v-icon small>mdi-chevron-left</v-icon>
                            </v-btn>
                            <v-toolbar-title>{{ title }}</v-toolbar-title>
                            <v-btn fab text small @click = "next">
                                <v-icon small>mdi-chevron-right</v-icon>
                            </v-btn>
                        </v-toolbar>
                    </v-sheet>

                    <v-sheet>
                        <v-calendar
                            ref="calendar"
                            v-model="dateFocus"
                            :events="tasks"
                            :event-color="getEventColor"
                            :event-text-color="getEventTextColor"
                            type="month"
                            color="primary"
                            event-height=18
                            :event-more=false
                            :weekdays="[1, 2, 3, 4, 5, 6, 0]"

                            @click:more="viewDay"
                            @click:date="viewDay"
                            @click:event="openDetailModal"
                        ></v-calendar>
                    </v-sheet>
                </v-col>
            </v-row>
        </v-card>

        <v-alert
            dense
            border="left"
            colored-border
            type="info"
            elevation="2"
            class="mt-5"
        >
            <strong>HFT</strong> is the indicator of an high frequency task, that's a task carried out many times per hour. To avoid saturating the display, in the calendar representations, both monthly and weekly, it will be shown only once a day.
        </v-alert>

        <!-- Actions buttons -->
        <actions-buttons v-on:read-data="readData()" v-on:export-task-list="exportTaskList()" v-on:new-task-modal="openNewTaskModal()" v-on:upload-modal="openUploadModal()"></actions-buttons>

    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            tasks: [],
            showNewTaskModal:false,
            showUploadModal:false,
            showLogModal:false,
            showDetailModal: false,
            selectedEvent: false,
            logData:false,

            dateFocus: dayjs().format('YYYY-MM-DD')
        }
    },

    mounted () {
        this.$refs.calendar.checkChange();
        this.readData();
    },

    computed: {
        title: function(){
            return dayjs(this.dateFocus, 'YYYY-MM-DD').format('MMMM YYYY').toString();
        }
    },

    methods: {

        setToday: function(){
            this.dateFocus = dayjs().format('YYYY-MM-DD');
            this.readData();
        },

        prev: function(){
            this.$refs.calendar.prev();
            this.readData();
        },

        next: function(){
            this.$refs.calendar.next();
            this.readData();
        },

        stringToColor: function(str){
            for (var i = 0, hash = 0; i < str.length; hash = str.charCodeAt(i++) + ((hash << 5) - hash));
            color = Math.floor(Math.abs((Math.sin(hash) * 10000) % 1 * 16777216)).toString(16);
            return '#' + Array(6 - color.length + 1).join('0') + color;
        },

        getEventColor: function(event) {
            return event.color;
        },
        getEventTextColor: function(event) {
            return event.colorText;
        },

        viewDay: function({ date }) {
            if(date != undefined){
                this.focus = date;
            }
            sessionStorage.setItem('date',date);
            this.$router.push({ path: '/menu/DailyCalendar' });
        },

        openDetailModal: function ({ nativeEvent, event }) {
            this.showDetailModal = true;
            this.selectedEvent = event != undefined ? event : false;
        },
        closeDetailModal: function(){
            this.showDetailModal = false;
        },

        openUploadModal: function () {
            this.showUploadModal = true;
        },
        closeUploadModal: function () {
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

        openLogModal: function () {
            this.logData = this.selectedEvent;
            this.showLogModal = true;
        },
        closeLogModal: function () {
            this.showLogModal = false;
        },

        readData: function(){

            var self = this;

            var apiParams={
                "calc_run_lst": 'Y',
                "outcome_executed_task_lst": "Y",
                "interval_from": dayjs(self.dateFocus,'YYYY-MM-DD').set('date',1).format('YYYY-MM-DD').toString(),
                "interval_to": dayjs(self.dateFocus,'YYYY-MM-DD').endOf('month').format('YYYY-MM-DD').toString()
            }

            Utils.apiCall("get", "/task/", apiParams)
            .then(function (response) {
                if( typeof response === 'undefined' || response === null ){
                    Utils.showConnError();
                }else{
                    if(response.data.length!=0){
                        var arr_temp=[];

                        for(let i=0; i<response.data.length; i++){
                            for (let interval_data_start in response.data[i].interval_run_lst) {

                                let tmp = JSON.parse(JSON.stringify(response.data[i]));
                                delete tmp.interval_run_lst;

                                let event_name = (response.data[i].filename.replace('.php', '').length > self.taskTitleLength) ? response.data[i].filename.replace('.php', '').substring(0, (self.taskTitleLength + 2))+".." : response.data[i].filename.replace('.php', '');
                                if(response.data[i].high_frequency){
                                    event_name = "HFT - " + event_name;
                                }

                                let event_color = self.stringToColor(response.data[i].task_path);

                                r = parseInt(event_color.slice(1,3),16);
                                g = parseInt(event_color.slice(3,5),16);
                                b = parseInt(event_color.slice(5,7),16);
                                yiq = ( (r * 299) + (g * 587) + (b * 114)) / 1000;

                                let event_color_text = "#FFFFFF";
                                if (yiq >= 100) {
                                    event_color_text = "#141414";
                                }

                                let obj_temp={
                                    name: event_name,
                                    start: interval_data_start,
                                    end: response.data[i].interval_run_lst[interval_data_start],
                                    details: response.data[i].task_description,
                                    color: event_color,
                                    colorText: event_color_text,
                                    data: tmp
                                }

                                arr_temp.push(obj_temp);
                            }
                        }
                        self.tasks = arr_temp;
                    }
                }
            });
        },

        exportTaskList:function(){

            var self = this;
            var params = {}

            Utils.apiCall("get", "/task/export",params, {})
            .then(function (response) {

                let error_dwl_msg = "Error exporting task list";

                if(response.data.length!=0){
                    if(response.data.content != '' && response.data.filename != ''){
                        var dec = atob(response.data.content);
                        Utils.downloadFile(dec,response.data.filename);
                    }else{
                        Utils.showAlertDialog('ERROR',error_dwl_msg,'error');
                    }
                }else{
                    Utils.showAlertDialog('ERROR',error_dwl_msg,'error');
                }
            });
        }
    },

    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue' + '?v=' + new Date().getTime()),
        'tasks-upload': httpVueLoader('../../shareds/TasksUpload.vue' + '?v=' + new Date().getTime()),
        'task-detail': httpVueLoader('../../shareds/TaskDetail.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime()),
        'new-task': httpVueLoader('../../shareds/NewTask.vue' + '?v=' + new Date().getTime())
    }
}
</script>
