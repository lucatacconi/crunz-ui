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
                        </v-toolbar>
                        <v-card-text>
                            <v-form data-vv-scope="login-area" @keyup.enter.native="execLogin">
                                <v-text-field
                                    v-model.trim="credentials.username"
                                    prepend-icon="person"
                                    name="username"
                                    label="Username"
                                    type="text"
                                    v-validate="{ required: true }"
                                    :error-messages="errors.collect('login-area.username')" >
                                </v-text-field>
                                <v-text-field
                                    v-model.trim="credentials.password"
                                    prepend-icon="lock"
                                    name="password"
                                    label="Password"
                                    type="password"
                                    v-validate="{ required: true }"
                                    :error-messages="errors.collect('login-area.password')" >
                                </v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="primary" @click.prevent="execLogin">Login</v-btn>
                        </v-card-actions>
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
                }
            }
        },
        props: [],
        methods: {
            execLogin: function () {

                var self = this;

                this.$validator.validateAll('login-area').then(function(result) {
                    if (result) {

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
                });
            }
        }
    }
</script>

<style>
</style>
