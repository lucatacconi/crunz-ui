<template>
    <v-card
        outlined
    >
        <v-toolbar
            dense
            flat
            tile
        >
            <v-toolbar-title>Task content</v-toolbar-title>
            <v-btn
                class="ml-2"
                small
                dense
                outlined
                color="button"
                @click="undo()"
            >
                <v-icon left>mdi-undo</v-icon>
                undo
            </v-btn>
            <v-btn
                class="ml-2"
                small
                dense
                outlined
                color="button"
                @click="redo()"
            >
                <v-icon left>mdi-redo</v-icon>
                redo
            </v-btn>
            <v-btn
                class="ml-2"
                small
                dense
                outlined
                color="button"
                @click="show_crunz_button=!show_crunz_button"
            >
                <v-icon left>{{show_crunz_button ? 'mdi-eye-off' : 'mdi-eye'}}</v-icon>
                {{show_crunz_button ? 'Hide Crunz rules' : 'Show crunz rules'}}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn
                v-if="ifClipboardEnabled"
                icon
                @click="copyToClipboard('task-edit')"
            >
                <v-icon>mdi-content-duplicate</v-icon>
            </v-btn>
        </v-toolbar>

        <div
            class="pb-2"
        >
            <center v-if="show_crunz_button">
                <template
                    v-for="(item,i) in crunz_button"
                >
                    <v-tooltip
                        top
                        v-if="item.tooltip!=undefined"
                        :key="i"
                    >
                        <template v-slot:activator="{ on, attrs }">
                        <v-btn
                            class="mt-2 ml-2 pa-0"
                            color="grey darken-2"
                            small
                            dense
                            outlined
                            style="width:160px;"
                            @click="crunzButtonAction(item)"
                            v-bind="attrs"
                            v-on="on"
                        >
                            {{item.text}}
                        </v-btn>
                        </template>
                        <span v-html="item.tooltip"></span>
                    </v-tooltip>
                    <v-btn
                        v-else
                        :key="i"
                        class="mt-2 ml-2 pa-0"
                        dark
                        small
                        dense
                        color="blue darken-2"
                        style="width:160px;"
                        @click="crunzButtonAction(item)"
                    >
                        {{item.text}}
                    </v-btn>
                </template>
            </center>
        </div>

        <v-card-text class="pa-0">
            <div id="task-edit"></div>
        </v-card-text>
    </v-card>
</template>

