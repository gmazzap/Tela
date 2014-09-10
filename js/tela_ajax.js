var TelaAjax = {};

function telaExec(callback, args) {
    if (typeof callback !== 'string' || !jQuery.isFunction(TelaAjax.Ajax[callback])) {
        return;
    }
    if (!jQuery.isArray(args)) {
        args = typeof args !== 'undefined' && args !== null ? [args] : [];
    }
    return TelaAjax.Ajax[callback].apply(TelaAjax, args);
}

(function($, _D, _W, Tela, window) {

    Tela.Ajax = {
        isValidAjaxResponse: function(jqXHR) {
            return typeof jqXHR === 'object' && typeof jqXHR.readyState === 'number';
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
            if (typeof _W.nonces[action] === 'undefined') {
                _W.nonces[action] = '';
            }
            var ajax_data = {
                telaajax_is_admin: _D.is_admin,
                telaajax_action: action,
                telaajax_nonce: _W.nonces[action],
                telaajax_data: data
            };
            return $.extend(configs, {url: _D.url, data: ajax_data, type: "POST"});
        },
        run: function(action, data, configs) {
            var settings = Tela.Ajax.getAjaxSettings(action, data, configs);
            var id_arr = action.split('::');
            if (typeof id_arr[0] === 'string' && typeof _W.entrypoints[ id_arr[0] ] === 'string') {
                var entrypoint = _W.entrypoints[ id_arr[0] ];
                entrypoint += entrypoint.indexOf('?') !== -1 ? '&' : '?';
                settings.url = entrypoint + _D.url_args;
            }
            return $.ajax(settings);
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
        jsonEvent: function(jsonEvent, action, data, settings, jqXHR) {
            if (typeof jsonEvent !== 'string' || jsonEvent === '') {
                return false;
            }
            if (!this.isValidAjaxResponse(jqXHR)) {
                if (settings === null || typeof settings !== 'object') {
                    settings = {};
                }
                settings.dataType = 'json';
                jqXHR = this.run(action, data, settings);
            }
            jqXHR.done(function(result) {
                $(window).trigger(jsonEvent, result);
            });
            return jqXHR;
        }
    };

    Tela.PluginHelpers = {
        ensureDataAttrType: function(data, type) {
            if (typeof type !== 'string') {
                type = 'string';
            }
            if (type === 'object' && data === null) {
                data = '';
            }
            if (typeof data === type) {
                return data;
            }
            if (typeof data === 'string' && data !== '') {
                switch (type) {
                    case 'boolean' :
                        if (data === 'false' || data === '0') {
                            return false;
                        } else if (data === 'true' || data === '1') {
                            return true;
                        }
                    case 'number' :
                        return Number(data);
                    case 'object' :
                        try {
                            return JSON.parse(data);
                        } catch (e) {
                            return null;
                        }
                }
            }
            return null;

        },
        getSettingByData: function(setting, dataid, lookfor, target) {
            if (typeof lookfor !== 'string') {
                lookfor = 'string';
            }
            var $target = (typeof target === 'string' && $(target).length > 0) ? $(target) : this;
            if (lookfor === 'func') {
                if ($.isFunction(setting) || $.isFunction(window[setting])) {
                    return $.isFunction(setting) ? setting : window[setting];
                } else {
                    var bydata = $target.data('tela-' + dataid);
                    if ($.isFunction(window[bydata])) {
                        return window[bydata];
                    }
                }
                return null;
            } else if (typeof dataid === 'string') {
                if (typeof setting !== lookfor || (lookfor === 'object' && setting === null)) {
                    var bydata = $target.data('tela-' + dataid);
                    return Tela.PluginHelpers.ensureDataAttrType(bydata, lookfor);
                }
            }
            return typeof setting === 'undefined' ? null : setting;
        },
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
            settings.action = Tela.PluginHelpers.getSettingByData.apply(this, [settings.action, 'action']);
            settings.event = Tela.PluginHelpers.getSettingByData.apply(this, [settings.event, 'event']);
            if (!settings.action || !settings.event) {
                return false;
            }
            settings.subject = Tela.PluginHelpers.getSettingByData.apply(this, [settings.subject, 'subject']);
            if (typeof settings.subject === 'string' && settings.subject !== '') {
                settings.updateOn = 'subject-event';
            } else {
                settings.updateOn = Tela.PluginHelpers.getSettingByData.apply(this, [settings.updateOn, 'update-on']);
            }
            settings.html = Tela.PluginHelpers.getSettingByData.apply(this, [settings.html, 'map-html', 'boolean']);
            settings.parseCb = Tela.PluginHelpers.getSettingByData.apply(this, [settings.parseCb, 'map-callback', 'func']);
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
                    args.options = {};
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
            if (typeof response !== 'object' || response === null) {
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
            if (typeof dataArgs !== 'object') {
                dataArgs = {};
            }
            settings = $.extend({action: null, ajax: null, html: null, parseCb: null}, settings);
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
            return Tela.PluginHelpers.response(response, settings);
        },
        runCallerAction: function(caller, settings) {
            settings = $.extend({action: null, ajax: null, data: null}, settings);
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
        if (typeof settings === 'object' && typeof settings.updateOn !== 'undefined') {
            switch (settings.updateOn) {
                case 'subject-event' :
                    Tela.PluginMethods.subjectEvent.apply(this, [settings]);
                    break;
                case 'global-event' :
                case 'document-event' :
                    Tela.PluginMethods.globalEvent.apply(this, [settings]);
                    break;
                case 'self-event' :
                    Tela.PluginMethods.selfEvent.apply(this, [settings]);
                    break;
                default :
                    Tela.PluginMethods.runAction.apply(this, [settings]);
                    break;
            }
        }
        return this;
    };

// TelaAjaxData and TelaAjaxWideData comes from wp_localize_script
})(jQuery, TelaAjaxData, TelaAjaxWideData, TelaAjax, window);
