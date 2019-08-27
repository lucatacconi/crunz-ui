<template id="userinfo" lang="html">
    <div>
        <v-menu left bottom>
            <!-- Launch button on NavBar -->
            <template v-slot:activator="{ on }">
                <v-btn icon v-on="on">
                    <v-icon>account_circle</v-icon>
                </v-btn>
            </template>

            <v-card max-width="500">
                <v-list-item three-line>

                    <v-list-item-content>
                        <div class="overline mb-4">Grant type: {{ usersData.userType }}</div>
                        <v-list-item-title class="headline mb-2"><strong>{{ usersData.name }}</strong></v-list-item-title>
                        <v-list-item-subtitle>
                            <span class="userData">Email: <strong>{{ usersData.email }}</strong></span><br>
                            <span class="userData">Account expire date: <strong>{{ usersData.expireDate }}</strong></span>
                        </v-list-item-subtitle>
                    </v-list-item-content>

                    <v-list-item-avatar tile size="80">
                        <v-icon size="80" color="blue-grey">account_box</v-icon>
                    </v-list-item-avatar>

                </v-list-item>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text>Close</v-btn>
                    <v-btn text color="error" v-on:click="logout()">Logout</v-btn>
                </v-card-actions>
            </v-card>
        </v-menu>
    </div>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
                usersData:{}
            }
        },
        methods: {
            logout:function(){
                Utils.doLogoutAndGoHome();
            }
        },
        mounted() {
            this.usersData = JSON.parse(localStorage.getItem('accountData'));
        },
    }
</script>

<style>
    .userData{
        line-height: 1.5em;
    }
</style>
