<template>
    <div>

        <v-card class="mb-16">

        <v-toolbar
            dense
            flat
            tile
        >
            <v-toolbar-title>Crunz configuration editor</v-toolbar-title>
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
                @click="loadDefautConfig()"
            >
                <v-icon left>mdi-download-multiple</v-icon>
                Load defaul config
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
            <v-card-text class="pa-0">
                <div id="config-editor"></div>
            </v-card-text>

            <v-card-actions class="pt-4 pr-9 pb-3">
                <v-spacer></v-spacer>
                <v-btn
                    small
                    outlined
                    color="button"
                    @click="saveConfig()"
                >
                    <v-icon left>mdi-content-save-outline</v-icon>
                    Save
                </v-btn>
            </v-card-actions>

        </v-card>

    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            configEditor: null,
            content: null
        }
    },
    methods: {

        readData:function(options = {}){
            var self = this;
            var params = {}
            Utils.apiCall("get", "/environment/crunz-config",params, options)
            .then(function (response) {
                if(typeof response !== 'undefined' && typeof response.data !== 'undefined'){
                    self.content = JSON.stringify(response.data, null, 4);
                }
            });
        },

        loadDefautConfig:function(options = {}){
            var self = this;
            var params = {}
            Utils.apiCall("get", "/environment/crunz-default-config",params, options)
            .then(function (response) {
                if(typeof response !== 'undefined' && typeof response.data !== 'undefined'){
                    self.content = JSON.stringify(response.data, null, 4);
                }
            });
        },

        saveConfig:function(){
            var self=this;

            if(self.configEditor==undefined) return
            if(self.configEditor==null) return

            if(self.configEditor.getValue().trim()==""){
                Utils.showAlertDialog('ERROR','Crunz config is empty','error');
                return;
            }

            var apiParams = {
                "config_content": btoa(this.configEditor.getValue())
            }
            Utils.apiCall("post", "/environment/crunz-config", apiParams)
            .then(function (response) {
                if(response.data.result){
                    var msg="Crunz config file updated";
                    Utils.showAlertDialog(msg,response.data.result_msg,'success',{},
                        ()=>{
                        if(edit_modal_close){
                            self.closeModal(true);
                        }
                    });
                }else{
                    Utils.showAlertDialog('ERROR',response.data.result_msg,'error');
                }
            });
        },

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
            ed.getSession().setMode("ace/mode/json");
            ed.getSession().setUndoManager(new ace.UndoManager())

            ed.setOptions({
                showPrintMargin: false,
                fontSize: 14
            });

            ed.session.setValue(content);

            if(editor=="config-editor"){
                this.configEditor = ed;
            }
            this.$emit('editor',this.configEditor)
        },

        copyToClipboard:function(editor){
            var ed = "";
            ed = this.configEditor;
            if(ed != ""){
                navigator.clipboard.writeText(ed.getValue());
            }
        },
        redo:function(){
            this.configEditor.redo()
        },
        undo:function(){
            this.configEditor.undo()
        }
    },

    watch:{
        content:function(value){
            if(this.configEditor!=null) this.configEditor.session.setValue(value);
        }
    },

    computed: {
        ifClipboardEnabled: function () {
            return Utils.ifClipboardEnabled();
        }
    },

    created:function() {
        this.readData();
    },

    mounted:function() {
        var self=this
        setTimeout(function(){
            self.initEditor('config-editor');
        }, 200);
    }
}
</script>

<style>
    #config-editor {
        height: 600px;
    }
</style>
