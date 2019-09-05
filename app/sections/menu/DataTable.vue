<template>
    <div>
        <!-- Task edit modal -->
        <task-edit
            v-if="showEditModal"
            @on-close-edit-modal="closeEditModal"
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
                                    <v-icon color="#607d8b" href="#" @click="editItem(item)">
                                        edit
                                    </v-icon>
                                    <v-icon color="red" href="#" @click="deleteItem(item)">
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
                            <!-- {{ item.execution_time }} -->
                        </td>
                        <td>
                            {{ item.next_run }}
                        </td>
                        <td>
                        </td>
                        <!-- <td :class="item.last_execution_status.toUpperCase()=='OK' ? 'green--text' : 'red--text'" >
                            {{ item.last_execution_status }}
                        </td> -->
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
            files: []
        }
    },
    methods: {
        readData:function(){
            var self=this
            Utils.apiCall("get", "/task/")
            .then(function (response) {
                if(response.data.length!=0){
                    console.log(response)
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
        prova:function(){
            console.log("dfdf")
        },
        newItem: function () {
            // this.activeRow = 0;
            this.showEditModal = true;
            // this.form.formdata = false;
        },
        editItem: function (rowdata) {
            // this.activeRow = rowdata.ENCA_IDASOL;
            this.showEditModal = true;
            // this.form.formdata = rowdata;
        },
        closeEditModal: function () {
            // this.activeRow = 0;
            this.showEditModal = false;
            // this.form.formdata = false;
            // this.readData();
        },
        deleteItem: function (rowdata) {
            var self = this;
            // self.activeRow = rowdata.ENCA_IDASOL;
            Swal.fire({
                title: 'Delete record',
                text: "Do you want delete record?",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f86c6b',
                cancelButtonColor: '#20a8d8',
                confirmButtonText: 'DELETE',
                cancelButtonText: 'Back'
            }).then( function (result) {
                // self.activeRow = 0;
                if (result.value) {
                    // var params = {
                    //     "p_uid": Util.jwtDecodeAccount("UID"),
                    //     // "p_codcli": Util.jwtDecodeAccount("CODCLI"),
                    //     "p_id": rowdata.ENCA_IDASOL,
                    // };
                    // ApiService.post("/index.php/anno-accademico/delete",params)
                    // .then(function (response) {
                    //     var apiCallResult = response.data;
                    //     if(apiCallResult.status == 'OK'){
                    //         Swal(
                    //             'Cancellazione!',
                    //             'Operazione effettuata correttamente.',
                    //             'success'
                    //         );
                    //         self.readData();
                    //     } else {
                    //         Swal(
                    //             'Cancellazione!',
                    //             'Errori nello svolgimento dell\'operazione: '+apiCallResult.error,
                    //             'warning'
                    //         );
                    //     }
                    // }).catch(function () {
                    //     Swal(
                    //         'Cancellazione!',
                    //         'Errori nello svolgimento dell\'operazione.',
                    //         'warning'
                    //     );
                    // });
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
