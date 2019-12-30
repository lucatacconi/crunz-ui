<template>
    <div>

        <!-- Upload file modal -->
        <task-upload
            v-if="showUploadModal"
            @on-close-edit-modal="closeUploadModal($event)"
            :rowdata="uploadData"
        ></task-upload>

        <!-- Log modal -->
        <task-log
            v-if="showLogModal"
            @on-close-edit-modal="closeLogModal"
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

                    <v-sheet height="600">
                        <v-calendar
                            ref="calendar"
                            v-model="focus"
                            :weekdays="[1, 2, 3, 4, 5, 6, 0]"
                            type="month"
                            locale="en-EN"
                            :short-months=false
                            :short-weekdays=false
                            color="primary"
                            :events="tasks"
                            :event-color="getEventColor"
                            :event-margin-bottom="3"
                            :now="today"

                            @click:event="showEvent"
                            @click:more="viewDay"
                            @click:date="viewDay"
                        ></v-calendar>
                        <v-menu
                            v-model="selectedOpen"
                            :close-on-content-click="false"
                            :activator="selectedElement"
                            full-width
                            offset-x
                        >
                            <v-card
                                min-width="800px"
                            >
                                <v-toolbar
                                    :color="selectedEvent.color"
                                    dark
                                    dense
                                >
                                    <v-toolbar-title v-html="selectedEvent.name"></v-toolbar-title>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        icon
                                        @click="openLogModal(selectedEvent)"
                                        v-if="checkLog(selectedEvent)"
                                    >
                                        <v-icon>fas fa-file-alt</v-icon>
                                    </v-btn>
                                    <v-btn
                                        icon
                                        @click="selectedOpen = false"
                                    >
                                        <v-icon>mdi-close</v-icon>
                                    </v-btn>
                                </v-toolbar>
                                <v-card-text>
                                    <v-layout class="pr-3 pl-3" row wrap justify-center>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pr-1"
                                            :value="selectedEvent.name"
                                            label="Name"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pl-1"
                                            :value="selectedEvent.details"
                                            label="Details"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pr-1"
                                            :value="selectedEvent.data!=undefined ? selectedEvent.data.expression_readable : ''"
                                            label="Rules"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pl-1"
                                            :value="selectedEvent.data!=undefined ? selectedEvent.data.task_path : ''"
                                            label="Task path"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pr-1"
                                            :value="selectedEvent.start"
                                            label="Task start"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pl-1"
                                            :value="selectedEvent.data!=undefined ? selectedEvent.data.last_run: ''"
                                            label="Last run"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pr-1"
                                            :value="selectedEvent.data!=undefined ? selectedEvent.data.last_duration : ''"
                                            label="Last duration"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                        <v-flex xs6>
                                            <v-text-field
                                            class="pl-1"
                                            :value="selectedEvent.data!=undefined ? selectedEvent.data.next_run : ''"
                                            label="Next run"
                                            readonly
                                            ></v-text-field>
                                        </v-flex>
                                    </v-layout>

                                    <!-- <span class="font-weight-black">Name: </span>
                                    <span>{{ selectedEvent.name }}</span>
                                    <br>
                                    <span>Details: </span>
                                    <span>{{ selectedEvent.details }}</span>
                                    <br>
                                    <span>Rules: </span>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.expression_readable : '' }}</span>
                                    <br>
                                    <span>Task path: </span>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.task_path : ''}}</span>
                                    <br>
                                    <span>Task start: </span>
                                    <span>{{ selectedEvent.start }}</span>
                                    <br>
                                    <span>Last run: </span>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.last_run: ''}}</span>
                                    <br>
                                    <span>Last duration: </span>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.last_duration : ''}}</span>
                                    <br>
                                    <span>Next run: </span>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.next_run : ''}}</span> -->

                                </v-card-text>
                            </v-card>
                        </v-menu>
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
            showUploadModal: false,
            showEditModal: false,
            showLogModal: false,
            today: moment().format('YYYY-MM-DD'),
            focus: moment().format('YYYY-MM-DD'),
            navigationDate: moment().format('YYYY-MM-DD'),
            selectedEvent: {},
            selectedElement: null,
            selectedOpen: false,
            tasks: [],
            editData: false,
            uploadData: false,
            logData: false,
        }
    },

    mounted () {
        this.$refs.calendar.checkChange()
        this.readData()
    },

    computed: {
        title () {
            return moment(this.navigationDate, 'YYYY-MM-DD').format('MMMM YYYY').toString()
        }
    },

    methods: {
        getEventColor (event) {
            return event.color
        },

        setToday () {
            this.focus = this.today
            this.navigationDate = this.today
            this.readData()
        },

        prev () {
            this.$refs.calendar.prev()
            this.navigationDate = moment(this.navigationDate,'YYYY-MM-DD').subtract(1, 'M').format('YYYY-MM-DD').toString();
            this.readData()
        },

        next () {
            this.$refs.calendar.next()
            this.navigationDate = moment(this.navigationDate,'YYYY-MM-DD').add(1, 'M').format('YYYY-MM-DD').toString();
            this.readData()
        },

        viewDay ({ date }) {
            if(date!=undefined){
                this.focus = date
            }
            sessionStorage.setItem('date',date)
            this.$router.push({ path: '/menu/DailyCalendar' });
        },

        checkLog:function(event){
            if(event.start < moment().format('YYYY-MM-DD HH:mm:ss')){
                return true
            }
            return false
        },

        openUploadModal: function () {
            this.showUploadModal = true;
        },
        closeUploadModal: function (result) {
            this.showUploadModal = false;
            if(result){
                this.readData();
            }
        },

        openLogModal: function (event) {
            this.showLogModal = true;
            this.logData = event.data != undefined ? event.data : false;
        },
        closeLogModal: function () {
            this.showLogModal = false;
        },

        showEvent ({ nativeEvent, event }) {
            const open = () => {
                this.selectedEvent = event
                this.selectedElement = nativeEvent.target
                setTimeout(() => this.selectedOpen = true, 10)
            }

            if (this.selectedOpen) {
                this.selectedOpen = false
                setTimeout(open, 10)
            } else {
                open()
            }

            nativeEvent.stopPropagation()
        },

        hashCode:function(str) {
            var hash = 0;
            for (var i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            return hash;
        },
        intToRGB:function(i){
            var c = (i & 0x00FFFFFF).toString(16).toUpperCase();
            return "00000".substring(0, 6 - c.length) + c;
        },


        readData:function(){

            var self = this;
            var from = moment(self.navigationDate,'YYYY-MM-DD').set('date',1).format('YYYY-MM-DD').toString();
            var to = moment(self.navigationDate,'YYYY-MM-DD').endOf('month').format('YYYY-MM-DD').toString();

            var apiParams={
                "calc_run_lst": 'Y',
                "interval_from": from,
                "interval_to": to
            }

            Utils.apiCall("get", "/task/", apiParams)
            .then(function (response) {

                if( typeof response === 'undefined' || response === null ){
                    Utils.showConnError();
                }else{
                    if(response.data.length!=0){
                        var arr_temp=[];

                        for(var i=0;i<response.data.length;i++){
                            for (var interval_data_start in response.data[i].interval_run_lst) {

                                var tmp=JSON.parse(JSON.stringify(response.data[i]))
                                delete tmp.interval_run_lst

                                var obj_temp={
                                    name:response.data[i].task_path,
                                    start:interval_data_start,
                                    end:response.data[i].interval_run_lst[interval_data_start],


                                    details:response.data[i].task_description,

                                    color: "#"+self.intToRGB(self.hashCode(response.data[i].filename)),
                                    data: tmp
                                }
                                arr_temp.push(obj_temp);
                            }
                        }
                        self.tasks=arr_temp;
                    }
                }
            });
        },
    },
    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue' + '?v=' + new Date().getTime()),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime())
    }
}
</script>
