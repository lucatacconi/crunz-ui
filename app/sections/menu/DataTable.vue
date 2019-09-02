<template>
    <div>
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
                                    <v-icon color="#607d8b" href="#" @click="prova()">
                                        edit
                                    </v-icon>
                                    <v-icon color="red" href="#" @click="prova()">
                                        delete
                                    </v-icon>
                                </center>
                            </td>
                        <td>
                            {{ item.file }}
                        </td>
                        <td>
                            {{ item.description }}
                        </td>
                        <td>
                            {{ item.execution_frequency }}
                        </td>
                        <td>
                            {{ item.execution_time }}
                        </td>
                        <td>
                            {{ item.next_execution }}
                        </td>
                        <td>
                            {{ item.last_execution_status }}
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
            >
            <v-icon>add</v-icon>
        </v-btn>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            headers: [
                {
                    text: 'Operations',
                    sortable: false,
                    value: ''
                },
                { text: 'File', value: 'file' },
                { text: 'Description', value: 'description' },
                { text: 'Execution frequency', value: 'execution_frequency' },
                { text: 'Execution time', value: 'execution_time' },
                { text: 'Next execution', value: 'next_execution' },
                { text: 'Last execution status', value: 'last_execution_status' },
            ],
            files: [
                {
                    file: 'Task1.php',
                    description: "Task di prova",
                    execution_frequency: "5",
                    execution_time: "1:00",
                    next_execution: "12:30",
                    last_execution_status: "OK",
                },
            ]
        }
    },
    methods: {
        prova:function(){
            console.log("dfdf")
        }
    },
}
</script>
