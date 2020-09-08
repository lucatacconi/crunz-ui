<template>
    <div>

        <!-- Detail modal -->
        <task-detail
            v-if="showDetailModal"
            @on-close-modal="closeDetailModal"
            @on-open-log-modal="openLogModal"
            :rowdata="selectedEvent"
        ></task-detail>

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

        <v-card>
            <v-row class="fill-height">
                <v-col class="py-0">

                    <v-sheet height="64">
                        <v-toolbar flat color="white">
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
                            type="month"
                            color="primary"
                            event-height=18

                            @click:more="viewDay"
                            @click:date="viewDay"
                            @click:event="openDetailModal"
                        ></v-calendar>
                    </v-sheet>
                </v-col>
            </v-row>
        </v-card>

        <!-- Actions buttons -->
        <actions-buttons v-on:read-data="readData()" v-on:upload-modal="openUploadModal()"></actions-buttons>

    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            tasks: [],
            showUploadModal:false,
            showLogModal:false,
            showDetailModal: false,
            selectedEvent: false,
            logData:false,

            dateFocus: moment().format('YYYY-MM-DD')
        }
    },

    mounted () {
        this.$refs.calendar.checkChange();
        this.readData();
    },

    computed: {
        title: function(){
            return moment(this.dateFocus, 'YYYY-MM-DD').format('MMMM YYYY').toString();
        }
    },

    methods: {

        setToday: function(){
            this.dateFocus = moment().format('YYYY-MM-DD');
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

        stringToColour: function(str){
            for (var i = 0, hash = 0; i < str.length; hash = str.charCodeAt(i++) + ((hash << 5) - hash));
            color = Math.floor(Math.abs((Math.sin(hash) * 10000) % 1 * 16777216)).toString(16);
            return '#' + Array(6 - color.length + 1).join('0') + color;
        },

        getEventColor: function(event) {
            return event.color;
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
                "interval_from": moment(self.dateFocus,'YYYY-MM-DD').set('date',1).format('YYYY-MM-DD').toString(),
                "interval_to": moment(self.dateFocus,'YYYY-MM-DD').endOf('month').format('YYYY-MM-DD').toString()
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
                                    event_name = "(HF) " + event_name;
                                }

                                let obj_temp={
                                    name: event_name,
                                    start: interval_data_start,
                                    end: response.data[i].interval_run_lst[interval_data_start],
                                    details: response.data[i].task_description,
                                    color: self.stringToColour(response.data[i].task_path),
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
    },

    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue' + '?v=' + new Date().getTime()),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue' + '?v=' + new Date().getTime()),
        'task-detail': httpVueLoader('../../shareds/TaskDetail.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime())
    }
}
</script>
