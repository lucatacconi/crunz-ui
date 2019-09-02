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
                fdssdf
            </v-card-text>

        </v-card>
    </v-dialog>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
                modalTitle:"Modifica riga"
            }
        },
        props: [],
        methods: {
            closeModal: function () {
                var self = this;
                self.$emit('on-close-edit-modal');
            },
        }
    }
</script>

<style>
</style>
