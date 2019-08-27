<template>
    <div>
        <v-card>
            <v-card-title>Users setup</v-card-title>
            <v-card-text class="pt-5">

                <p class="text-justify">
                    All users enabled to access the application are configured in the configuration file /config/accounts.json.
                </p>

                <p class="text-justify">
                    The accounts.json configuration file has the following format:
                </p>

                <v-card class="mt-10 mb-10">
                    <v-card-text class="grey lighten-4 font-weight-bold">
<pre class="red--text text--darken-3">
    [
        {
            "username":"admin",
            "name":"Admin User",
            "userType":"admin",
            "email":"admin@nomail.com",
            "password":"password",
            "active":"Y",
            "expireDate":"2020-10-10",
            "customSessionDuration":""
        },
        {
            "username":"j.doe",
            "name":"Jhon Doe",
            "userType":"user",
            "email":"j.doe@nomail.com",
            "password":"password",
            "active":"Y",
            "expireDate":"2020-10-10",
            "customSessionDuration":""
        },
        ...
    ]
</pre>
                    </v-card-text>
                </v-card>


                <p class="text-justify">
                    Among the various information listed, the type of user is also induced, information that is then used to filter the menu items enabled for the user.
                </p>

                <p class="text-justify">
                    For simplicity's choice the access configurations have been inserted in a file. However, nothing prevents the implementation of user management based on database reading.
                </p>

            </v-card-text>
        </v-card>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
            }
        }
    }
</script>

<style scoped>

</style>
