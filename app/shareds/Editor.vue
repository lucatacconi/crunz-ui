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
            <v-btn
                class="ml-2 pa-0"
                dark
                small
                dense
                color="blue darken-2"
                @click="test()"
            >
                Daily
            </v-btn>
            <v-btn
                class="ml-2 pa-0"
                dark
                small
                dense
                color="blue darken-2"
            >
                Monthly
            </v-btn>
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
        }
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
