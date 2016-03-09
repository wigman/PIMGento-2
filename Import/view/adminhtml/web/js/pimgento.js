/*global define*/
define(['jquery'], function($) {
    'use strict';

    return {
        options:{
            type:null,
            file:null,
            step:0,
            runUrl:null,
            console:null,
            identifier:null
        },

        init: function(url, console) {
            this.options.runUrl = url;
            this.console = $(console);
        },

        type: function(type, object) {
            this.options.type = type;
            this.step('type', $(object));
        },

        file: function(file, object) {
            this.options.file = file;
            this.step('file', $(object));
        },

        step: function(type, object) {
            /* Reset step */
            this.options.step = 0;

            /* Reset identifier */
            this.options.identifier = null;

            /* Enable button */
            this.disabledImport(false);

            /* Reset Console */
            this.cleanConsole();

            /* Reset active element */
            $('.import-' + type).each(function() {
                $(this).removeClass('active');
            });

            /* Active element */
            object.addClass('active');
        },

        run: function() {
            var pimgento = this;

            pimgento.disabledImport(true);

            if (pimgento.options.type && pimgento.options.file && pimgento.options.runUrl) {
                $.ajax({
                    url: pimgento.options.runUrl,
                    type: 'post',
                    context: this,
                    data: {
                        'code':pimgento.options.type,
                        'file':pimgento.options.file,
                        'step':pimgento.options.step,
                        'identifier':pimgento.options.identifier
                    },
                    success: function (response) {
                        pimgento.removeWaiting();

                        if (response.identifier) {
                            pimgento.options.identifier = response.identifier;
                        }

                        if (pimgento.options.step == 0) {
                            pimgento.listElement(response.comment, false);
                        }

                        if (response.message) {
                            if (response.status == false) {
                                pimgento.listElement(response.message, 'error');
                            } else {
                                pimgento.listElement(response.message, 'success');
                            }
                        }

                        if (response.continue) {
                            pimgento.listElement(response.next, 'waiting');
                            pimgento.options.step = pimgento.options.step + 1;
                            pimgento.run();
                        }

                        pimgento.console.scrollTop(100000);
                    }
                });
            }
        },

        removeWaiting: function() {
            this.console.find('li').removeClass('waiting');
        },

        listElement: function(content, elementClass) {
            this.console.append(
                '<li' + (elementClass ? ' class="' + elementClass + '"' : '') + '>' + content + '</li>'
            );
        },

        cleanConsole: function() {
            this.console.html(
                '<li class="selected">' +
                    (this.options.type ? this.options.type + ' ' : '') +
                    (this.options.file ? this.options.file : '') +
                '</li>'
            );
        },

        disabledImport: function(enable) {
            $('.pimgento-uploader').find('button').prop("disabled", enable);
        }
    }
});