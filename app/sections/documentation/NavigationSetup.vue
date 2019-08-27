<template>
    <div>
        <v-card>
            <v-card-title>Menu configuration</v-card-title>
            <v-card-text class="pt-5">

                <p class="text-justify">
                    All menu items shown in the main menu are configured in the configuration file /config/navigation.json.
                </p>

                <p class="text-justify">
                    The navigation.json configuration file contains two configuration items: the first element configures the bootstrap page (bootstrapPage), the second element configures the map of menu items (navigationMap).
                </p>

                <p class="text-justify">
                    <span class="font-weight-bold">bootstrapPage</span> configures the first page that will be shown following a successful login:
                </p>

                <v-card class="mt-10 mb-10">
                    <v-card-text class="grey lighten-4 font-weight-bold">
<pre class="red--text text--darken-3">
    "bootstrapPage":{
        "route": "/dashboard",
        "title": "Dashboard"
    }
</pre>
                    </v-card-text>
                </v-card>

                <p class="text-justify">
                    <span class="font-weight-bold">route</span> represents the route of one of the elements configured in the navigation map,
                    <span class="font-weight-bold">title</span>represents the title that will be shown in the application's NavBar:
                </p>

                <p class="text-justify">
                    <span class="font-weight-bold">navigationMap</span> contains all the elements that are shown in the main menu. It can contain three types of elements: the main menu element, the submenu element which in turn contains menu elements and the divider that represents a graphic subdivision within the mneu represented by a line. Each individual element can then be configured with different properties that set its behavior, display and layout.
                </p>

                <p class="text-justify">
                    The format for the single menu element is as follows:
                </p>

                <v-card class="mt-10 mb-10">
                    <v-card-text class="grey lighten-4 font-weight-bold">
<pre class="red--text text--darken-3">
    {
        "title": "Dashboard",
        "subtitle": "App main page",
        "allowed":[],
        "visible": true,

        # if you want to point to an internal section

        "action":{
            "path": "/dashboard",
            "component": "../../app/sections/dashboard/Dashboard.vue"
        },

        # if you want to call a javascript function

        "action": "alert('Hello world 1')",

        # if you want to call an external web page

        "action":{
            "url": "https://github.com/lucatacconi/silly-vue-scaffolding",
            "target": "_blank"
        },

        "action":{
            "url": "https://github.com/lucatacconi/silly-vue-scaffolding",
            "target": "_self"
        },

        "layout":{
            "icon":"mdi-view-dashboard-variant",
            "disabled": false,
            "color": false,
            "class": false
        }
    }
</pre>
                    </v-card-text>
                </v-card>

                <p class="text-justify">
                    The format for the divider element is as follows:
                </p>

                <v-card class="mt-10 mb-10">
                    <v-card-text class="grey lighten-4 font-weight-bold">
<pre class="red--text text--darken-3">
    {
        "divider": true,
        "visible": true
    }
</pre>
                    </v-card-text>
                </v-card>

                <p class="text-justify">
                    The format for the submenu element is as follows. Within the subMenuItems section the elements must be inserted as shown in the previous examples.
                </p>

                <v-card class="mt-10 mb-10">
                    <v-card-text class="grey lighten-4 font-weight-bold">
<pre class="red--text text--darken-3">
    {
        "title": "App1",
        "subtitle": "Example app1"
        "allowed": ["admin"],
        "visible": true,
        "layout": {
            "icon": "mdi-menu",
            "expanded": true,
            "disabled": false,
            "color": false,
            "class": false
        },
        "subMenuItems":[
            ...
        ]
    }
</pre>
                    </v-card-text>
                </v-card>


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
