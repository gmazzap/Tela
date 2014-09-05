var Tela = {};
(function($, _Data, Nonces, Tela, window) {

    Tela.Ajax = {
        run: function(action, data, configs) {
            if (typeof action !== 'string' || action === '') {
                return false;
            }
            if (configs === null || typeof configs !== 'object') {
                configs = {};
            }
            if (data === null || typeof data !== 'object') {
                data = {};
            }
            if (typeof Nonces.nonces[action] === 'undefined') {
                Nonces.nonces[action] = '';
            }
            var settings = $.extend(
                    configs,
                    {
                        url: _Data.url,
                        data: {
                            telaajax_is_admin: _Data.is_admin,
                            telaajax_action: action,
                            telaajax_nonce: Nonces.nonces[action],
                            telaajax_data: data
                        },
                        type: "POST"
                    }
            );
            return $.ajax(settings);
        },
        updateHtml: function(args) {
            args = $.extend({target: null, action: null, data: null, settings: null}, args);
            if (args.settings === null || typeof args.settings !== 'object') {
                args.settings = {};
            }
            if (!$(args.target).length) {
                return false;
            }
            args.settings.dataType = 'html';
            return this.run(args.action, args.data, args.settings).done(function(html) {
                $(args.target).html(html);
            });
        },
        bindDataMap: function(args) {
            var defaults = {
                target: null,
                html: true,
                action: null,
                data: null,
                settings: null,
                parseCb: null
            };
            args = $.extend(defaults, args);
            if (args.settings === null || typeof args.settings !== 'object') {
                args.settings = {};
            }
            var $target = $(args.target);
            if (!$target.length || !$target.find('[data-tela-map]').length) {
                return false;
            }
            args.settings.dataType = 'json';
            return this.run(args.action, args.data, args.settings).done(function(jsonData) {
                this.parseDataMap.apply(args.target, [jsonData, args]);
            });
        },
        parseDataMap: function(map, settings) {
            if ($.isFunction(settings.parseCb)) {
                map = settings.parseCb.apply(settings.target, [map, settings.target, settings.data]);
            }
            var $map = $(settings.target);
            $.each(map, function(field, value) {
                if (typeof value === 'string') {
                    var $el = $map.find('[data-tela-map="' + field + '"]');
                    if ($el.length && settings.html) {
                        $el.html(value);
                    } else if ($el.length && settings.args.html) {
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
                response.fail(settings.done);
            }
            if ($.isFunction(settings.always)) {
                response.always(settings.always);
            }
            return response;
        },
        runCallerUpdate: function(caller, target, settings, dataArgs) {
            var defaults = {
                target: null,
                html: true,
                action: null,
                data: null,
                settings: null,
                parseCb: null
            };
            var args = {
                target: target,
                action: settings.action,
                data: Tela.PluginHelpers.getPostData.apply(caller, [dataArgs]),
                settings: settings.ajax,
                html: true,
                parseCb: null
            };
            args = $.extend(defaults, args);
            if (typeof caller.data('tela-bind-map') !== 'undefined') {
                return Tela.Ajax.updateDataMap.apply(Tela.Ajax, [args]);
            } else {
                return Tela.Ajax.updateHtml.apply(Tela.Ajax, [args]);
            }
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

// TelaAjaxUrl and TelaAjaxDataNonces comes from wp_localize_script
})(jQuery, TelaAjaxData, TelaAjaxNonces, Tela, window);