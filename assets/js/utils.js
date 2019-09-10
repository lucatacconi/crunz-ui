var Utils = {
    createUUID: function () {
        var dt = new Date().getTime();
        var uuid = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = (dt + Math.random()*16)%16 | 0;
            dt = Math.floor(dt/16);
            return (c=='x' ? r :(r&0x3|0x8)).toString(16);
        });
        return uuid;
    },

    parseJwt: function (token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    },

    showLoading: function () {
        Edge.showloading = true;
    },
    hideLoading: function () {
        Edge.showloading = false;
    },

    goHome: function () {
        window.location.href = "index.php";
        return;
    },
    doLogoutAndGoHome: function () {
        localStorage.removeItem("token");
        localStorage.removeItem("accountData");
        window.location.href = "index.php";
        return;
    },

    apiCall: function (method, url, parameters, config) {

        if (!method || !url) {
            console.error('Function apiCall missing arguments');
            return;
        }

        if(typeof parameters === "undefined"){ parameters = null; }
        if(typeof config === "undefined"){ config = null; }

        this.showLoadingConf = true;
        if(config != null && typeof config.showLoading !== "undefined" && config.showLoading == false){
            this.showLoadingConf = false;
        }

        this.hideLoadingConf = true;
        if(config != null && typeof config.hideLoading !== "undefined" && config.hideLoading == false){
            this.hideLoadingConf = false;
        }

        if(this.showLoadingConf){
            Utils.showLoading();
        }

        //If url start with http or https I'll use url to call api. Api called is external
        //If url start with /xxxx it means that api is internal
        if ( url.indexOf("https") == -1 && url.indexOf("http") == -1 ) {
            if(url.substr(0, 1) == '/'){
                url = '../routes' + url;
            }else{
                url = '../routes' + '/' + url;
            }
        }

        //Preparing axios api call
        var call_config = {};
        call_config.method = method;
        call_config.url = url
        call_config.params = parameters;

        //Security check
        call_config.headers = {};
        if(config != null && typeof config.apikey !== "undefined"){
            call_config.headers.Authorization = "Bearer " + config.apikey;
        }else{
            if(localStorage.getItem("token") != '' && localStorage.getItem("token") != null && localStorage.getItem("token") != 'undefined'){
                apikey = localStorage.getItem("token");
                call_config.headers.Authorization = "Bearer " + apikey;
            }
        }

        //if i'm showing loading calling api i need to hide it once had the result
        if(this.showLoadingConf && this.hideLoadingConf){
            axios.interceptors.response.use(function (response) {
                Utils.hideLoading();
                return response;
            }, function (error) {
                // Do something with response error
                return Promise.reject(error);
            });
        }

        return axios(call_config).catch(function (error) {

            if(error.response.status == 401){
                Swal.fire({
                    type: 'error',
                    title: 'Account error',
                    text: "Login error or session expired",
                }).then((result) => {
                    Utils.doLogoutAndGoHome();
                }).catch(swal.noop);
            }else{
                Utils.hideLoading();
                Swal.fire({
                    type: 'error',
                    title: error.response.data.status,
                    text: error.response.data.message,
                }).then((result) => {
                    return;
                })
                .catch(swal.noop);
            }
        });
    },

    unserialize: function (data) {

        // Takes a string representation of variable and recreates it
        //
        // version: 810.114
        // discuss at: http://phpjs.org/functions/unserialize
        // +     original by: Arpad Ray (mailto:arpad@php.net)
        // +     improved by: Pedro Tainha (http://www.pedrotainha.com)
        // +     bugfixed by: dptr1988
        // +      revised by: d3x
        // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // %            note: We feel the main purpose of this function should be to ease the transport of data between php & js
        // %            note: Aiming for PHP-compatibility, we have to translate objects to arrays
        // *       example 1: unserialize('a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}');
        // *       returns 1: ['Kevin', 'van', 'Zonneveld']
        // *       example 2: unserialize('a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}');
        // *       returns 2: {firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'}

        var error = function (type, msg, filename, line){throw new window[type](msg, filename, line);};
        var read_until = function (data, offset, stopchr){
            var buf = [];
            var chr = data.slice(offset, offset + 1);
            var i = 2;
            while(chr != stopchr){
                if((i+offset) > data.length){
                    error('Error', 'Invalid');
                }
                buf.push(chr);
                chr = data.slice(offset + (i - 1),offset + i);
                i += 1;
            }
            return [buf.length, buf.join('')];
        };
        var read_chrs = function (data, offset, length){
            buf = [];
            for(var i = 0;i < length;i++){
                var chr = data.slice(offset + (i - 1),offset + i);
                buf.push(chr);
            }
            return [buf.length, buf.join('')];
        };
        var _unserialize = function (data, offset){
            if(!offset) offset = 0;
            var buf = [];
            var dtype = (data.slice(offset, offset + 1)).toLowerCase();

            var dataoffset = offset + 2;
            var typeconvert = new Function('x', 'return x');
            var chrs = 0;
            var datalength = 0;

            switch(dtype){
                case "i":
                    typeconvert = new Function('x', 'return parseInt(x)');
                    var readData = read_until(data, dataoffset, ';');
                    var chrs = readData[0];
                    var readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "b":
                    typeconvert = new Function('x', 'return (parseInt(x) == 1)');
                    var readData = read_until(data, dataoffset, ';');
                    var chrs = readData[0];
                    var readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "d":
                    typeconvert = new Function('x', 'return parseFloat(x)');
                    var readData = read_until(data, dataoffset, ';');
                    var chrs = readData[0];
                    var readdata = readData[1];
                    dataoffset += chrs + 1;
                break;
                case "n":
                    readdata = null;
                break;
                case "s":
                    var ccount = read_until(data, dataoffset, ':');
                    var chrs = ccount[0];
                    var stringlength = ccount[1];
                    dataoffset += chrs + 2;

                    var readData = read_chrs(data, dataoffset+1, parseInt(stringlength));
                    var chrs = readData[0];
                    var readdata = readData[1];
                    dataoffset += chrs + 2;
                    if(chrs != parseInt(stringlength) && chrs != readdata.length){
                        error('SyntaxError', 'String length mismatch');
                    }
                break;
                case "a":
                    var readdata = {};

                    var keyandchrs = read_until(data, dataoffset, ':');
                    var chrs = keyandchrs[0];
                    var keys = keyandchrs[1];
                    dataoffset += chrs + 2;

                    for(var i = 0;i < parseInt(keys);i++){
                        var kprops = _unserialize(data, dataoffset);
                        var kchrs = kprops[1];
                        var key = kprops[2];
                        dataoffset += kchrs;

                        var vprops = _unserialize(data, dataoffset);
                        var vchrs = vprops[1];
                        var value = vprops[2];
                        dataoffset += vchrs;

                        readdata[key] = value;
                    }

                    dataoffset += 1;
                break;
                default:
                    error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
                break;
            }
            return [dtype, dataoffset - offset, typeconvert(readdata)];
        };
        return _unserialize(data, 0)[2];
    }
};
