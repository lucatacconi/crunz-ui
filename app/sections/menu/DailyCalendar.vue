<template>
    <v-row class="fill-height">
        <v-col>
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
                    type="day"
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
                            <v-btn icon>
                                <v-icon>mdi-pencil</v-icon>
                            </v-btn>
                            <v-toolbar-title v-html="selectedEvent.name"></v-toolbar-title>
                            <div class="flex-grow-1"></div>
                            <v-btn icon>
                                <v-icon>mdi-heart</v-icon>
                            </v-btn>
                            <v-btn icon>
                                <v-icon>mdi-dots-vertical</v-icon>
                            </v-btn>
                        </v-toolbar>
                        <v-card-text>
                            <span v-html="selectedEvent.details"></span>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn
                                text
                                color="secondary"
                                @click="selectedOpen = false"
                            >
                                Cancel
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-menu>
            </v-sheet>
        </v-col>
    </v-row>
</template>

<script>
module.exports = {
    data:function(){
        return{
            today: moment().format('YYYY-MM-DD'),
            focus: moment().format('YYYY-MM-DD'),
            selectedEvent: {},
            selectedElement: null,
            selectedOpen: false,
            tasks:[],
        }
    },
    computed: {
        title () {
            return moment(this.focus, 'YYYY-MM-DD').format('MMMM YYYY DD dddd').toString()
        }
    },
    methods: {
        viewDay ({ date }) {
            if(date!=undefined){
                this.focus = date
            }
        },
        getEventColor (event) {
            return event.color
        },
        setToday () {
            this.focus = this.today
            this.readData()
        },
        prev () {
            this.$refs.calendar.prev()
            this.readData()
        },
        next () {
            this.$refs.calendar.next()
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
        readData:function(){
            var self=this
            var from= moment(self.focus,'YYYY-MM-DD').format('YYYY-MM-DD').toString()+" 00:00:00"
            var to= moment(self.focus,'YYYY-MM-DD').format('YYYY-MM-DD').toString()+" 23:59:59"
            var params={
                CALC_RUN_LST:'Y',
                INTERVAL_FROM:from,
                INTERVAL_TO:to
            }
            Utils.apiCall("get", "/task/",params)
            .then(function (response) {
                if(response.data.length!=0){
                    var arr_temp=[]
                    for(var i=0;i<response.data.length;i++){
                        for(var k=0;k<response.data[i].interval_run_lst.length;k++){
                            var obj_temp={
                                name:response.data[i].filename,
                                details:response.data[i].task_description,
                                start:response.data[i].interval_run_lst[k],
                                end:moment(response.data[i].interval_run_lst[k],'YYYY-MM-DD h:mm:ss').add(1,'h').format('YYYY-MM-DD h:mm:ss').toString(),
                                color: self.getRandomColor()
                            }
                            arr_temp.push(obj_temp)
                        }
                    }
                    self.tasks=arr_temp
                }
            });
        },
    },
    mounted () {
        if(sessionStorage.getItem('date')!=undefined){
            this.focus=sessionStorage.getItem('date')
            sessionStorage.removeItem('date')
        }
        this.$refs.calendar.checkChange()
        this.readData()
    },
}
</script>
