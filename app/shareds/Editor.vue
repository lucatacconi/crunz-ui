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
                class="ml-2 pa-0"
                small
                dense
                dark
                color="blue darken-2"
                @click="undo()"
            >
                <v-icon>mdi-undo</v-icon>
                undo
            </v-btn>
            <v-btn
                class="ml-2 pa-0"
                small
                dense
                dark
                color="blue darken-2"
                @click="redo()"
            >
                <v-icon>mdi-redo</v-icon>
                redo
            </v-btn>
            <v-btn
                class="ml-2 pa-0"
                small
                dense
                dark
                color="blue darken-2"
                @click="show_crunz_button=!show_crunz_button"
                v-if="actionButton!=undefined"
            >
                <v-icon>{{show_crunz_button ? 'mdi-eye-off' : 'mdi-eye'}}</v-icon>
                {{show_crunz_button ? 'Hide crunz button' : 'Show crunz button'}}
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn
                icon
                @click="copyToClipboard('task-edit')"
            >
                <v-icon>mdi-content-duplicate</v-icon>
            </v-btn>
        </v-toolbar>

        <div
            v-if="actionButton!=undefined"
            class="pb-2"
        >
            <!-- <v-layout
                row
                wrap
                class="ma-0"
            >
                <v-flex xs6>
                    <v-select
                        class="pa-0 ml-2"
                        hide-details
                        outlined
                        dense
                        :items="[]"
                    ></v-select>
                </v-flex>
                <v-flex xs4
                    class="ml-2"
                    style="margin-top:1px;"
                >
                    <v-btn
                        dark
                        color="blue darken-2"
                    >
                        Insert
                    </v-btn>
                </v-flex>
            </v-layout> -->
            <center v-if="show_crunz_button">
                <template
                    v-for="(item,i) in crunz_button"
                >
                    <v-btn
                        :key="i"
                        class="mt-2 ml-2 pa-0"
                        dark
                        small
                        dense
                        color="blue darken-2"
                        style="width:160px;"
                        @click="test()"
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
                    text:"hourly"
                },
                {
                    text:"daily"
                },
                {
                    text:"on"
                },
                {
                    text:"at"
                },
                {
                    text:"dailyAt"
                },
                {
                    text:"between"
                },
                {
                    text:"from"
                },
                {
                    text:"to"
                },
                {
                    text:"twiceDaily"
                },
                {
                    text:"weekdays"
                },
                {
                    text:"mondays"
                },
                {
                    text:"tuesdays"
                },
                {
                    text:"wednesdays"
                },
                {
                    text:"thursdays"
                },
                {
                    text:"fridays"
                },
                {
                    text:"saturdays"
                },
                {
                    text:"sundays"
                },
                {
                    text:"weeklyOn"
                },
                {
                    text:"monthly"
                },
                {
                    text:"quarterly"
                },
                {
                    text:"yearly"
                },
                {
                    text:"days"
                },
                {
                    text:"hour"
                },
                {
                    text:"minute"
                },
                {
                    text:"dayOfMonth"
                },
                {
                    text:"month"
                },
                {
                    text:"dayOfWeek"
                },
                {
                    text:"preventOverlapping"
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
                var sel = ed.selection.toJSON();
                ed.selectAll();
                ed.focus();
                document.execCommand('copy');
                ed.selection.fromJSON(sel);
            }
        },
        test:function(){
            var cursor=this.taskEditEditor.selection.getCursor()
            this.taskEditEditor.getSession().getDocument().insertInLine(cursor,"prova")
        },
        redo:function(){
            this.taskEditEditor.redo()
        },
        undo:function(){
            this.taskEditEditor.undo()
        },
    },
    mounted:function() {
        var self=this
        setTimeout(function(){
            self.initEditor('task-edit');
        }, 200);
    },
}
</script>

<style>
    #task-edit {
        height: 300px;
    }

</style>