<script>
module.exports = {
    data:function(){
        return{
            taskEditEditor: null,
            show_crunz_button:false,
            crunz_button:[
                {
                    text:"hourly",
                    function:"->hourly()",
                    tooltip:"Schedule the event to run hourly"
                },
                {
                    text:"daily",
                    function:"->daily()",
                    tooltip:"Schedule the event to run daily"
                },
                {
                    text:"on",
                    function:"->on($date)",
                    tooltip:"Schedule the event to run on a certain date"
                },
                {
                    text:"at",
                    function:"->at($time)",
                    tooltip:"Schedule the command at a given time"
                },
                {
                    text:"daily At",
                    function:"->dailyAt($time)",
                    tooltip:"Schedule the event to run daily at a given time (10:00, 19:30, etc)"
                },
                {
                    text:"between",
                    function:"->between($from, $to)",
                    tooltip:"Set Working period"
                },
                {
                    text:"from",
                    function:"->from($datetime)",
                    tooltip:"Check if event should be on"
                },
                {
                    text:"to",
                    function:"->to($datetime)",
                    tooltip:"Check if event should be off"
                },
                {
                    text:"twice Daily",
                    function:"->twiceDaily($first = 1, $second = 13)",
                    tooltip:"Schedule the event to run twice daily"
                },
                {
                    text:"weekdays",
                    function:"->weekdays()",
                    tooltip:"Schedule the event to run only on weekdays"
                },
                {
                    text:"mondays",
                    function:"->mondays()",
                    tooltip:"chedule the event to run only on Mondays"
                },
                {
                    text:"tuesdays",
                    function:"->tuesdays()",
                    tooltip:"Schedule the event to run only on Tuesdays"
                },
                {
                    text:"wednesdays",
                    function:"->wednesdays()",
                    tooltip:"Schedule the event to run only on Wednesdays"
                },
                {
                    text:"thursdays",
                    function:"->thursdays()",
                    tooltip:"Schedule the event to run only on Thursdays"
                },
                {
                    text:"fridays",
                    function:"->fridays()",
                    tooltip:"Schedule the event to run only on Fridays"
                },
                {
                    text:"saturdays",
                    function:"->saturdays()",
                    tooltip:"Schedule the event to run only on Saturdays"
                },
                {
                    text:"sundays",
                    function:"->sundays()",
                    tooltip:"Schedule the event to run only on Sundays"
                },
                {
                    text:"weekly",
                    function:"->weekly()",
                    tooltip:"Schedule the event to run weekly"
                },
                {
                    text:"weekly On",
                    function:"->weeklyOn($day, $time = '0:0')",
                    tooltip:"Schedule the event to run weekly on a given day and time"
                },
                {
                    text:"monthly",
                    function:"->monthly()",
                    tooltip:"Schedule the event to run monthly"
                },
                {
                    text:"quarterly",
                    function:"->quarterly()",
                    tooltip:"Schedule the event to run quarterly"
                },
                {
                    text:"yearly",
                    function:"->yearly()",
                    tooltip:"chedule the event to run yearly"
                },
                {
                    text:"days",
                    function:"->days($days)",
                    tooltip:"Set the days of the week the command should run on  (ex: ->days('15'), ->days([10,15,20])"
                },
                {
                    text:"hour",
                    function:"->hour($value)",
                    tooltip:"Set hour for the cron job (ex: ->hour('15'), ->hour([10,15,20])"
                },
                {
                    text:"minute",
                    function:"->minute($value)",
                    tooltip:"Set minute for the cron job (ex: ->minute('15'), ->minute([10,15,20])"
                },
                {
                    text:"day Of Month",
                    function:"->dayOfMonth($value)",
                    tooltip:"Set day of month for the cron job"
                },
                {
                    text:"month",
                    function:"->month($value)",
                    tooltip:"Set month for the cron job"
                },
                {
                    text:"day Of Week",
                    function:"->dayOfWeek($value)",
                    tooltip:"Set day of week for the cron job"
                },
                {
                    text:"every five minute",
                    function:"->everyFiveMinutes()",
                    tooltip:"Schedule the event to run the job every five minutes"
                },
                {
                    text:"every ten minute",
                    function:"->everyTenMinutes()",
                    tooltip:"Schedule the event to run the job every ten minutes"
                },
                {
                    text:"every fifteen minute",
                    function:"->everyFifteenMinutes()",
                    tooltip:"Schedule the event to run the job every fifteen minutes"
                },
                {
                    text:"every thirty minute",
                    function:"->everyThirtyMinutes()",
                    tooltip:"Schedule the event to run the job every thirty minutes"
                },
                {
                    text:"prevent Overlapping",
                    function:"->preventOverlapping()",
                    tooltip:"Do not allow the event to overlap each other<br>By default, the lock is acquired through file system locks. Alternatively, you can pass a symfony lock store that will be responsible for the locking"
                }
            ],
        }
    },
    props:['content','actionButton'],
    methods: {
        initEditor:function(editor){
            var ed = "";
            var content = "";

            if(this.content!=undefined){
                if(this.content!=null) content = this.content;
            }

            ed = ace.edit(editor);
            if(this.$vuetify.theme.dark){
                ed.setTheme("ace/theme/twilight");
            }
            ed.getSession().setMode("ace/mode/php");
            ed.getSession().setUndoManager(new ace.UndoManager())

            ed.setOptions({
                showPrintMargin: false,
                fontSize: 14
            });

            ed.session.setValue(content);

            if(editor=="task-edit"){
                this.taskEditEditor = ed;
            }
            this.$emit('editor',this.taskEditEditor)
        },

        copyToClipboard:function(editor){
            var ed = "";
            if(editor == "task-edit"){
                ed = this.taskEditEditor;
            }
            if(ed != ""){
                navigator.clipboard.writeText(ed.getValue());
            }
        },
        redo:function(){
            this.taskEditEditor.redo()
        },
        undo:function(){
            this.taskEditEditor.undo()
        },
        crunzButtonAction:function(item){
            var cursor=this.taskEditEditor.selection.getCursor()
            this.taskEditEditor.getSession().getDocument().insertInLine(cursor,item.function)
        },
    },

    computed: {
        ifClipboardEnabled: function () {
            return Utils.ifClipboardEnabled();
        }
    },

    mounted:function() {
        var self=this
        setTimeout(function(){
            self.initEditor('task-edit');
        }, 200);
    },
    watch:{
        content:function(value){
            if(this.taskEditEditor!=null) this.taskEditEditor.session.setValue(value);
        }
    }
}
</script>

<style>
    #task-edit {
        height: 500px;
    }

</style>
