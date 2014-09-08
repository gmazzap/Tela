var TelaAjax = {};
(function($, _Data, Nonces, Tela, window) {

    Tela.Ajax = {
        isValidAjaxResponse: function(jqXHR) {
            return typeof jqXHR === 'object'
                    && typeof jqXHR.readyState === 'number'
                    && jqXHR.readyState > 0;
        },
        getAjaxSettings: function(action, data, configs) {
            if (typeof action !== 'string' || action === '') {
                return false;
            }
            if (configs === null || typeof configs !== 'object') {
                configs = {};
            }
            if (data === null || typeof data !== 'object') {
                data = {};
            }
            if (typeof configs.data === 'object') {
                data = $.extend(configs.data, data);
            }
            if (typeof Nonces.nonces[action] === 'undefined') {
                Nonces.nonces[action] = '';
            }
            var ajax_data = {
                telaajax_is_admin: _Data.is_admin,
                telaajax_action: action,
                telaajax_nonce: Nonces.nonces[action],
                telaajax_data: data
            };
            return $.extend(configs, {url: _Data.url, data: ajax_data, type: "POST"});
        },
        run: function(action, data, configs) {
            return $.ajax(Tela.Ajax.getAjaxSettings(action, data, configs));
        },
        updateHtml: function(args, jqXHR) {
            args = $.extend({target: null, action: null, data: null, settings: null}, args);
            var $target = $(args.target);
            if ($target.length < 1) {
                return false;
            }
            if (!this.isValidAjaxResponse(jqXHR)) {
                if (args.settings === null || typeof args.settings !== 'object') {
                    args.settings = {};
                }
                args.settings.dataType = 'html';
                jqXHR = this.run(args.action, args.data, args.settings);
            }
            jqXHR.done(function(html) {
                $target.html(html);
            });
            return jqXHR;
        },
        updateDataMap: function(args, jqXHR) {
            var def = {target: {}, action: '', data: {}, settings: {}, html: true, parseCb: null};
            args = $.extend(def, args);
            var $target = $(args.target);
            if ($target.length < 1 || $target.find('[data-tela-map]').length < 1) {
                return false;
            }
            if (!this.isValidAjaxResponse(jqXHR)) {
                if (args.settings === null || typeof args.settings !== 'object') {
                    args.settings = {};
                }
                args.settings.dataType = 'json';
                jqXHR = this.run(args.action, args.data, args.settings);
            }
            jqXHR.done(function(jsonData) {
                Tela.Ajax.parseDataMap.apply(args.target, [jsonData, args]);
            });
            return jqXHR;
        },
        parseDataMap: function(map, args) {
            var def = {target: {}, data: {}, html: true, parseCb: null};
            args = $.extend(def, args);
            var $map = $(args.target);
            if ($map.length < 1 || $map.find('[data-tela-map]').length < 1) {
                return false;
            }
            if ($.isFunction(args.parseCb)) {
                map = args.parseCb.apply(args.target, [map, args.target, args.data]);
            }
            $.each(map, function(field, value) {
                if (typeof value === 'string') {
                    var $el = $map.find('[data-tela-map="' + field + '"]');
                    if ($el.length && args.html) {
                        $el.html(value);
                    } else if ($el.length && !args.html) {
                        $el.text(value);
                    }
                }
            });
        },
        jsonEvent: function(jsonEvent, action, data, settings) {
            if (typeof jsonEvent !== 'string' || jsonEvent === '') {
                return;
            }
            if (settings === null || typeof settings !== 'object') {
                settings = {};
            }
            settings.dataType = 'json';
            return this.run(action, data, settings).done(function(result) {
                $(window).trigger(jsonEvent, result);
            });
        }
    };

    Tela.PluginHelpers = {
        init: function(settings) {
            var defaults = {
                action: null,
                data: {},
                ajax: {},
                html: true,
                parseCb: null,
                event: null,
                updateOn: null,
                subject: null,
                done: null,
                fail: null,
                always: null
            };
            settings = $.extend(defaults, settings);
            if (typeof settings.action !== 'string' || settings.action === '') {
                settings.action = $(this).data('tela-action');
            }
            if (typeof settings.event !== 'string' || settings.event === '') {
                settings.event = $(this).data('tela-event');
            }
            if (typeof settings.event !== 'string' || settings.event === '') {
                return this;
            }
            if (typeof settings.subject !== 'string' || settings.subject === '') {
                settings.subject = $(this).data('tela-subject');
            }
            if (typeof settings.subject === 'string' && settings.subject !== '') {
                settings.updateOn = 'subject-event';
            } else if (typeof settings.updateOn !== 'string' || settings.updateOn === '') {
                settings.updateOn = $(this).data('tela-update-on');
            }
            if (typeof $(this).data('tela-map-html') !== 'undefined') {
                settings.html = $(this).data('tela-map-html');
            }
            if (!$.isFunction(settings.parseCb)) {
                var callback = $(this).data('tela-map-callback');
                if ($.isFunction(window[callback])) {
                    settings.parseCb = window[callback];
                }
            }
            return settings;
        },
        getPostData: function(args) {
            args = $.extend({source: null, context: null, options: null}, args);
            var postdata = {};
            if ($.isFunction(args.source)) {
                if (typeof args.context !== 'object') {
                    args.context = this;
                }
                if (typeof args.options === 'undefined') {
                    args.options = null;
                }
                postdata = args.source.apply(args.context, [args.options]);
            } else if (typeof args.source === 'object') {
                postdata = args.source;
            }
            return postdata;
        },
        getSelector: function() {
            var selector = $(this).data('tela-selector');
            if (typeof selector !== 'string' || selector === '') {
                var id = $(this).attr('id');
                if (typeof id === 'string' && id !== '') {
                    selector = '#' + id;
                }
            }
            return (typeof selector !== 'string' || selector === '') ? false : selector;
        },
        response: function(response, settings) {
            if (typeof response !== 'object') {
                return false;
            }
            if ($.isFunction(settings.done)) {
                response.done(settings.done);
            }
            if ($.isFunction(settings.fail)) {
                response.fail(settings.fail);
            }
            if ($.isFunction(settings.always)) {
                response.always(settings.always);
            }
            return response;
        },
        runCallerUpdate: function(caller, target, settings, dataArgs) {
            var args = {
                target: target,
                action: settings.action,
                data: Tela.PluginHelpers.getPostData.apply(caller, [dataArgs]),
                settings: settings.ajax,
                html: settings.html,
                parseCb: settings.parseCb
            };
            var response = {};
            if (typeof caller.data('tela-bind-map') !== 'undefined') {
                response = Tela.Ajax.updateDataMap.apply(Tela.Ajax, [args]);
            } else {
                response = Tela.Ajax.updateHtml.apply(Tela.Ajax, [args]);
            }
            return Tela.PluginHelpers.response(response, args.settings);
        },
        runCallerAction: function(caller, settings) {
            var args = {
                source: settings.data,
                context: caller
            };
            var postdata = Tela.PluginHelpers.getPostData.apply(caller, [args]);
            var responseArgs = [settings.action, postdata, settings.ajax];
            var response = Tela.Ajax.run.apply(Tela.Ajax, responseArgs);
            return Tela.PluginHelpers.response(response, settings);
        }
    };

    Tela.PluginMethods = {
        subjectEvent: function(settings) {
            var caller = this;
            if (typeof settings.subject === 'string' && settings.subject !== '') {
                var target = this;
                $(document).on(settings.event, settings.subject, function() {
                    var dataArgs = {
                        source: settings.data,
                        context: this
                    };
                    var args = [caller, target, settings, dataArgs];
                    return Tela.PluginHelpers.runCallerUpdate.apply(this, args);
                });
            }
        },
        globalEvent: function(settings) {
            var caller = this;
            var triggerer = settings.updateOn === 'global-event' ? $(window) : $(document);
            triggerer.on(settings.event, function() {
                var dataArgs = {
                    source: settings.data,
                    context: caller,
                    options: arguments
                };
                var args = [caller, caller, settings, dataArgs];
                return Tela.PluginHelpers.runCallerUpdate.apply(this, args);
            });
        },
        selfEvent: function(settings) {
            var caller = this;
            $(this).on(settings.event, function() {
                var dataArgs = {
                    source: settings.data,
                    context: caller
                };
                var args = [caller, caller, settings, dataArgs];
                return Tela.PluginHelpers.runCallerUpdate.apply(this, args);
            });
        },
        runAction: function(settings) {
            var caller = this;
            var selector = Tela.PluginHelpers.getSelector.apply(caller);
            if (typeof selector === 'string' && selector !== '') {
                $(document).on(settings.event, selector, function() {
                    return Tela.PluginHelpers.runCallerAction.apply(this, [caller, settings]);
                });
            } else {
                $(caller).on(settings.event, function() {
                    return Tela.PluginHelpers.runCallerAction.apply(this, [caller, settings]);
                });
            }
        }
    };

    $.fn.telaAjax = function(settings) {
        settings = Tela.PluginHelpers.init.apply(this, [settings]);
        switch (settings.updateOn) {
            case 'subject-event' :
                return Tela.PluginMethods.subjectEvent.apply(this, [settings]);
            case 'global-event' :
            case 'document-event' :
                return Tela.PluginMethods.globalEvent.apply(this, [settings]);
            case 'self-event' :
                return Tela.PluginMethods.selfEvent.apply(this, [settings]);
            default :
                return Tela.PluginMethods.runAction.apply(this, [settings]);
        }
        return this;
    };

// TelaAjaxData and TelaAjaxNonces comes from wp_localize_script
})(jQuery, TelaAjaxData, TelaAjaxNonces, TelaAjax, window);