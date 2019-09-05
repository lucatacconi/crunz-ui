<template id="taskEdit" lang="html">
    <v-dialog :value="true" persistent max-width="1000px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
            >
                <v-toolbar-title>
                    {{modalTitle}}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn
                        icon
                        @click="closeModal()"
                    >
                        <v-icon>
                            close
                        </v-icon>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text class="pt-5">
                <v-text-field
                    label="Filename"
                    v-model="formdata.filename"
                ></v-text-field>
                <v-textarea
                    label="Description"
                    v-model="formdata.task_description"
                ></v-textarea>
            </v-card-text>

        </v-card>
    </v-dialog>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
                formdata:{
                    filename:null,
                    task_description:null
                }
            }
        },
        props: ['data','row'],
        computed: {
            modalTitle:function(){
                return this.row==-1 ? 'Inserisci nuova riga' : 'Modifica riga'
            }
        },
        methods: {
            closeModal: function () {
                var self = this;
                self.$emit('on-close-edit-modal');
            },
        },
        created:function() {
            if(this.data){
                this.formdata=JSON.parse(JSON.stringify(this.data))
            }
        },
    }
</script>

<style>
</style>
