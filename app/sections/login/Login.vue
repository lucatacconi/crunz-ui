<template id="login" lang="html">
    <v-app>
        <v-main>
            <v-container fluid fill-height>
                <v-layout align-center justify-center>
                <v-flex xs12 sm8 md4>
                    <v-card class="elevation-12">
                        <v-toolbar dark color="primary">
                            <v-toolbar-title>Login form</v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-tooltip bottom>
                                <template v-slot:activator="{ on, attrs }">
                                <v-btn icon v-bind="attrs" v-on="on" @click="changeTheme"><v-icon>mdi-theme-light-dark</v-icon></v-btn>
                                </template>
                                <span>Change theme dark/light</span>
                            </v-tooltip>
                        </v-toolbar>
                        <validationobserver v-slot="{ handleSubmit, valid }">
                            <v-form @keyup.enter.native="handleSubmit(execLogin)">
                                <v-card-text>
                                    <validationprovider name="username" rules="required" v-slot="{ errors }">
                                        <v-text-field
                                            v-model.trim="credentials.username"
                                            prepend-icon="person"
                                            name="username"
                                            label="Username"
                                            type="text"
                                            :autofocus=true
                                            :error-messages="errors[0]" >
                                        </v-text-field>
                                    </validationprovider>
                                    <validationprovider name="password" rules="required" v-slot="{ errors }">
                                        <v-text-field
                                            v-model.trim="password_orgin"
                                            prepend-icon="lock"
                                            name="password"
                                            label="Password"
                                            type="password"
                                            :error-messages="errors[0]" >
                                        </v-text-field>
                                    </validationprovider>
                                </v-card-text>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn color="primary" :disabled="!valid" @click.prevent="handleSubmit(execLogin)">
                                        <v-icon left>mdi-key-variant</v-icon>
                                        Login
                                    </v-btn>
                                </v-card-actions>
                            </v-form>
                        </validationobserver>
                    </v-card>
                </v-flex>
                </v-layout>
            </v-container>
        </v-main>
    </v-app>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
                valid: false,
                credentials: {
                    username: '',
                    password: ''
                },

                password_orgin: ''
            }
        },
        props: [],
        methods: {
            changeTheme:function () {
                this.$vuetify.theme.dark=!this.$vuetify.theme.dark;
                localStorage.setItem("theme",this.$vuetify.theme.dark);
            },
            execLogin: function () {

                var self = this;

                self.credentials.password = CryptoJS.MD5(self.password_orgin).toString();

                var config = {
                    hideLoading: false
                }

                Utils.apiCall("post", "/auth/login", self.credentials, config)
                .then(function (response) {
                    localStorage.setItem("token", response.data.token);
                    localStorage.setItem("accountData", response.data.accountData);
                    Utils.goHome();
                });

            }
        }
    }
</script>

<style>
</style>
