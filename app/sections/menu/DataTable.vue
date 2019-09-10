<template>
    <div>
        <!-- Task edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-edit-modal="closeEditModal"
            :data="form.formdata"
            :row="activeRow"
        ></task-edit>
        <v-card>

            <v-data-table
                dense
                :headers="headers"
                :items="files"
            >
                <template v-if="files.length!=0" v-slot:body="{ items }">
                    <tbody>
                        <tr v-for="(item,i) in items" :key="i">
                            <td>
                                <center>
                                    <v-icon color="#607d8b" href="#" @click="editItem(item,i)">
                                        edit
                                    </v-icon>
                                    <v-icon color="red" href="#" @click="deleteItem(item,i)">
                                        delete
                                    </v-icon>
                                </center>
                            </td>
                        <td>
                            {{ item.filename }}
                        </td>
                        <td>
                            {{ item.task_description }}
                        </td>
                        <td>
                            <!-- {{ item.execution_frequency }} -->
                        </td>
                        <td>
                            {{ item.average_duration }}
                        </td>
                        <td>
                            {{ item.next_run }}
                        </td>
                        <td>
                        </td>
                        <td :class="item.last_outcome.toUpperCase()=='OK' ? 'green--text' : 'red--text'" >
                            {{ item.last_outcome }}
                        </td>
                        </tr>
                    </tbody>
                </template>

                <template v-slot:no-data>
                    NESSUN DATO
                </template>

            </v-data-table>

        </v-card>
        <v-btn
            absolute
            bottom
            right
            fab
            dark
            color="#607d8b"
            @click="newItem()"
            >
            <v-icon>add</v-icon>
        </v-btn>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            showEditModal:false,
            headers: [
                {
                    text: 'Operations',
                    sortable: false,
                    value: ''
                },
                { text: 'File', value: 'filename' },
                { text: 'Description', value: 'task_description' },
                { text: 'Execution frequency', value: 'execution_frequency' },
                { text: 'Execution time', value: 'execution_time' },
                { text: 'Next execution', value: 'next_run' },
                { text: 'Last execution status', value: 'last_execution_status' },
            ],
            files: [],
            activeRow:-1,
            form:{
                formdata:{}
            }
        }
    },
    methods: {
        readData:function(){
            var self=this
            Utils.apiCall("get", "/task/")
            .then(function (response) {
                if(response.data.length!=0){
                    console.log(response.data)
                    self.files=response.data
                }else{
                    Swal.fire({
                        title: 'Tasks not found',
                        text: "Tasks not found",
                        type: 'warning'
                    })
                }
            });
        },
        newItem: function () {
            this.activeRow = -1;
            this.showEditModal = true;
            this.form.formdata = false;
        },
        editItem: function (rowdata,i) {
            this.activeRow = i;
            this.showEditModal = true;
            this.form.formdata = rowdata;
        },
        closeEditModal: function () {
            // this.activeRow = 0;
            this.showEditModal = false;
            // this.form.formdata = false;
            // this.readData();
        },
        deleteItem: function (rowdata,i) {
            var self = this;
            // self.activeRow = rowdata.ENCA_IDASOL;
            Swal.fire({
                title: 'Delete task',
                text: "Do you want delete task?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'DELETE',
                cancelButtonText: 'Back'
            }).then( function (result) {
                // self.activeRow = 0;
                if (result.value) {
                    var self=this
                    Utils.apiCall("delete", "/task/")
                    .then(function (response) {
                        if(response.statusText=='OK'){
                            Swal.fire({
                                title: 'Task deleted',
                                text: "Task deleted",
                                type: 'success'
                            })
                        }else{
                            Swal.fire({
                                title: 'Error deleted task',
                                text: "Error deleted task",
                                type: 'error'
                            })
                        }
                    });
                }
            });
        },
    },
    created:function() {
        this.readData()
    },
    components:{
        'task-edit': httpVueLoader('../../shareds/TaskEdit.vue')
    }
}
</script>
