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
                <v-col class="pt-0 pb-0">
                    <v-sheet height="64">
                        <v-toolbar flat color="white">
                            <v-btn outlined class="mr-4" @click="setToday">
                                Today
                            </v-btn>
                            <v-btn fab text small @click="prev">
                                <v-icon small>mdi-chevron-left</v-icon>
                            </v-btn>
                            <v-toolbar-title>{{ title }}</v-toolbar-title>
                            <v-btn fab text small @click="next">
                                <v-icon small>mdi-chevron-right</v-icon>
                            </v-btn>
                        </v-toolbar>
                    </v-sheet>
                    <v-sheet height="600">
                        <v-calendar
                            ref="calendar"
                            v-model="focus"
                            color="primary"
                            :events="tasks"
                            :event-color="getEventColor"
                            :event-margin-bottom="3"
                            :now="today"
                            type="month"
                            @click:event="showEvent"
                            @click:more="viewDay"
                            @click:date="viewDay"
                            locale="en-EN"
                            :weekdays="[1, 2, 3, 4, 5, 6, 0]"
                        ></v-calendar>
                        <v-menu
                            v-model="selectedOpen"
                            :close-on-content-click="false"
                            :activator="selectedElement"
                            full-width
                            offset-x
                        >
                            <v-card
                                color="grey lighten-4"
                                min-width="350px"
                                flat
                            >
                                <v-toolbar
                                    :color="selectedEvent.color"
                                    dark
                                >
                                    <v-btn
                                        icon
                                    >
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>
                                    <v-btn
                                        icon
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
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
                                    <span>{{ selectedEvent.details }}</span>
                                    <br>
                                    <span>{{ selectedEvent.data!=undefined ? selectedEvent.data.expression_readable : '' }}</span>
                                    <br>
                                    <span>{{ selectedEvent.start }}</span>
                                </v-card-text>
                            </v-card>
                        </v-menu>
                    </v-sheet>
                </v-col>
            </v-row>

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
            today: moment().format('YYYY-MM-DD'),
            focus: moment().format('YYYY-MM-DD'),
            navigationDate: moment().format('YYYY-MM-DD'),
            selectedEvent: {},
            selectedElement: null,
            selectedOpen: false,
            tasks:[],
            editData:false,
            uploadData:false,
            logData:false,
        }
    },
    computed: {
        title () {
            return moment(this.navigationDate, 'YYYY-MM-DD').format('MMMM YYYY').toString()
        }
    },
    methods: {
        checkLog:function(event){
            if(event.start<moment().format('YYYY-MM-DD HH:mm:ss')){
                return true
            }
            return false
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
        closeUploadModal: function (result) {
            this.showUploadModal = false;
            if(result){
                this.readData();
            }
        },
        openLogModal: function (event) {
            this.showLogModal = true;
            this.logData = event.data!=undefined ? event.data : false;
            //console.log(JSON.stringify(this.logData));
        },
        closeLogModal: function () {
            this.showLogModal = false;
            // this.form. = false;
            // this.readData();
        },
        viewDay ({ date }) {
            if(date!=undefined){
                this.focus = date
            }
            sessionStorage.setItem('date',date)
            this.$router.push({ path: '/menu/DailyCalendar' });
        },
        getEventColor (event) {
            return event.color
        },
        setToday () {
            this.focus = this.today
            this.navigationDate=this.today
            this.readData()
        },
        prev () {
            this.$refs.calendar.prev()
            this.navigationDate=moment(this.navigationDate,'YYYY-MM-DD').subtract(1, 'M').format('YYYY-MM-DD').toString();
            this.readData()
        },
        next () {
            this.$refs.calendar.next()
            this.navigationDate=moment(this.navigationDate,'YYYY-MM-DD').add(1, 'M').format('YYYY-MM-DD').toString();
            this.readData()
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
        getRandomColor:function() {
            var letters = '0123456789ABCDEF';
            var color = '#';
            for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        },
        hashCode:function(str) {
            var hash = 0;
            for (var i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            return hash;
        },
        intToRGB:function(i){
            var c = (i & 0x00FFFFFF)
                .toString(16)
                .toUpperCase();

            return "00000".substring(0, 6 - c.length) + c;
        },
        readData:function(){
            var self=this
            var from= moment(self.navigationDate,'YYYY-MM-DD').set('date',1).format('YYYY-MM-DD').toString()
            var to= moment(self.navigationDate,'YYYY-MM-DD').endOf('month').format('YYYY-MM-DD').toString()
            var params={
                CALC_RUN_LST:'Y',
                INTERVAL_FROM:from,
                INTERVAL_TO:to
            }
            Utils.apiCall("get", "/task/",params)
            .then(function (response) {
                if(response.data.length!=0){
                    var arr_temp=[]

                    //console.log(JSON.stringify(response.data));


                    for(var i=0;i<response.data.length;i++){

                        for (var interval_data_start in response.data[i].interval_run_lst) {
                            // console.log(response.data[i].interval_run_lst[interval_data_start]);

                            var tmp=JSON.parse(JSON.stringify(response.data[i]))
                            delete tmp.interval_run_lst

                            var obj_temp={
                                name:response.data[i].filename,
                                details:response.data[i].task_description,
                                start:interval_data_start,
                                end:response.data[i].interval_run_lst[interval_data_start],
                                color: "#"+self.intToRGB(self.hashCode(response.data[i].filename)),
                                data: tmp
                            }
                            arr_temp.push(obj_temp)


                        }


                        // for(var k=0;k<response.data[i].interval_run_lst.length;k++){
                        //     var tmp=JSON.parse(JSON.stringify(response.data[i]))
                        //     delete tmp.interval_run_lst
                        //     var obj_temp={
                        //         name:response.data[i].filename,
                        //         details:response.data[i].task_description,
                        //         start:response.data[i].interval_run_lst[k],
                        //         end:moment(response.data[i].interval_run_lst[k],'YYYY-MM-DD h:mm:ss').add(1,'h').format('YYYY-MM-DD h:mm:ss').toString(),
                        //         color: "#"+self.intToRGB(self.hashCode(response.data[i].filename)),
                        //         data: tmp
                        //     }
                        //     arr_temp.push(obj_temp)
                        // }
                    }
                    self.tasks=arr_temp
                }
            });
        },
    },
    mounted () {
        this.$refs.calendar.checkChange()
        this.readData()
    },
    components:{
        'actions-buttons': httpVueLoader('../../shareds/ActionsButtons.vue' + '?v=' + new Date().getTime()),
        'task-edit': httpVueLoader('../../shareds/TaskEdit.vue' + '?v=' + new Date().getTime()),
        'task-upload': httpVueLoader('../../shareds/FileUpload.vue' + '?v=' + new Date().getTime()),
        'task-log': httpVueLoader('../../shareds/ExecutionLog.vue' + '?v=' + new Date().getTime())
    }
}
</script>
