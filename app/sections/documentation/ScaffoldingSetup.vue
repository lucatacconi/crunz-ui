<template>
    <div>
        <v-card>
            <v-card-title>Installation and application setup</v-card-title>
            <v-card-text class="pt-5">

                <p class="text-justify">
                    It's recommended that you use <a href="https://getcomposer.org">Composer</a> to install <span class="font-weight-bold">Silly-Vue-Scaffolding</span>.
                </p>

                <p class="text-justify">
                    Access the <span class="font-weight-bold">Document Root</span> folder on your <span class="font-weight-bold">Apache Server</span> or one of the configured virtual hosts and run the following command:<br>
                    <code>$ composer create-project lucatacconi/silly-vue-scaffolding</code>
                </p>

                <p class="text-justify">
                    Configure the main application information and environment by editing /config/application.json and .env file
                </p>

                <p class="text-justify">
                    In any case it is possible to download the complete package from Github and proceed with the configuration of the appropriate files.
                </p>

                <div class="title mt-10">System Requirements</div>

                <p class="text-justify">
                    <ul>
                        <li>Web server with URL rewriting</li>
                        <li>PHP 7.1.3 or newer</li>
                        <li>Composer</li>
                    </ul>
                </p>


                <div class="title mt-10">First Login</div>

                <p class="text-justify">
                    The application is preconfigured with a single access user to verify the login procedure and access the dashboard and the main menu.<br>
                    To test access use the login <span class="font-weight-bold">admin</span> and password <span class="font-weight-bold">password</span>
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
